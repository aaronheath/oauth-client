<?php

return [
    'default_profile' => 'default',

    'profiles' => [
        'default' => [
            'url' => env('OAUTH_CLIENT_URL', 'https://example.com/oauth/token'),
            'client_id' => env('OAUTH_CLIENT_ID', 'client_id'),
            'client_secret' => env('OAUTH_CLIENT_SECRET', 'client_secret'),
            'verify_https' => env('OAUTH_VERIFY_HTTPS', true),
            'scope' => env('OAUTH_SCOPE', ''),
        ],
    ]
];