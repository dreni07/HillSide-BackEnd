<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'name',
        'status',
        'ai_instructions',
        'meta_page_id',
        'meta_access_token',
        'meta_token_expires_at',
        'whatsapp_phone_number_id',
        'whatsapp_business_account_id',
        'whatsapp_display_phone_number',
        'viber_bot_id',
        'webhook_verify_token',
        'viber_webhook_registered_at',
        'connection_error',
        'connection_error_code',
        'connection_error_at',
    ];

    protected $hidden = [
        'meta_access_token',
        'webhook_verify_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        $casts = [
            'meta_token_expires_at' => 'datetime',
            'viber_webhook_registered_at' => 'datetime',
            'connection_error_at' => 'datetime',
        ];

        if (config('channels.encrypt_tokens')) {
            $casts['meta_access_token'] = 'encrypted';
            $casts['webhook_verify_token'] = 'encrypted';
        }

        return $casts;
    }

    public function recordConnectionFailure(string $message, ?string $errorCode = null): void
    {
        $this->forceFill([
            'connection_error' => $message,
            'connection_error_code' => $errorCode,
            'connection_error_at' => now(),
        ])->save();
    }

    public function clearConnectionError(): void
    {
        $this->forceFill([
            'connection_error' => null,
            'connection_error_code' => null,
            'connection_error_at' => null,
        ])->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
