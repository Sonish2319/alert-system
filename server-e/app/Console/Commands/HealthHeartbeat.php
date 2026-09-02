<?php

namespace App\Console\Commands;

use App\Services\HealthReporter;
use Illuminate\Console\Command;

class HealthHeartbeat extends Command
{
    protected $signature = 'health:heartbeat';

    protected $description = 'Send Server E heartbeat to the Alert Server';

    public function handle(
        HealthReporter $healthReporter
    ): int {
        $success = $healthReporter->heartbeat();

        if ($success) {
            $this->info('Heartbeat sent successfully.');

            return self::SUCCESS;
        }

        $this->error('Failed to send heartbeat.');

        return self::FAILURE;
    }
}