<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiRestriction extends Model
{
    use HasFactory;

    protected $fillable = [
        'allowed_topics',
        'restricted_topics',
        'blocked_words',
        'max_response_length',
        'content_guidelines',
    ];

    protected function casts(): array
    {
        return [
            'allowed_topics' => 'array',
            'restricted_topics' => 'array',
            'blocked_words' => 'array',
        ];
    }

    public function aiConfig(): HasOne
    {
        return $this->hasOne(AiConfig::class);
    }
}
