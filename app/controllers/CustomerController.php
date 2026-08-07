<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/helpers/CustomerCsrf.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/CustomerVehicle.php';

/**
 * Coordinates authenticated Customer foundation pages.
 * Feature-specific request handling belongs in the later Customer controllers.
 */
class CustomerController
{
    private Customer $customerModel;
    private CustomerVehicle $vehicleModel;

    public function __construct(?Customer $customerModel = null, ?CustomerVehicle $vehicleModel = null)
    {
        $this->customerModel = $customerModel ?? new Customer();
        $this->vehicleModel = $vehicleModel ?? new CustomerVehicle();
    }

    public function dashboard(): array
    {
        $data = $this->pageData(
            'Customer Dashboard',
            'Track your verification, bookings, payments, incidents, and rentals.',
            'dashboard'
        );

        $data['page_kicker'] = 'Customer workspace';
        $data['page_action'] = [
            'label' => 'Search Vehicles',
            'path' => 'vehicles/index.php',
        ];

        // Phase 3 display-only data. Later phases will replace this array with
        // Customer model queries after the shared database contract is approved.
        $data['dashboard'] = [
            'verification' => [
                'status' => 'Pending',
                'tone' => 'warning',
                'title' => 'Complete your verification before your first booking',
                'description' => 'Add the required identity and licence details so your account is ready to rent.',
                'action_label' => 'Complete verification',
                'action_path' => 'verification/index.php',
            ],
            'stats' => [
                ['label' => 'Verification', 'value' => 'Pending', 'detail' => 'Account review required', 'tone' => 'warning', 'icon' => 'shield'],
                ['label' => 'Active rental', 'value' => '0', 'detail' => 'No vehicle currently rented', 'tone' => 'info', 'icon' => 'car'],
                ['label' => 'Pending bookings', 'value' => '0', 'detail' => 'No requests awaiting action', 'tone' => 'neutral', 'icon' => 'clock'],
                ['label' => 'Completed bookings', 'value' => '0', 'detail' => 'Your history will appear here', 'tone' => 'success', 'icon' => 'check'],
            ],
            'progress' => [
                ['label' => 'Create your Customer account', 'description' => 'Your secure workspace is active.', 'state' => 'complete'],
                ['label' => 'Complete verification', 'description' => 'Add the required identity and licence details.', 'state' => 'current'],
                ['label' => 'Choose an approved vehicle', 'description' => 'Search the catalogue after verification.', 'state' => 'upcoming'],
                ['label' => 'Request a booking', 'description' => 'Confirm dates and rental preferences.', 'state' => 'upcoming'],
                ['label' => 'Confirm inspection and payment', 'description' => 'Review records before the rental begins.', 'state' => 'upcoming'],
                ['label' => 'Collect and return the vehicle', 'description' => 'Follow the agreed handover steps.', 'state' => 'upcoming'],
            ],
            'recent_booking' => null,
            'quick_links' => [
                ['label' => 'Search Vehicles', 'description' => 'Explore approved rental vehicles.', 'path' => 'vehicles/index.php', 'icon' => 'search'],
                ['label' => 'My Bookings', 'description' => 'View your booking workspace.', 'path' => 'bookings/index.php', 'icon' => 'calendar'],
                ['label' => 'Payments', 'description' => 'Review payment information.', 'path' => 'payments/index.php', 'icon' => 'credit-card'],
                ['label' => 'Get Support', 'description' => 'Open your Customer conversations.', 'path' => 'chat/index.php', 'icon' => 'chat'],
            ],
            'reminders' => [
                'Use the name and documents that match your official identification.',
                'Confirm vehicle availability and rental terms before requesting a booking.',
                'Keep payment and inspection records inside your Customer workspace.',
            ],
        ];

        return $data;
    }

    public function foundationPage(string $title, string $description, string $activeNav = ''): array
    {
        return $this->pageData($title, $description, $activeNav);
    }

    public function vehicleCatalogue(array $query): array
    {
        $validationErrors = [];
        $filters = $this->vehicleFilters($query, $validationErrors);
        $sort = $this->allowedValue($query['sort'] ?? '', array_keys($this->vehicleSortOptions()), 'newest');
        $page = $this->positiveInteger($query['page'] ?? null) ?? 1;
        $pageSize = 9;

        $data = $this->pageData(
            'Search Vehicles',
            'Find an approved Lanka Renters vehicle using secure server-side filters.',
            'vehicles'
        );
        $data['page_kicker'] = 'Vehicle catalogue';
        $data['filters'] = $filters;
        $data['sort'] = $sort;
        $data['sort_options'] = $this->vehicleSortOptions();
        $data['filter_options'] = $this->vehicleFilterOptions();
        $data['validation_errors'] = $validationErrors;
        $data['vehicles'] = [];
        $data['total_results'] = 0;
        $data['current_page'] = 1;
        $data['total_pages'] = 1;
        $data['page_size'] = $pageSize;
        $data['database_error'] = false;

        try {
            $totalResults = $this->vehicleModel->countVehicles($filters);
            $totalPages = max(1, (int) ceil($totalResults / $pageSize));
            $page = min($page, $totalPages);
            $offset = ($page - 1) * $pageSize;

            $data['vehicles'] = $this->vehicleModel->searchVehicles(
                $filters,
                $sort,
                $pageSize,
                $offset
            );
            $data['total_results'] = $totalResults;
            $data['current_page'] = $page;
            $data['total_pages'] = $totalPages;
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Customer vehicle catalogue error [%s]: %s',
                get_class($exception),
                $exception->getMessage()
            ));
            $data['database_error'] = true;
            http_response_code(503);
        }

        return $data;
    }

    public function vehicleDetails(array $query): array
    {
        $data = $this->pageData(
            'Vehicle Details',
            'Review approved vehicle information before continuing to the booking phase.',
            'vehicles'
        );
        $data['page_kicker'] = 'Vehicle catalogue';
        $data['vehicle'] = null;
        $data['customer_verification_status'] = 'pending';
        $data['database_error'] = false;
        $data['invalid_id'] = false;

        $vehicleId = $this->positiveInteger($query['id'] ?? null);
        if ($vehicleId === null) {
            $data['invalid_id'] = true;
            http_response_code(404);
            return $data;
        }

        try {
            $vehicle = $this->vehicleModel->findVisibleVehicleById($vehicleId);
            if ($vehicle === null) {
                http_response_code(404);
                return $data;
            }

            $data['vehicle'] = $vehicle;
            $data['page_title'] = trim((string) $vehicle['make'] . ' ' . (string) $vehicle['model']);
            $data['customer_verification_status'] = $this->vehicleModel->customerVerificationStatus(
                (int) ($data['customer']['user_id'] ?? 0)
            );
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Customer vehicle details error [%s]: %s',
                get_class($exception),
                $exception->getMessage()
            ));
            $data['database_error'] = true;
            http_response_code(503);
        }

        return $data;
    }

    private function vehicleFilters(array $query, array &$errors): array
    {
        $keyword = $this->queryText($query['keyword'] ?? '', 80);
        if (isset($query['keyword']) && is_string($query['keyword']) && strlen(trim($query['keyword'])) > 80) {
            $errors[] = 'Search text was shortened to 80 characters.';
        }

        $minPrice = $this->nonNegativeNumber($query['min_price'] ?? null, 'Minimum price', $errors);
        $maxPrice = $this->nonNegativeNumber($query['max_price'] ?? null, 'Maximum price', $errors);
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            $errors[] = 'Minimum price cannot be greater than maximum price.';
            $minPrice = null;
            $maxPrice = null;
        }

        $minSeats = null;
        if (($query['min_seats'] ?? '') !== '') {
            $minSeats = $this->positiveInteger($query['min_seats']);
            if ($minSeats === null || $minSeats > 100) {
                $errors[] = 'Minimum seats must be a positive number up to 100.';
                $minSeats = null;
            }
        }

        $startDate = $this->validDate($query['start_date'] ?? '');
        $endDate = $this->validDate($query['end_date'] ?? '');
        if (($query['start_date'] ?? '') !== '' && $startDate === '') {
            $errors[] = 'Start date is invalid.';
        }
        if (($query['end_date'] ?? '') !== '' && $endDate === '') {
            $errors[] = 'End date is invalid.';
        }
        if (($startDate === '') !== ($endDate === '')) {
            $errors[] = 'Choose both a start date and an end date to check date availability.';
            $startDate = '';
            $endDate = '';
        } elseif ($startDate !== '' && $endDate !== '' && $endDate <= $startDate) {
            $errors[] = 'End date must be later than start date.';
            $startDate = '';
            $endDate = '';
        }

        return [
            'keyword' => $keyword,
            'vehicle_type' => $this->allowedValue($query['vehicle_type'] ?? '', ['car', 'van', 'suv', 'lorry', 'motorbike']),
            'fuel_type' => $this->allowedValue($query['fuel_type'] ?? '', ['petrol', 'diesel', 'hybrid', 'electric']),
            'transmission' => $this->allowedValue($query['transmission'] ?? '', ['manual', 'automatic']),
            'service_type' => $this->allowedValue($query['service_type'] ?? '', ['self_drive', 'with_driver']),
            'availability' => $this->allowedValue($query['availability'] ?? '', ['available', 'all'], 'available'),
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_seats' => $minSeats,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    private function vehicleFilterOptions(): array
    {
        return [
            'vehicle_types' => ['car' => 'Car', 'van' => 'Van', 'suv' => 'SUV', 'lorry' => 'Lorry', 'motorbike' => 'Motorbike'],
            'fuel_types' => ['petrol' => 'Petrol', 'diesel' => 'Diesel', 'hybrid' => 'Hybrid', 'electric' => 'Electric'],
            'transmissions' => ['manual' => 'Manual', 'automatic' => 'Automatic'],
            'service_types' => ['self_drive' => 'Self-drive', 'with_driver' => 'With driver'],
            'availability' => ['available' => 'Available vehicles', 'all' => 'All approved vehicles'],
        ];
    }

    private function vehicleSortOptions(): array
    {
        return [
            'newest' => 'Newest',
            'price_asc' => 'Price: low to high',
            'price_desc' => 'Price: high to low',
            'year_desc' => 'Year: newest first',
        ];
    }

    private function queryText(mixed $value, int $maximumLength): string
    {
        if (!is_string($value)) {
            return '';
        }

        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        return function_exists('mb_substr')
            ? mb_substr($value, 0, $maximumLength, 'UTF-8')
            : substr($value, 0, $maximumLength);
    }

    private function allowedValue(mixed $value, array $allowed, string $default = ''): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }

    private function nonNegativeNumber(mixed $value, string $label, array &$errors): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($number === false || $number < 0) {
            $errors[] = $label . ' must be zero or greater.';
            return null;
        }

        return (float) $number;
    }

    private function positiveInteger(mixed $value): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $integer === false ? null : (int) $integer;
    }

    private function validDate(mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            return '';
        }

        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function pageData(string $title, string $description, string $activeNav): array
    {
        $user = AuthHelper::getCurrentUser();
        if (!is_array($user) || ($user['role'] ?? null) !== 'customer') {
            throw new LogicException('Authenticated customer context was not established.');
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
}
