<?php

namespace App\Services\Channels;

use App\Models\Channel;
use App\Support\ChannelOpsLogger;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ViberWebhookRegistrar
{
    public function register(Channel $channel): void
    {
        if ($channel->platform !== 'viber') {
            throw new RuntimeException('Kanali nuk është Viber.');
        }

        $auth = $channel->meta_access_token;
        if (! is_string($auth) || $auth === '') {
            throw new RuntimeException('Mungon token-i i autentikimit të botit Viber.');
        }

        $base = rtrim((string) config('services.integrations.public_api_url', config('app.url')), '/');
        $webhookUrl = $base.'/api/webhooks/viber';

        $setUrl = rtrim((string) config('services.viber.set_webhook_url', 'https://chatapi.viber.com/pa/set_webhook'), '/');

        $response = Http::timeout(25)
            ->acceptJson()
            ->withHeaders(['X-Viber-Auth-Token' => $auth])
            ->post($setUrl, [
                'url' => $webhookUrl,
            ]);

        if (! $response->successful()) {
            $httpStatus = $response->status();
            ChannelOpsLogger::warning('viber.set_webhook_http_failed', [
                'operation' => 'viber_set_webhook',
                'channel_id' => $channel->id,
                'platform' => 'viber',
                'error_code' => 'viber_set_webhook_http_error',
                'http_status' => $httpStatus,
            ]);

            throw new RuntimeException('Viber set_webhook: HTTP '.$httpStatus);
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Viber set_webhook: përgjigje e pavlefshme');
        }

        $status = $data['status'] ?? null;
        if ($status !== 0 && $status !== '0') {
            $msg = isset($data['status_message']) && is_string($data['status_message'])
                ? $data['status_message']
                : 'Viber refuzoi set_webhook.';

            throw new RuntimeException($msg);
        }

        $channel->viber_webhook_registered_at = now();
        $channel->clearConnectionError();
        $channel->save();
    }
}
