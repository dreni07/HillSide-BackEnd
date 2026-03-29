<?php

namespace App\Services\Outbound;

use RuntimeException;

class OutboundSendException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $provider
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $httpStatus = 422,
        public readonly ?array $provider = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
