<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inbound auto-reply (queued after webhook stores a customer message)
    |--------------------------------------------------------------------------
    |
    | Requires a queue worker when QUEUE_CONNECTION is not "sync", plus an
    | active AiConfig for the channel owner's first business.
    |
    */
    'auto_reply' => [
        'enabled' => filter_var(env('AI_AUTO_REPLY_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Groq / OpenAI-compatible chat API
    |--------------------------------------------------------------------------
    |
    | Vendosni bazën e URL-it deri te /v1 (pa /chat/completions). Shembull Groq:
    | AI_API_URL=https://api.groq.com/openai/v1
    |
    */
    'api_key' => env('AI_API_KEY'),

    'api_url' => rtrim((string) env('AI_API_URL', 'https://api.groq.com/openai/v1'), '/'),

    'model' => env('AI_MODEL', 'llama-3.3-70b-versatile'),

    /** Sekonda për thirrjen HTTP */
    'timeout' => (int) env('AI_HTTP_TIMEOUT', 120),

    /** Numri maksimal i shenjave të dërguara te LLM (parandalon kosto / kufij konteksti) */
    'max_input_chars' => (int) env('AI_MAX_INPUT_CHARS', 48000),

];
