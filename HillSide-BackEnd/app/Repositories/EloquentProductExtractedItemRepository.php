<?php

namespace App\Repositories;

use App\Contracts\ProductUpload\ProductExtractedItemRepositoryInterface;
use App\Models\Business;
use App\Models\ExtractedProductItem;
use App\Models\User;

class EloquentProductExtractedItemRepository implements ProductExtractedItemRepositoryInterface
{
    public function makeForIngestion(
        Business $business,
        User $user,
        array $parsedRow,
        string $sourceType,
        array $metadata,
        ?string $storedPath = null,
        ?string $fileDisk = 'local',
    ): ExtractedProductItem {
        return new ExtractedProductItem([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'title' => $parsedRow['title'] ?? null,
            'description' => $parsedRow['description'] ?? null,
            'price' => $parsedRow['price'] ?? null,
            'sku' => $parsedRow['sku'] ?? null,
            'category' => $parsedRow['category'] ?? null,
            'extracted_text' => $parsedRow['extracted_text'] ?? '',
            'source_type' => $sourceType,
            'metadata' => $metadata,
            'file_disk' => $storedPath ? $fileDisk : null,
            'file_path' => $storedPath,
        ]);
    }

    public function saveMany(array $items): array
    {
        foreach ($items as $item) {
            $item->save();
        }

        return $items;
    }

    public function deleteByBatchId(Business $business, string $batchId): int
    {
        return ExtractedProductItem::query()
            ->where('business_id', $business->id)
            ->where('metadata->batch_id', $batchId)
            ->delete();
    }
}
