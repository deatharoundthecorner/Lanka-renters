<?php

require_once __DIR__ . '/AuthHelper.php';

/**
 * Isolated session persistence for prototype workflows with no approved table.
 * These records are never written to MySQL and are always displayed as demo data.
 */
final class CustomerDemoData
{
    private const SESSION_KEY = '_customer_phase6_demo';

    public static function driverChanges(int $userId): array
    {
        return self::records('driver_changes', $userId);
    }

    public static function createDriverChange(int $userId, int $bookingId, string $reason, string $note): array
    {
        foreach (self::driverChanges($userId) as $record) {
            if ((int) $record['booking_id'] === $bookingId && $record['status'] === 'pending') {
                throw new DomainException('A pending demo driver-change request already exists for this booking.');
            }
        }
        return self::append('driver_changes', $userId, [
            'booking_id' => $bookingId,
            'reason' => $reason,
            'scheduling_note' => $note,
            'status' => 'pending',
        ]);
    }

    public static function findDriverChange(int $userId, int $recordId): ?array
    {
        return self::find('driver_changes', $userId, $recordId);
    }

    public static function returns(int $userId): array
    {
        return self::records('returns', $userId);
    }

    public static function createReturn(
        int $userId,
        int $bookingId,
        string $proposedReturnAt,
        string $note
    ): array {
        foreach (self::returns($userId) as $record) {
            if ((int) $record['booking_id'] === $bookingId && $record['status'] === 'pending') {
                throw new DomainException('A pending demo return request already exists for this booking.');
            }
        }
        return self::append('returns', $userId, [
            'booking_id' => $bookingId,
            'proposed_return_at' => $proposedReturnAt,
            'customer_note' => $note,
            'status' => 'pending',
        ]);
    }

    public static function findReturn(int $userId, int $recordId): ?array
    {
        return self::find('returns', $userId, $recordId);
    }

    public static function replacementDecisions(int $userId): array
    {
        return self::records('replacement_decisions', $userId);
    }

    public static function createReplacementDecision(
        int $userId,
        int $incidentId,
        int $replacementRequestId,
        string $decision,
        string $reason
    ): array {
        foreach (self::replacementDecisions($userId) as $record) {
            if ((int) $record['replacement_request_id'] === $replacementRequestId) {
                throw new DomainException('A demo decision has already been recorded for this replacement offer.');
            }
        }
        return self::append('replacement_decisions', $userId, [
            'incident_id' => $incidentId,
            'replacement_request_id' => $replacementRequestId,
            'decision' => $decision,
            'reason' => $reason,
            'status' => 'demo_recorded',
        ]);
    }

    public static function findReplacementDecision(int $userId, int $replacementRequestId): ?array
    {
        foreach (self::replacementDecisions($userId) as $record) {
            if ((int) $record['replacement_request_id'] === $replacementRequestId) {
                return $record;
            }
        }
        return null;
    }

    public static function enabled(): bool
    {
        $setting = getenv('LANKA_RENTERS_CUSTOMER_DEMO');
        if ($setting === false || trim($setting) === '') {
            return true;
        }
        return !in_array(strtolower(trim($setting)), ['0', 'false', 'off', 'disabled'], true);
    }

    private static function append(string $collection, int $userId, array $values): array
    {
        AuthHelper::startSession();
        self::initialize();
        $nextId = (int) ($_SESSION[self::SESSION_KEY]['next_id'] ?? 1);
        $_SESSION[self::SESSION_KEY]['next_id'] = $nextId + 1;
        $record = array_merge($values, [
            'id' => $nextId,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'data_source' => 'demo_session',
        ]);
        $_SESSION[self::SESSION_KEY][$collection][] = $record;
        return $record;
    }

    private static function records(string $collection, int $userId): array
    {
        AuthHelper::startSession();
        self::initialize();
        $records = $_SESSION[self::SESSION_KEY][$collection] ?? [];
        return array_values(array_filter($records, static fn (array $record): bool =>
            (int) ($record['user_id'] ?? 0) === $userId
        ));
    }

    private static function find(string $collection, int $userId, int $recordId): ?array
    {
        foreach (self::records($collection, $userId) as $record) {
            if ((int) ($record['id'] ?? 0) === $recordId) {
                return $record;
            }
        }
        return null;
    }

    private static function initialize(): void
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [
                'next_id' => 1,
                'driver_changes' => [],
                'returns' => [],
                'replacement_decisions' => [],
            ];
        }
    }
}
