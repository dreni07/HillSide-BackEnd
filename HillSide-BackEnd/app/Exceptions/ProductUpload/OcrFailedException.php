<?php

namespace App\Exceptions\ProductUpload;

use RuntimeException;
use Throwable;

class OcrFailedException extends RuntimeException
{
    public function __construct(string $message = 'OCR failed.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
