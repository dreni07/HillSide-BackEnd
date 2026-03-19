<?php

namespace App\Models;

use App\Enums\PriceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'price',
        'currency',
        'price_type',
        'duration_minutes',
        'category',
        'is_available',
        'attributes',
        'extra_context',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_type' => PriceType::class,
            'is_available' => 'boolean',
            'attributes' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}
