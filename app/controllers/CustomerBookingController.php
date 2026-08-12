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
            'Create Booking',
            'Choose a rental period and service for an approved vehicle.',
            'bookings'
        );
        $data['page_kicker'] = 'Booking management';
        $data['vehicle'] = null;
        $data['drivers'] = [];
        $data['form'] = $this->emptyForm();
        $data['field_errors'] = [];
        $data['eligibility_message'] = '';
        $data['database_error'] = false;
        $data['invalid_vehicle'] = false;
        $data['estimate'] = null;
        $data['redirect'] = '';
        $data['minimum_date'] = $this->today()->format('Y-m-d');

        $vehicleId = $this->positiveInteger($query['vehicle_id'] ?? null);
        if ($vehicleId === null) {
            $data['invalid_vehicle'] = true;
            http_response_code(404);
            return $data;
        }

        if ($requestMethod === 'POST') {
            $data['form'] = $this->bookingForm($post);
        }

        try {
            $context = $this->bookingModel->getCustomerContext($this->sessionUserId());
            $data['eligibility_message'] = $this->customerEligibilityMessage($context);
            $vehicle = $this->bookingModel->getBookableVehicle($vehicleId);
            if ($vehicle === null) {
                $data['invalid_vehicle'] = true;
                http_response_code(404);
                return $data;
            }
            $data['vehicle'] = $vehicle;

            $lookupDates = $this->driverLookupDates($data['form']);
            $data['drivers'] = $this->bookingModel->getEligibleDrivers(
                (int) $vehicle['owner_id'],
                $lookupDates['start'],
                $lookupDates['end']
            );
            $data['estimate'] = $this->estimate($vehicle, $data['form']);

            if ($requestMethod !== 'POST') {
                return $data;
            }

            $validated = $this->validateBookingForm($data['form'], $vehicle, $data['field_errors']);
            if ($data['eligibility_message'] !== '') {
                $data['field_errors']['form'] = $data['eligibility_message'];
            }
            if ($validated === null || $data['field_errors'] !== []) {
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
                $validated['delivery_address']
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
                    'delivery_address' => (string) ($booking['delivery_address'] ?? ''),
                ];
            }

            $lookupDates = $this->driverLookupDates($data['form']);
            $data['drivers'] = $this->bookingModel->getEligibleDrivers(
                (int) $booking['owner_id'],
                $lookupDates['start'],
                $lookupDates['end'],
                $bookingId
            );
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
                    $errors['end_date'] = 'The maximum rental period is six calendar months.';
                }
            }
        }

        $driverId = $this->positiveInteger($form['driver_id']);
        if ($bookingType === 'with_driver') {
            if (($vehicle['price_with_driver_per_day'] ?? null) === null) {
                $errors['booking_type'] = 'This vehicle does not offer a with-driver service.';
            }
            if ($driverId === null) {
                $errors['driver_id'] = 'Select an eligible Driver.';
            }
        } else {
            $driverId = null;
        }

        $deliveryAddress = preg_replace('/\s+/', ' ', trim((string) $form['delivery_address'])) ?? '';
        if ($this->textLength($deliveryAddress) > 255) {
            $errors['delivery_address'] = 'Delivery address must be 255 characters or fewer.';
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
            'delivery_address' => $deliveryAddress !== '' ? $deliveryAddress : null,
        ];
    }

    private function bookingForm(array $input): array
    {
        return [
            'booking_type' => is_string($input['booking_type'] ?? null) ? trim($input['booking_type']) : '',
            'start_date' => is_string($input['start_date'] ?? null) ? trim($input['start_date']) : '',
            'end_date' => is_string($input['end_date'] ?? null) ? trim($input['end_date']) : '',
            'driver_id' => is_string($input['driver_id'] ?? null) ? trim($input['driver_id']) : '',
            'delivery_address' => is_string($input['delivery_address'] ?? null)
                ? trim($input['delivery_address'])
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
            'delivery_address' => '',
        ];
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
