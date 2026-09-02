<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateHealthService
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $service = $request->input('service');

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service is required.',
            ], 401);
        }

        $configuredToken = config(
            "health.services.{$service}.token"
        );

        if (!$configuredToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unknown health service.',
            ], 401);
        }

        $providedToken = $request->bearerToken();

        if (
            !$providedToken ||
            !hash_equals($configuredToken, $providedToken)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid health service credentials.',
            ], 401);
        }

        return $next($request);
    }
}