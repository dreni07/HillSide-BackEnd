<?php

namespace App\Services\Webhooks;

use App\Events\InboundMessageStored;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Contacts\PlatformSenderContactLinker;
use App\Support\ChannelOpsLogger;
use Illuminate\Database\UniqueConstraintViolationException;

class ViberInboundWebhookService
{
    public function __construct(
        private readonly PlatformSenderContactLinker $contactLinker,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handlePayload(Channel $channel, array $payload): void
    {
        $event = isset($payload['event']) && is_string($payload['event']) ? $payload['event'] : '';

        if ($event !== 'message') {
            return;
        }

        $message = $payload['message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $messageToken = $payload['message_token'] ?? null;
        $platformMessageId = $messageToken !== null ? (string) $messageToken : '';
        if ($platformMessageId === '') {
            return;
        }

        $sender = $payload['sender'] ?? null;
        if (! is_array($sender) || empty($sender['id'])) {
            return;
        }

        $platformConversationId = (string) $sender['id'];

        $text = $this->extractText($message);
        if ($text === '') {
            return;
        }

        $senderName = isset($sender['name']) && is_string($sender['name']) ? trim($sender['name']) : null;
        if ($senderName === '') {
            $senderName = null;
        }

        $this->persistInbound(
            $channel,
            $platformConversationId,
            $platformMessageId,
            $text,
            [
                'source' => 'viber',
                'message_token' => $payload['message_token'] ?? null,
                'sender' => $payload['sender'] ?? null,
                'message' => $message,
            ],
            $senderName
        );
    }

    public function resolveChannelFromPayload(array $payload): ?Channel
    {
        $receiver = $payload['receiver'] ?? null;
        if (! is_string($receiver) || $receiver === '') {
            return null;
        }

        return Channel::query()
            ->where('platform', 'viber')
            ->where('viber_bot_id', $receiver)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function extractText(array $message): string
    {
        $type = isset($message['type']) && is_string($message['type']) ? $message['type'] : '';

        if ($type === 'text' && isset($message['text']) && is_string($message['text'])) {
            return $message['text'];
        }

        if ($type !== '' && $type !== 'text') {
            ChannelOpsLogger::info('webhook.viber.skipped_non_text', [
                'operation' => 'viber_webhook_ingress',
                'platform' => 'viber',
                'error_code' => 'non_text_message_skipped',
                'message_type' => $type,
            ]);
        }

        return '';
    }

    /**
     * @param  array<string, mixed>|null  $rawPayload
     */
    private function persistInbound(
        Channel $channel,
        string $platformConversationId,
        string $platformMessageId,
        string $text,
        ?array $rawPayload = null,
        ?string $senderDisplayName = null,
    ): void {
        $conversation = Conversation::query()->firstOrCreate(
            [
                'channel_id' => $channel->id,
                'platform_conversation_id' => $platformConversationId,
            ],
            [
                'user_id' => $channel->user_id,
                'title' => null,
                'status' => 'open',
            ]
        );

        try {
            $message = Message::query()->create([
                'conversation_id' => $conversation->id,
                'platform_message_id' => $platformMessageId,
                'text' => $text,
                'is_from_user' => false,
                'direction' => 'in',
                'sender_type' => 'customer',
                'raw_payload' => $rawPayload,
                'attachments' => null,
            ]);
        } catch (UniqueConstraintViolationException) {
            return;
        }

        $this->contactLinker->link(
            $conversation,
            $channel,
            $platformConversationId,
            $senderDisplayName,
            null
        );

        InboundMessageStored::dispatch($message);
    }
}
