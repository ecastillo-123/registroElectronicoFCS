<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device' => ['nullable', 'array'],
        ]);

        $user = User::query()
            ->with(['employee.workCenter'])
            ->where('email', $data['email'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        if (! $user->is_active || ! $user->employee) {
            throw ValidationException::withMessages([
                'email' => ['El usuario no está activo o no tiene empleado asignado.'],
            ]);
        }

        if (isset($data['device'])) {
            $this->actualizarDispositivo($user, $data['device']);
        }

        $token = $user->createToken('app-' . ($data['device']['uuid'] ?? 'mobile'))->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $this->perfil($user),
        ]);
    }

    public function estado(Request $request): JsonResponse
    {
        $user = $request->user()->load('employee.workCenter');
        $user->loadCount('devices');

        $ultimaChecada = CheckIn::query()
            ->with(['workCenter', 'device'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'user' => $this->perfil($user),
            'ultima_checada' => $ultimaChecada ? $this->checada($ultimaChecada) : null,
            'server_time' => now()->toISOString(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }

    private function perfil(User $user): array
    {
        $employee = $user->employee;
        $workCenter = $employee?->workCenter;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee' => $employee ? [
                'id' => $employee->id,
                'numero_empleado' => $employee->numero_empleado,
                'nombre_completo' => $employee->nombre_completo,
                'email' => $employee->email,
                'telefono' => $employee->telefono,
            ] : null,
            'work_center' => $workCenter ? [
                'id' => $workCenter->id,
                'nombre' => $workCenter->nombre,
                'direccion' => $workCenter->direccion,
                'lat' => (float) $workCenter->lat,
                'lng' => (float) $workCenter->lng,
                'radio_metros' => $workCenter->radio_metros,
            ] : null,
        ];
    }

    private function checada(CheckIn $checkIn): array
    {
        return [
            'id' => $checkIn->id,
            'tipo' => $checkIn->tipo,
            'lat' => (float) $checkIn->lat,
            'lng' => (float) $checkIn->lng,
            'precision_metros' => $checkIn->precision_metros,
            'distancia_metros' => $checkIn->distancia_metros,
            'dentro_rango' => $checkIn->dentro_rango,
            'fecha' => $checkIn->created_at?->toISOString(),
            'fecha_dispositivo' => $checkIn->fecha_dispositivo?->toISOString(),
            'work_center' => $checkIn->workCenter?->nombre,
            'device' => $checkIn->device?->modelo,
        ];
    }

    private function actualizarDispositivo(User $user, array $deviceData): void
    {
        $uuid = $deviceData['uuid'] ?? null;
        if (! $uuid) {
            return;
        }

        $device = \App\Models\Device::firstOrCreate(
            ['uuid' => $uuid],
            [
                'nombre' => $deviceData['nombre'] ?? null,
                'marca' => $deviceData['marca'] ?? null,
                'modelo' => $deviceData['modelo'] ?? null,
                'plataforma' => $deviceData['plataforma'] ?? null,
                'version_so' => $deviceData['version_so'] ?? null,
                'app_version' => $deviceData['app_version'] ?? null,
            ]
        );

        $device->fill([
            'nombre' => $deviceData['nombre'] ?? $device->nombre,
            'marca' => $deviceData['marca'] ?? $device->marca,
            'modelo' => $deviceData['modelo'] ?? $device->modelo,
            'plataforma' => $deviceData['plataforma'] ?? $device->plataforma,
            'version_so' => $deviceData['version_so'] ?? $device->version_so,
            'app_version' => $deviceData['app_version'] ?? $device->app_version,
            'user_id' => $user->id,
            'ultima_conexion_at' => now(),
        ]);
        $device->save();
    }
}
