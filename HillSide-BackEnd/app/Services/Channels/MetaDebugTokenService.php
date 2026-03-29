<?php

namespace App\Services\Channels;

use App\Models\Channel;
use App\Support\ChannelOpsLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class MetaDebugTokenService
{
    /**
     * Lexon skadimin e token-it nga Graph debug_token dhe e ruan te kanali.
     */
    public function syncExpiry(Channel $channel): void
    {
        if (! in_array($channel->platform, ['facebook', 'instagram', 'whatsapp'], true)) {
            return;
        }

        $token = $channel->meta_access_token;
        if (! is_string($token) || $token === '') {
            return;
        }

        $appId = config('services.meta.app_id');
        $appSecret = config('services.meta.app_secret');
        if (! is_string($appId) || $appId === '' || ! is_string($appSecret) || $appSecret === '') {
            ChannelOpsLogger::info('meta.debug_token_skipped', [
                'operation' => 'meta_debug_token',
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
                'error_code' => 'meta_app_credentials_missing',
            ]);

            return;
        }

        $graphBase = rtrim((string) config('services.meta.graph_base_url', 'https://graph.facebook.com/v21.0'), '/');
        $appAccessToken = $appId.'|'.$appSecret;

        $response = Http::timeout(20)->get($graphBase.'/debug_token', [
            'input_token' => $token,
            'access_token' => $appAccessToken,
        ]);

        if (! $response->successful()) {
            $httpStatus = $response->status();
            ChannelOpsLogger::warning('meta.debug_token_http_failed', [
                'operation' => 'meta_debug_token',
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
                'error_code' => 'meta_debug_token_http_error',
                'http_status' => $httpStatus,
            ]);
            $channel->recordConnectionFailure('Meta debug_token: HTTP '.$httpStatus, 'meta_debug_token_http_error');

            return;
        }

        $json = $response->json();
        if (! is_array($json) || ! isset($json['data']) || ! is_array($json['data'])) {
            ChannelOpsLogger::warning('meta.debug_token_invalid_response', [
                'operation' => 'meta_debug_token',
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
                'error_code' => 'meta_debug_token_invalid_response',
            ]);
            $channel->recordConnectionFailure('Meta debug_token: përgjigje e pavlefshme', 'meta_debug_token_invalid_response');

            return;
        }

        $data = $json['data'];
        $isValid = $data['is_valid'] ?? true;
        $expiresAtUnix = $data['expires_at'] ?? null;

        if ($isValid === false) {
            $msg = isset($data['message']) && is_string($data['message']) ? $data['message'] : 'Token i pavlefshëm (Meta debug_token).';
            ChannelOpsLogger::warning('meta.debug_token_invalid_token', [
                'operation' => 'meta_debug_token',
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
                'error_code' => 'meta_token_invalid_debug',
                'provider_message' => $msg,
            ]);
            $channel->forceFill(['meta_token_expires_at' => null])->save();
            $channel->recordConnectionFailure($msg, 'meta_token_invalid_debug');

            return;
        }

        $expiresAt = (is_numeric($expiresAtUnix) && (int) $expiresAtUnix > 0)
            ? Carbon::createFromTimestamp((int) $expiresAtUnix)
            : null;

        $channel->forceFill([
            'meta_token_expires_at' => $expiresAt,
            'connection_error' => null,
            'connection_error_code' => null,
            'connection_error_at' => null,
        ])->save();
    }
}
