<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

class CustomerBookingRuleException extends RuntimeException
{
}

/**
 * Customer-owned booking reads and writes.
 *
 * Customer identity is always resolved from the authenticated users.id value.
 */
class CustomerBooking
{
    private const BLOCKING_STATUSES = ['pending_payment', 'confirmed', 'ongoing'];
    private const CANCELLABLE_STATUSES = ['pending_payment', 'confirmed'];
    private const REQUIRED_DRIVER_DOCUMENTS = ['nic', 'driving_license', 'police_report'];

    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db;
    }

    public function getCustomerContext(int $sessionUserId, bool $forUpdate = false): ?array
    {
        $sql = "SELECT
                    u.id AS user_id,
                    u.name,
                    u.email,
                    u.role,
                    u.status AS user_status,
                    c.id AS customer_id,
                    c.verification_status
                FROM users u
                INNER JOIN customers c ON c.user_id = u.id
                WHERE u.id = :user_id
                  AND u.role = 'customer'
                LIMIT 1";

        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->connection()->prepare($sql);
        $statement->bindValue(':user_id', $sessionUserId, PDO::PARAM_INT);
        $statement->execute();
        $customer = $statement->fetch();

        return is_array($customer) ? $customer : null;
    }

    public function getBookableVehicle(int $vehicleId, bool $forUpdate = false): ?array
    {
        $sql = "SELECT
                    v.id,
                    v.owner_id,
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
                    owner_user.name AS owner_name
                FROM vehicles v
                INNER JOIN vehicle_owners vo ON vo.id = v.owner_id
                INNER JOIN users owner_user ON owner_user.id = vo.user_id
                WHERE v.id = :vehicle_id
                  AND v.status = 'available'
                  AND v.verification_status = 'approved'
                  AND v.price_per_day > 0
                  AND vo.verification_status = 'approved'
                  AND owner_user.role = 'owner'
                  AND owner_user.status = 'active'
                LIMIT 1";

        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->connection()->prepare($sql);
        $statement->bindValue(':vehicle_id', $vehicleId, PDO::PARAM_INT);
        $statement->execute();
        $vehicle = $statement->fetch();

        return is_array($vehicle) ? $vehicle : null;
    }

    public function getEligibleDrivers(
        int $ownerId,
        ?string $startDate = null,
        ?string $endDate = null,
        int $excludeBookingId = 0
    ): array {
        return $this->eligibleDriverQuery($ownerId, null, $startDate, $endDate, $excludeBookingId, false);
    }

    public function createForCustomer(
        int $sessionUserId,
        int $vehicleId,
        string $bookingType,
        string $startDate,
        string $endDate,
        int $rentalDays,
        ?int $driverId,
        ?string $deliveryAddress
    ): array {
        $db = $this->connection();

        try {
            $db->beginTransaction();
            $customer = $this->requireEligibleCustomer($sessionUserId, true);
            $vehicle = $this->getBookableVehicle($vehicleId, true);

            if ($vehicle === null) {
                throw new CustomerBookingRuleException('This vehicle is not available for booking.');
            }

            $selectedDriverId = $this->validateServiceAndDriver(
                $vehicle,
                $bookingType,
                $driverId,
                $startDate,
                $endDate,
                0,
                true
            );

            if ($this->hasVehicleOverlap($vehicleId, $startDate, $endDate, 0, true)) {
                throw new CustomerBookingRuleException('The vehicle is already booked for part of the selected period.');
            }

            if (
                $selectedDriverId !== null
                && $this->hasDriverOverlap($selectedDriverId, $startDate, $endDate, 0, true)
            ) {
                throw new CustomerBookingRuleException('The selected Driver is already assigned during part of this period.');
            }

            $rate = $bookingType === 'with_driver'
                ? (string) $vehicle['price_with_driver_per_day']
                : (string) $vehicle['price_per_day'];
            $totalPrice = $this->calculateTotal($rate, $rentalDays);

            $sql = "INSERT INTO bookings (
                        customer_id,
                        vehicle_id,
                        driver_id,
                        booking_type,
                        start_date,
                        end_date,
                        delivery_address,
                        total_price,
                        status,
                        pickup_status
                    ) VALUES (
                        :customer_id,
                        :vehicle_id,
                        :driver_id,
                        :booking_type,
                        :start_date,
                        :end_date,
                        :delivery_address,
                        :total_price,
                        'pending_payment',
                        'pending_pickup'
                    )";
            $statement = $db->prepare($sql);
            $statement->bindValue(':customer_id', (int) $customer['customer_id'], PDO::PARAM_INT);
            $statement->bindValue(':vehicle_id', $vehicleId, PDO::PARAM_INT);
            $statement->bindValue(
                ':driver_id',
                $selectedDriverId,
                $selectedDriverId === null ? PDO::PARAM_NULL : PDO::PARAM_INT
            );
            $statement->bindValue(':booking_type', $bookingType);
            $statement->bindValue(':start_date', $startDate);
            $statement->bindValue(':end_date', $endDate);
            $statement->bindValue(
                ':delivery_address',
                $deliveryAddress,
                $deliveryAddress === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );
            $statement->bindValue(':total_price', $totalPrice);
            $statement->execute();

            $bookingId = (int) $db->lastInsertId();
            $db->commit();

            return ['booking_id' => $bookingId, 'total_price' => $totalPrice];
        } catch (CustomerBookingRuleException $exception) {
            $this->rollBackIfNeeded();
            throw $exception;
        } catch (Throwable $exception) {
            $this->rollBackIfNeeded();
            error_log(sprintf(
                'Customer booking creation error [%s]: %s',
                get_class($exception),
                $exception->getMessage()
            ));
            throw new RuntimeException('The booking could not be saved. Please try again.');
        }
    }

    public function getCustomerBookings(
        int $sessionUserId,
        string $status,
        int $limit,
        int $offset
    ): array {
        $params = [':session_user_id' => $sessionUserId];
        $statusSql = '';
        if ($status !== '') {
            $statusSql = ' AND b.status = :booking_status';
            $params[':booking_status'] = $status;
        }

        $sql = "SELECT
                    b.id,
                    b.booking_type,
                    b.start_date,
                    b.end_date,
                    b.delivery_address,
                    b.total_price,
                    b.status,
                    b.pickup_status,
                    b.created_at,
                    b.updated_at,
                    v.id AS vehicle_id,
                    v.make,
                    v.model,
                    v.year,
                    driver_user.name AS driver_name,
                    TIMESTAMPDIFF(DAY, b.start_date, b.end_date) AS rental_days
                FROM bookings b
                INNER JOIN customers c ON c.id = b.customer_id
                INNER JOIN vehicles v ON v.id = b.vehicle_id
                LEFT JOIN drivers d ON d.id = b.driver_id
                LEFT JOIN users driver_user ON driver_user.id = d.user_id
                WHERE c.user_id = :session_user_id
                {$statusSql}
                ORDER BY b.created_at DESC, b.id DESC
                LIMIT :result_limit OFFSET :result_offset";

        $statement = $this->connection()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->bindValue(':result_limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':result_offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countCustomerBookings(int $sessionUserId, string $status): int
    {
        $params = [':session_user_id' => $sessionUserId];
        $statusSql = '';
        if ($status !== '') {
            $statusSql = ' AND b.status = :booking_status';
            $params[':booking_status'] = $status;
        }

        $sql = "SELECT COUNT(*)
                FROM bookings b
                INNER JOIN customers c ON c.id = b.customer_id
                WHERE c.user_id = :session_user_id
                {$statusSql}";
        $statement = $this->connection()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function findCustomerBooking(
        int $sessionUserId,
        int $bookingId,
        bool $forUpdate = false
    ): ?array {
        $sql = "SELECT
                    b.id,
                    b.customer_id,
                    b.vehicle_id,
                    b.driver_id,
                    b.booking_type,
                    b.start_date,
                    b.end_date,
                    b.delivery_address,
                    b.total_price,
                    b.status,
                    b.pickup_status,
                    b.created_at,
                    b.updated_at,
                    v.owner_id,
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
                    owner_user.name AS owner_name,
                    driver_user.name AS driver_name,
                    TIMESTAMPDIFF(DAY, b.start_date, b.end_date) AS rental_days
                FROM bookings b
                INNER JOIN customers c ON c.id = b.customer_id
                INNER JOIN vehicles v ON v.id = b.vehicle_id
                INNER JOIN vehicle_owners vo ON vo.id = v.owner_id
                INNER JOIN users owner_user ON owner_user.id = vo.user_id
                LEFT JOIN drivers d ON d.id = b.driver_id
                LEFT JOIN users driver_user ON driver_user.id = d.user_id
                WHERE b.id = :booking_id
                  AND c.user_id = :session_user_id
                LIMIT 1";

        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->connection()->prepare($sql);
        $statement->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $statement->bindValue(':session_user_id', $sessionUserId, PDO::PARAM_INT);
        $statement->execute();
        $booking = $statement->fetch();

        return is_array($booking) ? $booking : null;
    }

    public function updateCustomerBooking(
        int $sessionUserId,
        int $bookingId,
        string $bookingType,
        string $startDate,
        string $endDate,
        int $rentalDays,
        ?int $driverId,
        ?string $deliveryAddress,
        string $now
    ): array {
        $db = $this->connection();

        try {
            $db->beginTransaction();
            $this->requireEligibleCustomer($sessionUserId, true);
            $booking = $this->findCustomerBooking($sessionUserId, $bookingId, true);

            if ($booking === null) {
                throw new CustomerBookingRuleException('Booking not found.');
            }
            if ((string) $booking['status'] !== 'pending_payment' || (string) $booking['start_date'] <= $now) {
                throw new CustomerBookingRuleException('This booking can no longer be edited.');
            }

            $vehicleId = (int) $booking['vehicle_id'];
            $vehicle = $this->getBookableVehicle($vehicleId, true);
            if ($vehicle === null) {
                throw new CustomerBookingRuleException('The booked vehicle is no longer eligible for an update.');
            }

            $selectedDriverId = $this->validateServiceAndDriver(
                $vehicle,
                $bookingType,
                $driverId,
                $startDate,
                $endDate,
                $bookingId,
                true
            );

            if ($this->hasVehicleOverlap($vehicleId, $startDate, $endDate, $bookingId, true)) {
                throw new CustomerBookingRuleException('The vehicle is already booked for part of the updated period.');
            }
            if (
                $selectedDriverId !== null
                && $this->hasDriverOverlap($selectedDriverId, $startDate, $endDate, $bookingId, true)
            ) {
                throw new CustomerBookingRuleException('The selected Driver is already assigned during part of the updated period.');
            }

            $rate = $bookingType === 'with_driver'
                ? (string) $vehicle['price_with_driver_per_day']
                : (string) $vehicle['price_per_day'];
            $totalPrice = $this->calculateTotal($rate, $rentalDays);

            $sql = "UPDATE bookings
                    SET booking_type = :booking_type,
                        driver_id = :driver_id,
                        start_date = :start_date,
                        end_date = :end_date,
                        delivery_address = :delivery_address,
                        total_price = :total_price
                    WHERE id = :booking_id
                      AND customer_id = :customer_id
                      AND status = 'pending_payment'
                      AND start_date > :current_time";
            $statement = $db->prepare($sql);
            $statement->bindValue(':booking_type', $bookingType);
            $statement->bindValue(
                ':driver_id',
                $selectedDriverId,
                $selectedDriverId === null ? PDO::PARAM_NULL : PDO::PARAM_INT
            );
            $statement->bindValue(':start_date', $startDate);
            $statement->bindValue(':end_date', $endDate);
            $statement->bindValue(
                ':delivery_address',
                $deliveryAddress,
                $deliveryAddress === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );
            $statement->bindValue(':total_price', $totalPrice);
            $statement->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
            $statement->bindValue(':customer_id', (int) $booking['customer_id'], PDO::PARAM_INT);
            $statement->bindValue(':current_time', $now);
            $statement->execute();

            $db->commit();
            return ['booking_id' => $bookingId, 'total_price' => $totalPrice];
        } catch (CustomerBookingRuleException $exception) {
            $this->rollBackIfNeeded();
            throw $exception;
        } catch (Throwable $exception) {
            $this->rollBackIfNeeded();
            error_log(sprintf(
                'Customer booking update error [%s]: %s',
                get_class($exception),
                $exception->getMessage()
            ));
            throw new RuntimeException('The booking could not be updated. Please try again.');
        }
    }

    public function cancelCustomerBooking(int $sessionUserId, int $bookingId, string $now): void
    {
        $db = $this->connection();

        try {
            $db->beginTransaction();
            $booking = $this->findCustomerBooking($sessionUserId, $bookingId, true);

            if ($booking === null) {
                throw new CustomerBookingRuleException('Booking not found.');
            }
            if (!in_array((string) $booking['status'], self::CANCELLABLE_STATUSES, true)) {
                throw new CustomerBookingRuleException('This booking cannot be cancelled in its current status.');
            }
            if ((string) $booking['start_date'] <= $now) {
                throw new CustomerBookingRuleException('A booking that has already started cannot be cancelled.');
            }

            $sql = "UPDATE bookings
                    SET status = 'cancelled'
                    WHERE id = :booking_id
                      AND customer_id = :customer_id
                      AND status IN ('pending_payment', 'confirmed')
                      AND start_date > :current_time";
            $statement = $db->prepare($sql);
            $statement->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
            $statement->bindValue(':customer_id', (int) $booking['customer_id'], PDO::PARAM_INT);
            $statement->bindValue(':current_time', $now);
            $statement->execute();

            if ($statement->rowCount() !== 1) {
                throw new CustomerBookingRuleException('The booking changed before it could be cancelled.');
            }

            $db->commit();
        } catch (CustomerBookingRuleException $exception) {
            $this->rollBackIfNeeded();
            throw $exception;
        } catch (Throwable $exception) {
            $this->rollBackIfNeeded();
            error_log(sprintf(
                'Customer booking cancellation error [%s]: %s',
                get_class($exception),
                $exception->getMessage()
            ));
            throw new RuntimeException('The booking could not be cancelled. Please try again.');
        }
    }

    public function hasVehicleOverlap(
        int $vehicleId,
        string $startDate,
        string $endDate,
        int $excludeBookingId = 0,
        bool $forUpdate = false
    ): bool {
        return $this->hasBookingOverlap(
            'vehicle_id',
            $vehicleId,
            $startDate,
            $endDate,
            $excludeBookingId,
            $forUpdate
        );
    }

    public function hasDriverOverlap(
        int $driverId,
        string $startDate,
        string $endDate,
        int $excludeBookingId = 0,
        bool $forUpdate = false
    ): bool {
        return $this->hasBookingOverlap(
            'driver_id',
            $driverId,
            $startDate,
            $endDate,
            $excludeBookingId,
            $forUpdate
        );
    }

    private function requireEligibleCustomer(int $sessionUserId, bool $forUpdate): array
    {
        $customer = $this->getCustomerContext($sessionUserId, $forUpdate);
        if ($customer === null) {
            throw new CustomerBookingRuleException('A Customer profile is required before booking.');
        }
        if ((string) $customer['user_status'] !== 'active') {
            throw new CustomerBookingRuleException('Your Customer account is not active.');
        }
        if ((string) $customer['verification_status'] !== 'approved') {
            throw new CustomerBookingRuleException('Your Customer verification must be approved before booking.');
        }

        return $customer;
    }

    private function validateServiceAndDriver(
        array $vehicle,
        string $bookingType,
        ?int $driverId,
        string $startDate,
        string $endDate,
        int $excludeBookingId,
        bool $forUpdate
    ): ?int {
        if ($bookingType === 'self_drive') {
            return null;
        }
        if ($bookingType !== 'with_driver') {
            throw new CustomerBookingRuleException('Select a valid booking type.');
        }
        if ($vehicle['price_with_driver_per_day'] === null || (float) $vehicle['price_with_driver_per_day'] <= 0) {
            throw new CustomerBookingRuleException('This vehicle does not support a with-driver booking.');
        }
        if ($driverId === null || $driverId <= 0) {
            throw new CustomerBookingRuleException('Select an eligible Driver.');
        }

        $driver = $this->eligibleDriverQuery(
            (int) $vehicle['owner_id'],
            $driverId,
            $startDate,
            $endDate,
            $excludeBookingId,
            $forUpdate
        );
        if ($driver === null) {
            throw new CustomerBookingRuleException('The selected Driver is not eligible for this booking period.');
        }

        return $driverId;
    }

    private function eligibleDriverQuery(
        int $ownerId,
        ?int $driverId,
        ?string $startDate,
        ?string $endDate,
        int $excludeBookingId,
        bool $forUpdate
    ): array|null {
        $hasDates = $startDate !== null && $endDate !== null;
        $documentValidUntil = $hasDates
            ? substr($endDate, 0, 10)
            : (new DateTimeImmutable('today', new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
        $params = [
            ':owner_id' => $ownerId,
            ':document_valid_until' => $documentValidUntil,
        ];
        $driverCondition = '';
        if ($driverId !== null) {
            $driverCondition = ' AND d.id = :eligible_driver_id';
            $params[':eligible_driver_id'] = $driverId;
        }

        $dateConditions = '';
        if ($hasDates) {
            $excludeSql = '';
            if ($excludeBookingId > 0) {
                $excludeSql = ' AND assigned_booking.id <> :eligible_exclude_booking_id';
                $params[':eligible_exclude_booking_id'] = $excludeBookingId;
            }
            $dateConditions = "
                AND NOT EXISTS (
                    SELECT 1
                    FROM driver_leaves leave_record
                    WHERE leave_record.driver_id = d.id
                      AND leave_record.status = 'approved'
                      AND leave_record.start_date < DATE(:eligible_leave_end)
                      AND DATE_ADD(leave_record.end_date, INTERVAL 1 DAY) > DATE(:eligible_leave_start)
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM bookings assigned_booking
                    WHERE assigned_booking.driver_id = d.id
                      AND assigned_booking.status IN ('pending_payment', 'confirmed', 'ongoing')
                      AND assigned_booking.start_date < :eligible_booking_end
                      AND assigned_booking.end_date > :eligible_booking_start
                      {$excludeSql}
                )";
            $params[':eligible_leave_start'] = $startDate;
            $params[':eligible_leave_end'] = $endDate;
            $params[':eligible_booking_start'] = $startDate;
            $params[':eligible_booking_end'] = $endDate;
        }

        $requiredDocumentCount = count(self::REQUIRED_DRIVER_DOCUMENTS);
        $sql = "SELECT
                    d.id,
                    d.availability_status,
                    d.rating_avg,
                    driver_user.name
                FROM drivers d
                INNER JOIN users driver_user ON driver_user.id = d.user_id
                INNER JOIN driver_owner_links owner_link ON owner_link.driver_id = d.id
                WHERE owner_link.owner_id = :owner_id
                  AND owner_link.link_status = 'active'
                  AND d.availability_status = 'available'
                  AND driver_user.role = 'driver'
                  AND driver_user.status = 'active'
                  AND (
                      SELECT COUNT(DISTINCT approved_document.document_type)
                      FROM driver_documents approved_document
                      WHERE approved_document.driver_id = d.id
                        AND approved_document.document_type IN ('nic', 'driving_license', 'police_report')
                        AND approved_document.verification_status = 'approved'
                        AND (
                            approved_document.expiry_date IS NULL
                            OR approved_document.expiry_date >= :document_valid_until
                        )
                  ) = {$requiredDocumentCount}
                  {$driverCondition}
                  {$dateConditions}
                ORDER BY driver_user.name ASC, d.id ASC";

        if ($driverId !== null) {
            $sql .= ' LIMIT 1';
        }
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->connection()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->execute();

        if ($driverId !== null) {
            $driver = $statement->fetch();
            return is_array($driver) ? $driver : null;
        }

        return $statement->fetchAll();
    }

    private function hasBookingOverlap(
        string $field,
        int $resourceId,
        string $startDate,
        string $endDate,
        int $excludeBookingId,
        bool $forUpdate
    ): bool {
        if (!in_array($field, ['vehicle_id', 'driver_id'], true)) {
            throw new InvalidArgumentException('Unsupported booking overlap field.');
        }

        $excludeSql = '';
        if ($excludeBookingId > 0) {
            $excludeSql = ' AND id <> :overlap_exclude_booking_id';
        }
        $statusPlaceholders = implode(', ', array_map(
            static fn (int $index): string => ':blocking_status_' . $index,
            array_keys(self::BLOCKING_STATUSES)
        ));
        $sql = "SELECT id
                FROM bookings
                WHERE {$field} = :overlap_resource_id
                  AND status IN ({$statusPlaceholders})
                  AND start_date < :overlap_end
                  AND end_date > :overlap_start
                  {$excludeSql}
                LIMIT 1";
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->connection()->prepare($sql);
        $statement->bindValue(':overlap_resource_id', $resourceId, PDO::PARAM_INT);
        foreach (self::BLOCKING_STATUSES as $index => $status) {
            $statement->bindValue(':blocking_status_' . $index, $status);
        }
        $statement->bindValue(':overlap_end', $endDate);
        $statement->bindValue(':overlap_start', $startDate);
        if ($excludeBookingId > 0) {
            $statement->bindValue(':overlap_exclude_booking_id', $excludeBookingId, PDO::PARAM_INT);
        }
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    private function calculateTotal(string $dailyRate, int $rentalDays): string
    {
        $rateInCents = (int) round(((float) $dailyRate) * 100);
        $totalInCents = $rateInCents * $rentalDays;

        return number_format($totalInCents / 100, 2, '.', '');
    }

    private function bindValues(PDOStatement $statement, array $params): void
    {
        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }

    private function rollBackIfNeeded(): void
    {
        if ($this->connection()->inTransaction()) {
            $this->connection()->rollBack();
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
