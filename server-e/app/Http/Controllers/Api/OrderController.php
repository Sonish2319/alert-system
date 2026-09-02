<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        DB::select('SELECT 1');

        return response()->json([
            'success' => true,
            'server' => 'server-e',
            'message' => 'Server E API is working.',
        ]);
    }
}