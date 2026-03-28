<?php

namespace App\Services\ProductUpload\Ocr;

use App\Contracts\ProductUpload\OcrEngineInterface;
use App\Exceptions\ProductUpload\OcrFailedException;
use thiagoalessio\TesseractOCR\TesseractOCR;
use thiagoalessio\TesseractOCR\TesseractOcrException;
use Throwable;

/**
 * Implementim OCR përmes binarit Tesseract (thiagoalessio/tesseract_ocr).
 */
class TesseractOcrEngine implements OcrEngineInterface
{
    /**
     * @param  string  $languages  Shumë gjuhë me “+”, p.sh. “eng+sqi”.
     */
    public function __construct(
        private readonly string $executable = 'tesseract',
        private readonly string $languages = 'eng',
    ) {}

    public function run(string $imageAbsolutePath): string
    {
        try {
            $ocr = new TesseractOCR($imageAbsolutePath);
            $ocr->executable($this->executable);
            $langs = array_values(array_filter(array_map('trim', explode('+', $this->languages))));
            if ($langs !== []) {
                $ocr->lang(...$langs); // Option::lang përmes __call
            }

            return $ocr->run();
        } catch (TesseractOcrException $e) {
            throw new OcrFailedException('Tesseract OCR failed: '.$e->getMessage(), (int) $e->getCode(), $e);
        } catch (Throwable $e) {
            throw new OcrFailedException('OCR error: '.$e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
