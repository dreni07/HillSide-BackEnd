<?php

namespace App\Models;

use App\Enums\BusinessCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessType extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => BusinessCategory::class,
            'is_active' => 'boolean',
        ];
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCommerce($query)
    {
        return $query->where('category', BusinessCategory::COMMERCE);
    }

    public function scopeServices($query)
    {
        return $query->where('category', BusinessCategory::SERVICES);
    }
}
