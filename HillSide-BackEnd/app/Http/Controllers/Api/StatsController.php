<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        $from = $request->query('from');
        $to = $request->query('to');
        $channelId = $request->query('channelId');
        $adminUserId = $request->query('userId');

        $ownerUserId = $user->is_admin && $adminUserId ? (int) $adminUserId : $user->id;

        $conversationQuery = Conversation::query()
            ->where('user_id', $ownerUserId);

        if ($channelId) {
            $conversationQuery->where('channel_id', (int) $channelId);
        }

        $conversationIds = $conversationQuery->pluck('id');

        $messagesQuery = Message::query()
            ->whereIn('conversation_id', $conversationIds);

        if ($from) {
            $fromTime = Carbon::parse($from);
            $messagesQuery->where('created_at', '>=', $fromTime);
        }

        if ($to) {
            $toTime = Carbon::parse($to);
            $messagesQuery->where('created_at', '<=', $toTime);
        }

        $messages = $messagesQuery->get();

        $messagesIn = $messages->where('is_from_user', true)->count();
        $messagesOut = $messages->where('is_from_user', false)->count();

        $messagesByDay = [];
        foreach ($messages as $message) {
            /** @var \Illuminate\Support\Carbon $createdAt */
            $createdAt = $message->created_at instanceof Carbon
                ? $message->created_at
                : Carbon::parse($message->created_at);

            $dateKey = $createdAt->toDateString();
            if (!isset($messagesByDay[$dateKey])) {
                $messagesByDay[$dateKey] = ['date' => $dateKey, 'in' => 0, 'out' => 0];
            }

            if ($message->is_from_user) {
                $messagesByDay[$dateKey]['in']++;
            } else {
                $messagesByDay[$dateKey]['out']++;
            }
        }

        ksort($messagesByDay);

        $response = [
            'success' => true,
            'data' => [
                'messagesIn' => $messagesIn,
                'messagesOut' => $messagesOut,
                'conversationsCount' => $conversationIds->count(),
                'avgResponseTimeMinutes' => null,
                'messagesByDay' => array_values($messagesByDay),
                'workHoursStart' => null,
                'workHoursEnd' => null,
                'sentiment' => [
                    'enabled' => false,
                    'avgScore' => null,
                    'distribution' => [
                        'negative' => 0,
                        'neutral' => 0,
                        'positive' => 0,
                        'mixed' => 0,
                    ],
                    'byDay' => [],
                    'business' => null,
                ],
            ],
        ];

        return response()->json($response);
    }
}

