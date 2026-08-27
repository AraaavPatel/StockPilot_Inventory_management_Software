<?php

namespace App\Notifications;

use RuntimeException;

/**
 * Mailer
 *
 * Minimal dependency-free SMTP client (STARTTLS/SSL, AUTH LOGIN) so we
 * don't need a Composer package that requires network access to
 * packagist.org to install. Deliberately small — this is not a general
 * mail library, just enough to reliably deliver plain-text/HTML security
 * notifications from shared hosting via an external SMTP provider
 * (Gmail SMTP, Brevo, SendGrid, Mailtrap, etc — InfinityFree blocks
 * outbound port 25/465/587 to arbitrary hosts on the free tier, so an
 * SMTP *relay* provider is required, not "send it yourself").
 *
 * IMPORTANT: mail delivery NEVER throws out of send() — it returns
 * false and logs, so a business transaction (a sale, a user creation)
 * can never be rolled back just because an email failed. See
 * App\Notifications\AdminNotifier for the "attempt, never block" flow.
 */
class Mailer
{
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $host = config('MAIL_HOST', '');
        if ($host === '') {
            $this->log($toEmail, $subject, false, 'MAIL_HOST not configured — notification skipped.');
            return false;
        }

        $port = (int) config('MAIL_PORT', 587);
        $encryption = strtolower((string) config('MAIL_ENCRYPTION', 'tls')); // tls | ssl | none
        $username = (string) config('MAIL_USERNAME', '');
        $password = (string) config('MAIL_PASSWORD', '');
        $fromEmail = (string) config('MAIL_FROM_ADDRESS', 'no-reply@example.com');
        $fromName = (string) config('MAIL_FROM_NAME', 'StockPilot');

        $transport = $encryption === 'ssl' ? "ssl://{$host}" : $host;

        try {
            $stream = @stream_socket_client(
                "{$transport}:{$port}",
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT
            );
            if (!$stream) {
                throw new RuntimeException("Connection failed: {$errstr}");
            }
            stream_set_timeout($stream, 10);

            $this->expect($stream, '220');
            $this->command($stream, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), '250');

            if ($encryption === 'tls') {
                $this->command($stream, 'STARTTLS', '220');
                if (!@stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('STARTTLS negotiation failed.');
                }
                $this->command($stream, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), '250');
            }

            if ($username !== '') {
                $this->command($stream, 'AUTH LOGIN', '334');
                $this->command($stream, base64_encode($username), '334');
                $this->command($stream, base64_encode($password), '235');
            }

            $this->command($stream, "MAIL FROM:<{$fromEmail}>", '250');
            $this->command($stream, "RCPT TO:<{$toEmail}>", '250');
            $this->command($stream, 'DATA', '354');

            $headers = [
                'From: ' . $this->encodeHeader($fromName) . " <{$fromEmail}>",
                'To: ' . $this->encodeHeader($toName) . " <{$toEmail}>",
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Date: ' . date('r'),
            ];
            $message = implode("\r\n", $headers) . "\r\n\r\n"
                . str_replace("\n.", "\n..", $htmlBody) . "\r\n.";

            $this->command($stream, $message, '250');
            $this->command($stream, 'QUIT', '221');
            fclose($stream);

            $this->log($toEmail, $subject, true, null);
            return true;
        } catch (RuntimeException $e) {
            // Never leak SMTP host/credentials in the message we store.
            $this->log($toEmail, $subject, false, $e->getMessage());
            return false;
        }
    }

    private function command($stream, string $cmd, string $expectCode): void
    {
        fwrite($stream, $cmd . "\r\n");
        $this->expect($stream, $expectCode);
    }

    private function expect($stream, string $expectCode): void
    {
        $response = '';
        while ($line = fgets($stream, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break; // last line of a multi-line SMTP response
            }
        }
        if (!str_starts_with($response, $expectCode)) {
            throw new RuntimeException('Unexpected SMTP response (expected ' . $expectCode . ').');
        }
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function log(string $recipient, string $event, bool $success, ?string $error): void
    {
        try {
            $stmt = \App\Core\Database::connection()->prepare(
                'INSERT INTO notification_log (event, recipient, success, error_message) VALUES (:e, :r, :s, :err)'
            );
            $stmt->execute(['e' => $event, 'r' => $recipient, 's' => $success ? 1 : 0, 'err' => $error]);
        } catch (\Throwable $e) {
            // Even the log write must never break the caller.
            error_log('[email.log] ' . $recipient . ' | ' . $event . ' | ' . ($error ?? 'sent'));
        }
    }
}
