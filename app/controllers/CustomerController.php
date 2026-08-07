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
        return $this->pageData(
            'Customer Dashboard',
            'Your Customer workspace is authenticated and ready for feature integration.'
        );
    }

    public function foundationPage(string $title, string $description): array
    {
        return $this->pageData($title, $description);
    }

    private function pageData(string $title, string $description): array
    {
        $user = AuthHelper::getCurrentUser();
        if (!is_array($user) || ($user['role'] ?? null) !== 'customer') {
            throw new LogicException('Authenticated customer context was not established.');
        }

        return [
            'page_title' => $title,
            'page_description' => $description,
            'customer' => $this->customerModel->identityFromAuthenticatedUser($user),
            'csrf_token' => CustomerCsrf::token(),
            'csrf_field_name' => CustomerCsrf::fieldName(),
        ];
    }
}
