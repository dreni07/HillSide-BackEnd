<?php

namespace App\Enums;

enum SalesApproach: string
{
    case CONSULTATIVE = 'consultative';
    case SOFT_SELL = 'soft_sell';
    case DIRECT = 'direct';
    case RELATIONSHIP_BASED = 'relationship_based';

    public function label(): string
    {
        return match ($this) {
            self::CONSULTATIVE => 'Consultative',
            self::SOFT_SELL => 'Soft Sell',
            self::DIRECT => 'Direct',
            self::RELATIONSHIP_BASED => 'Relationship Based',
        };
    }
}
