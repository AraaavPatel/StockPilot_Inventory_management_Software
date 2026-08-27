<?php

namespace App\Audit;

use App\Core\Auth;
use App\Core\Database;

/**
 * AuditLogger
 *
 * Append-only. There is deliberately no update()/delete() method on
 * this class, and no route anywhere in the app that edits or removes
 * an audit_logs row — see SECURITY_AUDIT.md. Every write is chained to
 * the previous row's hash so bulk direct-DB tampering (skipping the
 * app entirely) is at least detectable via verifyChain().
 */
class AuditLogger
{
    /**
     * @param string      $action     e.g. 'PRODUCT_UPDATED'
     * @param string      $module     e.g. 'products'
     * @param string|null $entityType e.g. 'product'
     * @param int|null    $entityId
     * @param array|null  $old
     * @param array|null  $new
     */
    public static function log(
        string $action,
        string $module,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $old = null,
        ?array $new = null
    ): void {
        $db = Database::connection();
        $user = Auth::user();

        $prevHash = self::lastHash();

        $row = [
            'user_id'     => $user['id'] ?? null,
            'actor_name'  => $user['name'] ?? 'guest',
            'action'      => $action,
            'module'      => $module,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $old !== null ? json_encode($old, JSON_UNESCAPED_SLASHES) : null,
            'new_values'  => $new !== null ? json_encode($new, JSON_UNESCAPED_SLASHES) : null,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'  => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'request_id'  => RequestId::current(),
        ];

        // Server timestamp only — never accept one from the caller.
        $createdAt = date('Y-m-d H:i:s');

        $recordHash = hash('sha256', $prevHash . '|' . implode('|', [
            $row['user_id'], $row['action'], $row['module'], $row['entity_type'],
            $row['entity_id'], $row['old_values'], $row['new_values'],
            $row['ip_address'], $row['request_id'], $createdAt,
        ]));

        $stmt = $db->prepare(
            'INSERT INTO audit_logs
                (user_id, actor_name, action, module, entity_type, entity_id,
                 old_values, new_values, ip_address, user_agent, request_id,
                 prev_hash, record_hash, created_at)
             VALUES
                (:user_id, :actor_name, :action, :module, :entity_type, :entity_id,
                 :old_values, :new_values, :ip_address, :user_agent, :request_id,
                 :prev_hash, :record_hash, :created_at)'
        );

        $stmt->execute($row + [
            'prev_hash'   => $prevHash,
            'record_hash' => $recordHash,
            'created_at'  => $createdAt,
        ]);
    }

    private static function lastHash(): string
    {
        $stmt = Database::connection()->query(
            'SELECT record_hash FROM audit_logs ORDER BY id DESC LIMIT 1'
        );
        $row = $stmt->fetch();
        return $row['record_hash'] ?? str_repeat('0', 64); // genesis row
    }

    /**
     * Walk the chain and confirm every row's stored hash matches a
     * recomputed hash from its own fields + the previous row's hash.
     * Returns the id of the first tampered/broken row, or null if the
     * whole chain verifies clean.
     */
    public static function verifyChain(): ?int
    {
        $db = Database::connection();
        $prevHash = str_repeat('0', 64);

        $stmt = $db->query('SELECT * FROM audit_logs ORDER BY id ASC');
        while ($row = $stmt->fetch()) {
            $expected = hash('sha256', $prevHash . '|' . implode('|', [
                $row['user_id'], $row['action'], $row['module'], $row['entity_type'],
                $row['entity_id'], $row['old_values'], $row['new_values'],
                $row['ip_address'], $row['request_id'], $row['created_at'],
            ]));
            if (!hash_equals($expected, $row['record_hash']) || $row['prev_hash'] !== $prevHash) {
                return (int) $row['id'];
            }
            $prevHash = $row['record_hash'];
        }
        return null;
    }
}
