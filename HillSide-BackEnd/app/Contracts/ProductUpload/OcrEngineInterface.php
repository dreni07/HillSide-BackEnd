<?php

namespace App\Contracts\ProductUpload;

use App\Exceptions\ProductUpload\OcrFailedException;

/**
 * Abstraksion mbi motorin OCR (Tesseract ose mock në teste).
 */
interface OcrEngineInterface
{
    /**
     * Lexon tekstin nga një skedar imazhi në disk.
     *
     * @throws OcrFailedException
     */
    public function run(string $imageAbsolutePath): string;
}
