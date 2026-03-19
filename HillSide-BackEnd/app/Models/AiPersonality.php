<?php

namespace App\Models;

use App\Enums\AiResponseStyle;
use App\Enums\AiTone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiPersonality extends Model
{
    use HasFactory;

    protected $fillable = [
        'tone',
        'response_style',
        'language',
        'greeting_message',
        'farewell_message',
        'custom_instructions',
    ];

    protected function casts(): array
    {
        return [
            'tone' => AiTone::class,
            'response_style' => AiResponseStyle::class,
        ];
    }

    public function aiConfig(): HasOne
    {
        return $this->hasOne(AiConfig::class);
    }
}
