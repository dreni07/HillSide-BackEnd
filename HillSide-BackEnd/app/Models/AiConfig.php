<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'ai_personality_id',
        'ai_restrictions_id',
        'ai_salesman_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function personality(): BelongsTo
    {
        return $this->belongsTo(AiPersonality::class, 'ai_personality_id');
    }

    public function restrictions(): BelongsTo
    {
        return $this->belongsTo(AiRestriction::class, 'ai_restrictions_id');
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(AiSalesman::class, 'ai_salesman_id');
    }
}
