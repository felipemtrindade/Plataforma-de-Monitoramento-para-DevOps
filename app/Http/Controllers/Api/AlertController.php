<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;

class AlertController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Alert::with('service')->latest()->limit(100)->get()
        );
    }
}
