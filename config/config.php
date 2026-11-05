<?php
/**
 * Application Configuration
 */

return [
    'app_name'    => 'ResourceHub',
    'app_env'     => 'development', // 'development' or 'production'
    'timezone'    => 'UTC',
    'session'     => [
        'name'     => 'resourcehub_sess',
        'lifetime' => 86400, // 24 hours
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => false, // Set to true only when using HTTPS
    ],
    'pagination'  => [
        'per_page' => 10,
    ]
];

