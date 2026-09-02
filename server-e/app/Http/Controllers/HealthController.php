<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'server-e',
            'check' => 'live',
        ]);
    }

    public function ready(): JsonResponse
    {
        try {
            DB::select('SELECT 1');

            return response()->json([
                'status' => 'ok',
                'service' => 'server-e',
                'check' => 'ready',
                'dependencies' => [
                    'mysql' => 'healthy',
                ],
            ]);
        } catch (Throwable $exception) {

            return response()->json([
                'status' => 'error',
                'service' => 'server-e',
                'check' => 'ready',
                'dependencies' => [
                    'mysql' => 'unhealthy',
                ],
            ], 503);
        }
    }
}