<?php

namespace App\Services\Outbound;

use App\Models\Channel;
use App\Models\Conversation;
use App\Support\ChannelOpsLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Throwable;

class OutboundMessageDispatcher
{
    public function __construct(
        protected MetaMeMessagesSender $metaMeMessagesSender,
        protected WhatsAppCloudSender $whatsAppCloudSender,
        protected ViberOutboundSender $viberOutboundSender,
    ) {}

    /**
     * @throws OutboundSendException
     */
    public function sendTextReply(Channel $channel, Conversation $conversation, string $text): string
    {
        if (filter_var(config('services.outbound.skip_network', false), FILTER_VALIDATE_BOOLEAN)) {
            Log::info('Outbound send skipped (OUTBOUND_SKIP_NETWORK)', [
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
            ]);

            return 'dev_skip:'.uniqid('', true);
        }

        $recipient = $conversation->platform_conversation_id;
        if (! is_string($recipient) || $recipient === '') {
            throw new OutboundSendException(
                'Biseda nuk ka ID të platformës (mungon platform_conversation_id). S’mund të dërgohet përgjigje.',
                'missing_platform_recipient',
                422
            );
        }

        try {
            return match ($channel->platform) {
                'facebook', 'instagram' => $this->metaMeMessagesSender->sendText($channel, $recipient, $text),
                'whatsapp' => $this->whatsAppCloudSender->sendText($channel, $recipient, $text),
                'viber' => $this->viberOutboundSender->sendText($channel, $recipient, $text),
                default => throw new OutboundSendException(
                    'Platforma e kanalit nuk mbështet për dërgim: '.$channel->platform,
                    'unsupported_platform',
                    422
                ),
            };
        } catch (OutboundSendException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            ChannelOpsLogger::warning('outbound.network_error', [
                'operation' => 'outbound_send',
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
                'error_code' => 'network_error',
                'exception' => $e->getMessage(),
            ]);

            throw new OutboundSendException(
                'Nuk arritëm të lidhemi me platformën. Provoni përsëri.',
                'network_error',
                503,
                null,
                $e
            );
        } catch (Throwable $e) {
            ChannelOpsLogger::error('outbound.unexpected_failure', [
                'operation' => 'outbound_send',
                'channel_id' => $channel->id,
                'platform' => $channel->platform,
                'error_code' => 'unexpected_error',
                'exception' => $e->getMessage(),
            ]);

            throw new OutboundSendException(
                'Gabim i papritur gjatë dërgimit të mesazhit.',
                'unexpected_error',
                500,
                null,
                $e
            );
        }
    }
}
