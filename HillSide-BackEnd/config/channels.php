<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Encrypt channel secrets at rest (meta_access_token, webhook_verify_token)
    |--------------------------------------------------------------------------
    |
    | Uses Laravel encrypted cast (APP_KEY). Enable in production after deploy;
    | existing plaintext values must be re-saved or migrated while enabled.
    |
    */
    'encrypt_tokens' => filter_var(env('CHANNEL_ENCRYPT_TOKENS', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Log channel for structured operations / compliance logs
    |--------------------------------------------------------------------------
    |
    | Monolog channel name from config/logging.php (default: stack → single file).
    |
    */
    'ops_log_channel' => env('CHANNEL_OPS_LOG_CHANNEL', 'stack'),

];
