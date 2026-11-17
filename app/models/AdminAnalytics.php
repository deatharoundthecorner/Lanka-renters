<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

class AdminAnalytics
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function dashboardAnalytics(int $year): array
    {
        $stats = [
            'users' => (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
            'owners' => (int) $this->db->query('SELECT COUNT(*) FROM vehicle_owners')->fetchColumn(),
            'drivers' => (int) $this->db->query('SELECT COUNT(*) FROM drivers')->fetchColumn(),
            'vehicles' => (int) $this->db->query('SELECT COUNT(*) FROM vehicles')->fetchColumn(),
            'bookings' => (int) $this->db->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
            'pending_payments' => (int) $this->db->query("SELECT COUNT(*) FROM payments WHERE payment_status='pending'")->fetchColumn(),
        ];

        $years = array_map('intval', $this->db->query("SELECT DISTINCT YEAR(created_at) FROM payments WHERE payment_status='completed' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN));
        if (!in_array($year, $years, true)) $years[] = $year;
        sort($years);

        $monthly = array_fill(1, 12, ['total' => 0.0, 'count' => 0]);
        $statement = $this->db->prepare("SELECT MONTH(created_at) month_number, COALESCE(SUM(amount), 0) total, COUNT(*) payment_count FROM payments WHERE payment_status='completed' AND YEAR(created_at)=:year GROUP BY MONTH(created_at)");
        $statement->execute(['year' => $year]);
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $monthly[(int) $row['month_number']] = ['total' => (float) $row['total'], 'count' => (int) $row['payment_count']];
        }

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyPayments = [];
        foreach ($labels as $index => $label) {
            $monthlyPayments[] = ['label' => $label, 'total' => $monthly[$index + 1]['total'], 'count' => $monthly[$index + 1]['count']];
        }

        $status = $this->db->prepare("SELECT status, COUNT(*) count FROM bookings WHERE YEAR(created_at)=:year GROUP BY status ORDER BY status");
        $status->execute(['year' => $year]);

        return [
            'year' => $year,
            'available_years' => $years,
            'stats' => $stats,
            'monthly_payments' => $monthlyPayments,
            'booking_status' => $status->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
}