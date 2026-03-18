<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\UpdateChannelRequest;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Channel::query();

        if ($request->user()->is_admin && $request->filled('userId')) {
            $query->where('user_id', $request->integer('userId'));
        } else {
            $query->where('user_id', $request->user()->id);
        }

        $channels = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $channels,
        ]);
    }

    public function show(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeChannel($request, $channel);

        return response()->json([
            'success' => true,
            'data' => $channel,
        ]);
    }

    public function update(UpdateChannelRequest $request, Channel $channel): JsonResponse
    {
        $this->authorizeChannel($request, $channel);

        $channel->fill($request->validated());
        $channel->save();

        return response()->json([
            'success' => true,
            'data' => $channel,
        ]);
    }

    public function destroy(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeChannel($request, $channel);

        $channel->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    protected function authorizeChannel(Request $request, Channel $channel): void
    {
        if (!$request->user()->is_admin && $channel->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}

