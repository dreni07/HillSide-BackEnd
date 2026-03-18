<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationMessageController extends Controller
{
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $message = new Message([
            'text' => $request->validated()['text'],
            'is_from_user' => true,
        ]);
        $message->conversation()->associate($conversation);
        $message->user()->associate($request->user());
        $message->save();

        return response()->json([
            'success' => true,
            'data' => $message,
        ], 201);
    }

    protected function authorizeConversation(Request $request, Conversation $conversation): void
    {
        if (!$request->user()->is_admin && $conversation->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}

