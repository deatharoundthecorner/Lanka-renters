<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Database access for the remaining Customer workspace features.
 * Every record query starts from the authenticated users.id value and joins
 * through customers so request data can never choose the Customer identity.
 */
final class CustomerPortal
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    public function getContext(int $sessionUserId, bool $forUpdate = false): ?array
    {
        $sql = "SELECT u.id AS user_id, u.name, u.email, u.phone, u.status AS user_status,
                       c.id AS customer_id, c.nic_number, c.driving_license_number,
                       c.verification_status
                FROM users u
                JOIN customers c ON c.user_id = u.id
                WHERE u.id = :user_id AND u.role = 'customer'
                LIMIT 1" . ($forUpdate ? ' FOR UPDATE' : '');
        $statement = $this->db->prepare($sql);
        $statement->execute(['user_id' => $sessionUserId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function getDashboard(int $sessionUserId): array
    {
        $context = $this->getContext($sessionUserId);
        if ($context === null) {
            throw new RuntimeException('Customer profile was not found.');
        }

        $countsStatement = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'ongoing') AS active_count,
                    SUM(status = 'pending_payment') AS pending_count,
                    SUM(status = 'completed') AS completed_count
             FROM bookings WHERE customer_id = :customer_id"
        );
        $countsStatement->execute(['customer_id' => $context['customer_id']]);
        $counts = $countsStatement->fetch(PDO::FETCH_ASSOC) ?: [];

        $recentStatement = $this->db->prepare(
            "SELECT b.id, b.status, b.booking_type, b.start_date, b.end_date, b.total_price,
                    v.make, v.model, v.license_plate
             FROM bookings b
             JOIN vehicles v ON v.id = b.vehicle_id
             WHERE b.customer_id = :customer_id
             ORDER BY b.created_at DESC, b.id DESC LIMIT 1"
        );
        $recentStatement->execute(['customer_id' => $context['customer_id']]);
        $recentBooking = $recentStatement->fetch(PDO::FETCH_ASSOC);

        $paymentStatement = $this->db->prepare(
            "SELECT p.id, p.booking_id, p.payment_status, p.amount, p.created_at
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             WHERE b.customer_id = :customer_id
             ORDER BY p.created_at DESC, p.id DESC LIMIT 1"
        );
        $paymentStatement->execute(['customer_id' => $context['customer_id']]);
        $latestPayment = $paymentStatement->fetch(PDO::FETCH_ASSOC);

        $notificationStatement = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0'
        );
        $notificationStatement->execute(['user_id' => $sessionUserId]);

        return [
            'context' => $context,
            'counts' => [
                'total' => (int) ($counts['total'] ?? 0),
                'active' => (int) ($counts['active_count'] ?? 0),
                'pending' => (int) ($counts['pending_count'] ?? 0),
                'completed' => (int) ($counts['completed_count'] ?? 0),
            ],
            'recent_booking' => is_array($recentBooking) ? $recentBooking : null,
            'latest_payment' => is_array($latestPayment) ? $latestPayment : null,
            'unread_notifications' => (int) $notificationStatement->fetchColumn(),
        ];
    }

    public function updateProfile(int $sessionUserId, array $values): void
    {
        $this->db->beginTransaction();
        try {
            $context = $this->getContext($sessionUserId, true);
            if ($context === null || $context['user_status'] !== 'active') {
                throw new RuntimeException('Active Customer profile was not found.');
            }

            $userStatement = $this->db->prepare(
                'UPDATE users SET name = :name, phone = :phone WHERE id = :user_id AND role = \'customer\''
            );
            $userStatement->execute([
                'name' => $values['name'],
                'phone' => $values['phone'],
                'user_id' => $sessionUserId,
            ]);

            $customerStatement = $this->db->prepare(
                'UPDATE customers
                 SET nic_number = :nic_number, driving_license_number = :license_number
                 WHERE id = :customer_id AND user_id = :user_id'
            );
            $customerStatement->execute([
                'nic_number' => $values['nic_number'] !== '' ? $values['nic_number'] : null,
                'license_number' => $values['driving_license_number'] !== '' ? $values['driving_license_number'] : null,
                'customer_id' => $context['customer_id'],
                'user_id' => $sessionUserId,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function countNotifications(int $sessionUserId, string $filter): int
    {
        $sql = 'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id';
        if ($filter === 'unread') {
            $sql .= ' AND is_read = 0';
        }
        $statement = $this->db->prepare($sql);
        $statement->execute(['user_id' => $sessionUserId]);
        return (int) $statement->fetchColumn();
    }

    public function getNotifications(int $sessionUserId, string $filter, int $limit, int $offset): array
    {
        $sql = 'SELECT id, title, message, is_read, created_at, notification_type, related_id
                FROM notifications WHERE user_id = :user_id';
        if ($filter === 'unread') {
            $sql .= ' AND is_read = 0';
        }
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset';
        $statement = $this->db->prepare($sql);
        $statement->bindValue(':user_id', $sessionUserId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markNotificationRead(int $sessionUserId, int $notificationId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute(['id' => $notificationId, 'user_id' => $sessionUserId]);
        return $statement->rowCount() > 0;
    }

    public function markAllNotificationsRead(int $sessionUserId): int
    {
        $statement = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0'
        );
        $statement->execute(['user_id' => $sessionUserId]);
        return $statement->rowCount();
    }

    public function relatedCustomerRoute(int $sessionUserId, string $type, ?int $relatedId): ?string
    {
        if ($relatedId === null || $relatedId < 1) {
            return null;
        }

        $queries = [
            'booking' => [
                "SELECT b.id FROM bookings b JOIN customers c ON c.id = b.customer_id
                 WHERE b.id = :id AND c.user_id = :user_id",
                'bookings/details.php?id=',
            ],
            'payment' => [
                "SELECT p.id FROM payments p JOIN bookings b ON b.id = p.booking_id
                 JOIN customers c ON c.id = b.customer_id
                 WHERE p.id = :id AND c.user_id = :user_id",
                'payments/details.php?id=',
            ],
            'incident' => [
                "SELECT i.id FROM incidents i JOIN bookings b ON b.id = i.booking_id
                 JOIN customers c ON c.id = b.customer_id
                 WHERE i.id = :id AND c.user_id = :user_id",
                'incidents/details.php?id=',
            ],
            'review' => [
                "SELECT r.id FROM ratings_reviews r JOIN customers c ON c.id = r.customer_id
                 WHERE r.id = :id AND c.user_id = :user_id",
                'reviews/details.php?id=',
            ],
        ];

        if (!isset($queries[$type])) {
            return null;
        }
        [$sql, $prefix] = $queries[$type];
        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $relatedId, 'user_id' => $sessionUserId]);
        return $statement->fetchColumn() !== false ? $prefix . $relatedId : null;
    }

    public function getChatRooms(int $sessionUserId): array
    {
        $statement = $this->db->prepare(
            "SELECT cr.id AS room_id, b.id AS booking_id, b.status AS booking_status,
                    v.make, v.model,
                    (SELECT cm.message_text FROM chat_messages cm WHERE cm.room_id = cr.id
                     ORDER BY cm.sent_at DESC, cm.id DESC LIMIT 1) AS last_message,
                    (SELECT cm.sent_at FROM chat_messages cm WHERE cm.room_id = cr.id
                     ORDER BY cm.sent_at DESC, cm.id DESC LIMIT 1) AS last_message_at
             FROM chat_rooms cr
             JOIN bookings b ON b.id = cr.booking_id
             JOIN customers c ON c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             JOIN chat_participants cp ON cp.room_id = cr.id AND cp.user_id = :participant_id
             WHERE c.user_id = :customer_user_id
             ORDER BY COALESCE(last_message_at, cr.created_at) DESC, cr.id DESC"
        );
        $statement->execute([
            'participant_id' => $sessionUserId,
            'customer_user_id' => $sessionUserId,
        ]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getChatEligibleBookings(int $sessionUserId): array
    {
        $statement = $this->db->prepare(
            "SELECT b.id, b.status, b.booking_type, v.make, v.model
             FROM bookings b
             JOIN customers c ON c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             WHERE c.user_id = :user_id
               AND b.status IN ('pending_payment', 'confirmed', 'ongoing')
             ORDER BY b.created_at DESC, b.id DESC"
        );
        $statement->execute(['user_id' => $sessionUserId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ensureChatRoom(int $sessionUserId, int $bookingId): int
    {
        $this->db->beginTransaction();
        try {
            $bookingStatement = $this->db->prepare(
                "SELECT b.id, b.driver_id, c.user_id AS customer_user_id,
                        COALESCE(du.id, ou.id) AS contact_user_id
                 FROM bookings b
                 JOIN customers c ON c.id = b.customer_id
                 JOIN vehicles v ON v.id = b.vehicle_id
                 JOIN vehicle_owners vo ON vo.id = v.owner_id
                 JOIN users ou ON ou.id = vo.user_id
                 LEFT JOIN drivers d ON d.id = b.driver_id
                 LEFT JOIN users du ON du.id = d.user_id
                 WHERE b.id = :booking_id AND c.user_id = :user_id
                   AND b.status IN ('pending_payment', 'confirmed', 'ongoing')
                 LIMIT 1 FOR UPDATE"
            );
            $bookingStatement->execute(['booking_id' => $bookingId, 'user_id' => $sessionUserId]);
            $booking = $bookingStatement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($booking) || (int) $booking['customer_user_id'] !== $sessionUserId) {
                throw new DomainException('The selected booking is not available for chat.');
            }

            $roomStatement = $this->db->prepare('SELECT id FROM chat_rooms WHERE booking_id = :booking_id LIMIT 1');
            $roomStatement->execute(['booking_id' => $bookingId]);
            $roomId = $roomStatement->fetchColumn();
            if ($roomId === false) {
                $insertRoom = $this->db->prepare('INSERT INTO chat_rooms (booking_id) VALUES (:booking_id)');
                $insertRoom->execute(['booking_id' => $bookingId]);
                $roomId = (int) $this->db->lastInsertId();
            }

            $participantStatement = $this->db->prepare(
                'INSERT IGNORE INTO chat_participants (room_id, user_id) VALUES (:room_id, :user_id)'
            );
            foreach ([$sessionUserId, (int) $booking['contact_user_id']] as $participantId) {
                if ($participantId > 0) {
                    $participantStatement->execute(['room_id' => $roomId, 'user_id' => $participantId]);
                }
            }

            $this->db->commit();
            return (int) $roomId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function findOwnedChatRoom(int $sessionUserId, int $roomId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT cr.id AS room_id, b.id AS booking_id, b.status AS booking_status,
                    v.make, v.model, v.license_plate
             FROM chat_rooms cr
             JOIN bookings b ON b.id = cr.booking_id
             JOIN customers c ON c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             JOIN chat_participants cp ON cp.room_id = cr.id AND cp.user_id = :participant_id
             WHERE cr.id = :room_id AND c.user_id = :customer_user_id LIMIT 1"
        );
        $statement->execute([
            'participant_id' => $sessionUserId,
            'room_id' => $roomId,
            'customer_user_id' => $sessionUserId,
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function getChatMessages(int $sessionUserId, int $roomId): array
    {
        if ($this->findOwnedChatRoom($sessionUserId, $roomId) === null) {
            return [];
        }
        $statement = $this->db->prepare(
            'SELECT cm.id, cm.sender_id, cm.message_text, cm.sent_at, u.name AS sender_name, u.role AS sender_role
             FROM chat_messages cm JOIN users u ON u.id = cm.sender_id
             WHERE cm.room_id = :room_id ORDER BY cm.sent_at ASC, cm.id ASC'
        );
        $statement->execute(['room_id' => $roomId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sendChatMessage(int $sessionUserId, int $roomId, string $message): void
    {
        $room = $this->findOwnedChatRoom($sessionUserId, $roomId);
        if ($room === null || !in_array($room['booking_status'], ['pending_payment', 'confirmed', 'ongoing'], true)) {
            throw new DomainException('This conversation is not available.');
        }
        $statement = $this->db->prepare(
            'INSERT INTO chat_messages (room_id, sender_id, message_text)
             VALUES (:room_id, :sender_id, :message_text)'
        );
        $statement->execute(['room_id' => $roomId, 'sender_id' => $sessionUserId, 'message_text' => $message]);
    }

    public function getIncidentEligibleBookings(int $sessionUserId): array
    {
        return $this->getOwnedBookingsByStatuses($sessionUserId, ['ongoing']);
    }

    public function countIncidents(int $sessionUserId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM incidents i JOIN bookings b ON b.id = i.booking_id
             JOIN customers c ON c.id = b.customer_id
             WHERE c.user_id = :user_id AND i.reported_by = :reported_by'
        );
        $statement->execute(['user_id' => $sessionUserId, 'reported_by' => $sessionUserId]);
        return (int) $statement->fetchColumn();
    }

    public function getIncidents(int $sessionUserId, int $limit, int $offset): array
    {
        $statement = $this->db->prepare(
            'SELECT i.id, i.booking_id, i.description, i.incident_date, i.severity, i.status, i.created_at,
                    v.make, v.model, v.license_plate
             FROM incidents i
             JOIN bookings b ON b.id = i.booking_id
             JOIN customers c ON c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             WHERE c.user_id = :user_id AND i.reported_by = :reported_by
             ORDER BY i.created_at DESC, i.id DESC LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':user_id', $sessionUserId, PDO::PARAM_INT);
        $statement->bindValue(':reported_by', $sessionUserId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createIncident(int $sessionUserId, int $bookingId, string $description, string $dateTime, string $severity): int
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                "SELECT b.id FROM bookings b JOIN customers c ON c.id = b.customer_id
                 WHERE b.id = :booking_id AND c.user_id = :user_id AND b.status = 'ongoing'
                 LIMIT 1 FOR UPDATE"
            );
            $statement->execute(['booking_id' => $bookingId, 'user_id' => $sessionUserId]);
            if ($statement->fetchColumn() === false) {
                throw new DomainException('The selected booking is not eligible for incident reporting.');
            }
            $insert = $this->db->prepare(
                "INSERT INTO incidents
                 (booking_id, reported_by, description, incident_date, severity, status)
                 VALUES (:booking_id, :reported_by, :description, :incident_date, :severity, 'reported')"
            );
            $insert->execute([
                'booking_id' => $bookingId,
                'reported_by' => $sessionUserId,
                'description' => $description,
                'incident_date' => $dateTime,
                'severity' => $severity,
            ]);
            $incidentId = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $incidentId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function findIncident(int $sessionUserId, int $incidentId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT i.id, i.booking_id, i.description, i.incident_date, i.severity, i.status,
                    i.created_at, i.updated_at, v.make, v.model, v.license_plate,
                    b.start_date, b.end_date, b.status AS booking_status
             FROM incidents i JOIN bookings b ON b.id = i.booking_id
             JOIN customers c ON c.id = b.customer_id JOIN vehicles v ON v.id = b.vehicle_id
             WHERE i.id = :incident_id AND c.user_id = :user_id AND i.reported_by = :reported_by LIMIT 1'
        );
        $statement->execute([
            'incident_id' => $incidentId,
            'user_id' => $sessionUserId,
            'reported_by' => $sessionUserId,
        ]);
        $incident = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($incident)) {
            return null;
        }
        $replacementStatement = $this->db->prepare(
            'SELECT rr.id, rr.status, rr.reason, rr.admin_remarks, rr.created_at,
                    rv.make AS replacement_make, rv.model AS replacement_model,
                    rv.license_plate AS replacement_license_plate
             FROM replacement_requests rr
             LEFT JOIN vehicles rv ON rv.id = rr.replacement_vehicle_id
             WHERE rr.incident_id = :incident_id AND rr.booking_id = :booking_id
             ORDER BY rr.created_at DESC, rr.id DESC LIMIT 1'
        );
        $replacementStatement->execute(['incident_id' => $incidentId, 'booking_id' => $incident['booking_id']]);
        $replacement = $replacementStatement->fetch(PDO::FETCH_ASSOC);
        $incident['replacement'] = is_array($replacement) ? $replacement : null;
        return $incident;
    }

    public function getInspectionBookings(int $sessionUserId): array
    {
        $statement = $this->db->prepare(
            'SELECT b.id AS booking_id, b.status AS booking_status, v.make, v.model, v.license_plate,
                    COUNT(vi.id) AS inspection_count, MAX(vi.inspection_date) AS latest_inspection
             FROM bookings b JOIN customers c ON c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             LEFT JOIN vehicle_inspections vi ON vi.booking_id = b.id
             WHERE c.user_id = :user_id
             GROUP BY b.id, b.status, v.make, v.model, v.license_plate
             ORDER BY b.created_at DESC, b.id DESC'
        );
        $statement->execute(['user_id' => $sessionUserId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookingInspections(int $sessionUserId, int $bookingId): ?array
    {
        $bookingStatement = $this->db->prepare(
            'SELECT b.id, b.status, b.start_date, b.end_date, v.make, v.model, v.license_plate
             FROM bookings b JOIN customers c ON c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             WHERE b.id = :booking_id AND c.user_id = :user_id LIMIT 1'
        );
        $bookingStatement->execute(['booking_id' => $bookingId, 'user_id' => $sessionUserId]);
        $booking = $bookingStatement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($booking)) {
            return null;
        }
        $inspectionStatement = $this->db->prepare(
            'SELECT id, inspection_type, odometer_reading, exterior_condition, interior_condition,
                    fuel_level, status, comments, inspection_date
             FROM vehicle_inspections WHERE booking_id = :booking_id
             ORDER BY inspection_date DESC, id DESC'
        );
        $inspectionStatement->execute(['booking_id' => $bookingId]);
        return ['booking' => $booking, 'inspections' => $inspectionStatement->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function countReviews(int $sessionUserId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM ratings_reviews r JOIN customers c ON c.id = r.customer_id
             WHERE c.user_id = :user_id'
        );
        $statement->execute(['user_id' => $sessionUserId]);
        return (int) $statement->fetchColumn();
    }

    public function getReviews(int $sessionUserId, int $limit, int $offset): array
    {
        $statement = $this->db->prepare(
            'SELECT r.id, r.booking_id, r.driver_rating, r.vehicle_rating, r.review_text, r.created_at,
                    v.make, v.model, v.license_plate, u.name AS driver_name
             FROM ratings_reviews r JOIN customers c ON c.id = r.customer_id
             JOIN bookings b ON b.id = r.booking_id JOIN vehicles v ON v.id = b.vehicle_id
             LEFT JOIN drivers d ON d.id = r.driver_id LEFT JOIN users u ON u.id = d.user_id
             WHERE c.user_id = :user_id ORDER BY r.created_at DESC, r.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':user_id', $sessionUserId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewEligibleBookings(int $sessionUserId): array
    {
        $statement = $this->db->prepare(
            "SELECT b.id, b.driver_id, b.booking_type, v.id AS vehicle_id, v.make, v.model, v.license_plate
             FROM bookings b JOIN customers c ON c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             LEFT JOIN ratings_reviews r ON r.booking_id = b.id AND r.customer_id = c.id
             WHERE c.user_id = :user_id AND b.status = 'completed' AND r.id IS NULL
             ORDER BY b.end_date DESC, b.id DESC"
        );
        $statement->execute(['user_id' => $sessionUserId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createReview(
        int $sessionUserId,
        int $bookingId,
        int $vehicleRating,
        ?int $driverRating,
        string $reviewText
    ): int {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                "SELECT b.id, b.vehicle_id, b.driver_id, c.id AS customer_id
                 FROM bookings b JOIN customers c ON c.id = b.customer_id
                 WHERE b.id = :booking_id AND c.user_id = :user_id AND b.status = 'completed'
                 LIMIT 1 FOR UPDATE"
            );
            $statement->execute(['booking_id' => $bookingId, 'user_id' => $sessionUserId]);
            $booking = $statement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($booking)) {
                throw new DomainException('The selected booking is not eligible for a review.');
            }
            $duplicate = $this->db->prepare(
                'SELECT id FROM ratings_reviews WHERE booking_id = :booking_id AND customer_id = :customer_id LIMIT 1'
            );
            $duplicate->execute(['booking_id' => $bookingId, 'customer_id' => $booking['customer_id']]);
            if ($duplicate->fetchColumn() !== false) {
                throw new DomainException('A review already exists for this booking.');
            }
            if ($booking['driver_id'] === null) {
                $driverRating = null;
            }
            $insert = $this->db->prepare(
                'INSERT INTO ratings_reviews
                 (booking_id, customer_id, driver_id, vehicle_id, driver_rating, vehicle_rating, review_text)
                 VALUES (:booking_id, :customer_id, :driver_id, :vehicle_id, :driver_rating, :vehicle_rating, :review_text)'
            );
            $insert->execute([
                'booking_id' => $bookingId,
                'customer_id' => $booking['customer_id'],
                'driver_id' => $booking['driver_id'],
                'vehicle_id' => $booking['vehicle_id'],
                'driver_rating' => $driverRating,
                'vehicle_rating' => $vehicleRating,
                'review_text' => $reviewText !== '' ? $reviewText : null,
            ]);
            $reviewId = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $reviewId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function findReview(int $sessionUserId, int $reviewId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT r.id, r.booking_id, r.driver_rating, r.vehicle_rating, r.review_text, r.created_at,
                    v.make, v.model, v.license_plate, u.name AS driver_name
             FROM ratings_reviews r JOIN customers c ON c.id = r.customer_id
             JOIN bookings b ON b.id = r.booking_id JOIN vehicles v ON v.id = b.vehicle_id
             LEFT JOIN drivers d ON d.id = r.driver_id LEFT JOIN users u ON u.id = d.user_id
             WHERE r.id = :review_id AND c.user_id = :user_id LIMIT 1'
        );
        $statement->execute(['review_id' => $reviewId, 'user_id' => $sessionUserId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function getDriverChangeEligibleBookings(int $sessionUserId): array
    {
        return $this->getOwnedBookingsByStatuses($sessionUserId, ['confirmed', 'ongoing'], true);
    }

    public function getReturnEligibleBookings(int $sessionUserId): array
    {
        return $this->getOwnedBookingsByStatuses($sessionUserId, ['ongoing']);
    }

    public function findOwnedBooking(int $sessionUserId, int $bookingId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT b.id, b.status, b.booking_type, b.start_date, b.end_date, b.driver_id,
                    b.pickup_status, v.make, v.model, v.license_plate
             FROM bookings b JOIN customers c ON c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             WHERE b.id = :booking_id AND c.user_id = :user_id LIMIT 1'
        );
        $statement->execute(['booking_id' => $bookingId, 'user_id' => $sessionUserId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    private function getOwnedBookingsByStatuses(int $sessionUserId, array $statuses, bool $requiresDriver = false): array
    {
        if ($statuses === []) {
            return [];
        }
        $placeholders = [];
        $parameters = ['user_id' => $sessionUserId];
        foreach (array_values($statuses) as $index => $status) {
            $key = 'status_' . $index;
            $placeholders[] = ':' . $key;
            $parameters[$key] = $status;
        }
        $sql = 'SELECT b.id, b.status, b.booking_type, b.start_date, b.end_date, b.driver_id,
                       v.make, v.model, v.license_plate
                FROM bookings b JOIN customers c ON c.id = b.customer_id
                JOIN vehicles v ON v.id = b.vehicle_id
                WHERE c.user_id = :user_id AND b.status IN (' . implode(', ', $placeholders) . ')';
        if ($requiresDriver) {
            $sql .= " AND b.booking_type = 'with_driver' AND b.driver_id IS NOT NULL";
        }
        $sql .= ' ORDER BY b.start_date DESC, b.id DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
