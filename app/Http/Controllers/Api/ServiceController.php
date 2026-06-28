<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Service::withCount(['metrics', 'alerts'])->latest()->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $service = Service::create($this->validated($request));

        return response()->json($service, 201);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json(
            $service->load([
                'metrics' => fn ($query) => $query->latest()->limit(20),
                'alerts' => fn ($query) => $query->latest()->limit(20),
            ])
        );
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $service->update($this->validated($request));

        return response()->json($service);
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json(['message' => 'Serviço arquivado com sucesso. O histórico foi preservado.']);
    }

    public function metrics(Service $service): JsonResponse
    {
        return response()->json($service->metrics()->latest()->limit(50)->get());
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:WEB,DATABASE,DNS,SMTP'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'description' => ['nullable', 'string'],
            'current_status' => ['nullable', 'in:UP,DOWN'],
        ]);
    }
}
