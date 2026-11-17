<?php

require_once dirname(__DIR__) . '/helpers/Database.php';
require_once dirname(__DIR__) . '/models/Chat.php';

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
                       c.id AS customer_id, c.nic_number, c.driving_license_number, c.district, c.address,
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

    public function updateProfile(int $sessionUserId, array $values, ?array $guarantorValues = null): void
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
                 SET nic_number = :nic_number, driving_license_number = :license_number,
                     district = :district, address = :address
                 WHERE id = :customer_id AND user_id = :user_id'
            );
            $customerStatement->execute([
                'nic_number' => $values['nic_number'] !== '' ? $values['nic_number'] : null,
                'license_number' => $values['driving_license_number'] !== '' ? $values['driving_license_number'] : null,
                'district' => $values['district'] !== '' ? $values['district'] : null,
                'address' => $values['address'] !== '' ? $values['address'] : null,
                'customer_id' => $context['customer_id'],
                'user_id' => $sessionUserId,
            ]);

            if ($guarantorValues !== null) {
                // The parent Customer row is locked above, so two simultaneous profile
                // submissions for the same Customer cannot both insert a guarantor.
                $guarantorExists = $this->db->prepare(
                    'SELECT id FROM customer_guarantors WHERE customer_id = :customer_id LIMIT 1'
                );
                $guarantorExists->execute(['customer_id' => $context['customer_id']]);

                if ($guarantorExists->fetchColumn() !== false) {
                    $guarantorStatement = $this->db->prepare(
                        'UPDATE customer_guarantors
                         SET full_name = :full_name, nic = :nic, phone = :phone, relationship = :relationship,
                             verification_status = \'pending\', rejection_reason = NULL
                         WHERE customer_id = :customer_id'
                    );
                } else {
                    $guarantorStatement = $this->db->prepare(
                        'INSERT INTO customer_guarantors
                            (customer_id, full_name, nic, phone, relationship, verification_status)
                         VALUES (:customer_id, :full_name, :nic, :phone, :relationship, \'pending\')'
                    );
                }
                $guarantorStatement->execute([
                    'customer_id' => $context['customer_id'],
                    'full_name' => $guarantorValues['full_name'],
                    'nic' => $guarantorValues['nic'],
                    'phone' => $guarantorValues['phone'],
                    'relationship' => $guarantorValues['relationship'],
                ]);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    /** Returns the authenticated Customer's guarantor only. */
    public function getGuarantor(int $sessionUserId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT g.full_name, g.nic, g.phone, g.relationship,
                    g.verification_status, g.rejection_reason
             FROM customer_guarantors g
             JOIN customers c ON c.id = g.customer_id
             JOIN users u ON u.id = c.user_id
             WHERE u.id = :user_id AND u.role = 'customer'
             LIMIT 1"
        );
        $statement->execute(['user_id' => $sessionUserId]);
        $guarantor = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($guarantor) ? $guarantor : null;
    }

    /** Checks the unique guarantor NIC rule without trusting a posted customer ID. */
    public function guarantorNicIsUsedByAnotherCustomer(int $sessionUserId, string $nic): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1
             FROM customer_guarantors g
             JOIN customers c ON c.id = g.customer_id
             WHERE g.nic = :nic AND c.user_id <> :user_id
             LIMIT 1"
        );
        $statement->execute(['nic' => $nic, 'user_id' => $sessionUserId]);

        return $statement->fetchColumn() !== false;
    }

    /** Returns the authenticated Customer's latest identity-document records by type. */
    public function getVerificationDocuments(int $sessionUserId): array
    {
        $statement = $this->db->prepare(
            "SELECT d.document_type, d.original_name, d.verification_status, d.rejection_reason, d.updated_at
             FROM customer_verification_documents d
             JOIN customers c ON c.id = d.customer_id
             JOIN users u ON u.id = c.user_id
             WHERE u.id = :user_id AND u.role = 'customer'
             ORDER BY d.updated_at DESC, d.id DESC"
        );
        $statement->execute(['user_id' => $sessionUserId]);

        $documents = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $document) {
            $type = (string) ($document['document_type'] ?? '');
            if ($type !== '' && !isset($documents[$type])) {
                $documents[$type] = $document;
            }
        }

        return $documents;
    }

    /**
     * Replaces one document for the authenticated Customer without accepting a
     * Customer or guarantor id from the request.
     */
    public function upsertVerificationDocument(
        int $sessionUserId,
        string $documentType,
        string $originalName,
        string $filePath
    ): void {
        if (!in_array($documentType, ['nic', 'driving_license', 'guarantor_nic'], true)) {
            throw new InvalidArgumentException('Unsupported verification document.');
        }

        $this->db->beginTransaction();
        try {
            $context = $this->getContext($sessionUserId, true);
            if ($context === null || $context['user_status'] !== 'active') {
                throw new RuntimeException('Active Customer profile was not found.');
            }

            $guarantorId = null;
            if ($documentType === 'guarantor_nic') {
                $guarantorStatement = $this->db->prepare(
                    'SELECT id FROM customer_guarantors WHERE customer_id = :customer_id LIMIT 1 FOR UPDATE'
                );
                $guarantorStatement->execute(['customer_id' => $context['customer_id']]);
                $guarantorId = $guarantorStatement->fetchColumn();
                if ($guarantorId === false) {
                    throw new DomainException('Save guarantor details before adding the guarantor NIC document.');
                }
            }

            $documentStatement = $this->db->prepare(
                "INSERT INTO customer_verification_documents
                    (customer_id, guarantor_id, document_type, original_name, file_path, verification_status, rejection_reason)
                 VALUES
                    (:customer_id, :guarantor_id, :document_type, :original_name, :file_path, 'pending', NULL)
                 ON DUPLICATE KEY UPDATE
                    guarantor_id = VALUES(guarantor_id),
                    original_name = VALUES(original_name),
                    file_path = VALUES(file_path),
                    verification_status = 'pending',
                    rejection_reason = NULL"
            );
            $documentStatement->bindValue(':customer_id', (int) $context['customer_id'], PDO::PARAM_INT);
            $documentStatement->bindValue(
                ':guarantor_id',
                $guarantorId === null ? null : (int) $guarantorId,
                $guarantorId === null ? PDO::PARAM_NULL : PDO::PARAM_INT
            );
            $documentStatement->bindValue(':document_type', $documentType);
            $documentStatement->bindValue(':original_name', $originalName);
            $documentStatement->bindValue(':file_path', $filePath);
            $documentStatement->execute();

            if ($documentType === 'guarantor_nic') {
                $statusStatement = $this->db->prepare(
                    "UPDATE customer_guarantors
                     SET verification_status = 'pending', rejection_reason = NULL
                     WHERE id = :guarantor_id AND customer_id = :customer_id"
                );
                $statusStatement->execute([
                    'guarantor_id' => (int) $guarantorId,
                    'customer_id' => $context['customer_id'],
                ]);
            } else {
                $statusStatement = $this->db->prepare(
                    "UPDATE customers SET verification_status = 'pending' WHERE id = :customer_id"
                );
                $statusStatement->execute(['customer_id' => $context['customer_id']]);
            }

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
                $insertRoom = $this->db->prepare(
                    'INSERT INTO chat_rooms (booking_id) VALUES (:booking_id)
                     ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
                );
                $insertRoom->execute(['booking_id' => $bookingId]);
                $roomId = (int) $this->db->lastInsertId();
            }

            (new Chat())->ensureBookingParticipants($roomId, $bookingId);

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
            'SELECT i.id, i.booking_id, i.incident_type, i.district, i.vehicle_body_category, i.google_maps_url,
                    i.description, i.incident_date, i.severity, i.status, i.created_at,
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

    public function createDetailedIncident(int $userId, int $bookingId, array $data, array $photos, ?string $policePath): int
    {
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare("SELECT b.id,v.vehicle_type,b.start_date,b.end_date FROM bookings b JOIN customers c ON c.id=b.customer_id JOIN vehicles v ON v.id=b.vehicle_id WHERE b.id=:id AND c.user_id=:u AND b.status IN ('confirmed','ongoing') FOR UPDATE");
            $q->execute(['id'=>$bookingId,'u'=>$userId]); $booking=$q->fetch(PDO::FETCH_ASSOC);
            if(!is_array($booking) || $data['incident_date'] < $booking['start_date'] || $data['incident_date'] > $booking['end_date']) throw new DomainException('Choose an eligible booking and an incident time within its rental period.');
            $category=['suv'=>'SUV','van'=>'Van','lorry'=>'Pickup','car'=>'Car','motorbike'=>'Other'][$booking['vehicle_type']] ?? 'Other';
            $dupe=$this->db->prepare('SELECT id FROM incidents WHERE booking_id=:b AND incident_type=:t AND incident_date=:d AND description=:x LIMIT 1 FOR UPDATE');
            $dupe->execute(['b'=>$bookingId,'t'=>$data['incident_type'],'d'=>$data['incident_date'],'x'=>$data['description']]); if($dupe->fetchColumn()!==false) throw new DomainException('This incident report was already submitted.');
            $i=$this->db->prepare("INSERT INTO incidents (booking_id,reported_by,incident_type,description,incident_date,district,vehicle_body_category,google_maps_url,police_report_path,severity,status) VALUES (:b,:u,:t,:x,:d,:district,:category,:maps,:police,'moderate','reported')");
            $i->execute(['b'=>$bookingId,'u'=>$userId,'t'=>$data['incident_type'],'x'=>$data['description'],'d'=>$data['incident_date'],'district'=>$data['district'],'category'=>$category,'maps'=>$data['maps_url']?:null,'police'=>$policePath]); $id=(int)$this->db->lastInsertId();
            $p=$this->db->prepare('INSERT INTO incident_photos (incident_id,photo_path) VALUES (:id,:path)'); foreach($photos as $path){$p->execute(['id'=>$id,'path'=>$path]);}
            $this->db->commit(); return $id;
        } catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function findIncident(int $sessionUserId, int $incidentId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT i.id, i.booking_id, i.incident_type, i.district, i.vehicle_body_category, i.google_maps_url, i.police_report_path, i.resolution_preference, i.description, i.incident_date, i.severity, i.status,
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
            'SELECT rr.id, rr.status, rr.reason, rr.admin_remarks, rr.created_at, rr.replacement_vehicle_id,
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

    public function setIncidentResolutionPreference(int $userId,int $incidentId,string $preference): void {
        $q=$this->db->prepare("UPDATE incidents i JOIN bookings b ON b.id=i.booking_id JOIN customers c ON c.id=b.customer_id SET i.resolution_preference=:p WHERE i.id=:id AND i.reported_by=:u AND c.user_id=:cu AND i.status IN ('reported','investigating')");
        $q->execute(['p'=>$preference,'id'=>$incidentId,'u'=>$userId,'cu'=>$userId]); if($q->rowCount()!==1) throw new DomainException('This incident can no longer accept a resolution preference.');
    }

    /**
     * Creates one replacement request only after an administrator has completed
     * the incident review. The current schema calls that approved state
     * "resolved" (there is no separate approved incident enum value).
     */
    public function requestReplacementForResolvedIncident(int $userId, int $incidentId, string $reason): int
    {
        $this->db->beginTransaction();
        try {
            $incidentStatement = $this->db->prepare(
                "SELECT i.id, i.booking_id, b.vehicle_id
                 FROM incidents i
                 JOIN bookings b ON b.id = i.booking_id
                 JOIN customers c ON c.id = b.customer_id
                 WHERE i.id = :incident_id
                   AND i.reported_by = :user_id
                   AND c.user_id = :customer_user_id
                   AND i.status = 'resolved'
                   AND b.status = 'disputed'
                 LIMIT 1 FOR UPDATE"
            );
            $incidentStatement->execute(['incident_id' => $incidentId, 'user_id' => $userId, 'customer_user_id' => $userId]);
            $incident = $incidentStatement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($incident)) {
                throw new DomainException('A replacement can be requested only after this incident is approved.');
            }

            $existingStatement = $this->db->prepare(
                'SELECT id FROM replacement_requests WHERE incident_id = :incident_id AND booking_id = :booking_id LIMIT 1 FOR UPDATE'
            );
            $existingStatement->execute(['incident_id' => $incidentId, 'booking_id' => $incident['booking_id']]);
            if ($existingStatement->fetchColumn() !== false) {
                throw new DomainException('A replacement request has already been submitted for this incident.');
            }

            $insertStatement = $this->db->prepare(
                "INSERT INTO replacement_requests
                 (incident_id, booking_id, requested_by, original_vehicle_id, reason, status)
                 VALUES (:incident_id, :booking_id, :requested_by, :original_vehicle_id, :reason, 'pending')"
            );
            $insertStatement->execute([
                'incident_id' => $incidentId,
                'booking_id' => $incident['booking_id'],
                'requested_by' => $userId,
                'original_vehicle_id' => $incident['vehicle_id'],
                'reason' => $reason,
            ]);

            $requestId = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $requestId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
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
        $inspections = $inspectionStatement->fetchAll(PDO::FETCH_ASSOC);
        $photoStatement = $this->db->prepare('SELECT id, photo_path, caption FROM vehicle_inspection_photos WHERE inspection_id = :inspection_id ORDER BY id ASC');
        foreach ($inspections as &$inspection) {
            $photoStatement->execute(['inspection_id' => $inspection['id']]);
            $inspection['photos'] = $photoStatement->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($inspection);
        return ['booking' => $booking, 'inspections' => $inspections];
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
            'SELECT r.id, r.booking_id, r.driver_id, r.driver_rating, r.vehicle_rating, r.review_text, r.created_at,
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

    public function findReviewIdByBooking(int $sessionUserId, int $bookingId): ?int
    {
        $statement = $this->db->prepare(
            'SELECT r.id FROM ratings_reviews r
             JOIN customers c ON c.id = r.customer_id
             WHERE r.booking_id = :booking_id AND c.user_id = :user_id LIMIT 1'
        );
        $statement->execute([
            'booking_id' => $bookingId,
            'user_id' => $sessionUserId,
        ]);
        $id = $statement->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    public function findReview(int $sessionUserId, int $reviewId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT r.id, r.booking_id, r.driver_id, r.driver_rating, r.vehicle_rating, r.review_text, r.created_at,
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

    public function updateReview(
        int $sessionUserId,
        int $reviewId,
        int $vehicleRating,
        ?int $driverRating,
        string $reviewText
    ): bool {
        $statement = $this->db->prepare(
            'UPDATE ratings_reviews
             SET vehicle_rating = :vehicle_rating,
                 driver_rating = :driver_rating,
                 review_text = :review_text
             WHERE id = :review_id
               AND customer_id = (SELECT id FROM customers WHERE user_id = :user_id LIMIT 1)'
        );

        return $statement->execute([
            'vehicle_rating' => $vehicleRating,
            'driver_rating' => $driverRating,
            'review_text' => $reviewText !== '' ? $reviewText : null,
            'review_id' => $reviewId,
            'user_id' => $sessionUserId,
        ]);
    }

    public function deleteReview(int $sessionUserId, int $reviewId): bool
    {
        $statement = $this->db->prepare(
            'DELETE FROM ratings_reviews
             WHERE id = :review_id
               AND customer_id = (SELECT id FROM customers WHERE user_id = :user_id LIMIT 1)'
        );
        $statement->execute([
            'review_id' => $reviewId,
            'user_id' => $sessionUserId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function getDriverChangeEligibleBookings(int $sessionUserId): array
    {
        return $this->getOwnedBookingsByStatuses($sessionUserId, ['confirmed', 'ongoing'], true);
    }

    /**
     * Returns active with-driver bookings whose handover progress a Customer
     * can review or confirm. Self-drive rentals do not use driver pickup steps.
     */
    public function getPickupStatusBookings(int $sessionUserId): array
    {
        $statement = $this->db->prepare(
            "SELECT b.id, b.status, b.pickup_status, b.start_date, b.end_date,
                    v.make, v.model, v.license_plate,
                    u.name AS driver_name
             FROM bookings b
             JOIN customers c ON c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             JOIN drivers d ON d.id = b.driver_id
             JOIN users u ON u.id = d.user_id
             WHERE c.user_id = :user_id
               AND b.booking_type = 'with_driver'
               AND b.status IN ('confirmed', 'ongoing')
             ORDER BY b.start_date ASC, b.id DESC"
        );
        $statement->execute(['user_id' => $sessionUserId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Records the Customer's acknowledgement after the driver has collected them. */
    public function confirmPickup(int $sessionUserId, int $bookingId): void
    {
        $this->advanceCustomerPickupStatus($sessionUserId, $bookingId, 'picked_up', 'customer_confirmed_pickup', false);
    }

    /** Starts the rental only after the Customer has confirmed pickup. */
    public function startRental(int $sessionUserId, int $bookingId): void
    {
        $this->advanceCustomerPickupStatus($sessionUserId, $bookingId, 'customer_confirmed_pickup', 'rental_started', true);
    }

    public function getReturnEligibleBookings(int $sessionUserId): array
    {
        $statement = $this->db->prepare(
            "SELECT b.id, b.status, b.booking_type, v.make, v.model, v.license_plate
             FROM bookings b
             JOIN customers c ON c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             WHERE c.user_id = :user_id AND b.status = 'ongoing'
               AND NOT EXISTS (
                   SELECT 1 FROM return_requests rr
                   WHERE rr.booking_id = b.id AND rr.status IN ('pending', 'approved')
               )
             ORDER BY b.end_date ASC, b.id ASC"
        );
        $statement->execute(['user_id' => $sessionUserId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Creates one persistent return request for an owned, ongoing booking. */
    public function createReturnRequest(
        int $sessionUserId,
        int $bookingId,
        string $proposedReturnAt,
        string $customerNote
    ): int {
        $this->db->beginTransaction();
        try {
            // Lock the booking so concurrent submissions serialize before checking duplicates.
            $bookingStatement = $this->db->prepare(
                "SELECT b.id, c.id AS customer_id
                 FROM bookings b
                 JOIN customers c ON c.id = b.customer_id
                 WHERE b.id = :booking_id AND c.user_id = :user_id AND b.status = 'ongoing'
                 LIMIT 1 FOR UPDATE"
            );
            $bookingStatement->execute(['booking_id' => $bookingId, 'user_id' => $sessionUserId]);
            $booking = $bookingStatement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($booking)) {
                throw new DomainException('Choose an ongoing booking that belongs to your account.');
            }

            $duplicateStatement = $this->db->prepare(
                "SELECT id FROM return_requests
                 WHERE booking_id = :booking_id AND status IN ('pending', 'approved')
                 LIMIT 1 FOR UPDATE"
            );
            $duplicateStatement->execute(['booking_id' => $bookingId]);
            if ($duplicateStatement->fetchColumn() !== false) {
                throw new DomainException('An active return request already exists for this booking.');
            }

            $insertStatement = $this->db->prepare(
                "INSERT INTO return_requests
                    (booking_id, customer_id, proposed_return_at, customer_note, status)
                 VALUES (:booking_id, :customer_id, :proposed_return_at, :customer_note, 'pending')"
            );
            $insertStatement->execute([
                'booking_id' => $bookingId,
                'customer_id' => (int) $booking['customer_id'],
                'proposed_return_at' => $proposedReturnAt,
                'customer_note' => $customerNote !== '' ? $customerNote : null,
            ]);
            $requestId = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $requestId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    /** Returns persistent return requests belonging to the signed-in Customer. */
    public function getReturnRequests(int $sessionUserId): array
    {
        $statement = $this->db->prepare(
            'SELECT rr.id, rr.booking_id, rr.proposed_return_at, rr.customer_note,
                    rr.status, rr.admin_remarks, rr.created_at,
                    b.status AS booking_status, v.make, v.model
             FROM return_requests rr
             JOIN bookings b ON b.id = rr.booking_id
             JOIN customers c ON c.id = rr.customer_id AND c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             WHERE c.user_id = :user_id
             ORDER BY rr.created_at DESC, rr.id DESC'
        );
        $statement->execute(['user_id' => $sessionUserId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Returns one return request only when it belongs to the signed-in Customer. */
    public function findReturnRequest(int $sessionUserId, int $requestId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT rr.id, rr.booking_id, rr.proposed_return_at, rr.customer_note,
                    rr.status, rr.admin_remarks, rr.created_at,
                    b.status AS booking_status, v.make, v.model, v.license_plate
             FROM return_requests rr
             JOIN bookings b ON b.id = rr.booking_id
             JOIN customers c ON c.id = rr.customer_id AND c.id = b.customer_id
             JOIN vehicles v ON v.id = b.vehicle_id
             WHERE rr.id = :request_id AND c.user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['request_id' => $requestId, 'user_id' => $sessionUserId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
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

    private function advanceCustomerPickupStatus(
        int $sessionUserId,
        int $bookingId,
        string $expectedPickupStatus,
        string $newPickupStatus,
        bool $startRental
    ): void {
        $this->db->beginTransaction();
        try {
            $bookingStatement = $this->db->prepare(
                "SELECT b.id, b.status, b.pickup_status
                 FROM bookings b
                 JOIN customers c ON c.id = b.customer_id
                 WHERE b.id = :booking_id
                   AND c.user_id = :user_id
                   AND b.booking_type = 'with_driver'
                 FOR UPDATE"
            );
            $bookingStatement->execute(['booking_id' => $bookingId, 'user_id' => $sessionUserId]);
            $booking = $bookingStatement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($booking) || !in_array($booking['status'], ['confirmed', 'ongoing'], true)) {
                throw new DomainException('This booking is not available for pickup confirmation.');
            }
            if ($booking['pickup_status'] !== $expectedPickupStatus) {
                throw new DomainException('This pickup step is not ready yet, or it has already been confirmed.');
            }

            $updateSql = $startRental
                ? "UPDATE bookings SET pickup_status = :pickup_status, status = 'ongoing' WHERE id = :booking_id"
                : 'UPDATE bookings SET pickup_status = :pickup_status WHERE id = :booking_id';
            $updateStatement = $this->db->prepare($updateSql);
            $updateStatement->execute(['pickup_status' => $newPickupStatus, 'booking_id' => $bookingId]);

            $trackingStatement = $this->db->prepare(
                "INSERT INTO pickup_tracking (booking_id, status, updated_by, driver_note)
                 VALUES (:booking_id, :status, :updated_by, :note)"
            );
            $trackingStatement->execute([
                'booking_id' => $bookingId,
                'status' => $newPickupStatus,
                'updated_by' => $sessionUserId,
                'note' => $startRental ? 'Customer confirmed rental start.' : 'Customer confirmed pickup.',
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
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

    public function acceptReplacement(int $sessionUserId, int $incidentId, int $replacementRequestId): bool
    {
        $this->db->beginTransaction();
        try {
            // 1. Verify and lock Incident, Customer Ownership, and Booking
            $stmt = $this->db->prepare(
                "SELECT i.id AS incident_id, i.booking_id, b.status AS booking_status, b.vehicle_id AS current_vehicle_id
                 FROM incidents i
                 JOIN bookings b ON b.id = i.booking_id
                 JOIN customers c ON c.id = b.customer_id
                 WHERE i.id = :incident_id AND c.user_id = :user_id
                 LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([
                'incident_id' => $incidentId,
                'user_id'     => $sessionUserId,
            ]);
            $incidentRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$incidentRow) {
                throw new DomainException('Incident not found or unauthorized.');
            }

            if (!in_array($incidentRow['booking_status'], ['ongoing', 'disputed'], true)) {
                throw new DomainException('A replacement vehicle can only be accepted for an ongoing or disputed rental.');
            }

            $bookingId = (int) $incidentRow['booking_id'];
            $currentBookingVehicleId = (int) $incidentRow['current_vehicle_id'];

            // 2. Verify and lock Replacement Request
            $stmtRep = $this->db->prepare(
                "SELECT rr.id, rr.incident_id, rr.booking_id, rr.original_vehicle_id, rr.replacement_vehicle_id, rr.status
                 FROM replacement_requests rr
                 WHERE rr.id = :replacement_id
                   AND rr.incident_id = :incident_id
                   AND rr.booking_id = :booking_id
                 LIMIT 1 FOR UPDATE"
            );
            $stmtRep->execute([
                'replacement_id' => $replacementRequestId,
                'incident_id'    => $incidentId,
                'booking_id'     => $bookingId,
            ]);
            $rep = $stmtRep->fetch(PDO::FETCH_ASSOC);
            if (!$rep) {
                throw new DomainException('Replacement request record not found for this incident.');
            }

            // Check request status
            if ($rep['status'] !== 'approved') {
                if (in_array($rep['status'], ['dispatched', 'delivered'], true)) {
                    throw new DomainException('This replacement vehicle has already been accepted.');
                }
                if (in_array($rep['status'], ['rejected', 'cancelled'], true)) {
                    throw new DomainException('This replacement request has been closed or cancelled.');
                }
                throw new DomainException('Replacement request is not approved for customer response.');
            }

            // Check original vehicle matches current booking vehicle
            $origVehicleId = (int) $rep['original_vehicle_id'];
            if ($origVehicleId !== $currentBookingVehicleId) {
                throw new DomainException('Original vehicle does not match the active rental vehicle.');
            }

            // Check replacement vehicle ID exists
            $repVehicleId = (int) ($rep['replacement_vehicle_id'] ?? 0);
            if ($repVehicleId <= 0) {
                throw new DomainException('No replacement vehicle has been assigned by administrator.');
            }

            // 3. Verify and lock Replacement Vehicle
            $stmtVeh = $this->db->prepare(
                "SELECT id, status, verification_status
                 FROM vehicles
                 WHERE id = :rep_vehicle_id
                 LIMIT 1 FOR UPDATE"
            );
            $stmtVeh->execute(['rep_vehicle_id' => $repVehicleId]);
            $repVehicle = $stmtVeh->fetch(PDO::FETCH_ASSOC);
            if (!$repVehicle) {
                throw new DomainException('Assigned replacement vehicle record was not found.');
            }

            if ($repVehicle['status'] !== 'available') {
                throw new DomainException("Assigned replacement vehicle is unavailable (status: {$repVehicle['status']}).");
            }

            if ($repVehicle['verification_status'] !== 'approved') {
                throw new DomainException('Assigned replacement vehicle is not verified/approved.');
            }

            // Ensure replacement vehicle is not already bound to another active/ongoing booking
            $stmtActiveCheck = $this->db->prepare(
                "SELECT COUNT(*) FROM bookings
                 WHERE vehicle_id = :rep_vehicle_id AND status = 'ongoing' AND id != :booking_id"
            );
            $stmtActiveCheck->execute([
                'rep_vehicle_id' => $repVehicleId,
                'booking_id'     => $bookingId,
            ]);
            if ((int) $stmtActiveCheck->fetchColumn() > 0) {
                throw new DomainException('Assigned replacement vehicle is already active on another rental.');
            }

            // 4. Lock Original Vehicle
            $stmtOrigVeh = $this->db->prepare(
                "SELECT id, status FROM vehicles WHERE id = :orig_vehicle_id LIMIT 1 FOR UPDATE"
            );
            $stmtOrigVeh->execute(['orig_vehicle_id' => $origVehicleId]);
            $origVehicle = $stmtOrigVeh->fetch(PDO::FETCH_ASSOC);
            if (!$origVehicle) {
                throw new DomainException('Original vehicle record was not found.');
            }

            // 5. Execute Atomic Replacement Transitions
            // (a) Switch booking to replacement vehicle and resume as ongoing
            $updateBooking = $this->db->prepare(
                "UPDATE bookings SET vehicle_id = :rep_vehicle_id, status = 'ongoing' WHERE id = :booking_id"
            );
            $updateBooking->execute([
                'rep_vehicle_id' => $repVehicleId,
                'booking_id'     => $bookingId,
            ]);

            // (b) Set original vehicle to maintenance
            $updateOrigVeh = $this->db->prepare(
                "UPDATE vehicles SET status = 'maintenance' WHERE id = :orig_vehicle_id"
            );
            $updateOrigVeh->execute(['orig_vehicle_id' => $origVehicleId]);

            // (c) Set replacement vehicle to rented
            $updateRepVeh = $this->db->prepare(
                "UPDATE vehicles SET status = 'rented' WHERE id = :rep_vehicle_id"
            );
            $updateRepVeh->execute(['rep_vehicle_id' => $repVehicleId]);

            // (d) Update replacement request status to dispatched
            $updateRepReq = $this->db->prepare(
                "UPDATE replacement_requests SET status = 'dispatched' WHERE id = :replacement_id"
            );
            $updateRepReq->execute(['replacement_id' => $replacementRequestId]);

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function rejectReplacement(int $sessionUserId, int $incidentId, int $replacementRequestId): bool
    {
        $this->db->beginTransaction();
        try {
            // 1. Verify and lock Incident, Customer Ownership, and Booking
            $stmt = $this->db->prepare(
                "SELECT i.id AS incident_id, i.booking_id, b.status AS booking_status
                 FROM incidents i
                 JOIN bookings b ON b.id = i.booking_id
                 JOIN customers c ON c.id = b.customer_id
                 WHERE i.id = :incident_id AND c.user_id = :user_id
                 LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([
                'incident_id' => $incidentId,
                'user_id'     => $sessionUserId,
            ]);
            $incidentRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$incidentRow) {
                throw new DomainException('Incident not found or unauthorized.');
            }

            $bookingId = (int) $incidentRow['booking_id'];

            // 2. Verify and lock Replacement Request
            $stmtRep = $this->db->prepare(
                "SELECT rr.id, rr.incident_id, rr.booking_id, rr.status
                 FROM replacement_requests rr
                 WHERE rr.id = :replacement_id
                   AND rr.incident_id = :incident_id
                   AND rr.booking_id = :booking_id
                 LIMIT 1 FOR UPDATE"
            );
            $stmtRep->execute([
                'replacement_id' => $replacementRequestId,
                'incident_id'    => $incidentId,
                'booking_id'     => $bookingId,
            ]);
            $rep = $stmtRep->fetch(PDO::FETCH_ASSOC);
            if (!$rep) {
                throw new DomainException('Replacement request record not found for this incident.');
            }

            // Check request status
            if ($rep['status'] !== 'approved') {
                if (in_array($rep['status'], ['dispatched', 'delivered'], true)) {
                    throw new DomainException('This replacement offer was already accepted and cannot be rejected.');
                }
                if (in_array($rep['status'], ['rejected', 'cancelled'], true)) {
                    throw new DomainException('This replacement request has already been closed or cancelled.');
                }
                throw new DomainException('Replacement request is not in a state to be rejected.');
            }

            // 3. Update Replacement Request status to rejected
            // NOTE: Do NOT alter bookings.vehicle_id, vehicles.status, bookings.status, or incidents.status
            $updateRepReq = $this->db->prepare(
                "UPDATE replacement_requests SET status = 'rejected' WHERE id = :replacement_id"
            );
            $updateRepReq->execute(['replacement_id' => $replacementRequestId]);

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
