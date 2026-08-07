<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Read-only vehicle catalogue queries for authenticated Customers.
 */
class CustomerVehicle
{
    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db;
    }

    public function searchVehicles(array $filters, string $sort, int $limit, int $offset): array
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        $orderBy = $this->sortClause($sort, $filters);

        $sql = "SELECT
                    v.id,
                    v.make,
                    v.model,
                    v.year,
                    v.license_plate,
                    v.vehicle_type,
                    v.transmission,
                    v.fuel_type,
                    v.seating_capacity,
                    v.price_per_day,
                    v.price_with_driver_per_day,
                    v.status,
                    v.verification_status,
                    v.created_at,
                    u.name AS owner_name,
                    COALESCE(document_summary.approved_document_count, 0) AS approved_document_count
                FROM vehicles v
                INNER JOIN vehicle_owners vo ON vo.id = v.owner_id
                INNER JOIN users u ON u.id = vo.user_id
                LEFT JOIN (
                    SELECT vehicle_id, COUNT(*) AS approved_document_count
                    FROM vehicle_documents
                    WHERE verification_status = 'approved'
                    GROUP BY vehicle_id
                ) document_summary ON document_summary.vehicle_id = v.id
                {$where}
                ORDER BY {$orderBy}
                LIMIT :result_limit OFFSET :result_offset";

        $statement = $this->connection()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->bindValue(':result_limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':result_offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countVehicles(array $filters): int
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);

        $sql = "SELECT COUNT(*)
                FROM vehicles v
                INNER JOIN vehicle_owners vo ON vo.id = v.owner_id
                INNER JOIN users u ON u.id = vo.user_id
                {$where}";

        $statement = $this->connection()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function findVisibleVehicleById(int $vehicleId): ?array
    {
        $sql = "SELECT
                    v.id,
                    v.make,
                    v.model,
                    v.year,
                    v.license_plate,
                    v.vehicle_type,
                    v.transmission,
                    v.fuel_type,
                    v.seating_capacity,
                    v.price_per_day,
                    v.price_with_driver_per_day,
                    v.status,
                    v.verification_status,
                    v.created_at,
                    u.name AS owner_name,
                    COALESCE(document_summary.approved_document_count, 0) AS approved_document_count
                FROM vehicles v
                INNER JOIN vehicle_owners vo ON vo.id = v.owner_id
                INNER JOIN users u ON u.id = vo.user_id
                LEFT JOIN (
                    SELECT vehicle_id, COUNT(*) AS approved_document_count
                    FROM vehicle_documents
                    WHERE verification_status = 'approved'
                    GROUP BY vehicle_id
                ) document_summary ON document_summary.vehicle_id = v.id
                WHERE v.id = :vehicle_id
                  AND v.verification_status = 'approved'
                  AND vo.verification_status = 'approved'
                  AND u.status = 'active'
                  AND u.role = 'owner'
                LIMIT 1";

        $statement = $this->connection()->prepare($sql);
        $statement->bindValue(':vehicle_id', $vehicleId, PDO::PARAM_INT);
        $statement->execute();
        $vehicle = $statement->fetch();

        return is_array($vehicle) ? $vehicle : null;
    }

    public function customerVerificationStatus(int $userId): string
    {
        $sql = "SELECT verification_status
                FROM customers
                WHERE user_id = :user_id
                LIMIT 1";

        $statement = $this->connection()->prepare($sql);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();
        $status = $statement->fetchColumn();

        return is_string($status) ? $status : 'pending';
    }

    private function buildWhere(array $filters, array &$params): string
    {
        $conditions = [
            "v.verification_status = 'approved'",
            "vo.verification_status = 'approved'",
            "u.status = 'active'",
            "u.role = 'owner'",
        ];

        if (($filters['availability'] ?? 'available') === 'available') {
            $conditions[] = "v.status = 'available'";
        }

        if (($filters['keyword'] ?? '') !== '') {
            $conditions[] = '(v.make LIKE :keyword_make OR v.model LIKE :keyword_model OR CONCAT(v.make, \' \', v.model) LIKE :keyword_full)';
            $keyword = '%' . $filters['keyword'] . '%';
            $params[':keyword_make'] = $keyword;
            $params[':keyword_model'] = $keyword;
            $params[':keyword_full'] = $keyword;
        }

        foreach (['vehicle_type', 'fuel_type', 'transmission'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $conditions[] = "v.{$field} = :{$field}";
                $params[":{$field}"] = $filters[$field];
            }
        }

        $priceColumn = ($filters['service_type'] ?? '') === 'with_driver'
            ? 'v.price_with_driver_per_day'
            : 'v.price_per_day';

        if (($filters['service_type'] ?? '') === 'self_drive') {
            $conditions[] = 'v.price_per_day > 0';
        } elseif (($filters['service_type'] ?? '') === 'with_driver') {
            $conditions[] = 'v.price_with_driver_per_day IS NOT NULL';
            $conditions[] = 'v.price_with_driver_per_day > 0';
        }

        if (($filters['min_price'] ?? null) !== null) {
            $conditions[] = "{$priceColumn} >= :min_price";
            $params[':min_price'] = (string) $filters['min_price'];
        }

        if (($filters['max_price'] ?? null) !== null) {
            $conditions[] = "{$priceColumn} <= :max_price";
            $params[':max_price'] = (string) $filters['max_price'];
        }

        if (($filters['min_seats'] ?? null) !== null) {
            $conditions[] = 'v.seating_capacity >= :min_seats';
            $params[':min_seats'] = (int) $filters['min_seats'];
        }

        if (($filters['start_date'] ?? '') !== '' && ($filters['end_date'] ?? '') !== '') {
            $conditions[] = "NOT EXISTS (
                SELECT 1
                FROM bookings blocking_booking
                WHERE blocking_booking.vehicle_id = v.id
                  AND blocking_booking.status IN ('pending_payment', 'confirmed', 'ongoing')
                  AND blocking_booking.start_date < :availability_end
                  AND blocking_booking.end_date > :availability_start
            )";
            $params[':availability_start'] = $filters['start_date'] . ' 00:00:00';
            $params[':availability_end'] = $filters['end_date'] . ' 00:00:00';
        }

        return 'WHERE ' . implode("\n AND ", $conditions);
    }

    private function sortClause(string $sort, array $filters): string
    {
        $priceColumn = ($filters['service_type'] ?? '') === 'with_driver'
            ? 'v.price_with_driver_per_day'
            : 'v.price_per_day';
        $sorts = [
            'newest' => 'v.created_at DESC, v.id DESC',
            'price_asc' => "{$priceColumn} ASC, v.id DESC",
            'price_desc' => "{$priceColumn} DESC, v.id DESC",
            'year_desc' => 'v.year DESC, v.id DESC',
        ];

        return $sorts[$sort] ?? $sorts['newest'];
    }

    private function bindValues(PDOStatement $statement, array $params): void
    {
        foreach ($params as $name => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $statement->bindValue($name, $value, $type);
        }
    }

    private function connection(): PDO
    {
        if ($this->db === null) {
            $this->db = Database::getInstance()->getConnection();
        }

        return $this->db;
    }
}
