<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\StoreChannelRequest;
use App\Http\Requests\Channel\UpdateChannelRequest;
use App\Models\Channel;
use App\Services\Channels\ChannelConnectionSyncer;
use App\Support\ChannelCrmPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    public function store(StoreChannelRequest $request, ChannelConnectionSyncer $syncer): JsonResponse
    {
        $platform = $request->validated('platform');
        $accessToken = trim($request->validated('accessToken'));

        $nameRaw = $request->validated('name');
        $nameTrimmed = is_string($nameRaw) ? trim($nameRaw) : '';

        $pageIdRaw = $request->validated('platformPageId');
        $pageId = is_string($pageIdRaw) && trim($pageIdRaw) !== '' ? trim($pageIdRaw) : null;

        $waPhoneRaw = $request->validated('whatsappPhoneNumberId');
        $waPhoneId = is_string($waPhoneRaw) && trim($waPhoneRaw) !== '' ? trim($waPhoneRaw) : null;
        if ($platform === 'whatsapp' && $waPhoneId === null && $pageId !== null) {
            $waPhoneId = $pageId;
        }

        $viberRaw = $request->validated('viberBotId');
        $viberId = is_string($viberRaw) && trim($viberRaw) !== '' ? trim($viberRaw) : null;

        $wabaRaw = $request->validated('whatsappBusinessAccountId');
        $wabaId = is_string($wabaRaw) && trim($wabaRaw) !== '' ? trim($wabaRaw) : null;
        $displayRaw = $request->validated('whatsappDisplayPhoneNumber');
        $displayPhone = is_string($displayRaw) && trim($displayRaw) !== '' ? trim($displayRaw) : null;

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

        if ($platform !== 'viber' && $pageId !== null && $platform !== 'whatsapp') {
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

        if ($platform === 'whatsapp' && $waPhoneId !== null) {
            $exists = Channel::query()
                ->where('user_id', $request->user()->id)
                ->where('platform', 'whatsapp')
                ->where(function ($q) use ($waPhoneId) {
                    $q->where('whatsapp_phone_number_id', $waPhoneId)
                        ->orWhere('meta_page_id', $waPhoneId);
                })
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ky kanal WhatsApp është tashmë i lidhur.',
                ], 422);
            }
        }

        $defaultName = match ($platform) {
            'viber' => $viberId !== null ? 'Viber '.$viberId : 'Viber',
            'whatsapp' => $waPhoneId !== null
                ? 'WhatsApp '.$waPhoneId
                : ($pageId !== null ? 'WhatsApp '.$pageId : 'WhatsApp channel'),
            default => $pageId !== null
                ? ucfirst($platform).' '.$pageId
                : ucfirst($platform).' channel',
        };
        $name = $nameTrimmed !== '' ? $nameTrimmed : $defaultName;

        $metaPageId = match ($platform) {
            'viber' => null,
            'whatsapp' => null,
            default => $pageId,
        };

        $channel = Channel::query()->create([
            'user_id' => $request->user()->id,
            'platform' => $platform,
            'name' => $name,
            'status' => 'active',
            'ai_instructions' => null,
            'meta_page_id' => $metaPageId,
            'meta_access_token' => $accessToken,
            'whatsapp_phone_number_id' => $platform === 'whatsapp' ? $waPhoneId : null,
            'whatsapp_business_account_id' => $platform === 'whatsapp' ? $wabaId : null,
            'whatsapp_display_phone_number' => $platform === 'whatsapp' ? $displayPhone : null,
            'viber_bot_id' => $platform === 'viber' ? $viberId : null,
            'webhook_verify_token' => $platform === 'viber' ? Str::random(48) : null,
        ]);

        $syncer->sync($channel->fresh());

        return response()->json([
            'success' => true,
            'data' => ChannelCrmPresenter::toArray($channel->fresh(), $request->user()),
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
            ->map(fn (Channel $channel) => ChannelCrmPresenter::toArray($channel, $request->user()))
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
            'data' => ChannelCrmPresenter::toArray($channel, $request->user()),
        ]);
    }

    public function update(UpdateChannelRequest $request, Channel $channel): JsonResponse
    {
        $this->authorizeChannel($request, $channel);

        $validated = $request->validated();
        if ($request->user()->id !== $channel->user_id && array_key_exists('meta_access_token', $validated)) {
            abort(403, 'Vetëm pronari i kanalit mund të përditësojë token-in e aksesit.');
        }

        $channel->fill($validated);
        if (array_key_exists('meta_access_token', $validated)
            && is_string($validated['meta_access_token'])
            && trim($validated['meta_access_token']) !== '') {
            $channel->forceFill([
                'connection_error' => null,
                'connection_error_code' => null,
                'connection_error_at' => null,
            ]);
        }
        $channel->save();

        return response()->json([
            'success' => true,
            'data' => ChannelCrmPresenter::toArray($channel->fresh(), $request->user()),
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
        if (! $request->user()->is_admin && $channel->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
