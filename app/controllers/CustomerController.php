<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/helpers/CustomerCsrf.php';
require_once dirname(__DIR__) . '/models/Customer.php';

/**
 * Coordinates authenticated Customer foundation pages.
 * Feature-specific request handling belongs in the later Customer controllers.
 */
class CustomerController
{
    private Customer $customerModel;

    public function __construct(?Customer $customerModel = null)
    {
        $this->customerModel = $customerModel ?? new Customer();
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
