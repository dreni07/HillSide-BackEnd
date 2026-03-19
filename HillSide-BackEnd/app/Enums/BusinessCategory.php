<?php

namespace App\Enums;

enum BusinessCategory: string
{
    case COMMERCE = 'commerce';
    case SERVICES = 'services';

    public function label(): string
    {
        return match ($this) {
            self::COMMERCE => 'Commerce',
            self::SERVICES => 'Services',
        };
    }
}
