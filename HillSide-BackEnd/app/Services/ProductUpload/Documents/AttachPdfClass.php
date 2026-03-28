<?php

namespace App\Services\ProductUpload\Documents;

use App\Contracts\ProductUpload\ProductExtractedItemRepositoryInterface;
use App\Enums\ProductExtractionSourceType;
use App\Services\ProductUpload\Strategies\PdfParsingStrategy;

/**
 * Nënklasa: dokumente PDF — strategy PdfParsingStrategy.
 */
class AttachPdfClass extends AttachDocumentClass
{
    public function __construct(
        ProductExtractedItemRepositoryInterface $repository,
        PdfParsingStrategy $strategy,
    ) {
        parent::__construct($repository, $strategy);
    }

    protected function extractionSourceType(): ProductExtractionSourceType
    {
        return ProductExtractionSourceType::Pdf;
    }

    public static function acceptsMime(string $mime): bool
    {
        return in_array(strtolower($mime), [
            'application/pdf',
            'application/x-pdf',
        ], true);
    }
}
