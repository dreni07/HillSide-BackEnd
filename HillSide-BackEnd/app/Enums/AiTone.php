<?php

namespace App\Enums;

enum AiTone: string
{
    case PROFESSIONAL = 'professional';
    case FRIENDLY = 'friendly';
    case CASUAL = 'casual';
    case FORMAL = 'formal';
    case ENTHUSIASTIC = 'enthusiastic';

    public function label(): string
    {
        return match ($this) {
            self::PROFESSIONAL => 'Professional',
            self::FRIENDLY => 'Friendly',
            self::CASUAL => 'Casual',
            self::FORMAL => 'Formal',
            self::ENTHUSIASTIC => 'Enthusiastic',
        };
    }
}
