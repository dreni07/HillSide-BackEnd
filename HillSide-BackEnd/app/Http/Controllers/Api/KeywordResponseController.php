<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeywordResponse\StoreKeywordResponseRequest;
use App\Http\Requests\KeywordResponse\UpdateKeywordResponseRequest;
use App\Models\KeywordResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeywordResponseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = KeywordResponse::query()->with('channel');

        if ($request->filled('channelId')) {
            $query->where('channel_id', $request->integer('channelId'));
        }

        $responses = $query->get();

        return response()->json([
            'success' => true,
            'data' => $responses,
        ]);
    }

    public function store(StoreKeywordResponseRequest $request): JsonResponse
    {
        $data = $request->validated();

        $response = KeywordResponse::create([
            'channel_id' => $data['channelId'],
            'keyword' => $data['keyword'],
            'response_text' => $data['responseText'],
            'is_active' => $data['isActive'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 201);
    }

    public function update(UpdateKeywordResponseRequest $request, KeywordResponse $keywordResponse): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('keyword', $data)) {
            $keywordResponse->keyword = $data['keyword'];
        }
        if (array_key_exists('responseText', $data)) {
            $keywordResponse->response_text = $data['responseText'];
        }
        if (array_key_exists('isActive', $data)) {
            $keywordResponse->is_active = $data['isActive'];
        }

        $keywordResponse->save();

        return response()->json([
            'success' => true,
            'data' => $keywordResponse,
        ]);
    }

    public function destroy(KeywordResponse $keywordResponse): JsonResponse
    {
        $keywordResponse->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}

