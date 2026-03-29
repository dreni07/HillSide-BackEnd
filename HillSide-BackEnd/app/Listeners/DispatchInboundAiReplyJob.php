<?php

namespace App\Listeners;

use App\Events\InboundMessageStored;
use App\Jobs\GenerateInboundAiReplyJob;

class DispatchInboundAiReplyJob
{
    public function handle(InboundMessageStored $event): void
    {
        if (! config('ai.auto_reply.enabled')) {
            return;
        }

        GenerateInboundAiReplyJob::dispatch($event->message->id);
    }
}
