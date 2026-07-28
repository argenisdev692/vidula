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

    'brevo' => [
        // SMTP relay credentials (Shared\Infrastructure\Mail\BrevoMailAdapter).
        // Prefer MAIL_* in config/mail.php — these mirror them for service lookups.
        'host' => env('MAIL_HOST', 'smtp-relay.brevo.com'),
        'port' => env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
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

    'googlemaps' => [
        // Public JS API key (HTTP-referrer restricted); exposed to the SPA via
        // HandleInertiaRequests and consumed by common/address autocomplete.
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'tavily' => [
        // Grounds AI-generated Post content in current web data (Post module —
        // Shared\Infrastructure\Research\TavilyResearchAdapter).
        'api_key' => env('TAVILY_API_KEY'),
        'url' => env('TAVILY_SEARCH_URL', 'https://api.tavily.com/search'),
        'search_depth' => env('TAVILY_SEARCH_DEPTH', 'advanced'),
        'max_results' => env('TAVILY_MAX_RESULTS', 5),
    ],

    'context7' => [
        // Version-specific library documentation used to fact-check AI-generated
        // technical content (Products module — Shared\Infrastructure\Docs\Context7DocsAdapter).
        // Optional: with no key the adapter returns no snippets and generation continues.
        'api_key' => env('CONTEXT7_API_KEY'),
        'url' => env('CONTEXT7_API_URL', 'https://context7.com'),
        'max_snippets' => env('CONTEXT7_MAX_SNIPPETS', 5),
    ],

    'elevenlabs' => [
        // AI voiceover for the Post module's Reel/TikTok package —
        // Shared\Infrastructure\Speech\ElevenLabsSpeechAdapter.
        'api_key' => env('ELEVENLABS_API_KEY'),
        'voice_id' => env('ELEVENLABS_VOICE_ID'),
        'model_id' => env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Socialite OAuth Providers
    |--------------------------------------------------------------------------
    |
    | Consumed by the Auth module's social login (SocialAuthController).
    | Request only the minimum scopes needed to resolve a verified email.
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
        'scopes' => ['openid', 'profile', 'email'],
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URL'),
        'scopes' => ['user:email', 'read:user'],
    ],

];
