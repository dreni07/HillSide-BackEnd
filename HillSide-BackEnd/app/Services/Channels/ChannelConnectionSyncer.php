<?php

namespace App\Services\Channels;

use App\Models\Channel;
use App\Services\Channels\MetaDebugTokenService as MetaTokenExpirySyncer;
use App\Services\Channels\ViberWebhookRegistrar as ViberWebhookSetup;

class ChannelConnectionSyncer
{
    public function __construct(
        protected MetaTokenExpirySyncer $metaDebugTokenService,
        protected ViberWebhookSetup $viberWebhookRegistrar,
    ) {}

    /**
     * @return list<string> paralajmërime (p.sh. Viber dështoi por kanali u krijua)
     */
    public function sync(Channel $channel): array
    {
        $warnings = [];

        if ($channel->platform === 'viber') {
            try {
                $this->viberWebhookRegistrar->register($channel);
            } catch (\Throwable $e) {
                $channel->recordConnectionFailure($e->getMessage(), 'viber_webhook_register_failed');
                $warnings[] = 'Webhook Viber: '.$e->getMessage();
            }

            return $warnings;
        }

        if (in_array($channel->platform, ['facebook', 'instagram', 'whatsapp'], true)) {
            $this->metaDebugTokenService->syncExpiry($channel->fresh());
        }

        return $warnings;
    }
}
