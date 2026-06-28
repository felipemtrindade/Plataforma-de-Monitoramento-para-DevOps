<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class MonitorController extends Controller
{
    public function check(): JsonResponse
    {
        Artisan::call('monitor:check');

        return response()->json([
            'message' => 'Coleta executada com sucesso.',
            'output' => trim(Artisan::output()),
        ]);
    }
}
