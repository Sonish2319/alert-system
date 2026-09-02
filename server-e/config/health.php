<?php

return [

    'service' => env(
        'HEALTH_SERVICE_NAME',
        'server-e'
    ),

    'al_url' => env(
        'AL_URL',
        'http://127.0.0.1:8000'
    ),

    'al_token' => env(
        'AL_HEALTH_TOKEN'
    ),

    'heartbeat_interval' => (int) env(
        'HEARTBEAT_INTERVAL',
        10
    ),

];