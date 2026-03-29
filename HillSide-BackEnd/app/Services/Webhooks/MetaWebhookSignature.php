<?php

namespace App\Services\Webhooks;

class MetaWebhookSignature
{
    public static function isValid(string $appSecret, string $rawBody, ?string $signatureHeader): bool
    {
        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        if (! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $signatureHeader);
    }
}
