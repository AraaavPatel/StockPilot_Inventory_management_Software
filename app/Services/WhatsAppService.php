<?php

namespace App\Services;

/**
 * WhatsAppService
 *
 * Sends invoice notifications via the Meta WhatsApp Cloud API the
 * moment a sale is marked paid — no manual "share" step by the cashier.
 *
 * WHY CLOUD API AND NOT TWILIO:
 * Meta's own Cloud API gives 1,000 free conversations/month, which
 * covers a single kirana store's volume comfortably. Twilio charges
 * per message from message #1 — more expensive for this use case,
 * with no meaningful setup simplicity advantage.
 *
 * IMPORTANT — Meta's 24-hour rule:
 * A business can only send free-form text to a customer within 24h
 * of that customer messaging first. Since the STORE initiates here
 * (right after checkout), every message must use a pre-approved
 * "template" message, not free text. You approve templates once in
 * the Meta Business dashboard (usually within a day) and then send
 * them by name + parameters, as this class does.
 *
 * SETUP CHECKLIST (fill these into .env once ready):
 *   1. Create a Meta Business account -> business.facebook.com
 *   2. Add a WhatsApp Business product inside developers.facebook.com
 *   3. Get a WhatsApp phone number (new number is fine, doesn't need
 *      to be the owner's personal number)
 *   4. Create + submit a message template, e.g. name "invoice_ready"
 *      with body: "Hi {{1}}, your bill of ₹{{2}} at {{3}} is ready.
 *      View/download your invoice here: {{4}}"
 *   5. Copy your Phone Number ID and a permanent Access Token into
 *      WHATSAPP_PHONE_NUMBER_ID / WHATSAPP_ACCESS_TOKEN in .env
 *
 * Until those .env values are filled in, send() safely no-ops and
 * logs to storage/logs — checkout is never blocked by WhatsApp.
 */
class WhatsAppService
{
    private string $apiVersion = 'v20.0';
    private ?string $phoneNumberId;
    private ?string $accessToken;
    private string $templateName;

    public function __construct()
    {
        $this->phoneNumberId = config('WHATSAPP_PHONE_NUMBER_ID') ?: null;
        $this->accessToken = config('WHATSAPP_ACCESS_TOKEN') ?: null;
        $this->templateName = config('WHATSAPP_TEMPLATE_NAME', 'invoice_ready');
    }

    public function isConfigured(): bool
    {
        return $this->phoneNumberId !== null && $this->accessToken !== null;
    }

    /**
     * Send the invoice-ready template to a customer.
     *
     * @param string $toNumber     Customer's number, digits only, with country code (e.g. "919812345678")
     * @param string $customerName
     * @param float  $amount
     * @param string $storeName
     * @param string $invoiceUrl   Public URL to view/download the PDF invoice
     * @return array{success: bool, message: string}
     */
    public function sendInvoiceNotification(
        string $toNumber,
        string $customerName,
        float $amount,
        string $storeName,
        string $invoiceUrl
    ): array {
        $toNumber = $this->normalizeNumber($toNumber);

        if ($toNumber === null) {
            return ['success' => false, 'message' => 'Invalid WhatsApp number format.'];
        }

        if (!$this->isConfigured()) {
            $this->log("SKIPPED (not configured) — would have sent to {$toNumber}: invoice for ₹{$amount}");
            return ['success' => false, 'message' => 'WhatsApp API not configured yet. See .env.example for setup steps.'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toNumber,
            'type' => 'template',
            'template' => [
                'name' => $this->templateName,
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $customerName],
                            ['type' => 'text', 'text' => number_format($amount, 2)],
                            ['type' => 'text', 'text' => $storeName],
                            ['type' => 'text', 'text' => $invoiceUrl],
                        ],
                    ],
                ],
            ],
        ];

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->log("cURL ERROR sending to {$toNumber}: {$curlError}");
            return ['success' => false, 'message' => 'Network error contacting WhatsApp API.'];
        }

        $decoded = json_decode((string) $response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            $this->log("SENT to {$toNumber} — message id: " . ($decoded['messages'][0]['id'] ?? 'unknown'));
            return ['success' => true, 'message' => 'Invoice sent to WhatsApp.'];
        }

        $errorMsg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
        $this->log("FAILED to {$toNumber}: {$errorMsg}");
        return ['success' => false, 'message' => "WhatsApp send failed: {$errorMsg}"];
    }

    /**
     * Accepts Indian 10-digit numbers or already-prefixed numbers,
     * strips spaces/dashes, and defaults to +91 if no country code given.
     */
    private function normalizeNumber(string $raw): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $raw);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            return '91' . $digits; // assume India if no country code given
        }

        if (strlen($digits) >= 11 && strlen($digits) <= 15) {
            return $digits;
        }

        return null;
    }

    private function log(string $line): void
    {
        $path = __DIR__ . '/../../storage/logs/whatsapp.log';
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents($path, "[{$timestamp}] {$line}\n", FILE_APPEND);
    }
}
