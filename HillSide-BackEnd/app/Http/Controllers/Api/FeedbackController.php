<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Models\Conversation;
use App\Models\Feedback;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function showByConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $feedback = Feedback::where('conversation_id', $conversation->id)->get();

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ]);
    }

    public function coachingSummary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $feedback = Feedback::whereHas('conversation', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();

        $summary = [
            'count' => $feedback->count(),
        ];

        $goodExamples = $feedback->where('sentiment', 'positive')->values();
        $badExamples = $feedback->where('sentiment', 'negative')->values();

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'goodExamples' => $goodExamples,
            'badExamples' => $badExamples,
        ]);
    }

    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        $feedbackQuery = Feedback::query()
            ->with(['message', 'conversation.channel'])
            ->whereHas('conversation', function (Builder $q) use ($user) {
                if ($user->is_admin) {
                    // Adminët shohin gjithë feedback-un.
                    return;
                }

                $q->where('user_id', $user->id);
            });

        $grouped = $feedbackQuery->get()
            ->groupBy(function (Feedback $fb) {
                return $fb->conversation_id . ':' . ($fb->message_id ?? 'null');
            });

        $items = [];

        foreach ($grouped as $key => $group) {
            /** @var \Illuminate\Support\Collection<int, Feedback> $group */
            $first = $group->first();
            if (!$first) {
                continue;
            }

            $conversation = $first->conversation;
            $message = $first->message;
            $channel = $conversation?->channel;

            $likes = $group->where('rating', '>=', 4)->count();
            $dislikes = $group->where('rating', '<=', 2)->count();
            $avgRating = $group->avg('rating');
            $lastFeedbackAt = optional($group->max('created_at'))->toIso8601String();

            $items[] = [
                'conversationId' => (string) $first->conversation_id,
                'messageId' => (string) ($first->message_id ?? ''),
                'feedbackCount' => $group->count(),
                'dislikes' => $dislikes,
                'likes' => $likes,
                'avgRating' => $avgRating,
                'lastFeedbackAt' => $lastFeedbackAt,
                'message' => $message ? [
                    'content' => [
                        'text' => $message->text,
                    ],
                    'timestamp' => optional($message->created_at)->toIso8601String(),
                    'senderType' => $message->is_from_user ? 'customer' : 'human_agent',
                    'direction' => $message->is_from_user ? 'in' : 'out',
                    'sentimentScore' => null,
                    'sentimentLabel' => null,
                    'sentimentProvider' => null,
                ] : null,
                'conversation' => $conversation ? [
                    'platformUserId' => 'Unknown',
                    'channel' => $channel ? [
                        '_id' => (string) $channel->id,
                        'name' => $channel->name,
                        'platform' => $channel->platform,
                    ] : null,
                ] : null,
            ];
        }

        // Rradhit sipas feedback-ut më të fundit.
        usort($items, function (array $a, array $b) {
            return strcmp($b['lastFeedbackAt'] ?? '', $a['lastFeedbackAt'] ?? '');
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(StoreFeedbackRequest $request): JsonResponse
    {
        $data = $request->validated();

        $feedback = Feedback::create([
            'conversation_id' => $data['conversationId'],
            'message_id' => $data['messageId'] ?? null,
            'sentiment' => $data['sentiment'] ?? null,
            'reason_category' => $data['reasonCategory'] ?? null,
            'rating' => $data['rating'] ?? null,
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ], 201);
    }

    protected function authorizeConversation(Request $request, Conversation $conversation): void
    {
        if (!$request->user()->is_admin && $conversation->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}

