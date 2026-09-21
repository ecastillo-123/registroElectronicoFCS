<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarDay;
use App\Models\CorrectionAction;
use App\Models\CorrectionRequest;
use App\Models\Employee;
use App\Models\Incident;
use App\Models\Alert;
use App\Models\RuleSet;
use App\Models\Shift;
use App\Services\AuditService;
use App\Services\WorkdayEngine;
use App\Services\CheckInExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkforceController extends Controller
{
    public function workers(Request $request): JsonResponse
    {
        return response()->json(Employee::query()->with(['workCenter', 'shift'])->when($request->string('q')->value(), function ($query, $q): void {
            $query->where('numero_empleado', 'like', "%{$q}%")->orWhere('nombre', 'like', "%{$q}%")->orWhere('apellido_paterno', 'like', "%{$q}%");
        })->paginate(50));
    }

    public function storeWorker(Request $request): JsonResponse
    {
        $employee = Employee::create($request->validate([
            'numero_empleado' => ['required', 'string', 'max:50', 'unique:employees,numero_empleado'],
            'nombre' => ['required', 'string'], 'apellido_paterno' => ['required', 'string'], 'apellido_materno' => ['nullable', 'string'],
            'email' => ['nullable', 'email'], 'telefono' => ['nullable', 'string'], 'puesto' => ['nullable', 'string'], 'area' => ['nullable', 'string'], 'departamento' => ['nullable', 'string'],
            'work_center_id' => ['nullable', 'exists:work_centers,id'], 'shift_id' => ['nullable', 'exists:shifts,id'], 'fecha_ingreso' => ['nullable', 'date'],
        ]));
        return response()->json($employee->load(['workCenter', 'shift']), 201);
    }

    public function updateWorker(Request $request, Employee $employee): JsonResponse
    {
        $employee->update($request->validate(['nombre' => ['sometimes', 'string'], 'apellido_paterno' => ['sometimes', 'string'], 'apellido_materno' => ['nullable', 'string'], 'email' => ['nullable', 'email'], 'telefono' => ['nullable', 'string'], 'puesto' => ['nullable', 'string'], 'area' => ['nullable', 'string'], 'departamento' => ['nullable', 'string'], 'shift_id' => ['nullable', 'exists:shifts,id'], 'activo' => ['sometimes', 'boolean'], 'fecha_baja' => ['nullable', 'date']]));
        return response()->json($employee->load(['workCenter', 'shift']));
    }

    public function shifts(): JsonResponse { return response()->json(Shift::query()->where('activo', true)->orderBy('nombre')->get()); }

    public function storeShift(Request $request): JsonResponse
    {
        return response()->json(Shift::create($request->validate(['codigo' => ['required', 'string', 'unique:shifts,codigo'], 'nombre' => ['required', 'string'], 'hora_inicio' => ['required', 'date_format:H:i'], 'hora_fin' => ['required', 'date_format:H:i'], 'minutos_descanso' => ['nullable', 'integer', 'min:0'], 'tolerancia_minutos' => ['nullable', 'integer', 'min:0'], 'horas_semanales' => ['required', 'numeric'], 'horas_diarias' => ['required', 'numeric']])), 201);
    }

    public function calendar(Request $request): JsonResponse
    {
        return response()->json(CalendarDay::query()->when($request->date('desde'), fn ($q, $date) => $q->whereDate('fecha', '>=', $date))->when($request->date('hasta'), fn ($q, $date) => $q->whereDate('fecha', '<=', $date))->orderBy('fecha')->get());
    }

    public function storeCalendar(Request $request): JsonResponse
    {
        return response()->json(CalendarDay::updateOrCreate(['fecha' => $request->date('fecha')], $request->validate(['fecha' => ['required', 'date'], 'tipo' => ['required', 'in:laboral,descanso,festivo,vacaciones'], 'etiqueta' => ['nullable', 'string']])));
    }

    public function rules(): JsonResponse { return response()->json(RuleSet::query()->orderByDesc('vigente_desde')->get()); }

    public function storeRule(Request $request): JsonResponse
    {
        return response()->json(RuleSet::create($request->validate(['nombre' => ['required', 'string'], 'version' => ['required', 'string'], 'vigente_desde' => ['required', 'date'], 'vigente_hasta' => ['nullable', 'date'], 'horas_semanales' => ['required', 'numeric'], 'horas_diarias' => ['required', 'numeric'], 'umbral_extra_minutos' => ['required', 'integer'], 'multiplicador_extra' => ['required', 'numeric'], 'tolerancia_retardo_minutos' => ['required', 'integer'], 'maximo_horas_diarias' => ['required', 'numeric'], 'parametros' => ['nullable', 'array']])), 201);
    }

    public function incidents(Request $request): JsonResponse { return response()->json(Incident::with('employee')->when($request->string('estado')->value(), fn ($q, $status) => $q->where('estado', $status))->latest()->paginate(50)); }

    public function storeIncident(Request $request): JsonResponse
    {
        $incident = Incident::create($request->validate(['employee_id' => ['required', 'exists:employees,id'], 'tipo' => ['required', 'in:retardo,inasistencia,permiso,falla_dispositivo,registro_faltante,vacaciones,descanso,otro'], 'motivo' => ['required', 'string']] + ['solicitado_por' => ['nullable', 'exists:users,id']]));
        return response()->json($incident, 201);
    }

    public function corrections(Request $request): JsonResponse { return response()->json(CorrectionRequest::with(['employee', 'checkIn', 'requester', 'effect'])->when($request->string('estado')->value(), fn ($q, $status) => $q->where('estado', $status))->latest()->paginate(50)); }

    public function storeCorrection(Request $request): JsonResponse
    {
        $correction = CorrectionRequest::create($request->validate(['employee_id' => ['required', 'exists:employees,id'], 'check_in_id' => ['required', 'exists:check_ins,id'], 'tipo_propuesto' => ['required', 'in:entrada,salida'], 'fecha_hora_propuesta' => ['required', 'date'], 'motivo' => ['required', 'string']]));
        return response()->json($correction, 201);
    }

    public function authorizeCorrection(Request $request, CorrectionRequest $correction): JsonResponse
    {
        abort_unless($correction->estado === 'pendiente', 422, 'La solicitud ya fue procesada.');
        $approved = $request->validate(['aprobar' => ['required', 'boolean'], 'nota' => ['nullable', 'string']]);
        $newStatus = $approved['aprobar'] ? 'aprobado' : 'rechazado';
        DB::transaction(function () use ($correction, $approved, $newStatus, $request): void {
            $oldStatus = $correction->estado;
            AuditService::withoutGenericEvents(fn () => $correction->update(['estado' => $newStatus, 'aprobado_por' => $request->user()->id, 'aprobado_at' => now()]));
            CorrectionAction::create(['correction_request_id' => $correction->id, 'accion' => $newStatus, 'actor_user_id' => $request->user()->id, 'estado_anterior' => $oldStatus, 'estado_nuevo' => $newStatus, 'nota' => $approved['nota'] ?? null]);
            if ($approved['aprobar']) {
                $correction->effect()->create(['check_in_id' => $correction->check_in_id, 'tipo' => $correction->tipo_propuesto, 'fecha_hora' => $correction->fecha_hora_propuesta]);
            }
            AuditService::append('status_changed', 'CorrectionRequest', $correction->id, ['previous' => ['estado' => $oldStatus], 'current' => ['estado' => $newStatus], 'reason' => $approved['nota'] ?? null]);
        });
        return response()->json($correction->fresh(['effect', 'actions']));
    }

    public function calculate(Request $request, WorkdayEngine $engine): JsonResponse
    {
        $data = $request->validate(['employee_id' => ['required', 'exists:employees,id'], 'fecha' => ['required', 'date']]);
        $result = $engine->persist(Employee::findOrFail($data['employee_id']), now()->parse($data['fecha']));
        return response()->json($result->load(['employee', 'ruleSet']));
    }

    public function audit(): JsonResponse { return response()->json(AuditService::verify()); }

    public function auditEvents(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('ver_auditoria'), 403);
        return response()->json(\App\Models\AuditEvent::query()->with('actor')->latest('id')->paginate(100));
    }

    public function dossier(Employee $employee): JsonResponse
    {
        return response()->json($employee->load(['workCenter', 'shift', 'checkIns' => fn ($query) => $query->latest(), 'incidents', 'correctionRequests.effect', 'workdayCalculations']));
    }

    public function reportPdf(Request $request, Employee $employee)
    {
        return CheckInExporter::pdf($employee->checkIns()->with(['employee', 'workCenter.company', 'user', 'device', 'validator'])->latest()->get(), $request->all());
    }

    public function reportExcel(Request $request, Employee $employee)
    {
        return CheckInExporter::excel($employee->checkIns()->with(['employee', 'workCenter.company', 'user', 'device', 'validator'])->latest()->get(), $request->all());
    }

    public function alerts(): JsonResponse { return response()->json(Alert::query()->whereNull('resuelta_at')->latest()->paginate(50)); }

    public function resolveAlert(Alert $alert): JsonResponse { $alert->update(['resuelta_at' => now()]); return response()->json($alert); }
}
