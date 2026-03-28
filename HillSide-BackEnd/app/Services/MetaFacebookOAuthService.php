<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaFacebookOAuthService
{
    /**
     * Exchange an authorization code for a short-lived user access token.
     *
     * @return array{access_token: string, token_type?: string, expires_in?: int}
     */
    public static function shortLivedUserTokenFromCode(string $code): array
    {
        $meta = config('services.meta');
        $graphBase = self::graphBaseUrl();

        $response = Http::timeout(20)->get("{$graphBase}/oauth/access_token", [
            'client_id' => $meta['app_id'],
            'client_secret' => $meta['app_secret'],
            'redirect_uri' => $meta['redirect_uri'],
            'code' => $code,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Meta code exchange failed: HTTP ' . $response->status());
        }

        return self::assertTokenPayload($response->json());
    }

    /**
     * Exchange a short-lived user token for a long-lived one (~60 days).
     *
     * @return array{access_token: string, token_type?: string, expires_in?: int}
     */
    public static function longLivedUserToken(string $shortLivedAccessToken): array
    {
        $meta = config('services.meta');
        $graphBase = self::graphBaseUrl();

        $response = Http::timeout(20)->get("{$graphBase}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $meta['app_id'],
            'client_secret' => $meta['app_secret'],
            'fb_exchange_token' => $shortLivedAccessToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Meta long-lived token exchange failed: HTTP ' . $response->status());
        }

        return self::assertTokenPayload($response->json());
    }

    /**
     * @param  mixed  $data
     * @return array{access_token: string, token_type?: string, expires_in?: int}
     */
    private static function assertTokenPayload(mixed $data): array
    {
        if (! is_array($data) || empty($data['access_token']) || ! is_string($data['access_token'])) {
            throw new RuntimeException('Meta token response missing access_token.');
        }

        return $data;
    }

    private static function graphBaseUrl(): string
    {
        $base = (string) config('services.meta.graph_base_url', 'https://graph.facebook.com/v21.0');

        return rtrim($base, '/');
    }
}
