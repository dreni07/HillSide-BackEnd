<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Structured logs for webhooks, outbound sends, and token health (channel_id, platform, error_code).
 */
final class ChannelOpsLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $event, array $context = []): void
    {
        self::write('info', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $event, array $context = []): void
    {
        self::write('warning', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function error(string $event, array $context = []): void
    {
        self::write('error', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $event, array $context): void
    {
        $channel = (string) config('channels.ops_log_channel', 'stack');
        $payload = array_merge(
            [
                'domain' => 'channel_ops',
                'event' => $event,
            ],
            self::filterContext($context)
        );

        Log::channel($channel)->log($level, '[channel_ops] '.$event, $payload);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function filterContext(array $context): array
    {
        $out = [];
        foreach ($context as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            if (is_string($v) && strlen($v) > 2000) {
                $out[$k] = substr($v, 0, 2000).'…';

                continue;
            }
            $out[$k] = $v;
        }

        return $out;
    }
}
