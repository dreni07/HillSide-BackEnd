<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\StoreChannelRequest;
use App\Http\Requests\Channel\UpdateChannelRequest;
use App\Models\Channel;
use App\Support\ChannelCrmPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function store(StoreChannelRequest $request): JsonResponse
    {
        $platform = $request->validated('platform');
        $accessToken = trim($request->validated('accessToken'));

        $nameRaw = $request->validated('name');
        $nameTrimmed = is_string($nameRaw) ? trim($nameRaw) : '';

        $pageIdRaw = $request->validated('platformPageId');
        $pageId = is_string($pageIdRaw) && trim($pageIdRaw) !== '' ? trim($pageIdRaw) : null;

        $viberRaw = $request->validated('viberBotId');
        $viberId = is_string($viberRaw) && trim($viberRaw) !== '' ? trim($viberRaw) : null;

        if ($platform === 'viber' && $viberId !== null) {
            $exists = Channel::query()
                ->where('user_id', $request->user()->id)
                ->where('platform', 'viber')
                ->where('viber_bot_id', $viberId)
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ky kanal Viber është tashmë i lidhur.',
                ], 422);
            }
        }

        if ($platform !== 'viber' && $pageId !== null) {
            $exists = Channel::query()
                ->where('user_id', $request->user()->id)
                ->where('platform', $platform)
                ->where('meta_page_id', $pageId)
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ky kanal është tashmë i lidhur.',
                ], 422);
            }
        }

        $defaultName = match ($platform) {
            'viber' => $viberId !== null ? 'Viber ' . $viberId : 'Viber',
            default => $pageId !== null
                ? ucfirst($platform) . ' ' . $pageId
                : ucfirst($platform) . ' channel',
        };
        $name = $nameTrimmed !== '' ? $nameTrimmed : $defaultName;

        $channel = Channel::query()->create([
            'user_id' => $request->user()->id,
            'platform' => $platform,
            'name' => $name,
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => $platform === 'viber' ? null : $pageId,
            'meta_access_token' => $accessToken,
            'viber_bot_id' => $platform === 'viber' ? $viberId : null,
            'webhook_verify_token' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => ChannelCrmPresenter::toArray($channel),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Channel::query();

        if ($request->user()->is_admin && $request->filled('userId')) {
            $query->where('user_id', $request->integer('userId'));
        } else {
            $query->where('user_id', $request->user()->id);
        }

        $channels = $query->orderByDesc('id')->limit(100)->get();

        $data = $channels
            ->map(fn (Channel $channel) => ChannelCrmPresenter::toArray($channel))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function show(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeChannel($request, $channel);

        return response()->json([
            'success' => true,
            'data' => ChannelCrmPresenter::toArray($channel),
        ]);
    }

    public function update(UpdateChannelRequest $request, Channel $channel): JsonResponse
    {
        $this->authorizeChannel($request, $channel);

        $channel->fill($request->validated());
        $channel->save();

        return response()->json([
            'success' => true,
            'data' => ChannelCrmPresenter::toArray($channel->fresh()),
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

