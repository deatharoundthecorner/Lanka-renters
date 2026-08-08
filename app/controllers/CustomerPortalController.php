<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/helpers/CustomerCsrf.php';
require_once dirname(__DIR__) . '/helpers/CustomerDemoData.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/CustomerPortal.php';

/** Coordinates database-backed and clearly labelled demo Customer pages. */
final class CustomerPortalController
{
    private CustomerPortal $model;
    private Customer $customerModel;

    public function __construct(?CustomerPortal $model = null, ?Customer $customerModel = null)
    {
        $this->model = $model ?? new CustomerPortal();
        $this->customerModel = $customerModel ?? new Customer();
    }

    public function dashboardPage(): array
    {
        $data = $this->pageData('Customer Dashboard', 'Track your bookings and permitted next actions.', 'dashboard');
        $data['page_kicker'] = 'Customer workspace';
        $data['page_action'] = ['label' => 'Search Vehicles', 'path' => 'vehicles/index.php'];
        $data['database_error'] = false;

        try {
            $result = $this->model->getDashboard($this->userId());
            $context = $result['context'];
            $verificationStatus = (string) $context['verification_status'];
            $recent = $result['recent_booking'];
            $next = $this->nextAction($verificationStatus, $recent, $result['latest_payment']);
            $data['dashboard'] = [
                'verification' => $next,
                'verification_status' => $verificationStatus,
                'stats' => [
                    ['label' => 'Verification', 'value' => ucfirst($verificationStatus), 'detail' => 'Admin review status', 'tone' => $verificationStatus === 'approved' ? 'success' : 'warning', 'icon' => 'shield'],
                    ['label' => 'Active rental', 'value' => (string) $result['counts']['active'], 'detail' => 'Currently ongoing', 'tone' => 'info', 'icon' => 'car'],
                    ['label' => 'Pending bookings', 'value' => (string) $result['counts']['pending'], 'detail' => 'Awaiting payment', 'tone' => 'warning', 'icon' => 'clock'],
                    ['label' => 'Completed bookings', 'value' => (string) $result['counts']['completed'], 'detail' => 'Finished rentals', 'tone' => 'success', 'icon' => 'check'],
                ],
                'progress' => $this->progressItems($verificationStatus, $recent),
                'recent_booking' => $recent,
                'latest_payment' => $result['latest_payment'],
                'unread_notifications' => $result['unread_notifications'],
                'quick_links' => [
                    ['label' => 'Search Vehicles', 'description' => 'Explore approved rental vehicles.', 'path' => 'vehicles/index.php', 'icon' => 'search'],
                    ['label' => 'My Bookings', 'description' => 'Open your booking history.', 'path' => 'bookings/index.php', 'icon' => 'calendar'],
                    ['label' => 'Payments', 'description' => 'Review submitted payments.', 'path' => 'payments/index.php', 'icon' => 'credit-card'],
                    ['label' => 'Notifications', 'description' => $result['unread_notifications'] . ' unread notification(s).', 'path' => 'notifications/index.php', 'icon' => 'bell'],
                    ['label' => 'Chat', 'description' => 'Open booking conversations.', 'path' => 'chat/index.php', 'icon' => 'chat'],
                    ['label' => 'Profile', 'description' => 'Review your permitted account fields.', 'path' => 'profile/index.php', 'icon' => 'user'],
                ],
                'reminders' => [
                    'Payment submissions remain pending until authorized staff review them.',
                    'Inspection records are read-only in the Customer workspace.',
                    'Use incident reporting only for an ongoing Customer-owned rental.',
                ],
            ];
        } catch (Throwable $exception) {
            $this->log('dashboard', $exception);
            $data['database_error'] = true;
            $data['dashboard'] = [];
        }
        return $data;
    }

    public function profilePage(array $post, string $method): array
    {
        $data = $this->pageData('Profile', 'Update permitted Customer contact and verification fields.', 'profile');
        $data['errors'] = [];
        $data['flash'] = $this->pullFlash();
        $data['database_error'] = false;
        try {
            $context = $this->model->getContext($this->userId());
            if ($context === null) {
                throw new RuntimeException('Customer profile not found.');
            }
            $data['profile'] = $context;
            $data['form'] = [
                'name' => (string) $context['name'],
                'phone' => (string) $context['phone'],
                'nic_number' => (string) ($context['nic_number'] ?? ''),
                'driving_license_number' => (string) ($context['driving_license_number'] ?? ''),
            ];
            if ($method === 'POST') {
                $data['form'] = [
                    'name' => $this->text($post['name'] ?? '', 100),
                    'phone' => $this->text($post['phone'] ?? '', 20),
                    'nic_number' => $this->text($post['nic_number'] ?? '', 20),
                    'driving_license_number' => $this->text($post['driving_license_number'] ?? '', 30),
                ];
                if (strlen($data['form']['name']) < 2) {
                    $data['errors']['name'] = 'Enter a name with at least 2 characters.';
                }
                if (!preg_match('/^[0-9+() -]{7,20}$/', $data['form']['phone'])) {
                    $data['errors']['phone'] = 'Enter a valid phone number.';
                }
                if ($data['errors'] === []) {
                    $this->model->updateProfile($this->userId(), $data['form']);
                    $_SESSION['user']['name'] = $data['form']['name'];
                    $_SESSION['user']['phone'] = $data['form']['phone'];
                    $this->setFlash('success', 'Profile details updated. Verification decisions remain with Admin.');
                    $data['redirect'] = 'profile/index.php';
                }
            }
        } catch (Throwable $exception) {
            $this->log('profile', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function verificationPage(array $post, string $method): array
    {
        $data = $this->profilePage($post, $method);
        $data['page_title'] = 'Verification';
        $data['page_description'] = 'Maintain identity fields used by the Admin verification workflow.';
        $data['active_nav'] = 'verification';
        if (($data['redirect'] ?? '') === 'profile/index.php') {
            $data['redirect'] = 'verification/index.php';
        }
        return $data;
    }

    public function notificationsPage(array $query, array $post, string $method): array
    {
        $data = $this->pageData('Notifications', 'Review database notifications assigned to your account.', 'notifications');
        $filter = $this->allowed($query['filter'] ?? '', ['all', 'unread'], 'all');
        $page = $this->positiveInt($query['page'] ?? null) ?? 1;
        $data += ['filter' => $filter, 'notifications' => [], 'total' => 0, 'current_page' => 1, 'total_pages' => 1, 'database_error' => false, 'flash' => $this->pullFlash()];
        try {
            if ($method === 'POST') {
                $action = $this->allowed($post['action'] ?? '', ['mark_read', 'mark_all']);
                if ($action === 'mark_read') {
                    $id = $this->positiveInt($post['notification_id'] ?? null);
                    if ($id === null || !$this->model->markNotificationRead($this->userId(), $id)) {
                        throw new DomainException('Notification not found.');
                    }
                    $this->setFlash('success', 'Notification marked as read.');
                } elseif ($action === 'mark_all') {
                    $count = $this->model->markAllNotificationsRead($this->userId());
                    $this->setFlash('success', $count > 0 ? 'All notifications marked as read.' : 'There were no unread notifications.');
                } else {
                    throw new DomainException('Unsupported notification action.');
                }
                $data['redirect'] = 'notifications/index.php?filter=' . rawurlencode($filter);
                return $data;
            }
            $pageSize = 10;
            $total = $this->model->countNotifications($this->userId(), $filter);
            $totalPages = max(1, (int) ceil($total / $pageSize));
            $page = min($page, $totalPages);
            $notifications = $this->model->getNotifications($this->userId(), $filter, $pageSize, ($page - 1) * $pageSize);
            foreach ($notifications as &$notification) {
                $notification['related_route'] = $this->model->relatedCustomerRoute(
                    $this->userId(),
                    (string) ($notification['notification_type'] ?? ''),
                    isset($notification['related_id']) ? (int) $notification['related_id'] : null
                );
            }
            unset($notification);
            $data += ['notifications' => $notifications, 'total' => $total, 'current_page' => $page, 'total_pages' => $totalPages];
            $data['notifications'] = $notifications;
            $data['total'] = $total;
            $data['current_page'] = $page;
            $data['total_pages'] = $totalPages;
        } catch (DomainException $exception) {
            $data['errors'] = [$exception->getMessage()];
        } catch (Throwable $exception) {
            $this->log('notifications', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function chatPage(array $query, array $post, string $method): array
    {
        $data = $this->pageData('Chat', 'Use booking-scoped database conversations with the assigned rental contact.', 'chat');
        $data += ['rooms' => [], 'eligible_bookings' => [], 'room' => null, 'messages' => [], 'errors' => [], 'database_error' => false, 'flash' => $this->pullFlash(), 'form_message' => ''];
        try {
            if ($method === 'POST') {
                $action = $this->allowed($post['action'] ?? '', ['start', 'send']);
                if ($action === 'start') {
                    $bookingId = $this->positiveInt($post['booking_id'] ?? null);
                    if ($bookingId === null) {
                        throw new DomainException('Choose an eligible booking.');
                    }
                    $roomId = $this->model->ensureChatRoom($this->userId(), $bookingId);
                    $this->setFlash('success', 'Booking conversation opened.');
                    $data['redirect'] = 'chat/index.php?room=' . $roomId;
                    return $data;
                }
                if ($action === 'send') {
                    $roomId = $this->positiveInt($post['room_id'] ?? null);
                    $message = $this->text($post['message'] ?? '', 1000, false);
                    $data['form_message'] = $message;
                    if ($roomId === null) {
                        throw new DomainException('Conversation not found.');
                    }
                    if ($message === '') {
                        throw new DomainException('Enter a message before sending.');
                    }
                    $this->model->sendChatMessage($this->userId(), $roomId, $message);
                    $this->setFlash('success', 'Message saved to the booking conversation.');
                    $data['redirect'] = 'chat/index.php?room=' . $roomId;
                    return $data;
                }
                throw new DomainException('Unsupported chat action.');
            }
            $data['rooms'] = $this->model->getChatRooms($this->userId());
            $data['eligible_bookings'] = $this->model->getChatEligibleBookings($this->userId());
            $roomId = $this->positiveInt($query['room'] ?? null);
            if ($roomId !== null) {
                $room = $this->model->findOwnedChatRoom($this->userId(), $roomId);
                if ($room === null) {
                    http_response_code(404);
                    $data['errors'][] = 'Conversation not found.';
                } else {
                    $data['room'] = $room;
                    $data['messages'] = $this->model->getChatMessages($this->userId(), $roomId);
                }
            }
        } catch (DomainException $exception) {
            $data['errors'][] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->log('chat', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function incidentsPage(array $query): array
    {
        $data = $this->pageData('Incidents', 'View incident reports for your owned bookings.', 'incidents');
        $page = $this->positiveInt($query['page'] ?? null) ?? 1;
        $data += ['incidents' => [], 'total' => 0, 'current_page' => 1, 'total_pages' => 1, 'database_error' => false, 'flash' => $this->pullFlash()];
        try {
            $size = 8;
            $total = $this->model->countIncidents($this->userId());
            $pages = max(1, (int) ceil($total / $size));
            $page = min($page, $pages);
            $data['incidents'] = $this->model->getIncidents($this->userId(), $size, ($page - 1) * $size);
            $data['eligible_bookings'] = $this->model->getIncidentEligibleBookings($this->userId());
            $data['total'] = $total;
            $data['current_page'] = $page;
            $data['total_pages'] = $pages;
            if ($data['eligible_bookings'] !== []) {
                $data['page_action'] = ['label' => 'Report Incident', 'path' => 'incidents/create.php'];
            }
        } catch (Throwable $exception) {
            $this->log('incidents', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function incidentCreatePage(array $query, array $post, string $method): array
    {
        $data = $this->pageData('Report Incident', 'Create a database incident for an ongoing Customer-owned rental.', 'incidents');
        $data += ['eligible_bookings' => [], 'errors' => [], 'database_error' => false, 'form' => ['booking_id' => (string) ($query['booking_id'] ?? ''), 'incident_date' => date('Y-m-d\TH:i'), 'severity' => 'minor', 'description' => '']];
        try {
            $data['eligible_bookings'] = $this->model->getIncidentEligibleBookings($this->userId());
            if ($method === 'POST') {
                $data['form'] = [
                    'booking_id' => (string) ($post['booking_id'] ?? ''),
                    'incident_date' => (string) ($post['incident_date'] ?? ''),
                    'severity' => $this->allowed($post['severity'] ?? '', ['minor', 'moderate', 'major'], 'minor'),
                    'description' => $this->text($post['description'] ?? '', 2000, false),
                ];
                $bookingId = $this->positiveInt($data['form']['booking_id']);
                $dateTime = $this->dateTime($data['form']['incident_date']);
                if ($bookingId === null) {
                    $data['errors']['booking_id'] = 'Choose an eligible ongoing booking.';
                }
                if ($dateTime === null) {
                    $data['errors']['incident_date'] = 'Enter a valid incident date and time.';
                }
                if (strlen($data['form']['description']) < 10) {
                    $data['errors']['description'] = 'Describe the incident using at least 10 characters.';
                }
                if ($data['errors'] === []) {
                    $id = $this->model->createIncident($this->userId(), $bookingId, $data['form']['description'], $dateTime, $data['form']['severity']);
                    $this->setFlash('success', 'Incident saved with reported status for authorized review.');
                    $data['redirect'] = 'incidents/details.php?id=' . $id;
                }
            }
        } catch (DomainException $exception) {
            $data['errors']['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->log('incident create', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function incidentDetailsPage(array $query, array $post, string $method): array
    {
        $data = $this->pageData('Incident Details', 'Review your incident and any authorized replacement information.', 'incidents');
        $data += ['incident' => null, 'replacement_decision' => null, 'errors' => [], 'database_error' => false, 'flash' => $this->pullFlash()];
        $id = $this->positiveInt($query['id'] ?? $post['incident_id'] ?? null);
        if ($id === null) {
            http_response_code(404);
            return $data;
        }
        try {
            $incident = $this->model->findIncident($this->userId(), $id);
            if ($incident === null) {
                http_response_code(404);
                return $data;
            }
            $data['incident'] = $incident;
            if (is_array($incident['replacement'])) {
                $replacementId = (int) $incident['replacement']['id'];
                $data['demo_mode'] = CustomerDemoData::enabled();
                $data['replacement_decision'] = CustomerDemoData::findReplacementDecision($this->userId(), $replacementId);
                if ($method === 'POST') {
                    if (!$data['demo_mode']) {
                        throw new DomainException('Customer demo workflows are disabled in this environment.');
                    }
                    $decision = $this->allowed($post['decision'] ?? '', ['accept', 'reject']);
                    $reason = $this->text($post['reason'] ?? '', 300, false);
                    if ($decision === '') {
                        throw new DomainException('Choose accept or reject.');
                    }
                    CustomerDemoData::createReplacementDecision($this->userId(), $id, $replacementId, $decision, $reason);
                    $this->setFlash('success', 'Demo replacement decision recorded in this session only.');
                    $data['redirect'] = 'incidents/details.php?id=' . $id;
                }
            }
        } catch (DomainException $exception) {
            $data['errors'][] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->log('incident details', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function inspectionIndexPage(): array
    {
        $data = $this->pageData('Inspections', 'Open read-only inspection reports linked to your bookings.', 'inspection');
        $data += ['bookings' => [], 'database_error' => false];
        try {
            $data['bookings'] = $this->model->getInspectionBookings($this->userId());
        } catch (Throwable $exception) {
            $this->log('inspection index', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function inspectionDetailsPage(array $query): array
    {
        $data = $this->pageData('Inspection Report', 'Read-only vehicle inspection information for your booking.', 'inspection');
        $data += ['result' => null, 'database_error' => false];
        $id = $this->positiveInt($query['id'] ?? null);
        if ($id === null) {
            http_response_code(404);
            return $data;
        }
        try {
            $data['result'] = $this->model->getBookingInspections($this->userId(), $id);
            if ($data['result'] === null) {
                http_response_code(404);
            }
        } catch (Throwable $exception) {
            $this->log('inspection details', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function reviewsPage(array $query): array
    {
        $data = $this->pageData('Reviews', 'View reviews created for your completed bookings.', 'reviews');
        $page = $this->positiveInt($query['page'] ?? null) ?? 1;
        $data += ['reviews' => [], 'eligible_bookings' => [], 'total' => 0, 'current_page' => 1, 'total_pages' => 1, 'database_error' => false, 'flash' => $this->pullFlash()];
        try {
            $size = 8;
            $total = $this->model->countReviews($this->userId());
            $pages = max(1, (int) ceil($total / $size));
            $page = min($page, $pages);
            $data['reviews'] = $this->model->getReviews($this->userId(), $size, ($page - 1) * $size);
            $data['eligible_bookings'] = $this->model->getReviewEligibleBookings($this->userId());
            $data['total'] = $total;
            $data['current_page'] = $page;
            $data['total_pages'] = $pages;
            if ($data['eligible_bookings'] !== []) {
                $data['page_action'] = ['label' => 'Write Review', 'path' => 'reviews/create.php'];
            }
        } catch (Throwable $exception) {
            $this->log('reviews', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function reviewCreatePage(array $query, array $post, string $method): array
    {
        $data = $this->pageData('Write Review', 'Review a completed Customer-owned booking.', 'reviews');
        $data += ['eligible_bookings' => [], 'errors' => [], 'database_error' => false, 'form' => ['booking_id' => (string) ($query['booking_id'] ?? ''), 'vehicle_rating' => '5', 'driver_rating' => '', 'review_text' => '']];
        try {
            $data['eligible_bookings'] = $this->model->getReviewEligibleBookings($this->userId());
            if ($method === 'POST') {
                $data['form'] = [
                    'booking_id' => (string) ($post['booking_id'] ?? ''),
                    'vehicle_rating' => (string) ($post['vehicle_rating'] ?? ''),
                    'driver_rating' => (string) ($post['driver_rating'] ?? ''),
                    'review_text' => $this->text($post['review_text'] ?? '', 1500, false),
                ];
                $bookingId = $this->positiveInt($data['form']['booking_id']);
                $vehicleRating = $this->rating($data['form']['vehicle_rating']);
                $driverRating = $data['form']['driver_rating'] === '' ? null : $this->rating($data['form']['driver_rating']);
                if ($bookingId === null) {
                    $data['errors']['booking_id'] = 'Choose an eligible completed booking.';
                }
                if ($vehicleRating === null) {
                    $data['errors']['vehicle_rating'] = 'Vehicle rating must be from 1 to 5.';
                }
                if ($data['form']['driver_rating'] !== '' && $driverRating === null) {
                    $data['errors']['driver_rating'] = 'Driver rating must be from 1 to 5.';
                }
                if ($data['errors'] === []) {
                    $id = $this->model->createReview($this->userId(), $bookingId, $vehicleRating, $driverRating, $data['form']['review_text']);
                    $this->setFlash('success', 'Review saved for your completed booking.');
                    $data['redirect'] = 'reviews/details.php?id=' . $id;
                }
            }
        } catch (DomainException $exception) {
            $data['errors']['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->log('review create', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function reviewDetailsPage(array $query): array
    {
        $data = $this->pageData('Review Details', 'Review information from your completed booking.', 'reviews');
        $data += ['review' => null, 'database_error' => false];
        $id = $this->positiveInt($query['id'] ?? null);
        if ($id === null) {
            http_response_code(404);
            return $data;
        }
        try {
            $data['review'] = $this->model->findReview($this->userId(), $id);
            if ($data['review'] === null) {
                http_response_code(404);
            }
        } catch (Throwable $exception) {
            $this->log('review details', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function driverChangesPage(): array
    {
        $data = $this->pageData('Driver Change Requests', 'Prototype requests stored only in this signed-in session.', 'driver-change');
        $data['demo_mode'] = CustomerDemoData::enabled();
        $data['requests'] = $data['demo_mode'] ? CustomerDemoData::driverChanges($this->userId()) : [];
        $data['flash'] = $this->pullFlash();
        try {
            $data['eligible_bookings'] = $this->model->getDriverChangeEligibleBookings($this->userId());
        } catch (Throwable $exception) {
            $this->log('driver changes', $exception);
            $data['eligible_bookings'] = [];
            $data['database_error'] = true;
        }
        if ($data['demo_mode'] && $data['eligible_bookings'] !== []) {
            $data['page_action'] = ['label' => 'New Demo Request', 'path' => 'driver-change/create.php'];
        }
        return $data;
    }

    public function driverChangeCreatePage(array $query, array $post, string $method): array
    {
        $data = $this->pageData('Request Driver Change', 'Create a clearly labelled session-only prototype request.', 'driver-change');
        $data += ['demo_mode' => CustomerDemoData::enabled(), 'errors' => [], 'form' => ['booking_id' => (string) ($query['booking_id'] ?? ''), 'reason' => '', 'scheduling_note' => ''], 'eligible_bookings' => [], 'database_error' => false];
        try {
            if (!$data['demo_mode']) {
                throw new DomainException('Customer demo workflows are disabled in this environment.');
            }
            $data['eligible_bookings'] = $this->model->getDriverChangeEligibleBookings($this->userId());
            if ($method === 'POST') {
                $data['form'] = [
                    'booking_id' => (string) ($post['booking_id'] ?? ''),
                    'reason' => $this->text($post['reason'] ?? '', 500, false),
                    'scheduling_note' => $this->text($post['scheduling_note'] ?? '', 300, false),
                ];
                $bookingId = $this->positiveInt($data['form']['booking_id']);
                $booking = $bookingId !== null ? $this->model->findOwnedBooking($this->userId(), $bookingId) : null;
                if ($booking === null || $booking['booking_type'] !== 'with_driver' || !in_array($booking['status'], ['confirmed', 'ongoing'], true)) {
                    $data['errors']['booking_id'] = 'Choose an eligible with-driver booking.';
                }
                if (strlen($data['form']['reason']) < 10) {
                    $data['errors']['reason'] = 'Explain the request using at least 10 characters.';
                }
                if ($data['errors'] === []) {
                    $record = CustomerDemoData::createDriverChange($this->userId(), $bookingId, $data['form']['reason'], $data['form']['scheduling_note']);
                    $this->setFlash('success', 'Demo driver-change request saved in this session only.');
                    $data['redirect'] = 'driver-change/details.php?id=' . (int) $record['id'];
                }
            }
        } catch (DomainException $exception) {
            $data['errors']['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->log('driver change create', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function driverChangeDetailsPage(array $query): array
    {
        $data = $this->pageData('Driver Change Details', 'Session-only prototype request details.', 'driver-change');
        $data += ['demo_mode' => CustomerDemoData::enabled(), 'request' => null, 'booking' => null];
        if (!$data['demo_mode']) {
            http_response_code(404);
            return $data;
        }
        $id = $this->positiveInt($query['id'] ?? null);
        if ($id !== null) {
            $data['request'] = CustomerDemoData::findDriverChange($this->userId(), $id);
            if (is_array($data['request'])) {
                $data['booking'] = $this->model->findOwnedBooking($this->userId(), (int) $data['request']['booking_id']);
            }
        }
        if ($data['request'] === null || $data['booking'] === null) {
            http_response_code(404);
        }
        return $data;
    }

    public function returnsPage(): array
    {
        $data = $this->pageData('Return Requests', 'Prototype return requests stored only in this signed-in session.', 'return');
        $data['demo_mode'] = CustomerDemoData::enabled();
        $data['requests'] = $data['demo_mode'] ? CustomerDemoData::returns($this->userId()) : [];
        $data['flash'] = $this->pullFlash();
        try {
            $data['eligible_bookings'] = $this->model->getReturnEligibleBookings($this->userId());
        } catch (Throwable $exception) {
            $this->log('returns', $exception);
            $data['eligible_bookings'] = [];
            $data['database_error'] = true;
        }
        if ($data['demo_mode'] && $data['eligible_bookings'] !== []) {
            $data['page_action'] = ['label' => 'Start Demo Return', 'path' => 'returns/create.php'];
        }
        return $data;
    }

    public function returnCreatePage(array $query, array $post, string $method): array
    {
        $data = $this->pageData('Start Return Request', 'Create a clearly labelled session-only return prototype.', 'return');
        $data += ['demo_mode' => CustomerDemoData::enabled(), 'errors' => [], 'form' => ['booking_id' => (string) ($query['booking_id'] ?? ''), 'proposed_return_at' => date('Y-m-d\TH:i'), 'customer_note' => ''], 'eligible_bookings' => [], 'database_error' => false];
        try {
            if (!$data['demo_mode']) {
                throw new DomainException('Customer demo workflows are disabled in this environment.');
            }
            $data['eligible_bookings'] = $this->model->getReturnEligibleBookings($this->userId());
            if ($method === 'POST') {
                $data['form'] = [
                    'booking_id' => (string) ($post['booking_id'] ?? ''),
                    'proposed_return_at' => (string) ($post['proposed_return_at'] ?? ''),
                    'customer_note' => $this->text($post['customer_note'] ?? '', 500, false),
                ];
                $bookingId = $this->positiveInt($data['form']['booking_id']);
                $dateTime = $this->dateTime($data['form']['proposed_return_at']);
                $booking = $bookingId !== null ? $this->model->findOwnedBooking($this->userId(), $bookingId) : null;
                if ($booking === null || $booking['status'] !== 'ongoing') {
                    $data['errors']['booking_id'] = 'Choose an ongoing Customer-owned booking.';
                }
                if ($dateTime === null) {
                    $data['errors']['proposed_return_at'] = 'Enter a valid proposed return date and time.';
                }
                if (($post['confirm_return'] ?? '') !== 'yes') {
                    $data['errors']['confirm_return'] = 'Confirm that authorized staff must inspect and complete the return.';
                }
                if ($data['errors'] === []) {
                    $record = CustomerDemoData::createReturn($this->userId(), $bookingId, $dateTime, $data['form']['customer_note']);
                    $this->setFlash('success', 'Demo return request saved in this session only.');
                    $data['redirect'] = 'returns/details.php?id=' . (int) $record['id'];
                }
            }
        } catch (DomainException $exception) {
            $data['errors']['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->log('return create', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function returnDetailsPage(array $query): array
    {
        $data = $this->pageData('Return Request Details', 'Session-only prototype return details.', 'return');
        $data += ['demo_mode' => CustomerDemoData::enabled(), 'request' => null, 'booking' => null];
        if (!$data['demo_mode']) {
            http_response_code(404);
            return $data;
        }
        $id = $this->positiveInt($query['id'] ?? null);
        if ($id !== null) {
            $data['request'] = CustomerDemoData::findReturn($this->userId(), $id);
            if (is_array($data['request'])) {
                $data['booking'] = $this->model->findOwnedBooking($this->userId(), (int) $data['request']['booking_id']);
            }
        }
        if ($data['request'] === null || $data['booking'] === null) {
            http_response_code(404);
        }
        return $data;
    }

    private function pageData(string $title, string $description, string $activeNav): array
    {
        $user = AuthHelper::getCurrentUser();
        if (!is_array($user) || ($user['role'] ?? '') !== 'customer') {
            throw new LogicException('Customer session was not established.');
        }
        return [
            'page_title' => $title,
            'page_description' => $description,
            'active_nav' => $activeNav,
            'customer' => $this->customerModel->identityFromAuthenticatedUser($user),
            'csrf_token' => CustomerCsrf::token(),
            'csrf_field_name' => CustomerCsrf::fieldName(),
        ];
    }

    private function userId(): int
    {
        $user = AuthHelper::getCurrentUser();
        return (int) ($user['id'] ?? 0);
    }

    private function text(mixed $value, int $maximumLength, bool $collapseWhitespace = true): string
    {
        if (!is_string($value)) {
            return '';
        }
        $value = trim($value);
        if ($collapseWhitespace) {
            $value = preg_replace('/\s+/', ' ', $value) ?? '';
        }
        return function_exists('mb_substr') ? mb_substr($value, 0, $maximumLength, 'UTF-8') : substr($value, 0, $maximumLength);
    }

    private function positiveInt(mixed $value): ?int
    {
        $result = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $result === false ? null : (int) $result;
    }

    private function allowed(mixed $value, array $allowed, string $default = ''): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }

    private function dateTime(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date instanceof DateTimeImmutable) {
            return null;
        }
        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }
        return $date->format('Y-m-d\TH:i') === $value ? $date->format('Y-m-d H:i:s') : null;
    }

    private function rating(mixed $value): ?int
    {
        $rating = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        return $rating === false ? null : (int) $rating;
    }

    private function nextAction(string $verification, ?array $booking, ?array $payment): array
    {
        if ($verification !== 'approved') {
            return ['status' => $verification, 'tone' => 'warning', 'title' => 'Complete your verification details', 'description' => 'Admin approval is required before booking eligibility.', 'action_label' => 'Open verification', 'action_path' => 'verification/index.php'];
        }
        if ($booking === null) {
            return ['status' => 'ready', 'tone' => 'success', 'title' => 'Choose an approved vehicle', 'description' => 'Your account is ready to browse available vehicles.', 'action_label' => 'Search vehicles', 'action_path' => 'vehicles/index.php'];
        }
        if ($booking['status'] === 'pending_payment') {
            if (is_array($payment)) {
                return ['status' => 'payment', 'tone' => 'info', 'title' => 'Review your submitted payment', 'description' => 'Payment confirmation remains with authorized staff.', 'action_label' => 'View payments', 'action_path' => 'payments/index.php'];
            }
            return ['status' => 'payment', 'tone' => 'warning', 'title' => 'Submit payment evidence', 'description' => 'The stored booking total is ready for bank-transfer submission.', 'action_label' => 'Continue payment', 'action_path' => 'payments/create.php?booking_id=' . (int) $booking['id']];
        }
        if ($booking['status'] === 'ongoing') {
            return ['status' => 'ongoing', 'tone' => 'info', 'title' => 'Manage your ongoing rental', 'description' => 'Open inspection information or report an incident when needed.', 'action_label' => 'View booking', 'action_path' => 'bookings/details.php?id=' . (int) $booking['id']];
        }
        if ($booking['status'] === 'completed') {
            return ['status' => 'completed', 'tone' => 'success', 'title' => 'Share your rental experience', 'description' => 'Completed owned bookings may be reviewed once.', 'action_label' => 'View reviews', 'action_path' => 'reviews/index.php'];
        }
        return ['status' => (string) $booking['status'], 'tone' => 'info', 'title' => 'Review your latest booking', 'description' => 'Open its current database-backed details.', 'action_label' => 'View booking', 'action_path' => 'bookings/details.php?id=' . (int) $booking['id']];
    }

    private function progressItems(string $verification, ?array $booking): array
    {
        $verified = $verification === 'approved';
        $hasBooking = $booking !== null;
        $paidOrLater = $hasBooking && in_array($booking['status'], ['confirmed', 'ongoing', 'completed'], true);
        $ongoingOrLater = $hasBooking && in_array($booking['status'], ['ongoing', 'completed'], true);
        $completed = $hasBooking && $booking['status'] === 'completed';
        return [
            ['label' => 'Create your Customer account', 'description' => 'Your secure workspace is active.', 'state' => 'complete'],
            ['label' => 'Complete verification', 'description' => 'Admin reviews submitted identity fields.', 'state' => $verified ? 'complete' : 'current'],
            ['label' => 'Choose and request a vehicle', 'description' => 'Bookings use approved catalogue records.', 'state' => $hasBooking ? 'complete' : ($verified ? 'current' : 'upcoming')],
            ['label' => 'Submit payment evidence', 'description' => 'Authorized staff confirm pending payments.', 'state' => $paidOrLater ? 'complete' : ($hasBooking ? 'current' : 'upcoming')],
            ['label' => 'Rental and inspections', 'description' => 'Inspection records remain read-only.', 'state' => $ongoingOrLater ? 'complete' : ($paidOrLater ? 'current' : 'upcoming')],
            ['label' => 'Return and review', 'description' => 'Staff complete the return before review.', 'state' => $completed ? 'complete' : ($ongoingOrLater ? 'current' : 'upcoming')],
        ];
    }

    private function setFlash(string $tone, string $message): void
    {
        AuthHelper::startSession();
        $_SESSION['_customer_portal_flash'] = ['tone' => $tone, 'message' => $message];
    }

    private function pullFlash(): ?array
    {
        AuthHelper::startSession();
        $flash = $_SESSION['_customer_portal_flash'] ?? null;
        unset($_SESSION['_customer_portal_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function log(string $area, Throwable $exception): void
    {
        error_log(sprintf('Customer %s error [%s]: %s', $area, get_class($exception), $exception->getMessage()));
    }
}
