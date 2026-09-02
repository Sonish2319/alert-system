<?php

namespace App\Services;

use App\Models\HealthEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class HealthService
{
    public function process(HealthEvent $event): array
    {
        $key = $this->stateKey($event->service);

        $previous = Redis::get($key);

        $previousState = $previous
            ? json_decode($previous, true)  // json_decode converts JSON string to associative array
            : null;

        $previousStatus = $previousState['status'] ?? 'UNKNOWN';

        $transition = $previousStatus !== $event->status;  // Check if the status has changed to prevent duplicate logs for the same status

        $state = [
            'service' => $event->service,
            'status' => $event->status,
            'component' => $event->component,
            'reason' => $event->reason,
            'message' => $event->message,
            'timestamp' => $event->timestamp ?? now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        Redis::set(
            $key,
            json_encode($state)
        );

        if ($transition) {
            Log::info('Health state transition', [
                'service' => $event->service,
                'from' => $previousStatus,
                'to' => $event->status,
                'component' => $event->component,
                'reason' => $event->reason,
            ]);
        } else {
            Log::debug('Duplicate health state ignored for broadcast', [
                'service' => $event->service,
                'status' => $event->status,
            ]);
        }

        return [
            'state' => $state,
            'transition' => $transition,
            'previous_status' => $previousStatus,
        ];
    }

    public function getState(string $service): ?array
    {
        $value = Redis::get(
            $this->stateKey($service)
        );

        if ($value === null) {
            return null;
        }

        return json_decode($value, true);
    }

    private function stateKey(string $service): string
    {
        return config('health.redis.state_prefix') . $service;
    }
}