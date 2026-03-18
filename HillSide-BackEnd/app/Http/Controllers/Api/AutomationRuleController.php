<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AutomationRule\StoreAutomationRuleRequest;
use App\Http\Requests\AutomationRule\UpdateAutomationRuleRequest;
use App\Models\AutomationRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AutomationRule::query()->with('channel');

        if ($request->filled('channelId')) {
            $query->where('channel_id', $request->integer('channelId'));
        }

        $rules = $query->get();

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    public function store(StoreAutomationRuleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $rule = AutomationRule::create([
            'channel_id' => $data['channelId'],
            'name' => $data['name'],
            'conditions' => $data['conditions'] ?? null,
            'action' => $data['action'] ?? null,
            'is_active' => $data['isActive'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $rule,
        ], 201);
    }

    public function update(UpdateAutomationRuleRequest $request, AutomationRule $automationRule): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('name', $data)) {
            $automationRule->name = $data['name'];
        }

        if (array_key_exists('conditions', $data)) {
            $automationRule->conditions = $data['conditions'];
        }

        if (array_key_exists('action', $data)) {
            $automationRule->action = $data['action'];
        }

        if (array_key_exists('isActive', $data)) {
            $automationRule->is_active = $data['isActive'];
        }

        $automationRule->save();

        return response()->json([
            'success' => true,
            'data' => $automationRule,
        ]);
    }

    public function destroy(AutomationRule $automationRule): JsonResponse
    {
        $automationRule->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}

