<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\User;
use App\Services\Channels\ChannelConnectionSyncer;
use App\Services\MetaFacebookGraphService;
use App\Services\MetaFacebookOAuthService;
use App\Support\ChannelCrmPresenter;
use App\Support\Jwt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MetaOAuthController extends Controller
{
    /**
     * Redirect the browser to Facebook's OAuth dialog with the parameters Meta requires.
     * Verifies the CRM JWT once, then sends a short random `state` to Meta (not the JWT).
     *
     * @see https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow
     */
    public function start(Request $request): RedirectResponse
    {
        $meta = config('services.meta');
        $channelsUrl = $meta['frontend_url'].'/app/channels';

        $token = $request->query('token');
        if (! is_string($token) || $token === '') {
            return redirect()->away($channelsUrl.'?oauth_error=missing_token');
        }

        $payload = Jwt::decodeAndVerify($token);
        if ($payload === null || ! isset($payload['sub']) || ! is_numeric($payload['sub'])) {
            return redirect()->away($channelsUrl.'?oauth_error=invalid_token');
        }

        $userId = (int) $payload['sub'];
        if (! User::query()->whereKey($userId)->exists()) {
            return redirect()->away($channelsUrl.'?oauth_error=invalid_token');
        }

        $appId = $meta['app_id'] ?? null;
        $redirectUri = $meta['redirect_uri'] ?? null;
        if (! is_string($appId) || $appId === '' || ! is_string($redirectUri) || $redirectUri === '') {
            return redirect()->away($channelsUrl.'?oauth_error=oauth_failed');
        }

        $scopes = $meta['scopes'] ?? [];
        if (! is_array($scopes) || $scopes === []) {
            return redirect()->away($channelsUrl.'?oauth_error=oauth_failed');
        }

        $baseUrl = rtrim((string) ($meta['oauth_url'] ?? ''), '/');
        if ($baseUrl === '') {
            return redirect()->away($channelsUrl.'?oauth_error=oauth_failed');
        }

        $oauthState = Str::random(40);
        $stateTtl = max(120, (int) ($meta['oauth_state_ttl'] ?? 900));
        Cache::put(
            'meta_oauth_state:'.$oauthState,
            ['user_id' => $userId],
            $stateTtl
        );

        $query = [
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $oauthState,
            'response_type' => 'code',
            'scope' => implode(',', $scopes),
        ];

        $authorizeUrl = $baseUrl.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return redirect()->away($authorizeUrl);
    }

    /**
     * Facebook redirects here with ?code=…&state=… (state = random key bound to user_id in cache).
     * Exchanges the code for tokens, stores a short-lived session key in cache, redirects to CRM.
     */
    public function callback(Request $request): RedirectResponse
    {
        $meta = config('services.meta');
        $channelsUrl = $meta['frontend_url'].'/app/channels';

        if ($request->filled('error')) {
            return redirect()->away($channelsUrl.'?oauth_error=oauth_failed');
        }

        $code = $request->query('code');
        $state = $request->query('state');
        if (! is_string($code) || $code === '' || ! is_string($state) || $state === '') {
            return redirect()->away($channelsUrl.'?oauth_error=missing_code_or_state');
        }

        $statePayload = Cache::pull('meta_oauth_state:'.$state);
        if (! is_array($statePayload) || ! isset($statePayload['user_id']) || ! is_numeric($statePayload['user_id'])) {
            return redirect()->away($channelsUrl.'?oauth_error=session_expired_or_invalid');
        }

        $userId = (int) $statePayload['user_id'];

        $appId = $meta['app_id'] ?? null;
        $appSecret = $meta['app_secret'] ?? null;
        $redirectUri = $meta['redirect_uri'] ?? null;
        if (! is_string($appId) || $appId === ''
            || ! is_string($appSecret) || $appSecret === ''
            || ! is_string($redirectUri) || $redirectUri === '') {
            return redirect()->away($channelsUrl.'?oauth_error=oauth_failed');
        }

        try {
            $short = MetaFacebookOAuthService::shortLivedUserTokenFromCode($code);
            $shortToken = $short['access_token'];
        } catch (\Throwable) {
            return redirect()->away($channelsUrl.'?oauth_error=oauth_failed');
        }

        try {
            $long = MetaFacebookOAuthService::longLivedUserToken($shortToken);
            $accessToken = $long['access_token'];
            $expiresIn = isset($long['expires_in']) && is_numeric($long['expires_in'])
                ? (int) $long['expires_in']
                : null;
        } catch (\Throwable) {
            $accessToken = $shortToken;
            $expiresIn = isset($short['expires_in']) && is_numeric($short['expires_in'])
                ? (int) $short['expires_in']
                : null;
        }

        $sessionKey = Str::random(48);
        $ttlSeconds = max(60, (int) ($meta['oauth_session_ttl'] ?? 600));
        $expiresAt = $expiresIn !== null ? time() + $expiresIn : null;

        Cache::put(
            'meta_oauth:'.$sessionKey,
            [
                'user_id' => $userId,
                'access_token' => $accessToken,
                'expires_at' => $expiresAt,
            ],
            $ttlSeconds
        );

        return redirect()->away($channelsUrl.'?oauth=meta&key='.urlencode($sessionKey));
    }

    /**
     * List Facebook pages and linked Instagram accounts for the cached OAuth session (CRM modal).
     */
    public function selection(Request $request): JsonResponse
    {
        $key = $request->query('key');
        if (! is_string($key) || $key === '') {
            return response()->json(['success' => false, 'message' => 'Mungon çelësi i sesionit.'], 422);
        }

        $session = Cache::get('meta_oauth:'.$key);
        if (! is_array($session)) {
            return response()->json([
                'success' => false,
                'message' => 'Sesioni OAuth ka skaduar. Provoni përsëri.',
            ], 404);
        }

        if ((int) ($session['user_id'] ?? 0) !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Nuk keni qasje.'], 403);
        }

        $userToken = $session['access_token'] ?? null;
        if (! is_string($userToken) || $userToken === '') {
            return response()->json(['success' => false, 'message' => 'Sesioni OAuth është i pavlefshëm.'], 400);
        }

        try {
            $data = MetaFacebookGraphService::fetchPagesAndInstagram($userToken);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Gabim gjatë leximit nga Meta. Provoni përsëri.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Create a channel from a selected page or Instagram account; consumes OAuth session cache entry.
     */
    public function connect(Request $request, ChannelConnectionSyncer $syncer): JsonResponse
    {
        $validated = $request->validate([
            'oauthKey' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:facebook,instagram'],
            'platformPageId' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $oauthKey = $validated['oauthKey'];
        $platform = $validated['platform'];
        $platformPageId = $validated['platformPageId'];
        $displayName = isset($validated['name']) && is_string($validated['name']) ? trim($validated['name']) : '';

        $session = Cache::get('meta_oauth:'.$oauthKey);
        if (! is_array($session)) {
            return response()->json([
                'success' => false,
                'message' => 'Sesioni OAuth ka skaduar. Provoni përsëri.',
            ], 404);
        }

        if ((int) ($session['user_id'] ?? 0) !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Nuk keni qasje.'], 403);
        }

        $userToken = $session['access_token'] ?? null;
        if (! is_string($userToken) || $userToken === '') {
            return response()->json(['success' => false, 'message' => 'Sesioni OAuth është i pavlefshëm.'], 400);
        }

        $exists = Channel::query()
            ->where('user_id', $request->user()->id)
            ->where('platform', $platform)
            ->where('meta_page_id', $platformPageId)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ky kanal është tashmë i lidhur.',
            ], 422);
        }

        try {
            $resolved = MetaFacebookGraphService::resolvePageTokenForConnect($userToken, $platform, $platformPageId);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Gabim gjatë lidhjes me Meta. Provoni përsëri.',
            ], 502);
        }

        if ($resolved === null) {
            return response()->json([
                'success' => false,
                'message' => 'Faqja ose llogaria Instagram nuk u gjet në lidhjen Meta.',
            ], 422);
        }

        $name = $displayName !== '' ? $displayName : $resolved['page_name'];
        if ($name === '') {
            $name = $platform === 'instagram' ? 'Instagram' : 'Facebook';
        }

        $channel = Channel::query()->create([
            'user_id' => $request->user()->id,
            'platform' => $platform,
            'name' => $name,
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => $platformPageId,
            'meta_access_token' => $resolved['access_token'],
        ]);

        Cache::forget('meta_oauth:'.$oauthKey);

        $syncer->sync($channel->fresh());

        return response()->json([
            'success' => true,
            'data' => ChannelCrmPresenter::toArray($channel->fresh(), $request->user()),
        ], 201);
    }
}
