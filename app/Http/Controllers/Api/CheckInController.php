<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Device;
use App\Models\Employee;
use App\Services\GeofenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInController extends Controller
{
    public function __construct(private readonly GeofenceService $geofence) {}

    public function checar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'string', 'in:entrada,salida'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'precision_metros' => ['nullable', 'numeric', 'min:0'],
            'fecha_dispositivo' => ['nullable', 'date'],
            'checkin_type' => ['nullable', 'string', 'in:huella,facial'],
            'client_uuid' => ['nullable', 'string', 'uuid'],
            'device' => ['nullable', 'array'],
            'device.uuid' => ['nullable', 'string', 'max:255'],
            'device.nombre' => ['nullable', 'string', 'max:255'],
            'device.marca' => ['nullable', 'string', 'max:255'],
            'device.modelo' => ['nullable', 'string', 'max:255'],
            'device.plataforma' => ['nullable', 'string', 'max:255'],
            'device.version_so' => ['nullable', 'string', 'max:255'],
            'device.app_version' => ['nullable', 'string', 'max:255'],
        ]);

        // Idempotency: if client_uuid already exists, return existing checkin
        if (! empty($data['client_uuid'])) {
            $existing = CheckIn::byClientUuid($data['client_uuid'])->first();
            if ($existing) {
                $workCenter = $existing->workCenter;
                return response()->json([
                    'success' => true,
                    'registrada' => false,
                    'check_in' => [
                        'id' => $existing->id,
                        'tipo' => $existing->tipo,
                        'checkin_type' => $existing->checkin_type,
                        'sync_status' => $existing->sync_status,
                        'fecha' => $existing->created_at->toISOString(),
                        'dentro_rango' => $existing->dentro_rango,
                        'distancia_metros' => (float) $existing->distancia_metros,
                        'lat' => (float) $existing->lat,
                        'lng' => (float) $existing->lng,
                        'work_center' => $workCenter ? [
                            'id' => $workCenter->id,
                            'nombre' => $workCenter->nombre,
                            'radio_metros' => $workCenter->radio_metros,
                        ] : null,
                    ],
                    'mensaje' => $this->mensaje($existing),
                ]);
            }
        }

        $user = $request->user();

        if (! $user->employee) {
            throw ValidationException::withMessages([
                'tipo' => ['El usuario no tiene empleado asignado.'],
            ]);
        }

        $employee = $user->employee;

        if (! $employee->activo) {
            throw ValidationException::withMessages([
                'tipo' => ['El empleado está dado de baja.'],
            ]);
        }

        $device = $this->resolveDevice($user, $data['device'] ?? []);

        $checkIn = DB::transaction(function () use ($employee, $user, $device, $data) {
            return $this->geofence->registrar(
                employee: $employee,
                userId: $user->id,
                device: $device,
                tipo: $data['tipo'],
                lat: (float) $data['lat'],
                lng: (float) $data['lng'],
                precisionMetros: isset($data['precision_metros']) ? (float) $data['precision_metros'] : null,
                fechaDispositivo: $data['fecha_dispositivo'] ?? null,
            );
        });

        // Update with biometric and sync info
        if (! empty($data['checkin_type'])) {
            $checkIn->checkin_type = $data['checkin_type'];
        }
        $checkIn->sync_status = CheckIn::SYNC_STATUS_NORMAL;
        if (! empty($data['client_uuid'])) {
            $checkIn->client_uuid = $data['client_uuid'];
        }
        $checkIn->save();

        $workCenter = $checkIn->workCenter;

        return response()->json([
            'success' => true,
            'registrada' => true,
            'check_in' => [
                'id' => $checkIn->id,
                'tipo' => $checkIn->tipo,
                'checkin_type' => $checkIn->checkin_type,
                'sync_status' => $checkIn->sync_status,
                'fecha' => $checkIn->created_at->toISOString(),
                'dentro_rango' => $checkIn->dentro_rango,
                'distancia_metros' => (float) $checkIn->distancia_metros,
                'lat' => (float) $checkIn->lat,
                'lng' => (float) $checkIn->lng,
                'work_center' => $workCenter ? [
                    'id' => $workCenter->id,
                    'nombre' => $workCenter->nombre,
                    'radio_metros' => $workCenter->radio_metros,
                ] : null,
            ],
            'mensaje' => $this->mensaje($checkIn),
        ]);
    }

    public function syncPending(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'string', 'in:entrada,salida'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'precision_metros' => ['nullable', 'numeric', 'min:0'],
            'fecha_dispositivo' => ['nullable', 'date'],
            'checkin_type' => ['required', 'string', 'in:huella,facial'],
            'pending_checkin_datetime' => ['required', 'date'],
            'client_uuid' => ['required', 'string', 'uuid'],
            'device' => ['nullable', 'array'],
            'device.uuid' => ['nullable', 'string', 'max:255'],
            'device.nombre' => ['nullable', 'string', 'max:255'],
            'device.marca' => ['nullable', 'string', 'max:255'],
            'device.modelo' => ['nullable', 'string', 'max:255'],
            'device.plataforma' => ['nullable', 'string', 'max:255'],
            'device.version_so' => ['nullable', 'string', 'max:255'],
            'device.app_version' => ['nullable', 'string', 'max:255'],
        ]);

        // Idempotency
        $existing = CheckIn::byClientUuid($data['client_uuid'])->first();
        if ($existing) {
            $workCenter = $existing->workCenter;
            return response()->json([
                'success' => true,
                'registrada' => false,
                'check_in' => [
                    'id' => $existing->id,
                    'tipo' => $existing->tipo,
                    'checkin_type' => $existing->checkin_type,
                    'sync_status' => $existing->sync_status,
                    'fecha' => $existing->created_at->toISOString(),
                    'dentro_rango' => $existing->dentro_rango,
                    'distancia_metros' => (float) $existing->distancia_metros,
                    'lat' => (float) $existing->lat,
                    'lng' => (float) $existing->lng,
                    'work_center' => $workCenter ? [
                        'id' => $workCenter->id,
                        'nombre' => $workCenter->nombre,
                        'radio_metros' => $workCenter->radio_metros,
                    ] : null,
                ],
                'mensaje' => $this->mensaje($existing),
            ]);
        }

        $user = $request->user();

        if (! $user->employee) {
            throw ValidationException::withMessages([
                'tipo' => ['El usuario no tiene empleado asignado.'],
            ]);
        }

        $employee = $user->employee;

        if (! $employee->activo) {
            throw ValidationException::withMessages([
                'tipo' => ['El empleado está dado de baja.'],
            ]);
        }

        $device = $this->resolveDevice($user, $data['device'] ?? []);

        $checkIn = DB::transaction(function () use ($employee, $user, $device, $data) {
            return $this->geofence->registrar(
                employee: $employee,
                userId: $user->id,
                device: $device,
                tipo: $data['tipo'],
                lat: (float) $data['lat'],
                lng: (float) $data['lng'],
                precisionMetros: isset($data['precision_metros']) ? (float) $data['precision_metros'] : null,
                fechaDispositivo: $data['fecha_dispositivo'] ?? null,
            );
        });

        $checkIn->checkin_type = $data['checkin_type'];
        $checkIn->sync_status = CheckIn::SYNC_STATUS_PENDIENTE;
        $checkIn->pending_checkin_datetime = $data['pending_checkin_datetime'];
        $checkIn->synced_at = now();
        $checkIn->client_uuid = $data['client_uuid'];
        $checkIn->save();

        $workCenter = $checkIn->workCenter;

        return response()->json([
            'success' => true,
            'registrada' => true,
            'check_in' => [
                'id' => $checkIn->id,
                'tipo' => $checkIn->tipo,
                'checkin_type' => $checkIn->checkin_type,
                'sync_status' => $checkIn->sync_status,
                'fecha' => $checkIn->created_at->toISOString(),
                'dentro_rango' => $checkIn->dentro_rango,
                'distancia_metros' => (float) $checkIn->distancia_metros,
                'lat' => (float) $checkIn->lat,
                'lng' => (float) $checkIn->lng,
                'work_center' => $workCenter ? [
                    'id' => $workCenter->id,
                    'nombre' => $workCenter->nombre,
                    'radio_metros' => $workCenter->radio_metros,
                ] : null,
            ],
            'mensaje' => $this->mensaje($checkIn),
        ]);
    }

    private function resolveDevice($user, array $deviceData): Device
    {
        $uuid = $deviceData['uuid'] ?? null;

        if ($uuid) {
            return Device::updateOrCreate(
                ['uuid' => $uuid],
                array_merge($deviceData, [
                    'user_id' => $user->id,
                    'ultima_conexion_at' => now(),
                    'activo' => true,
                ])
            );
        }

        return Device::firstOrCreate(
            ['uuid' => 'device-' . $user->id . '-' . now()->format('YmdHisu')],
            [
                'user_id' => $user->id,
                'ultima_conexion_at' => now(),
                'activo' => true,
            ]
        );
    }

    private function mensaje(CheckIn $checkIn): string
    {
        if (! $checkIn->workCenter) {
            return 'Checada registrada, pero no se encontró un centro de trabajo válido.';
        }

        if ($checkIn->dentro_rango) {
            return "Checada de {$checkIn->tipo} registrada dentro del área de {$checkIn->workCenter->nombre}.";
        }

        return 'Checada registrada FUERA del área. Distancia: ' . round((float) $checkIn->distancia_metros) . ' m.';
    }
}
