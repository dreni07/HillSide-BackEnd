<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaFacebookGraphService
{
    /**
     * @return array{pages: list<array{id: string, name: string}>, instagram: list<array{id: string, username: string, pageId: string, pageName: string}>}
     */
    public static function fetchPagesAndInstagram(string $userAccessToken): array
    {
        $graphBase = self::graphBaseUrl();
        $response = Http::timeout(25)->get("{$graphBase}/me/accounts", [
            'fields' => 'id,name,access_token,instagram_business_account{id,username}',
            'access_token' => $userAccessToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Meta Graph /me/accounts failed: HTTP ' . $response->status());
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new RuntimeException('Meta Graph returned an invalid response.');
        }

        $pages = [];
        $instagram = [];

        foreach ($body['data'] ?? [] as $acc) {
            if (! is_array($acc) || empty($acc['id'])) {
                continue;
            }
            $pageId = (string) $acc['id'];
            $pageName = isset($acc['name']) && is_string($acc['name']) ? $acc['name'] : '';
            $pages[] = ['id' => $pageId, 'name' => $pageName];

            $ig = $acc['instagram_business_account'] ?? null;
            if (is_array($ig) && ! empty($ig['id'])) {
                $instagram[] = [
                    'id' => (string) $ig['id'],
                    'username' => isset($ig['username']) && is_string($ig['username']) ? $ig['username'] : '',
                    'pageId' => $pageId,
                    'pageName' => $pageName,
                ];
            }
        }

        return ['pages' => $pages, 'instagram' => $instagram];
    }

    /**
     * Resolve the Facebook page access token for Messenger or Instagram messaging.
     *
     * @return array{access_token: string, page_name: string}|null
     */
    public static function resolvePageTokenForConnect(
        string $userAccessToken,
        string $platform,
        string $platformPageId
    ): ?array {
        $graphBase = self::graphBaseUrl();
        $response = Http::timeout(25)->get("{$graphBase}/me/accounts", [
            'fields' => 'id,name,access_token,instagram_business_account{id,username}',
            'access_token' => $userAccessToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Meta Graph /me/accounts failed: HTTP ' . $response->status());
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new RuntimeException('Meta Graph returned an invalid response.');
        }

        foreach ($body['data'] ?? [] as $acc) {
            if (! is_array($acc) || empty($acc['access_token']) || ! is_string($acc['access_token'])) {
                continue;
            }
            $pageId = (string) $acc['id'];
            $pageName = isset($acc['name']) && is_string($acc['name']) ? $acc['name'] : '';

            if ($platform === 'facebook' && $pageId === $platformPageId) {
                return [
                    'access_token' => $acc['access_token'],
                    'page_name' => $pageName,
                ];
            }

            if ($platform === 'instagram') {
                $ig = $acc['instagram_business_account'] ?? null;
                if (is_array($ig) && isset($ig['id']) && (string) $ig['id'] === $platformPageId) {
                    return [
                        'access_token' => $acc['access_token'],
                        'page_name' => isset($ig['username']) && is_string($ig['username']) && $ig['username'] !== ''
                            ? '@' . $ig['username']
                            : $pageName,
                    ];
                }
            }
        }

        return null;
    }

    private static function graphBaseUrl(): string
    {
        return rtrim((string) config('services.meta.graph_base_url', 'https://graph.facebook.com/v21.0'), '/');
    }
}
