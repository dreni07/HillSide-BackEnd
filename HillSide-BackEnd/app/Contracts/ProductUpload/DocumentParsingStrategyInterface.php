<?php

namespace App\Contracts\ProductUpload;

/**
 * Strategy për leximin e përmbajtjes nga skedarë dokumenti (PDF / fletëllogaritje).
 *
 * @phpstan-type ParsedRow array{
 *   title?: string|null,
 *   description?: string|null,
 *   price?: float|null,
 *   sku?: string|null,
 *   category?: string|null,
 *   extracted_text: string
 * }
 */
interface DocumentParsingStrategyInterface
{
    /**
     * @return list<ParsedRow>
     */
    public function parse(string $absolutePath): array;
}
