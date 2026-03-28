<?php

namespace App\Models;

use App\Enums\ProductExtractionSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtractedProductItem extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'title',
        'description',
        'price',
        'sku',
        'category',
        'extracted_text',
        'source_type',
        'metadata',
        'file_disk',
        'file_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'metadata' => 'array',
            'source_type' => ProductExtractionSourceType::class,
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
