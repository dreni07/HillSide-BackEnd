<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Outbound\OutboundMessageDispatcher;
use App\Services\Outbound\OutboundSendException;
use App\Support\InboxPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationMessageController extends Controller
{
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        return response()->json([
            'success' => true,
            'data' => InboxPresenter::threadPayload($conversation),
        ]);
    }

    public function store(
        StoreMessageRequest $request,
        Conversation $conversation,
        OutboundMessageDispatcher $outbound
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation);

        $validated = $request->validated();
        $senderType = isset($validated['sender_type']) && is_string($validated['sender_type'])
            ? $validated['sender_type']
            : 'human_agent';

        $conversation->loadMissing('channel');
        $channel = $conversation->channel;
        if ($channel === null) {
            return response()->json([
                'success' => false,
                'message' => 'Biseda nuk ka kanal të lidhur.',
                'errorCode' => 'missing_channel',
            ], 422);
        }

        try {
            $platformMessageId = $outbound->sendTextReply($channel, $conversation, $validated['text']);
        } catch (OutboundSendException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errorCode' => $e->errorCode,
                'provider' => $e->provider,
            ], $e->httpStatus);
        }

        $message = new Message([
            'text' => $validated['text'],
            'is_from_user' => true,
            'direction' => 'out',
            'sender_type' => $senderType,
            'platform_message_id' => $platformMessageId,
        ]);
        $message->conversation()->associate($conversation);
        $message->user()->associate($request->user());
        $message->save();

        $saved = $message->fresh() ?? $message;

        return response()->json([
            'success' => true,
            'data' => InboxPresenter::messageToArray($saved),
        ], 201);
    }

    protected function authorizeConversation(Request $request, Conversation $conversation): void
    {
        if (! $request->user()->is_admin && $conversation->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
