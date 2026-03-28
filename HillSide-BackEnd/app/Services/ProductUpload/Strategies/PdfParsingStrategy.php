<?php

namespace App\Services\ProductUpload\Strategies;

use App\Contracts\ProductUpload\DocumentParsingStrategyInterface;
use App\Services\ProductUpload\Mapping\ExtractedProductRowMapper;
use Smalot\PdfParser\Parser;

/**
 * Strategy: nxjerr tekst nga PDF dhe e ndan në rreshta logjikë.
 */
class PdfParsingStrategy implements DocumentParsingStrategyInterface
{
    public function parse(string $absolutePath): array
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($absolutePath);
        $text = trim($pdf->getText());

        if ($text === '') {
            return [array_merge(ExtractedProductRowMapper::emptyRow(), ['extracted_text' => ''])];
        }

        $chunks = preg_split('/\n{2,}/', $text) ?: [];
        $rows = [];
        foreach ($chunks as $chunk) {
            $chunk = trim((string) $chunk);
            if ($chunk === '') {
                continue;
            }
            $rows[] = ExtractedProductRowMapper::fromFreeText($chunk);
        }

        if ($rows === []) {
            $rows[] = ExtractedProductRowMapper::fromFreeText($text);
        }

        return $rows;
    }
}
