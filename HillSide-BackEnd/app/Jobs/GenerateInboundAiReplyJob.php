<?php

namespace App\Jobs;

use App\Models\AiConfig;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\InboundReplyDraftGenerator;
use App\Services\Outbound\OutboundMessageDispatcher;
use App\Services\Outbound\OutboundSendException;
use App\Support\InboxPresenter;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateInboundAiReplyJob implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public int $messageId) {}

    public function uniqueId(): string
    {
        return 'inbound-ai-reply:'.$this->messageId;
    }

    public function handle(
        OutboundMessageDispatcher $outbound,
        InboundReplyDraftGenerator $drafts,
    ): void {
        if (! config('ai.auto_reply.enabled')) {
            return;
        }

        $message = Message::query()
            ->with(['conversation.channel'])
            ->find($this->messageId);

        if ($message === null) {
            return;
        }

        if (! InboxPresenter::isInbound($message)) {
            return;
        }

        if (($message->sender_type ?? 'customer') !== 'customer') {
            return;
        }

        $conversation = $message->conversation;
        if ($conversation === null || ($conversation->status ?? 'open') !== 'open') {
            return;
        }

        $channel = $conversation->channel;
        if ($channel === null || $channel->user_id === null) {
            return;
        }

        $user = User::query()->find($channel->user_id);
        $business = $user?->businesses()->orderBy('id')->first();
        if ($business === null) {
            Log::debug('Inbound AI reply skipped: no business for channel owner', [
                'message_id' => $this->messageId,
                'channel_id' => $channel->id,
            ]);

            return;
        }

        $aiConfig = AiConfig::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->with(['personality'])
            ->first();

        if ($aiConfig === null) {
            Log::debug('Inbound AI reply skipped: no active AI config', [
                'message_id' => $this->messageId,
                'business_id' => $business->id,
            ]);

            return;
        }

        $replyText = $drafts->draftForInbound($aiConfig, $message);
        if (trim($replyText) === '') {
            return;
        }

        try {
            $platformMessageId = $outbound->sendTextReply($channel, $conversation, $replyText);
        } catch (OutboundSendException $e) {
            if ($e->httpStatus >= 500 || $e->errorCode === 'network_error') {
                throw $e;
            }
            Log::warning('Inbound AI reply: outbound failed (no retry)', [
                'message_id' => $this->messageId,
                'error_code' => $e->errorCode,
                'http_status' => $e->httpStatus,
                'exception' => $e->getMessage(),
            ]);

            return;
        }

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'platform_message_id' => $platformMessageId,
            'text' => $replyText,
            'is_from_user' => true,
            'direction' => 'out',
            'sender_type' => 'ai',
            'raw_payload' => null,
            'attachments' => null,
        ]);
    }
}
