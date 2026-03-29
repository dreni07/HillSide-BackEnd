<?php

namespace App\Support;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InboxPresenter
{
    public static function messageDirection(Message $message): string
    {
        if ($message->direction === 'in' || $message->direction === 'out') {
            return $message->direction;
        }

        return $message->is_from_user ? 'out' : 'in';
    }

    public static function isInbound(Message $message): bool
    {
        return self::messageDirection($message) === 'in';
    }

    /**
     * @return array<string, mixed>
     */
    public static function messageToArray(Message $message): array
    {
        $direction = self::messageDirection($message);

        return [
            '_id' => (string) $message->id,
            'conversationId' => (string) $message->conversation_id,
            'direction' => $direction,
            'content' => [
                'text' => $message->text,
            ],
            'timestamp' => $message->created_at instanceof Carbon
                ? $message->created_at->toIso8601String()
                : Carbon::parse($message->created_at)->toIso8601String(),
            'platformMessageId' => $message->platform_message_id,
            'senderType' => $message->sender_type,
            'sentimentScore' => null,
            'sentimentLabel' => null,
            'sentimentProvider' => null,
            'sentimentAnalyzedAt' => null,
            'attachments' => $message->attachments,
            'rawPayload' => $message->raw_payload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function conversationToArray(Conversation $conversation, ?Message $lastMessage, ?Message $lastInboundMessage): array
    {
        $conversation->loadMissing(['channel', 'contact']);
        /** @var Channel|null $channel */
        $channel = $conversation->channel;
        /** @var Contact|null $contact */
        $contact = $conversation->contact;

        $lastMessageAt = $lastMessage
            ? ($lastMessage->created_at instanceof Carbon
                ? $lastMessage->created_at->toIso8601String()
                : Carbon::parse($lastMessage->created_at)->toIso8601String())
            : null;

        $lastUserMessageAt = $lastInboundMessage
            ? ($lastInboundMessage->created_at instanceof Carbon
                ? $lastInboundMessage->created_at->toIso8601String()
                : Carbon::parse($lastInboundMessage->created_at)->toIso8601String())
            : null;

        $metadata = $conversation->metadata;
        if (! is_array($metadata)) {
            $metadata = [];
        }

        $contactPayload = null;
        if ($contact !== null) {
            $contactPayload = [
                '_id' => (string) $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
            ];
        }

        return [
            '_id' => (string) $conversation->id,
            'channelId' => $channel ? [
                '_id' => (string) $channel->id,
                'name' => $channel->name,
                'platform' => $channel->platform,
            ] : (string) $conversation->channel_id,
            'title' => $conversation->title,
            'status' => $conversation->status,
            'platformUserId' => $conversation->platform_conversation_id ?? 'Unknown',
            'platformConversationId' => $conversation->platform_conversation_id,
            'lastMessageAt' => $lastMessageAt,
            'lastUserMessageAt' => $lastUserMessageAt,
            'metadata' => $metadata,
            'contactId' => $contactPayload,
            'createdAt' => $conversation->created_at?->toIso8601String() ?? '',
            'updatedAt' => $conversation->updated_at?->toIso8601String() ?? '',
            'sentimentScore' => null,
            'sentimentLabel' => null,
            'lastSentimentAt' => null,
            'sentimentMessageCount' => null,
        ];
    }

    /**
     * @param  Collection<int, Message>  $messagesForConversation
     * @return array{last: ?Message, lastInbound: ?Message}
     */
    public static function pickLastMessages(Collection $messagesForConversation): array
    {
        if ($messagesForConversation->isEmpty()) {
            return ['last' => null, 'lastInbound' => null];
        }

        $last = $messagesForConversation->last();

        $lastInbound = $messagesForConversation
            ->filter(fn (Message $m) => self::isInbound($m))
            ->last();

        return [
            'last' => $last instanceof Message ? $last : null,
            'lastInbound' => $lastInbound instanceof Message ? $lastInbound : null,
        ];
    }

    /**
     * @return array{conversation: array<string, mixed>, messages: list<array<string, mixed>>}
     */
    public static function threadPayload(Conversation $conversation): array
    {
        $messages = $conversation->messages()->orderBy('created_at')->get();
        $picked = self::pickLastMessages($messages);

        $conv = self::conversationToArray($conversation, $picked['last'], $picked['lastInbound']);

        return [
            'conversation' => $conv,
            'messages' => $messages
                ->map(fn (Message $m) => self::messageToArray($m))
                ->values()
                ->all(),
        ];
    }
}
