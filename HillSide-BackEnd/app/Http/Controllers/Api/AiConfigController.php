<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiConfig\StoreAiConfigRequest;
use App\Models\Business;
use App\Services\AiConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $aiConfig = $this->aiConfigService->getConfigForBusiness($business);

        if (!$aiConfig) {
            return response()->json([
                'success' => false,
                'message' => 'No AI configuration found for this business.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $aiConfig,
        ]);
    }
}
