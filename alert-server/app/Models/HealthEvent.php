<?php

namespace App\Models;

class HealthEvent
{
    public function __construct(
        public readonly string $service,
        public readonly string $status,
        public readonly string $component,
        public readonly string $reason,
        public readonly ?string $message = null,
        public readonly ?string $timestamp = null,
    ) {
    }

    public function toArray(): array  // convets the HealthEvent object to an associative array
    {
        return [
            'service' => $this->service,
            'status' => $this->status,
            'component' => $this->component,
            'reason' => $this->reason,
            'message' => $this->message,
            'timestamp' => $this->timestamp ?? now()->toISOString(),
        ];
    }
}