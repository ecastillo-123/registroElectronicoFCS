<?php

namespace App\Services;

use App\Models\CheckIn;
use App\Models\Device;
use App\Models\Employee;
use App\Models\WorkCenter;

class GeofenceService
{
    /**
     * Resuelve el centro de trabajo para un empleado.
     * Usa el centro asignado al empleado o, si no tiene, el más cercano activo.
     */
    public function resolveWorkCenter(Employee $employee, float $lat, float $lng): ?WorkCenter
    {
        if ($employee->work_center_id) {
            return $employee->workCenter;
        }

        return WorkCenter::query()
            ->where('activo', true)
            ->get()
            ->sortBy(fn (WorkCenter $wc) => $wc->distanceTo($lat, $lng))
            ->first();
    }

    /**
     * Registra la checada calculando si las coordenadas están dentro del rango del centro.
     */
    public function registrar(
        Employee $employee,
        int $userId,
        Device $device,
        string $tipo,
        float $lat,
        float $lng,
        ?float $precisionMetros = null,
        ?string $fechaDispositivo = null,
    ): CheckIn {
        $workCenter = $this->resolveWorkCenter($employee, $lat, $lng);

        $distancia = $workCenter
            ? $workCenter->distanceTo($lat, $lng)
            : null;

        $dentroRango = $workCenter !== null
            && $distancia !== null
            && $distancia <= $workCenter->radio_metros;

        return CheckIn::create([
            'employee_id' => $employee->id,
            'user_id' => $userId,
            'device_id' => $device->id,
            'work_center_id' => $workCenter?->id,
            'tipo' => $tipo,
            'lat' => $lat,
            'lng' => $lng,
            'precision_metros' => $precisionMetros,
            'fecha_dispositivo' => $fechaDispositivo,
            'distancia_metros' => $distancia,
            'dentro_rango' => $dentroRango,
        ]);
    }
}
