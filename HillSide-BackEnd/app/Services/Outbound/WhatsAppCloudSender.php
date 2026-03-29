<?php

namespace App\Services\Outbound;

use App\Models\Channel;
use App\Support\ChannelOpsLogger;
use Illuminate\Support\Facades\Http;

class WhatsAppCloudSender
{
    public function sendText(Channel $channel, string $toWaId, string $text): string
    {
        $token = $channel->meta_access_token;
        if (! is_string($token) || $token === '') {
            ChannelOpsLogger::warning('outbound.whatsapp_token_missing', [
                'operation' => 'whatsapp_send',
                'channel_id' => $channel->id,
                'platform' => 'whatsapp',
                'error_code' => 'channel_token_missing',
            ]);

            throw new OutboundSendException(
                'Mungon token-i i aksesit për WhatsApp. Kontrolloni kanalin.',
                'channel_token_missing',
                422
            );
        }

        $phoneNumberId = $channel->whatsapp_phone_number_id;
        if (! is_string($phoneNumberId) || $phoneNumberId === '') {
            $phoneNumberId = $channel->meta_page_id;
        }
        if (! is_string($phoneNumberId) || $phoneNumberId === '') {
            ChannelOpsLogger::warning('outbound.whatsapp_phone_number_id_missing', [
                'operation' => 'whatsapp_send',
                'channel_id' => $channel->id,
                'platform' => 'whatsapp',
                'error_code' => 'whatsapp_phone_number_id_missing',
            ]);

            throw new OutboundSendException(
                'Mungon Phone Number ID për WhatsApp. Shtoni whatsappPhoneNumberId te kanali.',
                'whatsapp_phone_number_id_missing',
                422
            );
        }

        $graphBase = rtrim((string) config('services.meta.graph_base_url', 'https://graph.facebook.com/v21.0'), '/');
        $url = $graphBase.'/'.$phoneNumberId.'/messages';

        $response = Http::timeout(35)
            ->acceptJson()
            ->withToken($token)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $toWaId,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $text,
                ],
            ]);

        if (! $response->successful()) {
            $summary = MetaGraphSendHelper::summarizeError($response);
            $mapped = MetaGraphSendHelper::mapToOutboundException((int) $channel->id, 'whatsapp', $summary);

            if ($mapped['errorCode'] === 'channel_token_invalid') {
                $channel->recordConnectionFailure($summary['message'], 'channel_token_invalid');
            }

            throw new OutboundSendException(
                $mapped['userMessage'],
                $mapped['errorCode'],
                $mapped['httpStatus'],
                [
                    'graph_code' => $summary['code'],
                    'graph_subcode' => $summary['subcode'],
                    'graph_type' => $summary['type'],
                ]
            );
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new OutboundSendException('WhatsApp u përgjigj me trup të pavlefshëm.', 'whatsapp_invalid_response', 502);
        }

        $messages = $data['messages'] ?? null;
        if (! is_array($messages) || $messages === [] || ! is_array($messages[0])) {
            ChannelOpsLogger::warning('outbound.whatsapp_unexpected_body', [
                'operation' => 'whatsapp_send',
                'channel_id' => $channel->id,
                'platform' => 'whatsapp',
                'error_code' => 'whatsapp_missing_message_id',
            ]);

            throw new OutboundSendException('WhatsApp nuk ktheu messages[].id.', 'whatsapp_missing_message_id', 502);
        }

        $id = $messages[0]['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new OutboundSendException('WhatsApp nuk ktheu id të mesazhit.', 'whatsapp_missing_message_id', 502);
        }

        return $id;
    }
}
