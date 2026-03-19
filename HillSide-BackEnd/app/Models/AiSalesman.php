<?php

namespace App\Models;

use App\Enums\SalesApproach;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiSalesman extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_approach',
        'upsell_enabled',
        'product_knowledge',
        'pricing_info',
        'call_to_action',
        'objection_handling',
    ];

    protected function casts(): array
    {
        return [
            'sales_approach' => SalesApproach::class,
            'upsell_enabled' => 'boolean',
        ];
    }

    public function aiConfig(): HasOne
    {
        return $this->hasOne(AiConfig::class);
    }
}
