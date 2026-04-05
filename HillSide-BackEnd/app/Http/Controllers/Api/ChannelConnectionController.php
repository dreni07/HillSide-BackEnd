<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\Channels\ChannelConnectionSyncer;
use App\Support\ChannelCrmPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelConnectionController extends Controller
{
    public function sync(Request $request, Channel $channel, ChannelConnectionSyncer $syncer): JsonResponse
    {
        $this->authorizeChannel($request, $channel);

        $warnings = $syncer->sync($channel->fresh());

        return response()->json([
            'success' => true,
            'data' => ChannelCrmPresenter::toArray($channel->fresh(), $request->user()),
            'sync' => [
                'warnings' => $warnings,
            ],
        ]);
    }

    protected function authorizeChannel(Request $request, Channel $channel): void
    {
        if (! $request->user()->is_admin && $channel->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
