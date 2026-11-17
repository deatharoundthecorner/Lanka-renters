<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/helpers/CustomerCsrf.php';
require_once dirname(__DIR__) . '/helpers/CustomerDemoData.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/CustomerPortal.php';
require_once dirname(__DIR__) . '/models/DriverChangeRequest.php';

/** Coordinates database-backed and clearly labelled demo Customer pages. */
final class CustomerPortalController
{
    private const SRI_LANKAN_DISTRICTS=['Ampara','Anuradhapura','Badulla','Batticaloa','Colombo','Galle','Gampaha','Hambantota','Jaffna','Kalutara','Kandy','Kegalle','Kilinochchi','Kurunegala','Mannar','Matale','Matara','Monaragala','Mullaitivu','Nuwara Eliya','Polonnaruwa','Puttalam','Ratnapura','Trincomalee','Vavuniya'];
    private const DOCUMENT_MAX_BYTES = 5 * 1024 * 1024;
    private const DOCUMENT_MIME_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    private const DOCUMENT_UPLOADS = [
        'nic_document' => ['type' => 'nic', 'label' => 'NIC document'],
        'driving_license_document' => ['type' => 'driving_license', 'label' => 'Driving licence document'],
        'guarantor_nic_document' => ['type' => 'guarantor_nic', 'label' => 'Guarantor NIC document'],
    ];

    private CustomerPortal $model;
    private Customer $customerModel;
    private DriverChangeRequest $driverChangeModel;

    public function __construct(
        ?CustomerPortal $model = null,
        ?Customer $customerModel = null,
        ?DriverChangeRequest $driverChangeModel = null
    ) {
        $this->model = $model ?? new CustomerPortal();
        $this->customerModel = $customerModel ?? new Customer();
        $this->driverChangeModel = $driverChangeModel ?? new DriverChangeRequest();
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

    public function profilePage(array $post, string $method, bool $withGuarantor = true, array $files = []): array
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
            $guarantor = $withGuarantor ? $this->model->getGuarantor($this->userId()) : null;
            $data['has_guarantor'] = $guarantor !== null;
            $data['documents'] = $this->model->getVerificationDocuments($this->userId());
            $data['form'] = [
                'name' => (string) $context['name'],
                'phone' => (string) $context['phone'],
                'nic_number' => (string) ($context['nic_number'] ?? ''),
                'driving_license_number' => (string) ($context['driving_license_number'] ?? ''),
                'district' => (string) ($context['district'] ?? ''),
                'address' => (string) ($context['address'] ?? ''),
            ];
            if ($withGuarantor) {
                $data['form'] += [
                    'guarantor_full_name' => (string) ($guarantor['full_name'] ?? ''),
                    'guarantor_nic' => (string) ($guarantor['nic'] ?? ''),
                    'guarantor_phone' => (string) ($guarantor['phone'] ?? ''),
                    'guarantor_relationship' => (string) ($guarantor['relationship'] ?? ''),
                ];
            }
            if ($method === 'POST') {
                $data['form'] = [
                    'name' => $this->text($post['name'] ?? '', 100),
                    'phone' => $this->text($post['phone'] ?? '', 20),
                    'nic_number' => $this->text($post['nic_number'] ?? '', 20),
                    'driving_license_number' => $this->text($post['driving_license_number'] ?? '', 30),
                    'district' => $this->text($post['district'] ?? '', 100),
                    'address' => $this->text($post['address'] ?? '', 255, false),
                ];
                if ($withGuarantor) {
                    $data['form'] += [
                        'guarantor_full_name' => $this->text($post['guarantor_full_name'] ?? '', 101),
                        'guarantor_nic' => $this->normaliseGuarantorNic($post['guarantor_nic'] ?? ''),
                        'guarantor_phone' => $this->normaliseGuarantorPhone($post['guarantor_phone'] ?? ''),
                        'guarantor_relationship' => $this->text($post['guarantor_relationship'] ?? '', 20),
                    ];
                }
                if (strlen($data['form']['name']) < 2) {
                    $data['errors']['name'] = 'Enter a name with at least 2 characters.';
                }
                if (!preg_match('/^[0-9+() -]{7,20}$/', $data['form']['phone'])) {
                    $data['errors']['phone'] = 'Enter a valid phone number.';
                }
                if ($data['form']['district'] !== '' && strlen($data['form']['district']) < 2) {
                    $data['errors']['district'] = 'Enter a district with at least 2 characters.';
                }
                if ($data['form']['address'] !== '' && strlen($data['form']['address']) < 5) {
                    $data['errors']['address'] = 'Enter an address with at least 5 characters.';
                }
                if ($withGuarantor) {
                    $guarantorNameLength = $this->characterLength($data['form']['guarantor_full_name']);
                    if (
                        $guarantorNameLength < 3
                        || $guarantorNameLength > 100
                        || !preg_match("/^(?=.*\\p{L})[\\p{L}\\p{M}\\s'-]+$/u", $data['form']['guarantor_full_name'])
                    ) {
                        $data['errors']['guarantor_full_name'] = 'Enter a valid guarantor name using 3 to 100 letters, spaces, apostrophes, or hyphens.';
                    }
                    if (!preg_match('/^(?:\\d{12}|\\d{9}[VX])$/', $data['form']['guarantor_nic'])) {
                        $data['errors']['guarantor_nic'] = 'Enter a valid Sri Lankan NIC: 12 digits or 9 digits followed by V or X.';
                    }
                    if (!preg_match('/^0\\d{9}$/', $data['form']['guarantor_phone'])) {
                        $data['errors']['guarantor_phone'] = 'Enter a 10-digit guarantor phone number beginning with 0.';
                    }
                    $relationships = ['Parent', 'Spouse', 'Sibling', 'Relative', 'Friend', 'Employer', 'Other'];
                    if (!in_array($data['form']['guarantor_relationship'], $relationships, true)) {
                        $data['errors']['guarantor_relationship'] = 'Choose a valid relationship to the customer.';
                    }
                    if (
                        !isset($data['errors']['guarantor_nic'])
                        && $this->model->guarantorNicIsUsedByAnotherCustomer($this->userId(), $data['form']['guarantor_nic'])
                    ) {
                        $data['errors']['guarantor_nic'] = 'This guarantor NIC is already linked to another customer.';
                    }
                }
                $uploads = $this->validateVerificationUploads($files, $withGuarantor, $data['errors']);
                if ($data['errors'] === []) {
                    $guarantorValues = $withGuarantor ? [
                        'full_name' => $data['form']['guarantor_full_name'],
                        'nic' => $data['form']['guarantor_nic'],
                        'phone' => $data['form']['guarantor_phone'],
                        'relationship' => $data['form']['guarantor_relationship'],
                    ] : null;
                    $this->model->updateProfile($this->userId(), $data['form'], $guarantorValues);
                    $uploadedLabels = $this->storeVerificationUploads($uploads);
                    $_SESSION['user']['name'] = $data['form']['name'];
                    $_SESSION['user']['phone'] = $data['form']['phone'];
                    $message = $withGuarantor ? 'Guarantor details saved successfully.' : 'Verification details updated.';
                    if ($uploadedLabels !== []) {
                        $message .= ' ' . implode(', ', $uploadedLabels) . ' submitted for Admin review.';
                    }
                    $this->setFlash('success', $message);
                    $data['redirect'] = $withGuarantor ? 'verification/index.php' : 'profile/index.php';
                }
            }
        } catch (Throwable $exception) {
            $this->log('profile', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function verificationPage(array $post, string $method, array $files = []): array
    {
        $data = $this->profilePage($post, $method, false, $files);
        $data['page_title'] = 'Verification';
        $data['page_description'] = 'Maintain identity fields used by the Admin verification workflow.';
        $data['active_nav'] = 'verification';
        if (!$data['database_error'] && is_array($data['profile'] ?? null)) {
            try {
                $data['verification'] = $this->verificationSummary(
                    $data['profile'],
                    $this->model->getGuarantor($this->userId())
                );
            } catch (Throwable $exception) {
                $this->log('verification summary', $exception);
                $data['database_error'] = true;
            }
        }
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

    public function incidentCreatePage(array $query, array $post, string $method, array $files = []): array
    {
        $data = $this->pageData('Report Incident', 'Create a database incident for an ongoing Customer-owned rental.', 'incidents');
        $data += ['eligible_bookings' => [], 'incidents' => [], 'errors' => [], 'database_error' => false, 'form' => ['booking_id'=>(string)($query['booking_id']??''),'incident_date'=>date('Y-m-d\TH:i'),'incident_type'=>'breakdown','district'=>'','maps_url'=>'','description'=>'']];
        try {
            $data['eligible_bookings'] = $this->model->getIncidentEligibleBookings($this->userId());
            $data['incidents'] = $this->model->getIncidents($this->userId(), 8, 0);
            if ($method === 'POST') {
                $data['form'] = [
                    'booking_id' => (string) ($post['booking_id'] ?? ''),
                    'incident_date' => (string) ($post['incident_date'] ?? ''),
                    'incident_type' => $this->allowed($post['incident_type'] ?? '', ['breakdown','accident','damage','tyre','battery','theft','vandalism','other'], ''),
                    'district' => $this->allowed($post['district'] ?? '', self::SRI_LANKAN_DISTRICTS),
                    'maps_url' => trim((string)($post['maps_url'] ?? '')),
                    'description' => $this->text($post['description'] ?? '', 2000, false),
                ];
                $bookingId = $this->positiveInt($data['form']['booking_id']);
                $dateTime = $this->dateTime($data['form']['incident_date']);
                if ($bookingId === null) {
                    $data['errors']['booking_id'] = 'Choose an eligible ongoing booking.';
                }
                if ($dateTime === null || $dateTime > date('Y-m-d H:i:s')) {
                    $data['errors']['incident_date'] = 'Enter a valid incident date and time.';
                }
                if ($data['form']['incident_type'] === '') $data['errors']['incident_type']='Choose an incident type.';
                if ($data['form']['district'] === '') $data['errors']['district']='Choose a Sri Lankan district.';
                if ($data['form']['maps_url'] !== '' && !preg_match('#^https://(?:www\\.)?(?:google\\.com/maps|maps\\.app\\.goo\\.gl)/#i',$data['form']['maps_url'])) $data['errors']['maps_url']='Enter a valid HTTPS Google Maps link.';
                if (strlen(trim($data['form']['description'])) < 20) {
                    $data['errors']['description'] = 'Describe the incident using at least 20 characters.';
                }
                $uploads=$this->incidentUploads($files,$data['form']['incident_type'],$data['errors']);
                if ($data['errors'] === []) {
                    $stored=$this->storeIncidentUploads($uploads); try{$id=$this->model->createDetailedIncident($this->userId(), $bookingId, $data['form']+['incident_date'=>$dateTime], $stored['photos'], $stored['police']);}catch(Throwable $e){foreach(array_merge($stored['photos'],$stored['police']?[$stored['police']]:[]) as $p){@unlink(dirname(__DIR__,2).'/storage/'.$p);}throw $e;}
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
        $data += ['incident' => null, 'replacement_decision' => null, 'can_request_replacement' => false, 'errors' => [], 'database_error' => false, 'flash' => $this->pullFlash()];
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
            $data['can_request_replacement'] = $incident['status'] === 'resolved'
                && $incident['booking_status'] === 'disputed'
                && !is_array($incident['replacement']);
            if ($method === 'POST' && ($post['action'] ?? '') === 'set_resolution_preference') {
                $preference = $post['resolution_preference'] ?? null;
                if (!is_string($preference) || !in_array($preference, ['refund', 'replacement'], true)) {
                    throw new DomainException('Choose either a refund or replacement preference.');
                }
                if ($incident['resolution_preference'] !== $preference) {
                    $this->model->setIncidentResolutionPreference($this->userId(), $id, $preference);
                }
                $this->setFlash('success', 'Your resolution preference has been recorded. This does not automatically trigger a refund or replacement.');
                $data['redirect'] = 'incidents/details.php?id=' . $id;
                return $data;
            }
            if ($method === 'POST' && ($post['action'] ?? '') === 'request_replacement') {
                if (!$data['can_request_replacement']) {
                    throw new DomainException('A replacement can be requested only after the incident is approved.');
                }
                $reason = $this->text($post['replacement_reason'] ?? '', 500, false);
                if ($this->characterLength($reason) < 10) {
                    throw new DomainException('Provide a short reason of at least 10 characters for the replacement request.');
                }
                $this->model->requestReplacementForResolvedIncident($this->userId(), $id, $reason);
                $this->setFlash('success', 'Replacement request submitted. Our team will review available vehicles.');
                $data['redirect'] = 'incidents/details.php?id=' . $id;
                return $data;
            }
            if (is_array($incident['replacement'])) {
                $replacementId = (int) $incident['replacement']['id'];
                if ($method === 'POST') {
                    $decision = $this->allowed($post['decision'] ?? '', ['accept', 'reject']);
                    if ($decision === '') {
                        throw new DomainException('Choose accept or reject.');
                    }
                    if ($decision === 'accept') {
                        $this->model->acceptReplacement($this->userId(), $id, $replacementId);
                        $this->setFlash('success', 'Replacement vehicle accepted and synchronized with your rental.');
                    } else {
                        $this->model->rejectReplacement($this->userId(), $id, $replacementId);
                        $this->setFlash('info', 'Replacement offer has been declined.');
                    }
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
            $requestedBookingId = $this->positiveInt($query['booking_id'] ?? null);
            if ($requestedBookingId !== null && $method === 'GET') {
                $existingReviewId = $this->model->findReviewIdByBooking($this->userId(), $requestedBookingId);
                if ($existingReviewId !== null) {
                    $this->setFlash('info', 'A review already exists for this booking. You can view or update it below.');
                    $data['redirect'] = 'reviews/details.php?id=' . $existingReviewId;
                    return $data;
                }
            }
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

    public function reviewDetailsPage(array $query, array $post = [], string $method = 'GET'): array
    {
        $data = $this->pageData('Review Details', 'Review information from your completed booking.', 'reviews');
        $data += ['review' => null, 'errors' => [], 'database_error' => false, 'flash' => $this->pullFlash(), 'form' => []];
        $id = $this->positiveInt($query['id'] ?? null);
        if ($id === null) {
            http_response_code(404);
            return $data;
        }
        try {
            $data['review'] = $this->model->findReview($this->userId(), $id);
            if ($data['review'] === null) {
                http_response_code(404);
                return $data;
            }

            $data['form'] = [
                'vehicle_rating' => (string) $data['review']['vehicle_rating'],
                'driver_rating' => $data['review']['driver_rating'] === null ? '' : (string) $data['review']['driver_rating'],
                'review_text' => (string) ($data['review']['review_text'] ?? ''),
            ];

            if ($method === 'POST') {
                $action = (string) ($post['action'] ?? '');
                if ($action === 'delete_review') {
                    if (!$this->model->deleteReview($this->userId(), $id)) {
                        throw new DomainException('The review could not be deleted or does not belong to you.');
                    }
                    $this->setFlash('success', 'Review deleted successfully.');
                    $data['redirect'] = 'reviews/index.php';
                    return $data;
                }

                if ($action !== 'update_review') {
                    throw new DomainException('Unsupported review action.');
                }

                $data['form'] = [
                    'vehicle_rating' => (string) ($post['vehicle_rating'] ?? ''),
                    'driver_rating' => (string) ($post['driver_rating'] ?? ''),
                    'review_text' => $this->text($post['review_text'] ?? '', 1500, false),
                ];
                $vehicleRating = $this->rating($data['form']['vehicle_rating']);
                $driverRating = $data['form']['driver_rating'] === '' ? null : $this->rating($data['form']['driver_rating']);
                if ($vehicleRating === null) {
                    $data['errors']['vehicle_rating'] = 'Vehicle rating must be from 1 to 5.';
                }
                if ($data['review']['driver_id'] !== null && $driverRating === null) {
                    $data['errors']['driver_rating'] = 'Driver rating must be from 1 to 5.';
                }
                if ($data['review']['driver_id'] === null) {
                    $driverRating = null;
                }
                if ($data['errors'] === []) {
                    $this->model->updateReview(
                        $this->userId(),
                        $id,
                        $vehicleRating,
                        $driverRating,
                        $data['form']['review_text']
                    );
                    $this->setFlash('success', 'Review updated successfully.');
                    $data['redirect'] = 'reviews/details.php?id=' . $id;
                }
            }
        } catch (DomainException $exception) {
            $data['errors']['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->log('review details', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function driverChangesPage(): array
    {
        $data = $this->pageData('Driver Change Requests', 'Request a Driver change for an eligible with-driver booking.', 'driver-change');
        $data['flash'] = $this->pullFlash();
        $data['database_error'] = false;
        try {
            $data['requests'] = $this->driverChangeModel->findByCustomer($this->userId());
            $data['eligible_bookings'] = $this->model->getDriverChangeEligibleBookings($this->userId());
        } catch (Throwable $exception) {
            $this->log('driver changes', $exception);
            $data['requests'] = [];
            $data['eligible_bookings'] = [];
            $data['database_error'] = true;
        }
        if (!empty($data['eligible_bookings'])) {
            $data['page_action'] = ['label' => 'New Request', 'path' => 'driver-change/create.php'];
        }
        return $data;
    }

    public function pickupStatusPage(array $post, string $method): array
    {
        $data = $this->pageData('Pickup Status', 'Review driver handover progress and confirm your pickup before the rental starts.', 'pickup-status');
        $data += ['bookings' => [], 'errors' => [], 'database_error' => false, 'flash' => $this->pullFlash()];

        try {
            if ($method === 'POST') {
                $bookingId = $this->positiveInt($post['booking_id'] ?? null);
                $action = $this->allowed($post['action'] ?? '', ['confirm_pickup', 'start_rental']);
                if ($bookingId === null || $action === '') {
                    throw new DomainException('Choose a valid pickup action.');
                }
                if ($action === 'confirm_pickup') {
                    $this->model->confirmPickup($this->userId(), $bookingId);
                    $this->setFlash('success', 'Pickup confirmed. You can now start the rental when you are ready.');
                } else {
                    $this->model->startRental($this->userId(), $bookingId);
                    $this->setFlash('success', 'Rental started successfully.');
                }
                $data['redirect'] = 'pickup-status/index.php';
                return $data;
            }
            $data['bookings'] = $this->model->getPickupStatusBookings($this->userId());
        } catch (DomainException $exception) {
            $data['errors'][] = $exception->getMessage();
            $data['bookings'] = $this->model->getPickupStatusBookings($this->userId());
        } catch (Throwable $exception) {
            $this->log('pickup status', $exception);
            $data['database_error'] = true;
        }
        return $data;
    }

    public function driverChangeCreatePage(array $query, array $post, string $method): array
    {
        $data = $this->pageData('Request Driver Change', 'Submit a replacement driver request for your active rental.', 'driver-change');
        $bookingIdQuery = $this->positiveInt($query['booking_id'] ?? null) ?? $this->positiveInt($post['booking_id'] ?? null);

        $data += [
            'errors' => [],
            'form' => [
                'booking_id' => $bookingIdQuery !== null ? (string)$bookingIdQuery : '',
                'requested_driver_id' => (string)($post['requested_driver_id'] ?? $query['driver_id'] ?? ''),
                'reason' => (string)($post['reason'] ?? ''),
            ],
            'eligible_bookings' => [],
            'available_drivers' => [],
            'database_error' => false,
        ];

        try {
            $data['eligible_bookings'] = $this->model->getDriverChangeEligibleBookings($this->userId());

            $effectiveBookingId = $this->positiveInt($data['form']['booking_id']);
            if ($effectiveBookingId !== null) {
                $data['available_drivers'] = $this->driverChangeModel->getEligibleDriversForBooking($effectiveBookingId, $this->userId());
            }

            if ($method === 'POST') {
                $data['form'] = [
                    'booking_id' => (string)($post['booking_id'] ?? ''),
                    'requested_driver_id' => (string)($post['requested_driver_id'] ?? ''),
                    'reason' => $this->text($post['reason'] ?? '', 500, false),
                ];

                $bookingId = $this->positiveInt($data['form']['booking_id']);
                $requestedDriverId = $this->positiveInt($data['form']['requested_driver_id']);

                if ($bookingId === null) {
                    $data['errors']['booking_id'] = 'Choose an eligible with-driver booking.';
                }
                if ($requestedDriverId === null) {
                    $data['errors']['requested_driver_id'] = 'Select an eligible replacement driver.';
                }
                if (mb_strlen($data['form']['reason']) < 10) {
                    $data['errors']['reason'] = 'Explain the request using at least 10 characters.';
                }

                if ($data['errors'] === []) {
                    $record = $this->driverChangeModel->create(
                        $this->userId(),
                        $bookingId,
                        $requestedDriverId,
                        $data['form']['reason']
                    );
                    $this->setFlash('success', 'Driver change request submitted successfully and is awaiting owner review.');
                    $data['redirect'] = 'driver-change/details.php?id=' . (int)$record['id'];
                }
            }
        } catch (DriverChangeRequestException $exception) {
            $data['errors']['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->log('driver change create', $exception);
            $data['database_error'] = true;
            $data['errors']['form'] = 'Unable to submit driver change request. Please try again.';
        }
        return $data;
    }

    public function driverChangeDetailsPage(array $query, array $post = [], string $method = 'GET'): array
    {
        $data = $this->pageData('Driver Change Details', 'Review status and details of your driver change request.', 'driver-change');
        $data += ['request' => null, 'flash' => $this->pullFlash(), 'errors' => []];

        $id = $this->positiveInt($query['id'] ?? $post['id'] ?? null);
        if ($id !== null) {
            if ($method === 'POST' && ($post['action'] ?? '') === 'cancel') {
                try {
                    $this->driverChangeModel->cancel($id, $this->userId());
                    $this->setFlash('success', 'Driver change request has been cancelled.');
                    $data['redirect'] = 'driver-change/details.php?id=' . $id;
                    return $data;
                } catch (DriverChangeRequestException $e) {
                    $data['errors']['form'] = $e->getMessage();
                } catch (Throwable $e) {
                    $this->log('driver change cancel', $e);
                    $data['errors']['form'] = 'Failed to cancel driver change request.';
                }
            }

            $req = $this->driverChangeModel->find($id);
            if (is_array($req) && (int)$req['requested_by'] === $this->userId()) {
                $data['request'] = $req;
            }
        }

        if ($data['request'] === null) {
            http_response_code(404);
        }

        return $data;
    }

    public function returnsPage(): array
    {
        $data = $this->pageData('Return Requests', 'Start the return process for an eligible active rental.', 'return');
        $data += ['requests' => [], 'eligible_bookings' => [], 'database_error' => false];
        $data['flash'] = $this->pullFlash();
        try {
            $data['requests'] = $this->model->getReturnRequests($this->userId());
            $data['eligible_bookings'] = $this->model->getReturnEligibleBookings($this->userId());
        } catch (Throwable $exception) {
            $this->log('returns', $exception);
            $data['requests'] = [];
            $data['eligible_bookings'] = [];
            $data['database_error'] = true;
        }
        if ($data['eligible_bookings'] !== []) {
            $data['page_action'] = ['label' => 'Start Return Request', 'path' => 'returns/create.php'];
        }
        return $data;
    }

    public function returnCreatePage(array $query, array $post, string $method): array
    {
        $data = $this->pageData('Start Return Request', 'Create a return request for an ongoing rental.', 'return');
        $data += ['errors' => [], 'form' => ['booking_id' => (string) ($query['booking_id'] ?? ''), 'proposed_return_at' => date('Y-m-d\TH:i'), 'customer_note' => ''], 'eligible_bookings' => [], 'database_error' => false];
        try {
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
                    $requestId = $this->model->createReturnRequest(
                        $this->userId(),
                        $bookingId,
                        $dateTime,
                        $data['form']['customer_note']
                    );
                    $this->setFlash('success', 'Return request submitted for owner inspection.');
                    $data['redirect'] = 'returns/details.php?id=' . $requestId;
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
        $data = $this->pageData('Return Request Details', 'Review your persistent return request.', 'return');
        $data += ['request' => null, 'database_error' => false];
        $id = $this->positiveInt($query['id'] ?? null);
        if ($id === null) {
            http_response_code(404);
            return $data;
        }
        try {
            $data['request'] = $this->model->findReturnRequest($this->userId(), $id);
            if ($data['request'] === null) {
                http_response_code(404);
            }
        } catch (Throwable $exception) {
            $this->log('return details', $exception);
            $data['database_error'] = true;
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

    private function normaliseGuarantorNic(mixed $value): string
    {
        $nic = $this->text($value, 25);
        $nic = preg_replace('/\\s+/u', '', $nic) ?? '';
        return strtoupper($nic);
    }

    private function normaliseGuarantorPhone(mixed $value): string
    {
        $phone = $this->text($value, 25);
        return preg_replace('/[\\s-]+/', '', $phone) ?? '';
    }

    private function characterLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    /** Builds Customer-only verification rows from persisted, session-scoped data. */
    private function verificationSummary(array $profile, ?array $guarantor): array
    {
        // The current schema has one Admin-owned status for the Customer's NIC and
        // driving licence. Keep that existing behaviour until separate document
        // statuses are introduced by an approved schema change.
        $identityStatus = $this->verificationStatus($profile['verification_status'] ?? 'pending');
        $guarantorStatus = $guarantor === null
            ? 'missing'
            : $this->verificationStatus($guarantor['verification_status'] ?? 'pending');
        $allApproved = $identityStatus === 'approved' && $guarantorStatus === 'approved';
        $overallStatus = $allApproved ? 'approved' : ($guarantorStatus === 'rejected' || $identityStatus === 'rejected' ? 'rejected' : 'pending');

        return [
            'overall_status' => $overallStatus,
            'overall_message' => $allApproved
                ? 'Customer verification is complete.'
                : ($guarantorStatus === 'missing'
                    ? 'Add guarantor details to complete verification.'
                    : ($guarantorStatus === 'pending'
                        ? 'Guarantor verification is pending.'
                        : 'A verification item needs correction.')),
            'rows' => [
                $this->verificationRow('nic', 'NIC', $identityStatus, 'Identity document verification', 'verification/index.php#identity-details'),
                $this->verificationRow('licence', 'Driving Licence', $identityStatus, 'Licence verification', 'verification/index.php#identity-details'),
                $this->guarantorVerificationRow($guarantor, $guarantorStatus),
            ],
        ];
    }

    private function verificationRow(string $key, string $title, string $status, string $description, string $path): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'description' => $status === 'approved' ? 'Verified' : ($status === 'rejected' ? 'Update and resubmit' : $description),
            'status' => $status,
            'icon' => $status === 'approved' ? 'check' : ($status === 'rejected' ? 'alert' : 'clock'),
            'tone' => $status === 'approved' ? 'success' : ($status === 'rejected' ? 'error' : 'warning'),
            'action_label' => $status === 'approved' ? 'View' : 'Update',
            'action_path' => $path,
        ];
    }

    private function guarantorVerificationRow(?array $guarantor, string $status): array
    {
        $description = match ($status) {
            'missing' => 'Add one trusted contact',
            'pending' => 'Waiting for verification',
            'approved' => 'Trusted contact verified',
            'rejected' => trim((string) ($guarantor['rejection_reason'] ?? '')) ?: 'Guarantor details were rejected. Please update and resubmit.',
        };

        return [
            'key' => 'guarantor',
            'title' => 'Guarantor details',
            'description' => $description,
            'status' => $status,
            'icon' => $status === 'missing' ? 'info' : ($status === 'approved' ? 'check' : ($status === 'rejected' ? 'alert' : 'clock')),
            'tone' => $status === 'missing' ? 'neutral' : ($status === 'approved' ? 'success' : ($status === 'rejected' ? 'error' : 'warning')),
            'action_label' => $status === 'missing' ? 'Upload' : ($status === 'approved' ? 'View' : 'Update'),
            'action_path' => 'profile/index.php#guarantor-details',
        ];
    }

    private function verificationStatus(mixed $status): string
    {
        return is_string($status) && in_array($status, ['pending', 'approved', 'rejected'], true)
            ? $status
            : 'pending';
    }

    private function incidentUploads(array $files,string $type,array &$errors): array { $out=['photos'=>[],'police'=>null]; $photos=$files['photos']??null; if(is_array($photos)&&is_array($photos['name']??null)){if(count($photos['name'])>5)$errors['photos']='Upload no more than five photos.'; foreach($photos['name'] as $i=>$name){if(($photos['error'][$i]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)continue; $f=['name'=>$name,'tmp_name'=>$photos['tmp_name'][$i]??'','size'=>$photos['size'][$i]??0,'error'=>$photos['error'][$i]??1]; if(!$this->validIncidentFile($f,['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'],5*1024*1024)){$errors['photos']='Photos must be genuine JPEG, PNG, or WebP files up to 5 MB.';break;} $out['photos'][]=$f;}} $police=$files['police_report']??null; if($type!=='breakdown'&&is_array($police)&&($police['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){if(!$this->validIncidentFile($police,['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'],10*1024*1024))$errors['police_report']='Police report must be PDF, JPEG, or PNG up to 10 MB.';else $out['police']=$police;} return $out; }
    private function validIncidentFile(array $f,array $allowed,int $max): bool {if(($f['error']??1)!==UPLOAD_ERR_OK||(int)($f['size']??0)<1||(int)($f['size']??0)>$max||!is_uploaded_file((string)($f['tmp_name']??'')))return false;$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);return is_string($mime)&&isset($allowed[$mime]);}
    private function storeIncidentUploads(array $uploads): array {$root=dirname(__DIR__,2).'/storage/customer-incident-uploads';if(!is_dir($root)&&!mkdir($root,0750,true)&&!is_dir($root))throw new RuntimeException('Incident upload storage is unavailable.');$save=function($f,$kind)use($root){$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'][$mime];$rel='customer-incident-uploads/'.$kind.'-'.bin2hex(random_bytes(16)).'.'.$ext;if(!move_uploaded_file($f['tmp_name'],dirname(__DIR__,2).'/storage/'.$rel))throw new RuntimeException('Upload could not be stored.');return $rel;};$paths=[];foreach($uploads['photos'] as $f)$paths[]=$save($f,'photo');return ['photos'=>$paths,'police'=>$uploads['police']?$save($uploads['police'],'police'):null];}

    /** @param array<string, mixed> $files @param array<string, string> $errors */
    private function validateVerificationUploads(array $files, bool $withGuarantor, array &$errors): array
    {
        $uploads = [];
        foreach (self::DOCUMENT_UPLOADS as $inputName => $definition) {
            if (!$withGuarantor && $definition['type'] === 'guarantor_nic') {
                continue;
            }

            $file = $files[$inputName] ?? null;
            if ($file === null) {
                continue;
            }
            if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['size'], $file['name'])) {
                $errors[$inputName] = 'The ' . $definition['label'] . ' upload is invalid.';
                continue;
            }

            $error = (int) $file['error'];
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                $errors[$inputName] = 'The ' . $definition['label'] . ' could not be uploaded.';
                continue;
            }
            if ((int) $file['size'] < 1 || (int) $file['size'] > self::DOCUMENT_MAX_BYTES) {
                $errors[$inputName] = $definition['label'] . ' must be no larger than 5 MB.';
                continue;
            }

            $temporaryPath = (string) $file['tmp_name'];
            if (!is_uploaded_file($temporaryPath)) {
                $errors[$inputName] = 'The ' . $definition['label'] . ' upload was not accepted.';
                continue;
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
            if (!is_string($mime) || !isset(self::DOCUMENT_MIME_EXTENSIONS[$mime])) {
                $errors[$inputName] = 'Only genuine PDF, JPEG, and PNG documents are allowed.';
                continue;
            }

            $originalName = $this->text(basename((string) $file['name']), 255, false);
            $uploads[] = [
                'type' => $definition['type'],
                'label' => $definition['label'],
                'temporary_path' => $temporaryPath,
                'original_name' => $originalName !== '' ? $originalName : $definition['type'] . '.' . self::DOCUMENT_MIME_EXTENSIONS[$mime],
                'extension' => self::DOCUMENT_MIME_EXTENSIONS[$mime],
            ];
        }

        return $uploads;
    }

    /** @param list<array{type:string,label:string,temporary_path:string,original_name:string,extension:string}> $uploads */
    private function storeVerificationUploads(array $uploads): array
    {
        if ($uploads === []) {
            return [];
        }

        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'customer-verification-documents';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Secure document storage is unavailable.');
        }

        $labels = [];
        foreach ($uploads as $upload) {
            $filename = bin2hex(random_bytes(16)) . '.' . $upload['extension'];
            $target = $directory . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($upload['temporary_path'], $target)) {
                throw new RuntimeException('The ' . $upload['label'] . ' could not be stored.');
            }

            try {
                $this->model->upsertVerificationDocument(
                    $this->userId(),
                    $upload['type'],
                    $upload['original_name'],
                    'customer-verification-documents/' . $filename
                );
            } catch (Throwable $exception) {
                @unlink($target);
                throw $exception;
            }
            $labels[] = $upload['label'];
        }

        return $labels;
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
