<?php

return [

    'services' => [

        'server-e' => [
            'token' => env('HEALTH_SERVICE_TOKENS_SERVER_E'),
        ],

        'server-d' => [
            'token' => env('HEALTH_SERVICE_TOKENS_SERVER_D'),
        ],

    ],

    'redis' => [

        'state_prefix' => 'alert:service:',

    ],

];