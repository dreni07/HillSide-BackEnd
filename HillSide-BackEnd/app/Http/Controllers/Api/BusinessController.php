<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\AssignBusinessTypeRequest;
use App\Http\Requests\Business\StoreBusinessRequest;
use App\Http\Requests\Business\UpdateBusinessRequest;
use App\Models\Business;
use App\Services\BusinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BusinessController extends Controller
{
    public function __construct(
        private readonly BusinessService $businessService
    ) {}

    public function store(StoreBusinessRequest $request): JsonResponse
    {
        $business = $this->businessService->createBusiness(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Business created successfully.',
            'data' => $business->load('businessType'),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request): JsonResponse
    {
        $business = $request->user()
            ->businesses()
            ->with('businessType')
            ->first();

        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'No business found. Please create a business first.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $business,
        ]);
    }

    public function update(UpdateBusinessRequest $request, Business $business): JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this business.',
            ], Response::HTTP_FORBIDDEN);
        }

        $business = $this->businessService->updateBusiness(
            $business,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Business updated successfully.',
            'data' => $business->load('businessType'),
        ]);
    }

    public function assignBusinessType(AssignBusinessTypeRequest $request, Business $business): JsonResponse
    {
        if ($business->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this business.',
            ], Response::HTTP_FORBIDDEN);
        }

        $business = $this->businessService->assignBusinessType(
            $business,
            $request->validated()['business_type_id']
        );

        return response()->json([
            'success' => true,
            'message' => 'Business type assigned successfully.',
            'data' => $business,
        ]);
    }
}
