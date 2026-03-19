<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_type_id',
        'name',
        'description',
        'phone',
        'email',
        'address',
        'logo',
        'website',
        'timezone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function aiConfig(): HasOne
    {
        return $this->hasOne(AiConfig::class);
    }

    public function aiPersonality(): HasOneThrough
    {
        return $this->hasOneThrough(
            AiPersonality::class,
            AiConfig::class,
            'business_id',
            'id',
            'id',
            'ai_personality_id'
        );
    }

    public function aiRestrictions(): HasOneThrough
    {
        return $this->hasOneThrough(
            AiRestriction::class,
            AiConfig::class,
            'business_id',
            'id',
            'id',
            'ai_restrictions_id'
        );
    }

    public function aiSalesman(): HasOneThrough
    {
        return $this->hasOneThrough(
            AiSalesman::class,
            AiConfig::class,
            'business_id',
            'id',
            'id',
            'ai_salesman_id'
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
