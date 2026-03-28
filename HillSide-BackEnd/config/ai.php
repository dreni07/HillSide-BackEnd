<?php

return [

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
