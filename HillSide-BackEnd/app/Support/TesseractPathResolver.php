<?php

namespace App\Support;

/**
 * Gjen ekzekutuesin e Tesseract: rrugë nga .env, auto-zbulim në Windows për instalimin zyrtar,
 * përndryshe emri "tesseract" (duhet në PATH).
 */
final class TesseractPathResolver
{
    public static function resolve(): string
    {
        $configured = trim((string) config('services.tesseract.executable', 'tesseract'));

        if ($configured !== '' && $configured !== 'tesseract' && is_file($configured)) {
            return $configured;
        }

        if (PHP_OS_FAMILY === 'Windows' && ($configured === '' || $configured === 'tesseract')) {
            foreach ([
                'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
                'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            ] as $path) {
                if (is_file($path)) {
                    return $path;
                }
            }
        }

        return $configured !== '' ? $configured : 'tesseract';
    }
}
