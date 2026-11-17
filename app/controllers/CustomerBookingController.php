<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/helpers/CustomerCsrf.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/CustomerBooking.php';

class CustomerBookingController
{
    private const TIMEZONE = 'Asia/Colombo';
    private const PAGE_SIZE = 6;
    private const FLASH_KEY = '_customer_booking_flash';
    private const AGREEMENT_VERSION = 'customer-booking-terms-v1';

    private Customer $customerModel;
    private CustomerBooking $bookingModel;

    public function __construct(?Customer $customerModel = null, ?CustomerBooking $bookingModel = null)
    {
        $this->customerModel = $customerModel ?? new Customer();
        $this->bookingModel = $bookingModel ?? new CustomerBooking();
    }

    public function createPage(array $query, array $post, string $requestMethod): array
    {
        $data = $this->pageData(
            'Booking Request Form',
            'Complete your rental details and accept the booking conditions before creating a request.',
            'bookings'
        );
        $data['page_kicker'] = 'Booking management';
        $data['vehicle'] = null;
        $data['drivers'] = [];
        $data['form'] = $requestMethod === 'POST' ? $this->bookingForm($post) : $this->bookingForm($query);
        $data['agreements'] = $requestMethod === 'POST' ? $this->agreementForm($post) : $this->emptyAgreements();
        $data['field_errors'] = [];
        $data['eligibility_message'] = '';
        $data['database_error'] = false;
        $data['invalid_vehicle'] = false;
        $data['estimate'] = null;
        $data['customer_context'] = null;
        $data['requirements'] = [];
        $data['driver_dates_ready'] = false;
        $data['can_send_request'] = false;
        $data['can_handoff'] = false;
        $data['can_complete_selection'] = false;
        $data['can_create_request'] = false;
        $data['selected_driver'] = null;
        $data['redirect'] = '';
        $data['minimum_date'] = $this->today()->format('Y-m-d');

        $vehicleId = $this->positiveInteger($query['vehicle_id'] ?? null);
        if ($vehicleId === null) {
            $data['invalid_vehicle'] = true;
            http_response_code(404);
            return $data;
        }

        try {
            $context = $this->bookingModel->getCustomerContext($this->sessionUserId());
            $data['customer_context'] = $context;
            $data['eligibility_message'] = $this->customerEligibilityMessage($context);
            $vehicle = $this->bookingModel->getBookableVehicle($vehicleId);
            if ($vehicle === null) {
                $data['invalid_vehicle'] = true;
                http_response_code(404);
                return $data;
            }
            $data['vehicle'] = $vehicle;

            $data['drivers'] = $this->eligibleDriversForForm($vehicle, $data['form']);
            $data['estimate'] = $this->estimate($vehicle, $data['form']);
            $data['driver_dates_ready'] = $this->driverLookupDates($data['form'])['start'] !== null;
            $data['requirements'] = $this->bookingRequirements($vehicle, $data['form'], $context, $data['drivers']);
            $data['can_send_request'] = $this->canSendRequest(
                $data['requirements'],
                (string) $data['form']['booking_type']
            );
            $data['can_handoff'] = $data['can_send_request'];
            $data['can_complete_selection'] = $this->canCompleteSelection(
                $data['requirements'],
                (string) $data['form']['booking_type']
            );
            $data['can_create_request'] = $data['can_handoff'] && $this->allAgreementsAccepted($data['agreements']);
            $data['selected_driver'] = $this->selectedDriver($data['drivers'], $data['form']);

            if ($requestMethod !== 'POST') {
                return $data;
            }

            $bookingAction = (string) ($post['booking_action'] ?? '');
            if ($bookingAction === 'preview') {
                return $data;
            }

            $validated = $this->validateBookingForm($data['form'], $vehicle, $data['field_errors']);
            if ($data['eligibility_message'] !== '') {
                $data['field_errors']['form'] = $data['eligibility_message'];
            }
            if ($validated === null || $data['field_errors'] !== []) {
                return $data;
            }

            if ($bookingAction === 'handoff') {
                if (!$data['can_send_request']) {
                    $data['field_errors']['form'] = 'Complete the outstanding booking requirements before continuing.';
                    return $data;
                }
                $data['redirect'] = $this->bookingReviewPath($vehicleId, $validated);
                return $data;
            }

            if ($bookingAction !== 'create') {
                $data['field_errors']['form'] = 'The booking request action was invalid.';
                return $data;
            }

            if (!$this->allAgreementsAccepted($data['agreements'])) {
                $data['field_errors']['booking_agreements'] = 'You must accept the Booking Agreement before creating your request.';
                return $data;
            }

            $result = $this->bookingModel->createForCustomer(
                $this->sessionUserId(),
                $vehicleId,
                $validated['booking_type'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['rental_days'],
                $validated['driver_id'],
                $validated['delivery_address'],
                self::AGREEMENT_VERSION
            );
            $this->setFlash('success', 'Booking #' . $result['booking_id'] . ' was created and is pending payment.');
            $data['redirect'] = 'bookings/details.php?id=' . (int) $result['booking_id'];
        } catch (CustomerBookingRuleException $exception) {
            $data['field_errors']['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->recordDatabaseError('create page', $exception);
            $data['database_error'] = true;
            http_response_code(503);
        }

        return $data;
    }

    public function historyPage(array $query): array
    {
        $data = $this->pageData(
            'My Bookings',
            'Review your booking requests and preserved rental history.',
            'bookings'
        );
        $data['page_kicker'] = 'Booking management';
        $data['status_options'] = $this->statusOptions();
        $data['status_filter'] = $this->allowedValue(
            $query['status'] ?? '',
            array_keys($data['status_options'])
        );
        $data['bookings'] = [];
        $data['total_results'] = 0;
        $data['current_page'] = 1;
        $data['total_pages'] = 1;
        $data['database_error'] = false;
        $data['flash'] = $this->pullFlash();

        $page = $this->positiveInteger($query['page'] ?? null) ?? 1;
        try {
            $total = $this->bookingModel->countCustomerBookings(
                $this->sessionUserId(),
                $data['status_filter']
            );
            $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
            $page = min($page, $totalPages);
            $data['bookings'] = $this->bookingModel->getCustomerBookings(
                $this->sessionUserId(),
                $data['status_filter'],
                self::PAGE_SIZE,
                ($page - 1) * self::PAGE_SIZE
            );
            $data['total_results'] = $total;
            $data['current_page'] = $page;
            $data['total_pages'] = $totalPages;
        } catch (Throwable $exception) {
            $this->recordDatabaseError('history page', $exception);
            $data['database_error'] = true;
            http_response_code(503);
        }

        return $data;
    }

    public function detailsPage(array $query): array
    {
        $data = $this->pageData(
            'Booking Details',
            'Review the stored details and currently permitted actions for your booking.',
            'bookings'
        );
        $data['page_kicker'] = 'Booking management';
        $data['booking'] = null;
        $data['database_error'] = false;
        $data['invalid_id'] = false;
        $data['flash'] = $this->pullFlash();

        $bookingId = $this->positiveInteger($query['id'] ?? null);
        if ($bookingId === null) {
            $data['invalid_id'] = true;
            http_response_code(404);
            return $data;
        }

        try {
            $booking = $this->bookingModel->findCustomerBooking($this->sessionUserId(), $bookingId);
            if ($booking === null) {
                http_response_code(404);
                return $data;
            }
            $booking['_can_edit'] = $this->canEdit($booking);
            $booking['_can_cancel'] = $this->canCancel($booking);
            $data['booking'] = $booking;
            $data['page_title'] = 'Booking #' . (int) $booking['id'];
        } catch (Throwable $exception) {
            $this->recordDatabaseError('details page', $exception);
            $data['database_error'] = true;
            http_response_code(503);
        }

        return $data;
    }

    public function editPage(array $query, array $post, string $requestMethod): array
    {
        $data = $this->pageData(
            'Edit Booking',
            'Update eligible future rental details before payment is completed.',
            'bookings'
        );
        $data['page_kicker'] = 'Booking management';
        $data['booking'] = null;
        $data['vehicle'] = null;
        $data['drivers'] = [];
        $data['form'] = $this->emptyForm();
        $data['field_errors'] = [];
        $data['eligibility_message'] = '';
        $data['database_error'] = false;
        $data['invalid_id'] = false;
        $data['not_editable'] = false;
        $data['estimate'] = null;
        $data['redirect'] = '';
        $data['minimum_date'] = $this->today()->format('Y-m-d');

        $bookingId = $this->positiveInteger($query['id'] ?? null);
        if ($bookingId === null) {
            $data['invalid_id'] = true;
            http_response_code(404);
            return $data;
        }

        try {
            $context = $this->bookingModel->getCustomerContext($this->sessionUserId());
            $data['eligibility_message'] = $this->customerEligibilityMessage($context);
            $booking = $this->bookingModel->findCustomerBooking($this->sessionUserId(), $bookingId);
            if ($booking === null) {
                http_response_code(404);
                return $data;
            }
            $data['booking'] = $booking;
            $data['vehicle'] = $booking;
            $data['not_editable'] = !$this->canEdit($booking);

            if ($requestMethod === 'POST') {
                $data['form'] = $this->bookingForm($post);
            } else {
                $data['form'] = [
                    'booking_type' => (string) $booking['booking_type'],
                    'start_date' => substr((string) $booking['start_date'], 0, 10),
                    'end_date' => substr((string) $booking['end_date'], 0, 10),
                    'driver_id' => $booking['driver_id'] !== null ? (string) $booking['driver_id'] : '',
                    'pickup_location' => (string) ($booking['delivery_address'] ?? ''),
                ];
            }

            $data['drivers'] = $this->eligibleDriversForForm($booking, $data['form'], $bookingId);
            $data['estimate'] = $this->estimate($booking, $data['form']);

            if ($requestMethod !== 'POST') {
                return $data;
            }
            if ($data['not_editable']) {
                $data['field_errors']['form'] = 'This booking can no longer be edited.';
                return $data;
            }

            $validated = $this->validateBookingForm($data['form'], $booking, $data['field_errors']);
            if ($data['eligibility_message'] !== '') {
                $data['field_errors']['form'] = $data['eligibility_message'];
            }
            if ($validated === null || $data['field_errors'] !== []) {
                return $data;
            }

            $this->bookingModel->updateCustomerBooking(
                $this->sessionUserId(),
                $bookingId,
                $validated['booking_type'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['rental_days'],
                $validated['driver_id'],
                $validated['delivery_address'],
                $this->now()->format('Y-m-d H:i:s')
            );
            $this->setFlash('success', 'Booking #' . $bookingId . ' was updated and repriced securely.');
            $data['redirect'] = 'bookings/details.php?id=' . $bookingId;
        } catch (CustomerBookingRuleException $exception) {
            $data['field_errors']['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $this->recordDatabaseError('edit page', $exception);
            $data['database_error'] = true;
            http_response_code(503);
        }

        return $data;
    }

    public function cancel(array $post, string $requestMethod): array
    {
        if ($requestMethod !== 'POST') {
            http_response_code(405);
            return ['method_not_allowed' => true, 'redirect' => ''];
        }

        $bookingId = $this->positiveInteger($post['booking_id'] ?? null);
        if ($bookingId === null) {
            $this->setFlash('error', 'The cancellation request was invalid.');
            return ['method_not_allowed' => false, 'redirect' => 'bookings/index.php'];
        }
        if (($post['confirm_cancel'] ?? '') !== 'yes') {
            $this->setFlash('error', 'Confirm the cancellation before submitting.');
            return ['method_not_allowed' => false, 'redirect' => 'bookings/details.php?id=' . $bookingId];
        }

        try {
            $this->bookingModel->cancelCustomerBooking(
                $this->sessionUserId(),
                $bookingId,
                $this->now()->format('Y-m-d H:i:s')
            );
            $this->setFlash('success', 'Booking #' . $bookingId . ' was cancelled. Its history has been preserved.');
            return ['method_not_allowed' => false, 'redirect' => 'bookings/details.php?id=' . $bookingId];
        } catch (CustomerBookingRuleException $exception) {
            $message = $exception->getMessage() === 'Booking not found.'
                ? 'The booking was not found.'
                : $exception->getMessage();
            $this->setFlash('error', $message);
            $path = $exception->getMessage() === 'Booking not found.'
                ? 'bookings/index.php'
                : 'bookings/details.php?id=' . $bookingId;
            return ['method_not_allowed' => false, 'redirect' => $path];
        } catch (Throwable $exception) {
            $this->recordDatabaseError('cancellation endpoint', $exception);
            $this->setFlash('error', 'The booking could not be cancelled. Please try again.');
            return ['method_not_allowed' => false, 'redirect' => 'bookings/details.php?id=' . $bookingId];
        }
    }

    private function validateBookingForm(array $form, array $vehicle, array &$errors): ?array
    {
        $bookingType = $this->allowedValue($form['booking_type'], ['self_drive', 'with_driver']);
        if ($bookingType === '') {
            $errors['booking_type'] = 'Select self-drive or with-driver service.';
        }

        $start = $this->formDate($form['start_date']);
        $end = $this->formDate($form['end_date']);
        if ($start === null) {
            $errors['start_date'] = 'Enter a valid start date.';
        }
        if ($end === null) {
            $errors['end_date'] = 'Enter a valid end date.';
        }

        $rentalDays = 0;
        if ($start !== null && $end !== null) {
            if ($start < $this->today()) {
                $errors['start_date'] = 'The start date cannot be in the past.';
            }
            if ($end <= $start) {
                $errors['end_date'] = 'The end date must be later than the start date.';
            } else {
                $rentalDays = (int) $start->diff($end)->days;
                if ($rentalDays < 28) {
                    $errors['end_date'] = 'The minimum rental period is 28 days.';
                }
                if ($end > $start->modify('+6 months')) {
                    $errors['end_date'] = 'The maximum rental period is 6 months.';
                }
            }
        }

        $submittedDriverId = $this->positiveInteger($form['driver_id']);
        $driverId = $submittedDriverId;
        if ($bookingType === 'with_driver') {
            if (($vehicle['price_with_driver_per_day'] ?? null) === null) {
                $errors['booking_type'] = 'This vehicle does not offer a with-driver service.';
            }
            if ($driverId === null) {
                $errors['driver_id'] = 'Select an eligible Driver.';
            }
        } else {
            if (trim((string) $form['driver_id']) !== '') {
                $errors['driver_id'] = 'Driver selection is not available for Self Drive.';
            }
            $driverId = null;
        }

        $pickupLocation = preg_replace('/\s+/', ' ', trim((string) $form['pickup_location'])) ?? '';
        if ($bookingType === 'with_driver') {
            $pickupLength = $this->textLength($pickupLocation);
            if ($pickupLength < 5 || $pickupLength > 255) {
                $errors['pickup_location'] = 'Pickup Location must be between 5 and 255 characters.';
            }
        } elseif ($pickupLocation !== '') {
            $errors['pickup_location'] = 'Custom pickup location is only available with With Driver.';
        }

        $collectionLocation = preg_replace('/\s+/', ' ', trim((string) ($vehicle['location'] ?? ''))) ?? '';
        if ($bookingType === 'self_drive' && $collectionLocation === '') {
            $errors['form'] = 'This vehicle has no listed collection location.';
        }
        if ($errors !== [] || $start === null || $end === null || $bookingType === '') {
            return null;
        }

        return [
            'booking_type' => $bookingType,
            'start_date' => $start->format('Y-m-d 00:00:00'),
            'end_date' => $end->format('Y-m-d 00:00:00'),
            'rental_days' => $rentalDays,
            'driver_id' => $driverId,
            'delivery_address' => $bookingType === 'self_drive'
                ? $collectionLocation
                : $pickupLocation,
        ];
    }

    private function bookingForm(array $input): array
    {
        return [
            'booking_type' => is_string($input['booking_type'] ?? null) ? trim($input['booking_type']) : '',
            'start_date' => is_string($input['start_date'] ?? null) ? trim($input['start_date']) : '',
            'end_date' => is_string($input['end_date'] ?? null) ? trim($input['end_date']) : '',
            'driver_id' => is_string($input['driver_id'] ?? null) ? trim($input['driver_id']) : '',
            'pickup_location' => is_string($input['pickup_location'] ?? null)
                ? trim($input['pickup_location'])
                : '',
        ];
    }

    private function emptyForm(): array
    {
        return [
            'booking_type' => 'self_drive',
            'start_date' => '',
            'end_date' => '',
            'driver_id' => '',
            'pickup_location' => '',
        ];
    }

    private function agreementForm(array $input): array
    {
        return [
            'accepted' => ($input['booking_terms_accepted'] ?? null) === '1',
        ];
    }

    private function emptyAgreements(): array
    {
        return [
            'accepted' => false,
        ];
    }

    private function allAgreementsAccepted(array $agreements): bool
    {
        return (bool) ($agreements['accepted'] ?? false);
    }

    private function bookingReviewPath(int $vehicleId, array $validated): string
    {
        $parameters = [
            'vehicle_id' => $vehicleId,
            'booking_type' => $validated['booking_type'],
            'start_date' => substr($validated['start_date'], 0, 10),
            'end_date' => substr($validated['end_date'], 0, 10),
        ];
        if ($validated['driver_id'] !== null) {
            $parameters['driver_id'] = $validated['driver_id'];
        }
        if ($validated['booking_type'] === 'with_driver' && $validated['delivery_address'] !== null) {
            $parameters['pickup_location'] = $validated['delivery_address'];
        }

        return 'bookings/create.php?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    private function selectedDriver(array $drivers, array $form): ?array
    {
        $driverId = $this->positiveInteger($form['driver_id'] ?? null);
        if ($driverId === null || $form['booking_type'] !== 'with_driver') {
            return null;
        }
        foreach ($drivers as $driver) {
            if ((int) ($driver['id'] ?? 0) === $driverId) {
                return $driver;
            }
        }

        return null;
    }

    private function driverLookupDates(array $form): array
    {
        $start = $this->formDate($form['start_date']);
        $end = $this->formDate($form['end_date']);
        if ($start === null || $end === null || $end <= $start) {
            return ['start' => null, 'end' => null];
        }

        return [
            'start' => $start->format('Y-m-d 00:00:00'),
            'end' => $end->format('Y-m-d 00:00:00'),
        ];
    }

    private function eligibleDriversForForm(array $vehicle, array $form, int $excludeBookingId = 0): array
    {
        $lookupDates = $this->driverLookupDates($form);
        if ($lookupDates['start'] === null || $lookupDates['end'] === null) {
            return [];
        }

        try {
            return $this->bookingModel->getEligibleDrivers(
                (int) $vehicle['owner_id'],
                $lookupDates['start'],
                $lookupDates['end'],
                $excludeBookingId
            );
        } catch (Throwable $exception) {
            $this->recordDatabaseError('eligible Driver lookup', $exception);

            return [];
        }
    }

    private function estimate(array $vehicle, array $form): ?array
    {
        $start = $this->formDate($form['start_date']);
        $end = $this->formDate($form['end_date']);
        if ($start === null || $end === null || $end <= $start) {
            return null;
        }

        $days = (int) $start->diff($end)->days;
        $rate = $form['booking_type'] === 'with_driver'
            ? ($vehicle['price_with_driver_per_day'] ?? null)
            : ($vehicle['price_per_day'] ?? null);
        if ($rate === null) {
            return null;
        }

        return ['rental_days' => $days, 'estimated_total' => round((float) $rate * $days, 2)];
    }

    private function customerEligibilityMessage(?array $context): string
    {
        if ($context === null) {
            return 'A Customer profile is required before you can create or change a booking.';
        }
        if ((string) $context['user_status'] !== 'active') {
            return 'Your Customer account must be active before you can create or change a booking.';
        }
        if ((string) $context['verification_status'] !== 'approved') {
            return 'Your Customer verification must be approved before you can create or change a booking.';
        }

        return '';
    }

    /** Builds display-only readiness items; final enforcement remains in CustomerBooking. */
    private function bookingRequirements(array $vehicle, array $form, ?array $context, array $drivers): array
    {
        $dates = $this->driverLookupDates($form);
        $validDatesState = 'pending';
        $validDatesDescription = 'Choose pickup and return dates.';
        $rentalPeriodState = 'pending';
        $rentalPeriodDescription = 'Choose dates to check the 28-day to 6-month rental period.';
        $availabilityState = 'pending';
        $availabilityDescription = 'Choose dates to check availability.';

        if ($dates['start'] !== null && $dates['end'] !== null) {
            $start = $this->formDate($form['start_date']);
            $end = $this->formDate($form['end_date']);
            $days = $start !== null && $end !== null ? (int) $start->diff($end)->days : 0;
            if ($start !== null && $end !== null && $start >= $this->today()) {
                $validDatesState = 'complete';
                $validDatesDescription = 'Pickup and return dates are valid.';
                if ($days >= 28 && $end <= $start->modify('+6 months')) {
                    $rentalPeriodState = 'complete';
                    $rentalPeriodDescription = $days . ' day rental period selected.';
                } else {
                    $rentalPeriodState = 'failed';
                    $rentalPeriodDescription = 'Choose a rental period from 28 days up to 6 calendar months.';
                }

                if ($rentalPeriodState === 'complete') {
                try {
                    $available = !$this->bookingModel->hasVehicleOverlap((int) $vehicle['id'], $dates['start'], $dates['end']);
                    $availabilityState = $available ? 'complete' : 'failed';
                    $availabilityDescription = $available
                        ? 'Vehicle is available for the selected dates.'
                        : 'Vehicle is already booked for part of the selected period.';
                } catch (Throwable) {
                    $availabilityState = 'pending';
                    $availabilityDescription = 'Availability will be rechecked when you send the request.';
                }
                } else {
                    $availabilityDescription = 'Choose a valid rental period before availability can be checked.';
                }
            } else {
                $validDatesState = 'failed';
                $validDatesDescription = 'Choose future pickup and return dates.';
            }
        }

        $customerStatus = (string) ($context['customer_verification_status'] ?? 'pending');
        $guarantorStatus = (string) ($context['guarantor_verification_status'] ?? 'missing');
        $overallVerificationStatus = (string) ($context['verification_status'] ?? 'pending');
        $customerComplete = $overallVerificationStatus === 'approved';
        $guarantorComplete = $guarantorStatus === 'approved';
        $requirements = [
            [
                'state' => $customerComplete ? 'complete' : ($overallVerificationStatus === 'rejected' ? 'failed' : 'pending'),
                'label' => 'Customer verification',
                'description' => $customerComplete
                    ? 'Customer verification is approved.'
                    : ($overallVerificationStatus === 'rejected'
                        ? 'A Customer verification requirement was rejected.'
                        : 'Complete all Customer verification requirements.'),
                'action' => 'verification/index.php',
                'action_label' => 'Complete Verification',
                'applies_to' => 'all',
            ],
            [
                'state' => $customerStatus === 'approved' ? 'complete' : ($customerStatus === 'rejected' ? 'failed' : 'pending'),
                'label' => 'NIC verification',
                'description' => $customerStatus === 'approved' ? 'NIC verification is approved.' : 'NIC verification must be approved.',
                'action' => 'verification/index.php',
                'action_label' => 'Complete Verification',
                'applies_to' => 'all',
            ],
            [
                'state' => $customerStatus === 'approved' ? 'complete' : ($customerStatus === 'rejected' ? 'failed' : 'pending'),
                'label' => 'Driving Licence verification',
                'description' => $customerStatus === 'approved' ? 'Driving Licence verification is approved.' : 'Driving Licence verification must be approved.',
                'action' => 'verification/index.php',
                'action_label' => 'Complete Verification',
                'applies_to' => 'all',
            ],
            [
                'state' => $guarantorComplete ? 'complete' : ($guarantorStatus === 'rejected' ? 'failed' : 'pending'),
                'label' => 'Guarantor verification',
                'description' => $guarantorComplete ? 'Guarantor details are approved.' : 'An approved guarantor is required.',
                'action' => 'profile/index.php#guarantor-details',
                'action_label' => 'Add Guarantor',
                'applies_to' => 'all',
            ],
            [
                'state' => $validDatesState,
                'label' => 'Valid rental dates',
                'description' => $validDatesDescription,
                'action' => '#booking-dates',
                'action_label' => 'Change Dates',
                'applies_to' => 'all',
            ],
            [
                'state' => $rentalPeriodState,
                'label' => 'Rental period is between 28 days and 6 months',
                'description' => $rentalPeriodDescription,
                'action' => '#booking-dates',
                'action_label' => 'Change Dates',
                'applies_to' => 'all',
            ],
            [
                'state' => $availabilityState,
                'label' => 'Vehicle is available for the selected dates',
                'description' => $availabilityDescription,
                'action' => '#booking-dates',
                'action_label' => 'Change Dates',
                'applies_to' => 'all',
            ],
        ];

        $driverId = $this->positiveInteger($form['driver_id']);
        $selected = $driverId !== null && in_array($driverId, array_map(static fn (array $driver): int => (int) $driver['id'], $drivers), true);
        $driversAvailable = $dates['start'] !== null && $rentalPeriodState === 'complete' && $drivers !== [];
        $pickupLocation = preg_replace('/\s+/', ' ', trim((string) $form['pickup_location'])) ?? '';
        $pickupLength = $this->textLength($pickupLocation);
        $pickupValid = $pickupLength >= 5 && $pickupLength <= 255;

        array_unshift($requirements,
            [
                'state' => (float) ($vehicle['price_per_day'] ?? 0) > 0 ? 'complete' : 'failed',
                'label' => 'Vehicle supports Self Drive',
                'description' => (float) ($vehicle['price_per_day'] ?? 0) > 0 ? 'Self Drive is offered for this vehicle.' : 'Self Drive is not offered for this vehicle.',
                'action' => '',
                'applies_to' => 'self_drive',
            ],
            [
                'state' => ($vehicle['price_with_driver_per_day'] ?? null) !== null && (float) $vehicle['price_with_driver_per_day'] > 0 ? 'complete' : 'failed',
                'label' => 'Vehicle supports With Driver',
                'description' => ($vehicle['price_with_driver_per_day'] ?? null) !== null && (float) $vehicle['price_with_driver_per_day'] > 0 ? 'With Driver is offered for this vehicle.' : 'With Driver is not offered for this vehicle.',
                'action' => '',
                'applies_to' => 'with_driver',
            ]
        );

        $requirements[] = [
            'state' => $dates['start'] === null || $rentalPeriodState !== 'complete'
                ? 'pending'
                : ($driversAvailable ? 'complete' : 'failed'),
            'label' => 'Eligible drivers are available',
            'description' => $dates['start'] === null || $rentalPeriodState !== 'complete'
                ? 'Select valid rental dates before Driver availability can be checked.'
                : ($driversAvailable ? 'Verified Drivers are available for the complete rental period.' : 'No verified drivers linked to this vehicle are available for your selected dates.'),
            'action' => '#booking-dates',
            'action_label' => 'Change Dates',
            'applies_to' => 'with_driver',
            'key' => 'driver-availability',
        ];
        $requirements[] = [
            'state' => $dates['start'] === null || $rentalPeriodState !== 'complete'
                ? 'pending'
                : ($selected ? 'complete' : 'pending'),
            'label' => 'An eligible driver has been selected',
            'description' => $dates['start'] === null || $rentalPeriodState !== 'complete'
                ? 'Select valid dates before Drivers can be checked.'
                : ($selected ? 'Selected Driver is available for the complete rental period.' : 'Select an eligible Driver.'),
            'action' => '#driver-selection',
            'action_label' => 'Select Driver',
            'applies_to' => 'with_driver',
            'key' => 'driver-selection',
        ];
        $requirements[] = [
            'state' => $pickupValid ? 'complete' : 'pending',
            'label' => 'Pickup Location is provided',
            'description' => $pickupValid ? 'A pickup address is ready for the selected Driver.' : 'Enter a clear pickup address for the selected Driver.',
            'action' => '#pickup-location',
            'action_label' => 'Enter Pickup Location',
            'applies_to' => 'with_driver',
            'key' => 'pickup-location',
        ];

        return $requirements;
    }

    private function canSendRequest(array $requirements, string $bookingType): bool
    {
        foreach ($requirements as $requirement) {
            if (($requirement['applies_to'] ?? 'all') !== 'all' && ($requirement['applies_to'] ?? '') !== $bookingType) {
                continue;
            }
            if (($requirement['state'] ?? '') !== 'complete') {
                return false;
            }
        }

        return true;
    }

    /**
     * Lets the browser enable the final action after choosing a Driver from the
     * server-filtered list. Final creation still repeats the database check.
     */
    private function canCompleteSelection(array $requirements, string $bookingType): bool
    {
        foreach ($requirements as $requirement) {
            if (($requirement['applies_to'] ?? 'all') !== 'all' && ($requirement['applies_to'] ?? '') !== $bookingType) {
                continue;
            }
            if (in_array(($requirement['action'] ?? ''), ['#driver-selection', '#pickup-location'], true)) {
                continue;
            }
            if (($requirement['state'] ?? '') !== 'complete') {
                return false;
            }
        }

        return true;
    }

    private function canEdit(array $booking): bool
    {
        return (string) $booking['status'] === 'pending_payment'
            && (string) $booking['start_date'] > $this->now()->format('Y-m-d H:i:s');
    }

    private function canCancel(array $booking): bool
    {
        return in_array((string) $booking['status'], ['pending_payment', 'confirmed'], true)
            && (string) $booking['start_date'] > $this->now()->format('Y-m-d H:i:s');
    }

    private function statusOptions(): array
    {
        return [
            '' => 'All statuses',
            'pending_payment' => 'Pending payment',
            'confirmed' => 'Confirmed',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    private function pageData(string $title, string $description, string $activeNav): array
    {
        $user = AuthHelper::getCurrentUser();
        if (!is_array($user) || ($user['role'] ?? null) !== 'customer') {
            throw new LogicException('Authenticated Customer context was not established.');
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

    private function sessionUserId(): int
    {
        $user = AuthHelper::getCurrentUser();
        return is_array($user) ? (int) ($user['id'] ?? 0) : 0;
    }

    private function positiveInteger(mixed $value): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $integer === false ? null : (int) $integer;
    }

    private function allowedValue(mixed $value, array $allowed): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : '';
    }

    private function formDate(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            trim($value),
            new DateTimeZone(self::TIMEZONE)
        );
        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === trim($value)
            ? $date
            : null;
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE));
    }

    private function today(): DateTimeImmutable
    {
        return new DateTimeImmutable('today', new DateTimeZone(self::TIMEZONE));
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function setFlash(string $tone, string $message): void
    {
        AuthHelper::startSession();
        $_SESSION[self::FLASH_KEY] = ['tone' => $tone, 'message' => $message];
    }

    private function pullFlash(): ?array
    {
        AuthHelper::startSession();
        $flash = $_SESSION[self::FLASH_KEY] ?? null;
        unset($_SESSION[self::FLASH_KEY]);

        return is_array($flash) ? $flash : null;
    }

    private function recordDatabaseError(string $context, Throwable $exception): void
    {
        error_log(sprintf(
            'Customer booking %s error [%s]: %s',
            $context,
            get_class($exception),
            $exception->getMessage()
        ));
    }
}
