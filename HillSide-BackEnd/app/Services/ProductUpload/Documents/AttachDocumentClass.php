<?php

namespace App\Services\ProductUpload\Documents;

use App\Contracts\ProductUpload\DocumentParsingStrategyInterface;
use App\Contracts\ProductUpload\ProductExtractedItemRepositoryInterface;
use App\Enums\ProductExtractionSourceType;
use App\Models\Business;
use App\Models\ExtractedProductItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Blueprint (Template Method) për ngarkimin e dokumenteve: ruajtje skedari → parse strategy → repository.
 */
abstract class AttachDocumentClass
{
    public function __construct(
        protected ProductExtractedItemRepositoryInterface $repository,
        protected DocumentParsingStrategyInterface $strategy,
    ) {}

    abstract protected function extractionSourceType(): ProductExtractionSourceType;

    abstract public static function acceptsMime(string $mime): bool;

    /**
     * @return Collection<int, ExtractedProductItem>
     */
    public function process(Business $business, User $user, UploadedFile $file): Collection
    {
        return DB::transaction(function () use ($business, $user, $file): Collection {
            $disk = 'local';
            $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin'));
            $path = $file->storeAs(
                'product-uploads/'.$business->id,
                Str::uuid()->toString().'.'.$ext,
                $disk
            );
            $fullPath = Storage::disk($disk)->path($path);
            $rows = $this->strategy->parse($fullPath);
            $batchId = (string) Str::uuid();

            $models = [];
            foreach ($rows as $index => $row) {
                $models[] = $this->repository->makeForIngestion(
                    $business,
                    $user,
                    $row,
                    $this->extractionSourceType()->value,
                    [
                        'batch_id' => $batchId,
                        'original_name' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                        'row_index' => $index,
                    ],
                    $path,
                    $disk,
                );
            }

            return new Collection($this->repository->saveMany($models));
        });
    }
}
