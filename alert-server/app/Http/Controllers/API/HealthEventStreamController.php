<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HealthEventStreamController
{
    public function stream(): StreamedResponse
    {
        return response()->stream(
            function () {
                set_time_limit(0);

                Log::info('SSE: callback started');

                echo ": connected\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();

                Log::info('SSE: creating Redis connection');

                $redis = Redis::connection();

                Log::info('SSE: Redis connection created');

                Log::info('SSE: subscribing to health-events');

                $redis->subscribe(
                    ['health-events'],
                    function (string $message) {
                        Log::info('SSE: Redis message received', [
                            'message' => $message,
                        ]);

                        echo "event: health\n";
                        echo "data: {$message}\n\n";

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }

                        flush();
                    }
                );

                Log::info('SSE: Redis subscribe ended');
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-transform',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }
}