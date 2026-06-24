<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'auth/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:9000',
        'http://127.0.0.1:9000',
        'http://localhost:8000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];