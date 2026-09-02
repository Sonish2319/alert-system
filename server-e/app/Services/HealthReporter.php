<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthReporter
{
    public function report(
        string $status,
        string $component,
        string $reason,
        ?string $message = null
    ): bool {
        $payload = [
            'service' => config('health.service'),
            'status' => $status,
            'component' => $component,
            'reason' => $reason,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        try {
            $response = Http::timeout(3)
                ->withToken(config('health.al_token'))
                ->post(
                    config('health.al_url') . '/api/v1/health-events',
                    $payload
                );

            if ($response->successful()) {
                Log::info('Health event sent to AL', [
                    'payload' => $payload,
                    'response' => $response->json(),
                ]);

                return true;
            }

            Log::error('AL rejected health event', [
                'status' => $response->status(),
                'response' => $response->body(),
                'payload' => $payload,
            ]);

            return false;

        } catch (\Throwable $exception) {

            Log::error('Unable to communicate with AL', [
                'error' => $exception->getMessage(),
                'payload' => $payload,
            ]);

            return false;
        }
    }

    public function healthy(
        string $component,
        string $reason = 'SERVICE_AVAILABLE'
    ): bool {
        return $this->report(
            'HEALTHY',
            $component,
            $reason
        );
    }

    public function degraded(
        string $component,
        string $reason,
        ?string $message = null
    ): bool {
        return $this->report(
            'DEGRADED',
            $component,
            $reason,
            $message
        );
    }

    public function down(
        string $component,
        string $reason,
        ?string $message = null
    ): bool {
        return $this->report(
            'DOWN',
            $component,
            $reason,
            $message
        );
    }

    public function heartbeat(): bool
{
    return $this->report(
        'HEALTHY',
        'application',
        'HEARTBEAT'
    );
}
}