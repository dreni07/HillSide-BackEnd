<?php

namespace App\Enums;

enum AiResponseStyle: string
{
    case CONCISE = 'concise';
    case BALANCED = 'balanced';
    case DETAILED = 'detailed';

    public function label(): string
    {
        return match ($this) {
            self::CONCISE => 'Concise',
            self::BALANCED => 'Balanced',
            self::DETAILED => 'Detailed',
        };
    }
}
