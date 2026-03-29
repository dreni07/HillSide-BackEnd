<?php

namespace App\Services\Outbound;

use App\Models\Channel;
use App\Support\ChannelOpsLogger;
use Illuminate\Support\Facades\Http;

/**
 * Facebook Messenger dhe Instagram DM përmes të njëjtit endpoint Graph:
 * POST /me/messages me Page access token (recipient = PSID ose IGSID).
 *
 * @see https://developers.facebook.com/docs/messenger-platform/instagram/features/send-message
 */
class MetaMeMessagesSender
{
    public function sendText(Channel $channel, string $recipientPlatformId, string $text): string
    {
        $token = $channel->meta_access_token;
        if (! is_string($token) || $token === '') {
            ChannelOpsLogger::warning('outbound.meta_token_missing', [
                'operation' => 'meta_graph_send',
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
                'error_code' => 'channel_token_missing',
            ]);

            throw new OutboundSendException(
                'Mungon token-i i aksesit për këtë kanal Meta. Rilidhni kanalin.',
                'channel_token_missing',
                422
            );
        }

        if ($channel->platform !== 'facebook' && $channel->platform !== 'instagram') {
            ChannelOpsLogger::warning('outbound.invalid_channel_platform', [
                'operation' => 'meta_graph_send',
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
                'error_code' => 'invalid_channel_platform',
            ]);

            throw new OutboundSendException('Platforma e kanalit nuk mbështetet për Meta /me/messages.', 'invalid_channel_platform', 422);
        }

        $graphBase = rtrim((string) config('services.meta.graph_base_url', 'https://graph.facebook.com/v21.0'), '/');
        $url = $graphBase.'/me/messages?'.http_build_query(['access_token' => $token], '', '&', PHP_QUERY_RFC3986);

        $body = [
            'recipient' => ['id' => $recipientPlatformId],
            'messaging_type' => 'RESPONSE',
            'message' => ['text' => $text],
        ];

        $response = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->post($url, $body);

        if (! $response->successful()) {
            $summary = MetaGraphSendHelper::summarizeError($response);
            $mapped = MetaGraphSendHelper::mapToOutboundException((int) $channel->id, (string) $channel->platform, $summary);

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
            ChannelOpsLogger::warning('outbound.meta_invalid_response', [
                'operation' => 'meta_graph_send',
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
                'error_code' => 'meta_invalid_response',
            ]);

            throw new OutboundSendException('Meta u përgjigj me trup të pavlefshëm.', 'meta_invalid_response', 502);
        }

        $mid = $data['message_id'] ?? null;
        if (! is_string($mid) || $mid === '') {
            ChannelOpsLogger::warning('outbound.meta_missing_message_id', [
                'operation' => 'meta_graph_send',
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
                'error_code' => 'meta_missing_message_id',
            ]);

            throw new OutboundSendException('Meta nuk ktheu message_id.', 'meta_missing_message_id', 502);
        }

        return $mid;
    }
}
