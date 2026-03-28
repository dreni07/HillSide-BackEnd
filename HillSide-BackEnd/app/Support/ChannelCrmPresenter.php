<?php

namespace App\Support;

use App\Models\Channel;

class ChannelCrmPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Channel $channel): array
    {
        return [
            '_id' => (string) $channel->id,
            'userId' => (string) $channel->user_id,
            'platform' => $channel->platform,
            'platformPageId' => $channel->meta_page_id,
            'viberBotId' => $channel->viber_bot_id,
            'webhookVerifyToken' => $channel->webhook_verify_token,
            'status' => $channel->status,
            'tokenStatus' => 'valid',
            'name' => $channel->name,
            'aiInstructions' => $channel->ai_instructions ?? '',
            'createdAt' => $channel->created_at?->toIso8601String() ?? '',
            'updatedAt' => $channel->updated_at?->toIso8601String() ?? '',
        ];
    }
}
