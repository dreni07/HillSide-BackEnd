<?php

namespace App\Support;

use App\Models\Channel;
use App\Models\User;

class ChannelCrmPresenter
{
    /**
     * valid | expiring_soon | expired | unknown | needs_reconnect
     */
    public static function tokenStatus(Channel $channel): string
    {
        if ($channel->connection_error_at && $channel->connection_error
            && $channel->connection_error_at->greaterThan(now()->subDays(14))) {
            return 'needs_reconnect';
        }

        if ($channel->platform === 'viber') {
            return $channel->viber_webhook_registered_at ? 'valid' : 'unknown';
        }

        if (! in_array($channel->platform, ['facebook', 'instagram', 'whatsapp'], true)) {
            return 'unknown';
        }

        if ($channel->meta_token_expires_at === null) {
            return 'unknown';
        }

        if ($channel->meta_token_expires_at->isPast()) {
            return 'expired';
        }

        if ($channel->meta_token_expires_at->lessThan(now()->addDays(7))) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(Channel $channel, ?User $viewer = null): array
    {
        $canManageCredentials = $viewer === null || (int) $viewer->id === (int) $channel->user_id;

        return [
            '_id' => (string) $channel->id,
            'userId' => (string) $channel->user_id,
            'platform' => $channel->platform,
            'platformPageId' => $channel->meta_page_id,
            'whatsappPhoneNumberId' => $channel->whatsapp_phone_number_id,
            'whatsappBusinessAccountId' => $channel->whatsapp_business_account_id,
            'whatsappDisplayPhoneNumber' => $channel->whatsapp_display_phone_number,
            'viberBotId' => $channel->viber_bot_id,
            'hasWebhookVerifyToken' => $channel->webhook_verify_token !== null && $channel->webhook_verify_token !== '',
            'viberWebhookRegisteredAt' => $channel->viber_webhook_registered_at?->toIso8601String(),
            'metaTokenExpiresAt' => $channel->meta_token_expires_at?->toIso8601String(),
            'connectionError' => $channel->connection_error,
            'connectionErrorCode' => $channel->connection_error_code,
            'connectionErrorAt' => $channel->connection_error_at?->toIso8601String(),
            'status' => $channel->status,
            'tokenStatus' => self::tokenStatus($channel),
            'name' => $channel->name,
            'aiInstructions' => $channel->ai_instructions ?? '',
            'createdAt' => $channel->created_at?->toIso8601String() ?? '',
            'updatedAt' => $channel->updated_at?->toIso8601String() ?? '',
            'canManageCredentials' => $canManageCredentials,
        ];
    }
}
