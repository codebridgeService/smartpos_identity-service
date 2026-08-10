<?php

return [

    'api_path' => 'api/v1',

    'api_domain' => null,

    'info' => [
        'version' => '1.0.0',

        'description' => '
# SmartPOS Identity

Identity and Access Management API for SmartPOS.

## Features

- JWT Authentication
- Refresh Tokens
- Users
- Roles
- Permissions
- POS PIN
- Devices
- Sessions
- OTP
- Login Attempts
        ',
    ],

    'ui' => [
        'title' => 'SmartPOS Identity',
    ],

    'security_strategy' =>
        \Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy::class,
];