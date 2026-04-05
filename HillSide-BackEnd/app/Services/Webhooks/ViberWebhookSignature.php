<?php

namespace App\Services\Webhooks;

class ViberWebhookSignature
{
    /**
     * X-Viber-Content-Signature: HMAC-SHA256 of the raw body, key = bot auth token, as hex.
     */
    public static function isValid(string $authToken, string $rawBody, ?string $signatureHeader): bool
    {
        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $authToken);

        return hash_equals(strtolower($expected), strtolower($signatureHeader));
    }
}
