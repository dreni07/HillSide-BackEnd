<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Models\Conversation;
use App\Models\Feedback;
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

