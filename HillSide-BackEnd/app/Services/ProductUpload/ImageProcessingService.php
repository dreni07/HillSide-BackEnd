<?php

namespace App\Services\ProductUpload;

use App\Contracts\ProductUpload\OcrEngineInterface;
use App\Contracts\ProductUpload\ProductExtractedItemRepositoryInterface;
use App\Enums\ProductExtractionSourceType;
use App\Exceptions\ProductUpload\OcrFailedException;
use App\Models\Business;
use App\Models\ExtractedProductItem;
use App\Models\User;
use App\Services\ProductUpload\Mapping\ExtractedProductRowMapper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shërbim për imazhe: OCR (+ parapranim i lehtë), hartim në rresht, ruajtje përmes repository.
 */
class ImageProcessingService
{
    public function __construct(
        private readonly ProductExtractedItemRepositoryInterface $repository,
        private readonly OcrEngineInterface $ocr,
    ) {}

    /**
     * Parapranim opsional (p.sh. grayscale) — kthen rrugën për OCR.
     */
    public function preprocessForOcr(string $absolutePath): string
    {
        if (! extension_loaded('gd')) {
            return $absolutePath;
        }

        $image = @imagecreatefromstring((string) file_get_contents($absolutePath));
        if ($image === false) {
            return $absolutePath;
        }

        if (! imagefilter($image, IMG_FILTER_GRAYSCALE)) {
            imagedestroy($image);

            return $absolutePath;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pu_ocr_');
        if ($tmp === false) {
            imagedestroy($image);

            return $absolutePath;
        }

        $pngPath = $tmp.'.png';
        imagepng($image, $pngPath);
        imagedestroy($image);
        @unlink($tmp);

        return $pngPath;
    }

    /**
     * Ekstraktim: OCR nga skedari i imazhit.
     *
     * @throws OcrFailedException
     */
    public function extractText(string $absolutePath): string
    {
        $workPath = $this->preprocessForOcr($absolutePath);

        try {
            return $this->ocr->run($workPath);
        } finally {
            if ($workPath !== $absolutePath && is_file($workPath)) {
                @unlink($workPath);
            }
        }
    }

    /**
     * Harton një rresht të strukturuar nga teksti i OCR.
     *
     * @return array<string, mixed>
     */
    public function mapOcrTextToRow(string $ocrText): array
    {
        return ExtractedProductRowMapper::fromFreeText($ocrText);
    }

    /**
     * Ruajtje në DB (transaksion) — një rekord për imazh.
     *
     * @return Collection<int, ExtractedProductItem>
     */
    public function processAndSave(Business $business, User $user, UploadedFile $file): Collection
    {
        return DB::transaction(function () use ($business, $user, $file): Collection {
            $disk = 'local';
            $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg'));
            $path = $file->storeAs(
                'product-uploads/'.$business->id.'/images',
                Str::uuid()->toString().'.'.$ext,
                $disk
            );
            $fullPath = Storage::disk($disk)->path($path);

            $text = $this->extractText($fullPath);
            $row = $this->mapOcrTextToRow($text);
            $batchId = (string) Str::uuid();

            $model = $this->repository->makeForIngestion(
                $business,
                $user,
                $row,
                ProductExtractionSourceType::Image->value,
                [
                    'batch_id' => $batchId,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'ocr_engine' => 'tesseract',
                ],
                $path,
                $disk,
            );

            return new Collection($this->repository->saveMany([$model]));
        });
    }
}
