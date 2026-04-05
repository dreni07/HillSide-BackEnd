<?php

namespace App\Services\Webhooks;

use App\Events\InboundMessageStored;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Contacts\PlatformSenderContactLinker;
use App\Support\ChannelOpsLogger;
use Illuminate\Database\UniqueConstraintViolationException;

class MetaInboundWebhookService
{
    public function __construct(
        private readonly PlatformSenderContactLinker $contactLinker,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handlePayload(array $payload): void
    {
        $object = isset($payload['object']) && is_string($payload['object']) ? $payload['object'] : '';

        match ($object) {
            'page' => $this->handlePageObject($payload),
            'instagram' => $this->handleInstagramObject($payload),
            'whatsapp_business_account' => $this->handleWhatsAppObject($payload),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handlePageObject(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $pageId = isset($entry['id']) ? (string) $entry['id'] : '';
            if ($pageId === '') {
                continue;
            }

            $channel = $this->resolveFacebookChannel($pageId);
            if ($channel === null) {
                ChannelOpsLogger::info('webhook.meta.channel_unresolved', [
                    'operation' => 'meta_webhook_ingress',
                    'platform' => 'facebook',
                    'error_code' => 'channel_not_found_for_page',
                    'page_id' => $pageId,
                ]);

                continue;
            }

            foreach ($entry['messaging'] ?? [] as $event) {
                if (! is_array($event)) {
                    continue;
                }
                $this->handleMessagingEvent($channel, $event);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleInstagramObject(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $igAccountId = isset($entry['id']) ? (string) $entry['id'] : '';
            if ($igAccountId === '') {
                continue;
            }

            $channel = $this->resolveInstagramChannel($igAccountId);
            if ($channel === null) {
                ChannelOpsLogger::info('webhook.meta.channel_unresolved', [
                    'operation' => 'meta_webhook_ingress',
                    'platform' => 'instagram',
                    'error_code' => 'channel_not_found_for_ig_account',
                    'ig_account_id' => $igAccountId,
                ]);

                continue;
            }

            foreach ($entry['messaging'] ?? [] as $event) {
                if (! is_array($event)) {
                    continue;
                }
                $this->handleMessagingEvent($channel, $event);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleWhatsAppObject(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (! is_array($change)) {
                    continue;
                }
                $value = $change['value'] ?? null;
                if (! is_array($value)) {
                    continue;
                }

                $metadata = $value['metadata'] ?? [];
                $phoneNumberId = is_array($metadata) && isset($metadata['phone_number_id'])
                    ? (string) $metadata['phone_number_id']
                    : '';

                if ($phoneNumberId === '') {
                    continue;
                }

                $channel = $this->resolveWhatsAppChannel($phoneNumberId);
                if ($channel === null) {
                    ChannelOpsLogger::info('webhook.meta.channel_unresolved', [
                        'operation' => 'meta_webhook_ingress',
                        'platform' => 'whatsapp',
                        'error_code' => 'channel_not_found_for_phone_number_id',
                        'phone_number_id' => $phoneNumberId,
                    ]);

                    continue;
                }

                foreach ($value['messages'] ?? [] as $msg) {
                    if (! is_array($msg)) {
                        continue;
                    }
                    $this->handleWhatsAppMessage($channel, $msg);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handleMessagingEvent(Channel $channel, array $event): void
    {
        if (! empty($event['message']['is_echo'])) {
            return;
        }

        $message = $event['message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $mid = isset($message['mid']) ? (string) $message['mid'] : '';
        if ($mid === '') {
            return;
        }

        $senderId = isset($event['sender']['id']) ? (string) $event['sender']['id'] : '';
        if ($senderId === '') {
            return;
        }

        $text = $this->extractMessengerText($message);
        if ($text === '') {
            return;
        }

        $this->persistInbound(
            $channel,
            $senderId,
            $mid,
            $text,
            ['source' => 'meta', 'messaging' => $event],
            $this->extractMessengerAttachments($message)
        );
    }

    /**
     * @param  array<string, mixed>  $msg
     */
    private function handleWhatsAppMessage(Channel $channel, array $msg): void
    {
        $id = isset($msg['id']) ? (string) $msg['id'] : '';
        if ($id === '') {
            return;
        }

        $from = isset($msg['from']) ? (string) $msg['from'] : '';
        if ($from === '') {
            return;
        }

        $text = $this->extractWhatsAppText($msg);
        if ($text === '') {
            return;
        }

        $this->persistInbound(
            $channel,
            $from,
            $id,
            $text,
            ['source' => 'whatsapp', 'message' => $msg],
            $this->extractWhatsAppAttachments($msg),
            null,
            $from
        );
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function extractMessengerText(array $message): string
    {
        if (isset($message['text']) && is_string($message['text'])) {
            return $message['text'];
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $msg
     */
    private function extractWhatsAppText(array $msg): string
    {
        $type = isset($msg['type']) && is_string($msg['type']) ? $msg['type'] : '';

        if ($type === 'text') {
            $text = $msg['text'] ?? null;

            return is_array($text) && isset($text['body']) && is_string($text['body']) ? $text['body'] : '';
        }

        if ($type === 'interactive') {
            $interactive = $msg['interactive'] ?? null;
            if (is_array($interactive)) {
                $button = $interactive['button_reply'] ?? null;
                if (is_array($button) && isset($button['title']) && is_string($button['title'])) {
                    return $button['title'];
                }
                $list = $interactive['list_reply'] ?? null;
                if (is_array($list) && isset($list['title']) && is_string($list['title'])) {
                    return $list['title'];
                }
            }
        }

        if ($type === 'button') {
            $button = $msg['button'] ?? null;
            if (is_array($button) && isset($button['text']) && is_string($button['text'])) {
                return $button['text'];
            }
        }

        if (in_array($type, ['image', 'video', 'document', 'audio', 'sticker'], true)) {
            $block = $msg[$type] ?? null;
            if (is_array($block) && isset($block['caption']) && is_string($block['caption']) && $block['caption'] !== '') {
                return $block['caption'];
            }

            return '['.$type.']';
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $message
     * @return list<array<string, mixed>>|null
     */
    private function extractMessengerAttachments(array $message): ?array
    {
        $attachments = $message['attachments'] ?? null;
        if (! is_array($attachments) || $attachments === []) {
            return null;
        }

        $out = [];
        foreach ($attachments as $att) {
            if (! is_array($att)) {
                continue;
            }
            $out[] = [
                'type' => $att['type'] ?? null,
                'payload' => $att['payload'] ?? null,
            ];
        }

        return $out === [] ? null : $out;
    }

    /**
     * @param  array<string, mixed>  $msg
     * @return list<array<string, mixed>>|null
     */
    private function extractWhatsAppAttachments(array $msg): ?array
    {
        $type = isset($msg['type']) && is_string($msg['type']) ? $msg['type'] : '';
        if ($type === '' || $type === 'text' || $type === 'interactive' || $type === 'button') {
            return null;
        }

        $block = $msg[$type] ?? null;
        if (! is_array($block)) {
            return null;
        }

        return [['type' => $type, 'detail' => $block]];
    }

    private function resolveFacebookChannel(string $pageId): ?Channel
    {
        return Channel::query()
            ->where('platform', 'facebook')
            ->where('meta_page_id', $pageId)
            ->first();
    }

    private function resolveInstagramChannel(string $igAccountId): ?Channel
    {
        return Channel::query()
            ->where('platform', 'instagram')
            ->where('meta_page_id', $igAccountId)
            ->first();
    }

    private function resolveWhatsAppChannel(string $phoneNumberId): ?Channel
    {
        return Channel::query()
            ->where('platform', 'whatsapp')
            ->where(function ($q) use ($phoneNumberId) {
                $q->where('whatsapp_phone_number_id', $phoneNumberId)
                    ->orWhere(function ($q2) use ($phoneNumberId) {
                        $q2->whereNull('whatsapp_phone_number_id')
                            ->where('meta_page_id', $phoneNumberId);
                    });
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>|null  $rawPayload
     * @param  list<array<string, mixed>>|null  $attachments
     */
    private function persistInbound(
        Channel $channel,
        string $platformConversationId,
        string $platformMessageId,
        string $text,
        ?array $rawPayload = null,
        ?array $attachments = null,
        ?string $senderDisplayName = null,
        ?string $senderPhone = null,
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
                'attachments' => $attachments,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Duplicate delivery (Meta/Viber retries).

            return;
        }

        $this->contactLinker->link(
            $conversation,
            $channel,
            $platformConversationId,
            $senderDisplayName,
            $senderPhone
        );

        InboundMessageStored::dispatch($message);
    }
}
