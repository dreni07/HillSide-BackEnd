<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\UpdateBusinessRequest;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $business = Business::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['name' => $request->user()->name . '\'s Business']
        );

        return response()->json([
            'success' => true,
            'data' => $business,
        ]);
    }

    public function update(UpdateBusinessRequest $request): JsonResponse
    {
        $business = Business::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['name' => $request->user()->name . '\'s Business']
        );

        $business->fill($request->validated());
        $business->save();

        return response()->json([
            'success' => true,
            'data' => $business,
        ]);
    }
}

