<?php

namespace App\Enums;

enum PriceType: string
{
    case FIXED = 'fixed';
    case HOURLY = 'hourly';
    case PER_SESSION = 'per_session';
    case RANGE = 'range';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixed Price',
            self::HOURLY => 'Hourly Rate',
            self::PER_SESSION => 'Per Session',
            self::RANGE => 'Price Range',
            self::CUSTOM => 'Custom / Quote',
        };
    }
}
