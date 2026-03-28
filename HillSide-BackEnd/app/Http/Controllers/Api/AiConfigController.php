<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiConfig\SaveAiConfigRequest;
use App\Http\Requests\AiConfig\StoreAiConfigRequest;
use App\Models\Business;
use App\Services\AiConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AiConfigController extends Controller
{
    public function __construct(
        private readonly AiConfigService $aiConfigService
    ) {}

    public function store(StoreAiConfigRequest $request, Business $business): JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this business.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($this->aiConfigService->businessHasConfig($business)) {
            return response()->json([
                'success' => false,
                'message' => 'This business already has an AI configuration. Each business can only have one AI configuration.',
            ], Response::HTTP_CONFLICT);
        }

        $aiConfig = $this->aiConfigService->createConfig(
            $business,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'AI configuration created successfully.',
            'data' => $aiConfig,
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Business $business): JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this business.',
            ], Response::HTTP_FORBIDDEN);
        }

        $config = $this->aiConfigService->getConfigForBusiness($business);
        $expectedQuestions = $business->aiExpectedQuestions()->orderBy('sort_order')->get();
        $behaviour = $business->aiBehaviour()->first();

        return response()->json([
            'success' => true,
            'data' => [
                'config' => $config,
                'expected_questions' => $expectedQuestions,
                'behaviour' => $behaviour,
            ],
        ]);
    }

    public function save(SaveAiConfigRequest $request, Business $business): JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this business.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $result = $this->aiConfigService->saveFullConfiguration($business, $request->validated());
        } catch (Throwable $e) {
            Log::error('AI config save failed', [
                'business_id' => $business->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not save AI configuration. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'message' => 'AI configuration saved successfully.',
            'data' => [
                'config' => $result['config'],
                'expected_questions' => $result['expected_questions'],
                'behaviour' => $result['behaviour'],
            ],
        ]);
    }
}
