<?php

namespace App\Services\ProductUpload\Documents;

use App\Contracts\ProductUpload\ProductExtractedItemRepositoryInterface;
use App\Enums\ProductExtractionSourceType;
use App\Services\ProductUpload\Strategies\SpreadsheetParsingStrategy;

/**
 * Nënklasa: fletëllogaritje (CSV, XLSX, ODS) — strategy SpreadsheetParsingStrategy.
 */
class AttachSpreadsheetClass extends AttachDocumentClass
{
    public function __construct(
        ProductExtractedItemRepositoryInterface $repository,
        SpreadsheetParsingStrategy $strategy,
    ) {
        parent::__construct($repository, $strategy);
    }

    protected function extractionSourceType(): ProductExtractionSourceType
    {
        return ProductExtractionSourceType::Spreadsheet;
    }

    public static function acceptsMime(string $mime): bool
    {
        return in_array(strtolower($mime), [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.spreadsheet',
        ], true);
    }
}
