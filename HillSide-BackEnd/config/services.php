<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     * Meta OAuth: shiko komentet në .env.example për Redirect URI, scopes dhe App ID/Secret
     * në developers.facebook.com (duhet të përputhen me APP_URL / META_REDIRECT_URI).
     */
    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'oauth_url' => env('META_OAUTH_URL') ?: 'https://www.facebook.com/v21.0/dialog/oauth',
        /*
         * Must match exactly a "Valid OAuth Redirect URI" in the Meta app (Facebook Login).
         * Override if the API is served on a different public URL than APP_URL.
         */
        'redirect_uri' => env('META_REDIRECT_URI') ?: rtrim((string) env('APP_URL', 'http://localhost'), '/').'/api/oauth/meta/callback',
        'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/'),
        'graph_base_url' => rtrim((string) (env('META_GRAPH_BASE_URL') ?: 'https://graph.facebook.com/v21.0'), '/'),
        /** Seconds to keep OAuth session data for selection / connect endpoints */
        'oauth_session_ttl' => (int) env('META_OAUTH_SESSION_TTL', 600),
        /** Seconds to keep the random OAuth `state` ↔ user_id binding (start → Meta → callback) */
        'oauth_state_ttl' => (int) env('META_OAUTH_STATE_TTL', 900),
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'META_OAUTH_SCOPES',
                'pages_show_list,pages_read_engagement,pages_manage_metadata,pages_messaging,instagram_basic,instagram_manage_messages'
            ))
        ))),
        /** Must match Meta Developer → Webhooks → Verify Token (GET subscription challenge). */
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        /**
         * If true, POST /webhooks/meta skips X-Hub-Signature-256 checks (local tunnel testing only).
         */
        'webhook_skip_signature' => filter_var(env('META_WEBHOOK_SKIP_SIGNATURE', false), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
     * Dërgim mesazhesh nga CRM drejt platformave (Messenger, Instagram DM, WhatsApp Cloud, Viber).
     */
    'outbound' => [
        /**
         * Nëse true, nuk bëhen thirrje HTTP; kthehet një message_id simuluar (vetëm zhvillim/test).
         */
        'skip_network' => filter_var(env('OUTBOUND_SKIP_NETWORK', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'viber' => [
        'send_url' => env('VIBER_SEND_URL', 'https://chatapi.viber.com/pa/send_message'),
        'set_webhook_url' => env('VIBER_SET_WEBHOOK_URL', 'https://chatapi.viber.com/pa/set_webhook'),
    ],

    /*
     * URL publike ku platformat (Viber, Meta) mund të arrijnë API-në (webhooks).
     * Nëse API është pas reverse proxy / domeni tjetër nga APP_URL, vendoseni PUBLIC_API_URL.
     */
    'integrations' => [
        'public_api_url' => rtrim((string) (env('PUBLIC_API_URL') ?: env('APP_URL', 'http://localhost')), '/'),
    ],

    /*
     * Tesseract OCR: instaloni binarin dhe paketat e gjuhës (tesseract --list-langs).
     * Në Windows vendosni rrugën e plotë te TESSERACT_EXECUTABLE nëse nuk është në PATH.
     */
    'tesseract' => [
        'executable' => env('TESSERACT_EXECUTABLE', 'tesseract'),
        'languages' => env('TESSERACT_LANGUAGES', 'eng'),
    ],

];
