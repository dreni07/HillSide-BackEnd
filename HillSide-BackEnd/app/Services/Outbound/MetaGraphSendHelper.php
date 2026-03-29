<?php

namespace App\Services\Outbound;

use App\Support\ChannelOpsLogger;
use Illuminate\Http\Client\Response;

class MetaGraphSendHelper
{
    /**
     * @return array{message: string, code: int|string|null, subcode: int|string|null, type: string|null}
     */
    public static function summarizeError(Response $response): array
    {
        $json = $response->json();
        $err = is_array($json) && isset($json['error']) && is_array($json['error']) ? $json['error'] : [];

        return [
            'message' => isset($err['message']) && is_string($err['message']) ? $err['message'] : $response->body(),
            'code' => $err['code'] ?? null,
            'subcode' => $err['error_subcode'] ?? null,
            'type' => isset($err['type']) && is_string($err['type']) ? $err['type'] : null,
        ];
    }

    /**
     * @param  array{message: string, code: int|string|null, subcode: int|string|null, type: string|null}  $summary
     * @return array{userMessage: string, errorCode: string, httpStatus: int}
     */
    public static function mapToOutboundException(
        int $channelId,
        string $platform,
        array $summary
    ): array {
        $code = is_numeric($summary['code']) ? (int) $summary['code'] : null;
        $sub = is_numeric($summary['subcode']) ? (int) $summary['subcode'] : null;

        if ($code === 190 || (is_string($summary['message']) && str_contains(strtolower($summary['message']), 'access token'))) {
            $mapped = [
                'userMessage' => 'Lidhja me Meta ka skaduar ose token-i është i pavlefshëm. Rilidhni kanalin nga CRM.',
                'errorCode' => 'channel_token_invalid',
                'httpStatus' => 502,
            ];
        } elseif ($code === 131_047) {
            // WhatsApp Cloud: re-engagement / outside service window (often requires template).
            $mapped = [
                'userMessage' => 'WhatsApp: dritarja e përgjigjes ka mbaruar ose duhet mesazh shabllon. Përdorni një template të miratuar nga Meta.',
                'errorCode' => 'whatsapp_policy_window',
                'httpStatus' => 422,
            ];
        } elseif ($sub === 2_534_068) {
            // Instagram / Messenger: feature not available for account (transient rollout, etc.).
            $mapped = [
                'userMessage' => 'Funksioni i dërgimit nuk është i disponueshëm për këtë llogari nga Meta. Provoni më vonë ose kontrolloni cilësimet e aplikacionit.',
                'errorCode' => 'meta_feature_unavailable',
                'httpStatus' => 503,
            ];
        } elseif ($code === 4 || $code === 17 || $code === 32) {
            $mapped = [
                'userMessage' => 'Kufizim nga Meta (rate limit). Provoni përsëri pas pak.',
                'errorCode' => 'meta_rate_limit',
                'httpStatus' => 429,
            ];
        } else {
            $mapped = [
                'userMessage' => 'Dërgimi në Meta dështoi: '.$summary['message'],
                'errorCode' => 'meta_provider_error',
                'httpStatus' => 502,
            ];
        }

        ChannelOpsLogger::warning('outbound.graph_send_rejected', [
            'operation' => 'meta_graph_send',
            'channel_id' => $channelId,
            'platform' => $platform,
            'error_code' => $mapped['errorCode'],
            'graph_code' => $summary['code'],
            'graph_subcode' => $summary['subcode'],
            'graph_type' => $summary['type'],
            'provider_message' => $summary['message'],
        ]);

        return $mapped;
    }
}
