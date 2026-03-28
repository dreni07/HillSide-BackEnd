<?php

namespace App\Providers;

use App\Contracts\ProductUpload\OcrEngineInterface;
use App\Contracts\ProductUpload\ProductExtractedItemRepositoryInterface;
use App\Repositories\EloquentProductExtractedItemRepository;
use App\Services\Ai\GroqProductStructuringService;
use App\Services\ProductUpload\Documents\AttachPdfClass;
use App\Services\ProductUpload\Documents\AttachSpreadsheetClass;
use App\Services\ProductUpload\ImageProcessingService;
use App\Services\ProductUpload\Ocr\TesseractOcrEngine;
use App\Services\ProductUpload\ProductUploadService;
use App\Services\ProductUpload\Strategies\PdfParsingStrategy;
use App\Services\ProductUpload\Strategies\SpreadsheetParsingStrategy;
use App\Support\TesseractPathResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProductExtractedItemRepositoryInterface::class, EloquentProductExtractedItemRepository::class);

        $this->app->singleton(GroqProductStructuringService::class, fn () => GroqProductStructuringService::fromConfig());

        $this->app->singleton(PdfParsingStrategy::class, PdfParsingStrategy::class);
        $this->app->singleton(SpreadsheetParsingStrategy::class, SpreadsheetParsingStrategy::class);

        $this->app->singleton(AttachPdfClass::class, function ($app) {
            return new AttachPdfClass(
                $app->make(ProductExtractedItemRepositoryInterface::class),
                $app->make(PdfParsingStrategy::class),
            );
        });

        $this->app->singleton(AttachSpreadsheetClass::class, function ($app) {
            return new AttachSpreadsheetClass(
                $app->make(ProductExtractedItemRepositoryInterface::class),
                $app->make(SpreadsheetParsingStrategy::class),
            );
        });

        $this->app->singleton(OcrEngineInterface::class, function () {
            $cfg = config('services.tesseract', []);

            return new TesseractOcrEngine(
                TesseractPathResolver::resolve(),
                (string) ($cfg['languages'] ?? 'eng'),
            );
        });

        $this->app->singleton(ImageProcessingService::class, function ($app) {
            return new ImageProcessingService(
                $app->make(ProductExtractedItemRepositoryInterface::class),
                $app->make(OcrEngineInterface::class),
            );
        });

        $this->app->singleton(ProductUploadService::class, function ($app) {
            return new ProductUploadService(
                $app->make(AttachPdfClass::class),
                $app->make(AttachSpreadsheetClass::class),
                $app->make(ImageProcessingService::class),
                $app->make(ProductExtractedItemRepositoryInterface::class),
                $app->make(GroqProductStructuringService::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
