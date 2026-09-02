<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function registrar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uuid' => ['required', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'plataforma' => ['nullable', 'string', 'max:255'],
            'version_so' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $device = Device::updateOrCreate(
            ['uuid' => $data['uuid']],
            array_merge($data, [
                'user_id' => $user->id,
                'ultima_conexion_at' => now(),
                'activo' => true,
            ])
        );

        return response()->json([
            'success' => true,
            'device' => $device,
        ]);
    }
}
