<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HealthEventRequest;
use App\Models\HealthEvent;
use App\Services\HealthService;
use Illuminate\Http\JsonResponse;

class HealthEventController extends Controller
{
    public function __construct(
        private readonly HealthService $healthService
    ) {
    }

    public function store(
        HealthEventRequest $request
    ): JsonResponse {
        $event = new HealthEvent(
            service: $request->string('service')->toString(),
            status: $request->string('status')->toString(),
            component: $request->string('component')->toString(),
            reason: $request->string('reason')->toString(),
            message: $request->input('message'),
            timestamp: $request->input('timestamp'),
        );

        $result = $this->healthService->process($event);

        return response()->json([
            'success' => true,
            'service' => $event->service,
            'status' => $event->status,
            'transition' => $result['transition'],
            'previous_status' => $result['previous_status'],
            'state' => $result['current_state'],
        ]);
    }

    public function status(
        string $service
    ): JsonResponse {
        $state = $this->healthService->getState($service);

        if ($state === null) {
            return response()->json([
                'success' => false,
                'service' => $service,
                'status' => 'UNKNOWN',
                'message' => 'No health state is currently available.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'service' => $service,
            'state' => $state,
        ]);
    }
}