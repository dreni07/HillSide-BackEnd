<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'price',
        'currency',
        'sku',
        'category',
        'stock_quantity',
        'is_available',
        'images',
        'attributes',
        'extra_context',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'images' => 'array',
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

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('stock_quantity')
              ->orWhere('stock_quantity', '>', 0);
        });
    }
}
