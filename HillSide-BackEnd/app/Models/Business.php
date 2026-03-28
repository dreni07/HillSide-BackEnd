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

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function aiExpectedQuestions(): HasMany
    {
        return $this->hasMany(AiExpectedQuestion::class)->orderBy('sort_order');
    }

    public function aiBehaviour(): HasOne
    {
        return $this->hasOne(AiBehaviour::class);
    }

    public function extractedProductItems(): HasMany
    {
        return $this->hasMany(ExtractedProductItem::class);
    }

    /**
     * Përputhet me kriteret e formës së onboarding në CRM (të gjitha fushat + lloj biznesi).
     */
    public function isProfileCompleteForOnboarding(): bool
    {
        $name = trim((string) $this->name);
        if (strlen($name) < 2) {
            return false;
        }

        $desc = trim((string) ($this->description ?? ''));
        if (strlen($desc) < 10) {
            return false;
        }

        if ($this->business_type_id === null) {
            return false;
        }

        $phone = trim((string) ($this->phone ?? ''));
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return false;
        }

        $email = trim((string) ($this->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $address = trim((string) ($this->address ?? ''));
        if (strlen($address) < 5) {
            return false;
        }

        $website = trim((string) ($this->website ?? ''));
        if (! preg_match('#^https?://.+#i', $website)) {
            return false;
        }

        $tz = trim((string) ($this->timezone ?? ''));

        return $tz !== '';
    }
}
