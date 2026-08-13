<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Settlement Model
 * Manages vehicle owner payouts and 10% platform commission calculations.
 */
class Settlement {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves settlements list or dynamically generates pending settlements from completed bookings.
     */
    public function getSettlements(): array {
        $sql = "SELECT s.*,
                       uo.name as owner_name, uo.email as owner_email, vo.bank_name, vo.bank_account_no, vo.bank_branch,
                       b.total_price as booking_amount, v.make, v.model, v.license_plate
                FROM `settlements` s
                JOIN `vehicle_owners` vo ON s.owner_id = vo.id
                JOIN `users` uo ON vo.user_id = uo.id
                JOIN `bookings` b ON s.booking_id = b.id
                JOIN `vehicles` v ON b.vehicle_id = v.id
                ORDER BY s.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($records)) {
            // Dynamically query completed bookings without settlements and generate pending view records
            $dynSql = "SELECT b.id as booking_id, b.total_price as gross_amount,
                              (b.total_price * 0.10) as commission_amount,
                              (b.total_price * 0.90) as net_amount,
                              'pending' as status,
                              b.created_at,
                              vo.id as owner_id, uo.name as owner_name, uo.email as owner_email,
                              vo.bank_name, vo.bank_account_no, vo.bank_branch,
                              v.make, v.model, v.license_plate
                       FROM `bookings` b
                       JOIN `vehicles` v ON b.vehicle_id = v.id
                       JOIN `vehicle_owners` vo ON v.owner_id = vo.id
                       JOIN `users` uo ON vo.user_id = uo.id
                       WHERE b.status = 'completed'
                       ORDER BY b.created_at DESC";
            $dynStmt = $this->db->prepare($dynSql);
            $dynStmt->execute();
            return $dynStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $records;
    }

    /**
     * Updates settlement status.
     */
    public function processSettlement(int $ownerId, int $bookingId, float $gross, string $status = 'completed'): bool {
        $commission = $gross * 0.10;
        $net = $gross * 0.90;

        $sql = "INSERT INTO `settlements` (`owner_id`, `booking_id`, `gross_amount`, `commission_amount`, `net_amount`, `settlement_date`, `status`)
                VALUES (:oid, :bid, :gross, :comm, :net, CURDATE(), :status)
                ON DUPLICATE KEY UPDATE `status` = :status, `settlement_date` = CURDATE()";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'oid' => $ownerId,
            'bid' => $bookingId,
            'gross' => $gross,
            'comm' => $commission,
            'net' => $net,
            'status' => $status
        ]);
    }
}
