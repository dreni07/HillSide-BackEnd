<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Support\InboxPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $channelId = $request->query('channelId');
        $adminUserId = $request->query('userId');

        $query = Conversation::query()
            ->with(['channel', 'contact']);

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

        $messagesByConversation = Message::query()
            ->whereIn('conversation_id', $conversationIds)
            ->orderBy('created_at')
            ->get()
            ->groupBy('conversation_id');

        $result = $conversations->map(function (Conversation $conversation) use ($messagesByConversation) {
            $messages = $messagesByConversation->get($conversation->id) ?? collect();
            $picked = InboxPresenter::pickLastMessages($messages);

            return InboxPresenter::conversationToArray(
                $conversation,
                $picked['last'],
                $picked['lastInbound']
            );
        });

        $sorted = $result
            ->sortByDesc(function (array $row) {
                $at = $row['lastMessageAt'] ?? null;

                return is_string($at) && $at !== '' ? $at : '1970-01-01T00:00:00+00:00';
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $sorted,
        ]);
    }
}
