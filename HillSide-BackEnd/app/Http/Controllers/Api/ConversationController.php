<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $channelId = $request->query('channelId');
        $adminUserId = $request->query('userId');

        $query = Conversation::query()
            ->with('channel');

        if ($user->is_admin && $adminUserId) {
            $query->where('user_id', (int) $adminUserId);
        } else {
            $query->where('user_id', $user->id);
        }

        if ($channelId) {
            $query->where('channel_id', (int) $channelId);
        }

        $conversations = $query->get();

        $conversationIds = $conversations->pluck('id');

        $lastMessages = Message::query()
            ->whereIn('conversation_id', $conversationIds)
            ->orderBy('created_at')
            ->get()
            ->groupBy('conversation_id')
            ->map(function ($group) {
                /** @var \Illuminate\Support\Collection<int, Message> $group */
                return $group->last();
            });

        $result = $conversations->map(function (Conversation $conversation) use ($lastMessages) {
            /** @var Channel|null $channel */
            $channel = $conversation->channel;
            /** @var Message|null $lastMessage */
            $lastMessage = $lastMessages->get($conversation->id);

            $lastMessageAt = $lastMessage
                ? ($lastMessage->created_at instanceof Carbon
                    ? $lastMessage->created_at->toIso8601String()
                    : Carbon::parse($lastMessage->created_at)->toIso8601String())
                : null;

            return [
                '_id' => (string) $conversation->id,
                'channelId' => $channel ? [
                    '_id' => (string) $channel->id,
                    'name' => $channel->name,
                    'platform' => $channel->platform,
                ] : (string) $conversation->channel_id,
                'platformUserId' => 'Unknown',
                'platformConversationId' => null,
                'lastMessageAt' => $lastMessageAt,
                'lastUserMessageAt' => $lastMessageAt,
                'metadata' => [],
                'contactId' => null,
                'createdAt' => $conversation->created_at?->toIso8601String(),
                'updatedAt' => $conversation->updated_at?->toIso8601String(),
                'sentimentScore' => null,
                'sentimentLabel' => null,
                'lastSentimentAt' => null,
                'sentimentMessageCount' => null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}

