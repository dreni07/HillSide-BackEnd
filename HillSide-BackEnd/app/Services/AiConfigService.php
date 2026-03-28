<?php

namespace App\Services;

use App\Models\AiBehaviour;
use App\Models\AiConfig;
use App\Models\AiExpectedQuestion;
use App\Models\AiPersonality;
use App\Models\AiRestriction;
use App\Models\AiSalesman;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    /**
     * Create or update AI personality, restrictions, salesman, and sync expected questions for the business.
     *
     * @param  array<string, mixed>  $data  Validated payload from SaveAiConfigRequest
     * @return array{config: AiConfig, expected_questions: \Illuminate\Database\Eloquent\Collection<int, AiExpectedQuestion>, behaviour: AiBehaviour|null}
     *
     * @throws Throwable
     */
    public function saveFullConfiguration(Business $business, array $data): array
    {
        return DB::transaction(function () use ($business, $data) {
            $config = $this->getConfigForBusiness($business);

            if ($config === null) {
                $personality = AiPersonality::create($data['personality']);
                $restrictions = AiRestriction::create($this->normalizeRestrictionsPayload($data['restrictions']));
                $salesman = AiSalesman::create($data['salesman']);

                $config = AiConfig::create([
                    'business_id' => $business->id,
                    'ai_personality_id' => $personality->id,
                    'ai_restrictions_id' => $restrictions->id,
                    'ai_salesman_id' => $salesman->id,
                    'is_active' => $data['is_active'] ?? true,
                ]);
            } else {
                $config->personality->update($data['personality']);
                $config->restrictions->update($this->normalizeRestrictionsPayload($data['restrictions']));
                $config->salesman->update($data['salesman']);
                if (array_key_exists('is_active', $data)) {
                    $config->update(['is_active' => (bool) $data['is_active']]);
                }
                $config->refresh();
            }

            $this->syncExpectedQuestions($business, $data['expected_questions'] ?? []);

            $behaviour = null;
            if (array_key_exists('behaviour', $data) && is_array($data['behaviour'])) {
                $behaviour = $this->upsertBehaviour($business, $data['behaviour']);
            }

            $config->load(['personality', 'restrictions', 'salesman']);

            return [
                'config' => $config,
                'expected_questions' => $business->aiExpectedQuestions()->orderBy('sort_order')->get(),
                'behaviour' => $behaviour ?? $business->aiBehaviour()->first(),
            ];
        });
    }

    /**
     * Partial update: only keys present in the request overwrite existing columns.
     *
     * @param  array<string, mixed>  $behaviour
     */
    private function upsertBehaviour(Business $business, array $behaviour): AiBehaviour
    {
        $model = AiBehaviour::firstOrNew(['business_id' => $business->id]);

        foreach ((new AiBehaviour)->getFillable() as $key) {
            if ($key === 'business_id' || ! array_key_exists($key, $behaviour)) {
                continue;
            }
            $model->{$key} = $behaviour[$key];
        }

        $model->business_id = $business->id;
        $model->save();

        return $model->fresh();
    }

    /**
     * @param  array<string, mixed>  $restrictions
     * @return array<string, mixed>
     */
    private function normalizeRestrictionsPayload(array $restrictions): array
    {
        return [
            'allowed_topics' => $restrictions['allowed_topics'] ?? null,
            'restricted_topics' => $restrictions['restricted_topics'] ?? null,
            'blocked_words' => $restrictions['blocked_words'] ?? null,
            'max_response_length' => $restrictions['max_response_length'] ?? null,
            'content_guidelines' => $restrictions['content_guidelines'] ?? null,
        ];
    }

    /**
     * @param  array<int, array{question: string, answer: string}>  $items
     */
    private function syncExpectedQuestions(Business $business, array $items): void
    {
        $business->aiExpectedQuestions()->delete();

        foreach ($items as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $question = isset($row['question']) ? trim((string) $row['question']) : '';
            $answer = isset($row['answer']) ? trim((string) $row['answer']) : '';
            if ($question === '' && $answer === '') {
                continue;
            }
            AiExpectedQuestion::create([
                'business_id' => $business->id,
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $index,
            ]);
        }
    }
}
