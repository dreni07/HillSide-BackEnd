<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Webhooks\ViberInboundWebhookService;
use App\Services\Webhooks\ViberWebhookSignature;
use App\Support\ChannelOpsLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ViberWebhookController extends Controller
{
    public function handle(Request $request, ViberInboundWebhookService $ingress): Response
    {
        $raw = $request->getContent();

        /** @var mixed $decoded */
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            ChannelOpsLogger::warning('webhook.viber.body_invalid_json', [
                'operation' => 'viber_webhook_receive',
                'platform' => 'viber',
                'error_code' => 'invalid_json_body',
            ]);

            return response('', 400);
        }

        $channel = $ingress->resolveChannelFromPayload($decoded);
        if ($channel === null) {
            ChannelOpsLogger::info('webhook.viber.channel_unresolved', [
                'operation' => 'viber_webhook_receive',
                'platform' => 'viber',
                'error_code' => 'channel_not_found_for_receiver',
            ]);

            return response()->json(['status' => 0]);
        }

        $authToken = $channel->meta_access_token;
        if (! is_string($authToken) || $authToken === '') {
            ChannelOpsLogger::warning('webhook.viber.bot_token_missing', [
                'operation' => 'viber_webhook_receive',
                'channel_id' => $channel->id,
                'platform' => 'viber',
                'error_code' => 'channel_token_missing',
            ]);

            return response('', 403);
        }

        $signature = $request->header('X-Viber-Content-Signature');
        if (! ViberWebhookSignature::isValid($authToken, $raw, $signature)) {
            ChannelOpsLogger::warning('webhook.viber.signature_invalid', [
                'operation' => 'viber_webhook_receive',
                'channel_id' => $channel->id,
                'platform' => 'viber',
                'error_code' => 'invalid_signature',
            ]);

            return response('', 403);
        }

        $ingress->handlePayload($channel, $decoded);

        return response()->json(['status' => 0]);
    }
}
