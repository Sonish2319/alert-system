<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class HealthMonitor
{
    public function __construct(
        private readonly DatabaseHealthChecker $databaseHealthChecker,
        private readonly HealthReporter $healthReporter,
    ) {
    }

    public function checkDatabase(): bool
    {
        if ($this->databaseHealthChecker->check()) {

            $this->healthReporter->healthy(
                'mysql',
                'DATABASE_AVAILABLE'
            );

            return true;
        }

        Log::error('Server E MySQL health check failed.');

        $this->healthReporter->down(
            'mysql',
            'DATABASE_UNAVAILABLE',
            'Unable to connect to MySQL.'
        );

        return false;
    }
}