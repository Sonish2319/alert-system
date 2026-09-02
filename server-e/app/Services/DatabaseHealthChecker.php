<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseHealthChecker
{
    public function check(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}