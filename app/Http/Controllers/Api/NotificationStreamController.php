<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationFeed;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationStreamController extends Controller
{
    public function __invoke(Request $request, NotificationFeed $feed): StreamedResponse
    {
        $token = $request->query('token');

        $user = $token
            ? User::where('api_token', hash('sha256', $token))->where('is_admin', true)->first()
            : null;

        abort_unless($user, 403, 'Acesso restrito ao administrador.');

        return response()->stream(function () use ($feed) {
            $lastHash = null;

            for ($i = 0; $i < 120; $i++) {
                if (connection_aborted()) {
                    break;
                }

                $notifications = $feed->recent()->values();
                $payload = $notifications->toJson();
                $hash = sha1($payload);

                if ($hash !== $lastHash) {
                    echo "event: notifications\n";
                    echo 'data: '.$payload."\n\n";
                    $lastHash = $hash;
                } else {
                    echo ": heartbeat\n\n";
                }

                @ob_flush();
                flush();
                sleep(5);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
