<?php

require_once dirname(__DIR__) . '/models/AdminAnalytics.php';

class AdminDashboardController
{
    private AdminAnalytics $analytics;

    public function __construct()
    {
        $this->analytics = new AdminAnalytics();
    }

    public function liveAnalytics(mixed $requestedYear): array
    {
        $year = filter_var($requestedYear, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]);
        return $this->analytics->dashboardAnalytics($year ?: (int) date('Y'));
    }
}