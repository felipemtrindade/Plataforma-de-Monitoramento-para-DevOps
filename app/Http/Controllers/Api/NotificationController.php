<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationFeed;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(NotificationFeed $feed): JsonResponse
    {
        return response()->json($feed->recent());
    }
}
