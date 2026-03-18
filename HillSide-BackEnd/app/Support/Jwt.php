<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Config;

class Jwt
{
    /**
     * @return array{token:string, exp:int}
     */
    public static function issueForUser(User $user): array
    {
        $now = time();
        $ttlSeconds = (int) (env('JWT_TTL_SECONDS', 60 * 60)); // default 1h
        $exp = $now + $ttlSeconds;

        $payload = [
            'sub' => (string) $user->id,
            'iat' => $now,
            'exp' => $exp,
        ];

        return [
            'token' => self::encode($payload),
            'exp' => $exp,
        ];
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function encode(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $segments = [
            self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];

        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, self::secret(), true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function decodeAndVerify(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $signingInput = $encodedHeader . '.' . $encodedPayload;
        $expected = hash_hmac('sha256', $signingInput, self::secret(), true);
        $actual = self::base64UrlDecode($encodedSignature);

        if (!is_string($actual) || !hash_equals($expected, $actual)) {
            return null;
        }

        $payloadJson = self::base64UrlDecode($encodedPayload);
        if (!is_string($payloadJson)) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && is_numeric($payload['exp']) && (int) $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private static function secret(): string
    {
        // Use app key as default secret (remove "base64:" prefix if present).
        $key = (string) Config::get('app.key', '');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            return $decoded !== false ? $decoded : $key;
        }

        return $key;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string|false
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}

