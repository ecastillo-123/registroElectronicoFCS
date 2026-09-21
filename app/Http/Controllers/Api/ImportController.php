<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function employees(Request $request): JsonResponse
    {
        $data = $request->validate(['archivo' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $file = $data['archivo'];
        $contents = file_get_contents($file->getRealPath());
        $batch = ImportBatch::create(['nombre_archivo' => $file->getClientOriginalName(), 'sha256' => hash('sha256', $contents), 'estado' => 'procesando', 'subido_por' => $request->user()->id]);
        $handle = fopen($file->getRealPath(), 'rb');
        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), fgetcsv($handle) ?: []);
        $accepted = 0; $rejected = 0; $errors = []; $total = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $total++;
            $record = array_combine($headers, $row);
            if (! $record || blank($record['numero_empleado'] ?? null) || blank($record['nombre'] ?? null) || blank($record['apellido_paterno'] ?? null)) {
                $rejected++; $errors[] = ['fila' => $total + 1, 'error' => 'Faltan columnas obligatorias.']; continue;
            }
            try {
                DB::transaction(fn () => Employee::updateOrCreate(['numero_empleado' => $record['numero_empleado']], ['nombre' => $record['nombre'], 'apellido_paterno' => $record['apellido_paterno'], 'apellido_materno' => $record['apellido_materno'] ?? null, 'email' => $record['email'] ?? null, 'telefono' => $record['telefono'] ?? null, 'puesto' => $record['puesto'] ?? null, 'area' => $record['area'] ?? null, 'departamento' => $record['departamento'] ?? null, 'activo' => filter_var($record['activo'] ?? true, FILTER_VALIDATE_BOOLEAN)]));
                $accepted++;
            } catch (\Throwable $exception) { $rejected++; $errors[] = ['fila' => $total + 1, 'error' => $exception->getMessage()]; }
        }
        fclose($handle);
        $batch->update(['total_filas' => $total, 'filas_aceptadas' => $accepted, 'filas_rechazadas' => $rejected, 'estado' => $rejected ? 'completado_con_errores' : 'completado', 'errores' => $errors]);
        return response()->json($batch, 201);
    }
}
