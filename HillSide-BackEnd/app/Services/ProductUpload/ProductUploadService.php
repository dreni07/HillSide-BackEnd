<?php

namespace App\Services\ProductUpload;

use App\Contracts\ProductUpload\ProductExtractedItemRepositoryInterface;
use App\Enums\ProductExtractionSourceType;
use App\Exceptions\ProductUpload\UnsupportedDocumentTypeException;
use App\Models\Business;
use App\Models\ExtractedProductItem;
use App\Models\User;
use App\Services\Ai\GroqProductStructuringService;
use App\Services\ProductUpload\Documents\AttachPdfClass;
use App\Services\ProductUpload\Documents\AttachSpreadsheetClass;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orkestrues: zgjedh AttachPdf / AttachSpreadsheet, ose ImageProcessingService, ose manual;
 * pas nxjerrjes heuristike, përpiqet të zëvendësojë me strukturim LLM (Groq) kur API është konfiguruar.
 */
class ProductUploadService
{
    public function __construct(
        private readonly AttachPdfClass $attachPdf,
        private readonly AttachSpreadsheetClass $attachSpreadsheet,
        private readonly ImageProcessingService $imageProcessing,
        private readonly ProductExtractedItemRepositoryInterface $repository,
        private readonly GroqProductStructuringService $groqProductStructuring,
    ) {}

    /**
     * @return Collection<int, ExtractedProductItem>
     *
     * @throws UnsupportedDocumentTypeException
     */
    public function ingestDocument(Business $business, User $user, UploadedFile $file): Collection
    {
        $mime = strtolower((string) $file->getMimeType());

        if (AttachPdfClass::acceptsMime($mime)) {
            $items = $this->attachPdf->process($business, $user, $file);

            return $this->replaceWithLlmStructuredIfPossible(
                $business,
                $user,
                $items,
                ProductExtractionSourceType::Pdf
            );
        }

        if (AttachSpreadsheetClass::acceptsMime($mime)) {
            $items = $this->attachSpreadsheet->process($business, $user, $file);

            return $this->replaceWithLlmStructuredIfPossible(
                $business,
                $user,
                $items,
                ProductExtractionSourceType::Spreadsheet
            );
        }

        throw new UnsupportedDocumentTypeException('Document MIME type not supported: '.$mime);
    }

    /**
     * @return Collection<int, ExtractedProductItem>
     */
    public function ingestImage(Business $business, User $user, UploadedFile $file): Collection
    {
        $items = $this->imageProcessing->processAndSave($business, $user, $file);

        return $this->replaceWithLlmStructuredIfPossible(
            $business,
            $user,
            $items,
            ProductExtractionSourceType::Image
        );
    }

    /**
     * @param  array{
     *   name: string,
     *   description?: string|null,
     *   price?: string|float|null,
     *   sku?: string|null,
     *   category?: string|null,
     *   stock?: string|int|null,
     *   unit?: string|null,
     *   tags?: string|null
     * }  $data
     * @return Collection<int, ExtractedProductItem>
     */
    public function storeManual(Business $business, User $user, array $data): Collection
    {
        return DB::transaction(function () use ($business, $user, $data): Collection {
            $price = null;
            if (isset($data['price']) && $data['price'] !== '' && $data['price'] !== null) {
                $normalized = str_replace(',', '.', (string) $data['price']);
                $price = is_numeric($normalized) ? (float) $normalized : null;
            }

            $meta = [
                'batch_id' => (string) Str::uuid(),
                'stock' => $data['stock'] ?? null,
                'unit' => $data['unit'] ?? null,
                'tags' => $data['tags'] ?? null,
            ];

            $row = [
                'title' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $price,
                'sku' => $data['sku'] ?? null,
                'category' => $data['category'] ?? null,
                'extracted_text' => trim(($data['name'] ?? '')."\n".($data['description'] ?? '')),
            ];

            $model = $this->repository->makeForIngestion(
                $business,
                $user,
                $row,
                ProductExtractionSourceType::Manual->value,
                $meta,
                null,
                null,
            );

            return new Collection($this->repository->saveMany([$model]));
        });
    }

    /**
     * Nëse Groq është aktiv dhe përgjigjja përmban produkte, fshin rreshtat heuristike të të njëjtit batch
     * dhe ruan versionin e strukturuar nga LLM (i njëjti batch_id dhe file_path).
     *
     * @param  Collection<int, ExtractedProductItem>  $heuristicItems
     * @return Collection<int, ExtractedProductItem>
     */
    private function replaceWithLlmStructuredIfPossible(
        Business $business,
        User $user,
        Collection $heuristicItems,
        ProductExtractionSourceType $sourceType,
    ): Collection {
        if (! $this->groqProductStructuring->isEnabled()) {
            return $heuristicItems;
        }

        $first = $heuristicItems->first();
        if ($first === null) {
            return $heuristicItems;
        }

        $batchId = data_get($first->metadata, 'batch_id');
        if (! is_string($batchId) || $batchId === '') {
            return $heuristicItems;
        }

        $combined = $heuristicItems
            ->pluck('extracted_text')
            ->filter(fn ($t) => is_string($t) && trim($t) !== '')
            ->implode("\n\n---\n\n");

        if ($combined === '') {
            return $heuristicItems;
        }

        try {
            $rows = $this->groqProductStructuring->structureExtractedContent($combined, $sourceType);
        } catch (Throwable $e) {
            Log::warning('LLM product structuring failed; keeping heuristic extracted rows.', [
                'business_id' => $business->id,
                'batch_id' => $batchId,
                'message' => $e->getMessage(),
            ]);

            return $heuristicItems;
        }

        $rows = array_values(array_filter(
            $rows,
            fn (array $r): bool => ($r['title'] ?? null) || ($r['description'] ?? null) || ($r['sku'] ?? null)
                || (isset($r['extracted_text']) && trim((string) $r['extracted_text']) !== '')
        ));

        if ($rows === []) {
            return $heuristicItems;
        }

        $filePath = $first->file_path;
        $fileDisk = $first->file_disk ?? 'local';
        $modelName = (string) config('ai.model', '');

        return DB::transaction(function () use (
            $business,
            $user,
            $rows,
            $sourceType,
            $batchId,
            $filePath,
            $fileDisk,
            $modelName,
        ): Collection {
            $this->repository->deleteByBatchId($business, $batchId);

            $models = [];
            foreach ($rows as $index => $row) {
                $models[] = $this->repository->makeForIngestion(
                    $business,
                    $user,
                    $row,
                    $sourceType->value,
                    [
                        'batch_id' => $batchId,
                        'llm_structured' => true,
                        'llm_model' => $modelName,
                        'row_index' => $index,
                    ],
                    $filePath,
                    $filePath ? $fileDisk : null,
                );
            }

            return new Collection($this->repository->saveMany($models));
        });
    }
}
