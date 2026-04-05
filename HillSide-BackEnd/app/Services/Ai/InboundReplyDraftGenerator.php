<?php

namespace App\Services\Ai;

use App\Models\AiConfig;
use App\Models\Message;

/**
 * Minimal draft for auto-replies. Swap in LLM / sales orchestration using {@see AiConfig} and thread context.
 */
class InboundReplyDraftGenerator
{
    public function draftForInbound(AiConfig $config, Message $inboundMessage): string
    {
        $config->loadMissing('personality');

        $greeting = trim((string) ($config->personality?->greeting_message ?? ''));
        if ($greeting !== '') {
            return $greeting;
        }

        $instructions = trim((string) ($config->personality?->custom_instructions ?? ''));
        if ($instructions !== '') {
            return mb_substr($instructions, 0, 500);
        }

        return 'Thanks for your message—we will get back to you shortly.';
    }
}
