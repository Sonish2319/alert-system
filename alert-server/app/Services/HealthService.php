<?php

namespace App\Services;

use App\Models\HealthEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class HealthService
{
    public function __construct(
        private readonly HealthEventBroadcaster $broadcaster,
    ) {
    }

    public function process(HealthEvent $event): array
    {
        $key = $this->stateKey($event->service);

        $previous = Redis::get($key);

        $previousState = $previous
            ? json_decode($previous, true)
            : null;

        $previousStatus = $previousState['status'] ?? 'UNKNOWN';

        $transition = $previousStatus !== $event->status;

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
                'message' => $event->message,
                'timestamp' => $state['timestamp'],
            ]);

            $this->broadcaster->broadcast([
                'type' => 'health_state_changed',
                'service' => $event->service,
                'previous_status' => $previousStatus,
                'status' => $event->status,
                'component' => $event->component,
                'reason' => $event->reason,
                'message' => $event->message,
                'timestamp' => $state['timestamp'],
            ]);
        } else {
            Log::debug('Duplicate health state', [
                'service' => $event->service,
                'status' => $event->status,
            ]);
        }

        return [
            'transition' => $transition,
            'previous_status' => $previousStatus,
            'current_state' => $state,
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