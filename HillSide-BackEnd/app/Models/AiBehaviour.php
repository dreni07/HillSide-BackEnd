<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiBehaviour extends Model
{
    protected $table = 'ai_behaviours';

    protected $fillable = [
        'business_id',
        'orchestration_title',
        'orchestration_subtitle',
        'insight_banner_message',
        'active_workflow_tab',
        'flow_graph_json',
        'selected_palette_item_id',
        'inspector_detail_json',
        'personality_summary',
        'customer_restriction_rules',
        'sales_objectives',
        'voice_tone',
        'implementation_method',
        'scenario_flags_json',
        'goals_maintain_json',
        'goals_minimize_json',
        'constraints_notes',
        'perception_modules_snapshot_json',
        'selector_modules_snapshot_json',
        'skill_modules_snapshot_json',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'inspector_detail_json' => 'array',
            'scenario_flags_json' => 'array',
            'goals_maintain_json' => 'array',
            'goals_minimize_json' => 'array',
            'perception_modules_snapshot_json' => 'array',
            'selector_modules_snapshot_json' => 'array',
            'skill_modules_snapshot_json' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
