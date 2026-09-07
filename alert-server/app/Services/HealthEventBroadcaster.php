<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class HealthEventBroadcaster
{
    private const CHANNEL = 'health-events';

    public function broadcast(array $event): void
    {
        Redis::publish(
            self::CHANNEL,
            json_encode($event)
        );
    }
}