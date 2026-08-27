<?php

namespace App\Notifications;

/**
 * AdminNotifier
 *
 * High-level "what to say" layer over Mailer (the "how to send" layer).
 * Every method here is fire-and-forget: it never throws, and callers
 * should never wrap these in anything that could roll back a business
 * transaction. If MAIL_HOST isn't configured, these silently no-op
 * (logged in notification_log) rather than erroring the request.
 */
class AdminNotifier
{
    public function newUserCreated(array $user, array $createdBy): void
    {
        $isAdmin = $user['role'] === 'admin';
        $subject = $isAdmin
            ? 'StockPilot Security Alert — New Administrator Created'
            : 'StockPilot — New User Account Created';

        $body = $this->wrap($subject, "
            <p><strong>Name:</strong> " . htmlspecialchars($user['name']) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>
            <p><strong>Role:</strong> " . htmlspecialchars($user['role']) . "</p>
            <p><strong>Created by:</strong> " . htmlspecialchars($createdBy['name'] ?? 'system') . "</p>
            <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . " (server time)</p>
            <p><strong>IP address:</strong> " . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "</p>
        " . ($isAdmin ? '<p style="color:#b00;"><strong>This grants full administrative access. If this was not expected, revoke access immediately.</strong></p>' : ''));

        $this->notifyAdmin($subject, $body);
    }

    public function roleChanged(array $user, array $changedBy, string $oldRole): void
    {
        $subject = 'StockPilot Security Alert — Role Changed';
        $body = $this->wrap($subject, "
            <p><strong>User:</strong> " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")</p>
            <p><strong>Role changed:</strong> " . htmlspecialchars($oldRole) . " &rarr; " . htmlspecialchars($user['role']) . "</p>
            <p><strong>Changed by:</strong> " . htmlspecialchars($changedBy['name'] ?? 'system') . "</p>
            <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . " (server time)</p>
        ");
        $this->notifyAdmin($subject, $body);
    }

    public function suspiciousLogin(string $email, string $ip, string $reason): void
    {
        $subject = 'StockPilot Security Alert — Suspicious Login Activity';
        $body = $this->wrap($subject, "
            <p><strong>Email attempted:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>IP address:</strong> " . htmlspecialchars($ip) . "</p>
            <p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>
            <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . " (server time)</p>
        ");
        $this->notifyAdmin($subject, $body);
    }

    private function notifyAdmin(string $subject, string $body): void
    {
        $adminEmail = config('MAIL_ADMIN_ADDRESS', '');
        if ($adminEmail === '') {
            return;
        }
        (new Mailer())->send($adminEmail, 'StockPilot Admin', $subject, $body);
    }

    private function wrap(string $title, string $innerHtml): string
    {
        return '<div style="font-family:Arial,sans-serif;max-width:520px;">'
            . '<h2 style="margin:0 0 12px;">' . htmlspecialchars($title) . '</h2>'
            . $innerHtml
            . '<p style="color:#888;font-size:12px;margin-top:24px;">Automated message from StockPilot. Do not reply.</p>'
            . '</div>';
    }
}
