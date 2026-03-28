<?php

namespace App\Contracts\ProductUpload;

use App\Models\Business;
use App\Models\ExtractedProductItem;
use App\Models\User;

/**
 * Repository për ruajtjen e rreshtave të nxjerra (Repository pattern).
 */
interface ProductExtractedItemRepositoryInterface
{
    /**
     * Ndërton modelin pa e ruajtur (për transaksione).
     *
     * @param  array<string, mixed>  $parsedRow  title, description, price, sku, category, extracted_text
     * @param  array<string, mixed>  $metadata
     */
    public function makeForIngestion(
        Business $business,
        User $user,
        array $parsedRow,
        string $sourceType,
        array $metadata,
        ?string $storedPath = null,
        ?string $fileDisk = 'local',
    ): ExtractedProductItem;

    /**
     * @param  list<ExtractedProductItem>  $items
     * @return list<ExtractedProductItem>
     */
    public function saveMany(array $items): array;

    /**
     * Fshin të gjitha rreshtat e një ngarkimi (batch) për biznesin.
     */
    public function deleteByBatchId(Business $business, string $batchId): int;
}
