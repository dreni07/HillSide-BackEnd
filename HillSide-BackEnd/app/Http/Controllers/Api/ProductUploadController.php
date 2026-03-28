<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ProductUpload\OcrFailedException;
use App\Exceptions\ProductUpload\UnsupportedDocumentTypeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductUpload\StoreProductDocumentRequest;
use App\Http\Requests\ProductUpload\StoreProductImageRequest;
use App\Http\Requests\ProductUpload\StoreProductManualRequest;
use App\Models\Business;
use App\Services\ProductUpload\ProductUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProductUploadController extends Controller
{
    public function __construct(
        private readonly ProductUploadService $productUploadService
    ) {}

    public function uploadDocument(StoreProductDocumentRequest $request, Business $business): JsonResponse
    {
        if (! $this->userOwnsBusiness($request, $business)) {
            return $this->forbidden();
        }

        try {
            $items = $this->productUploadService->ingestDocument(
                $business,
                $request->user(),
                $request->file('file')
            );
        } catch (UnsupportedDocumentTypeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) {
            Log::error('Product document upload failed', [
                'business_id' => $business->id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not process document.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document processed.',
            'data' => [
                'items' => $items->map(fn ($m) => $m->fresh())->values(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function uploadImage(StoreProductImageRequest $request, Business $business): JsonResponse
    {
        if (! $this->userOwnsBusiness($request, $business)) {
            return $this->forbidden();
        }

        try {
            $items = $this->productUploadService->ingestImage(
                $business,
                $request->user(),
                $request->file('file')
            );
        } catch (OcrFailedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) {
            Log::error('Product image OCR failed', [
                'business_id' => $business->id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not process image.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image processed.',
            'data' => [
                'items' => $items->map(fn ($m) => $m->fresh())->values(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function storeManual(StoreProductManualRequest $request, Business $business): JsonResponse
    {
        if (! $this->userOwnsBusiness($request, $business)) {
            return $this->forbidden();
        }

        try {
            $items = $this->productUploadService->storeManual(
                $business,
                $request->user(),
                $request->validated()
            );
        } catch (Throwable $e) {
            Log::error('Manual product save failed', [
                'business_id' => $business->id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not save product.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product saved.',
            'data' => [
                'items' => $items->map(fn ($m) => $m->fresh())->values(),
            ],
        ], Response::HTTP_CREATED);
    }

    private function userOwnsBusiness(Request $request, Business $business): bool
    {
        return (int) $business->user_id === (int) $request->user()->id;
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'You do not own this business.',
        ], Response::HTTP_FORBIDDEN);
    }
}
