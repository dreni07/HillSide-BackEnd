<?php

namespace App\Services;

use App\Models\AiConfig;
use App\Models\AiPersonality;
use App\Models\AiRestriction;
use App\Models\AiSalesman;
use App\Models\Business;
use Illuminate\Support\Facades\DB;

class AiConfigService
{
    public function getConfigForBusiness(Business $business): ?AiConfig
    {
        return $business->aiConfig()
            ->with(['personality', 'restrictions', 'salesman'])
            ->first();
    }

    public function createConfig(Business $business, array $data): AiConfig
    {
        return DB::transaction(function () use ($business, $data) {
            $personality = AiPersonality::create($data['personality']);
            $restrictions = AiRestriction::create($data['restrictions']);
            $salesman = AiSalesman::create($data['salesman']);

            $aiConfig = AiConfig::create([
                'business_id' => $business->id,
                'ai_personality_id' => $personality->id,
                'ai_restrictions_id' => $restrictions->id,
                'ai_salesman_id' => $salesman->id,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $aiConfig->load(['personality', 'restrictions', 'salesman']);
        });
    }

    public function businessHasConfig(Business $business): bool
    {
        return $business->aiConfig()->exists();
    }
}
