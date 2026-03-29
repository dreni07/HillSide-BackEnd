<?php

namespace App\Services\Outbound;

use App\Models\Channel;
use App\Support\ChannelOpsLogger;
use Illuminate\Support\Facades\Http;

class ViberOutboundSender
{
    public function sendText(Channel $channel, string $receiverUserId, string $text): string
    {
        $token = $channel->meta_access_token;
        if (! is_string($token) || $token === '') {
            ChannelOpsLogger::warning('outbound.viber_token_missing', [
                'operation' => 'viber_send',
                'channel_id' => $channel->id,
                'platform' => 'viber',
                'error_code' => 'channel_token_missing',
            ]);

            throw new OutboundSendException(
                'Mungon token-i i autentikimit të botit Viber.',
                'channel_token_missing',
                422
            );
        }

        $url = rtrim((string) config('services.viber.send_url', 'https://chatapi.viber.com/pa/send_message'), '/');

        $response = Http::timeout(25)
            ->acceptJson()
            ->withHeaders(['X-Viber-Auth-Token' => $token])
            ->post($url, [
                'receiver' => $receiverUserId,
                'type' => 'text',
                'text' => $text,
            ]);

        if (! $response->successful()) {
            $httpStatus = $response->status();
            $errorCode = in_array($httpStatus, [401, 403], true) ? 'viber_token_invalid' : 'viber_http_error';

            ChannelOpsLogger::warning('outbound.viber_http_failed', [
                'operation' => 'viber_send',
                'channel_id' => $channel->id,
                'platform' => 'viber',
                'error_code' => $errorCode,
                'http_status' => $httpStatus,
            ]);

            if (in_array($httpStatus, [401, 403], true)) {
                $channel->recordConnectionFailure(
                    'Viber HTTP '.$httpStatus.': token i pavlefshëm ose i ndaluar.',
                    'viber_token_invalid'
                );
            }

            throw new OutboundSendException(
                'Dërgimi në Viber dështoi (HTTP '.$httpStatus.').',
                'viber_http_error',
                502,
                ['status' => $httpStatus]
            );
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new OutboundSendException('Viber u përgjigj me trup të pavlefshëm.', 'viber_invalid_response', 502);
        }

        $status = $data['status'] ?? null;
        if ($status !== 0 && $status !== '0') {
            $msg = isset($data['status_message']) && is_string($data['status_message'])
                ? $data['status_message']
                : 'Viber refuzoi dërgimin.';

            ChannelOpsLogger::warning('outbound.viber_provider_rejected', [
                'operation' => 'viber_send',
                'channel_id' => $channel->id,
                'platform' => 'viber',
                'error_code' => 'viber_provider_error',
                'viber_status' => $status,
                'provider_message' => $msg,
            ]);

            throw new OutboundSendException($msg, 'viber_provider_error', 422, $data);
        }

        $messageToken = $data['message_token'] ?? null;

        return $messageToken !== null ? (string) $messageToken : 'viber:'.uniqid('', true);
    }
}
