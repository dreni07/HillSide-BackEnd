<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Webhooks\MetaInboundWebhookService;
use App\Services\Webhooks\MetaWebhookSignature;
use App\Support\ChannelOpsLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MetaWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub.mode') ?? $request->query('hub_mode');
        $token = $request->query('hub.verify_token') ?? $request->query('hub_verify_token');
        $challenge = $request->query('hub.challenge') ?? $request->query('hub_challenge');

        $expected = config('services.meta.webhook_verify_token');
        if (! is_string($expected) || $expected === '') {
            abort(503, 'Webhook verify token not configured');
        }

        if ($mode === 'subscribe' && $token === $expected && is_string($challenge) && $challenge !== '') {
            return response($challenge, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request, MetaInboundWebhookService $ingress): Response
    {
        $raw = $request->getContent();

        $meta = config('services.meta');
        $skipSignature = filter_var($meta['webhook_skip_signature'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $appSecret = $meta['app_secret'] ?? null;

        if (! $skipSignature) {
            if (! is_string($appSecret) || $appSecret === '') {
                ChannelOpsLogger::error('webhook.meta.app_secret_missing', [
                    'operation' => 'meta_webhook_receive',
                    'platform' => 'meta',
                    'error_code' => 'meta_app_secret_not_configured',
                ]);

                return response()->json(['message' => 'Meta app secret not configured'], 500);
            }

            $signature = $request->header('X-Hub-Signature-256');
            if (! MetaWebhookSignature::isValid($appSecret, $raw, $signature)) {
                ChannelOpsLogger::warning('webhook.meta.signature_invalid', [
                    'operation' => 'meta_webhook_receive',
                    'platform' => 'meta',
                    'error_code' => 'invalid_signature',
                ]);

                return response()->json(['message' => 'Invalid signature'], 403);
            }
        }

        /** @var mixed $decoded */
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            ChannelOpsLogger::warning('webhook.meta.body_invalid_json', [
                'operation' => 'meta_webhook_receive',
                'platform' => 'meta',
                'error_code' => 'invalid_json_body',
            ]);

            return response()->json(['message' => 'Invalid JSON body'], 400);
        }

        $ingress->handlePayload($decoded);

        return response()->json(['success' => true]);
    }
}
