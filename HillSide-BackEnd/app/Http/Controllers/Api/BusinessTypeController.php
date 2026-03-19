<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BusinessType::active();

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        $types = $query->orderBy('category')->orderBy('name')->get();

        $grouped = $types->groupBy(fn ($type) => $type->category->value);

        return response()->json([
            'success' => true,
            'data' => $types,
            'grouped' => $grouped,
        ]);
    }
}
