<?php

namespace App\Filament\Widgets;

use App\Models\AuditEvent;
use App\Models\CheckIn;
use App\Models\CorrectionRequest;
use App\Models\Employee;
use App\Models\Incident;
use App\Models\WorkdayCalculation;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class InstitutionalDashboard extends Widget
{
    protected string $view = 'filament.widgets.institutional-dashboard';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    protected function getViewData(): array
    {
        $user = auth()->user();
        $checkIns = CheckIn::query()->visibleTo($user);
        $today = (clone $checkIns)->whereDate('created_at', today());
        $employees = Employee::query()->where('activo', true);
        $todayRecords = $today->with('employee')->get()->groupBy('employee_id');
        $incomplete = $todayRecords->filter(fn (Collection $records): bool => $records->contains('tipo', CheckIn::TIPO_ENTRADA) && ! $records->contains('tipo', CheckIn::TIPO_SALIDA))->count();
        $totalEmployees = (clone $employees)->count();
        $attendanceEmployees = $todayRecords->keys()->count();
        $pendingIncidents = Incident::query()->where('estado', 'pendiente')->count();
        $pendingCorrections = CorrectionRequest::query()->where('estado', 'pendiente')->count();
        $approvedIncidents = Incident::query()->whereIn('estado', ['aprobado', 'rechazado'])->count();
        $approvedOnly = Incident::query()->where('estado', 'aprobado')->count();
        $periodCalculations = WorkdayCalculation::query()->whereBetween('fecha', [now()->startOfMonth()->toDateString(), now()->toDateString()]);
        $calculated = (clone $periodCalculations)->count();
        $complete = (clone $periodCalculations)->where('estado', 'calculado')->count();
        $auditCheckIns = AuditEvent::query()->where('tipo_entidad', 'CheckIn')->count();
        $allCheckIns = CheckIn::query()->count();
        $overtimeMinutes = (int) (clone $periodCalculations)->sum('minutos_extra');
        $alerts = [];

        if ($incomplete > 0) {
            $alerts[] = ['tone' => 'danger', 'title' => $incomplete.' registro'.($incomplete === 1 ? '' : 's').' sin salida detectada.', 'url' => route('filament.admin.resources.check-ins.index')];
        }
        if ($pendingIncidents > 0) {
            $alerts[] = ['tone' => 'warning', 'title' => $pendingIncidents.' incidencia'.($pendingIncidents === 1 ? '' : 's').' requiere'.($pendingIncidents === 1 ? '' : 'n').' autorización.', 'url' => route('filament.admin.resources.incidents.index')];
        }
        if ($pendingCorrections > 0) {
            $alerts[] = ['tone' => 'info', 'title' => $pendingCorrections.' solicitud'.($pendingCorrections === 1 ? '' : 'es').' de corrección pendiente'.($pendingCorrections === 1 ? '' : 's').'.', 'url' => route('filament.admin.resources.correction-requests.index')];
        }

        return [
            'userName' => $user?->name ?? 'Usuario',
            'userRole' => $user?->getRoleNames()->first() ?? 'Panel administrativo',
            'totalEmployees' => $totalEmployees,
            'inactiveEmployees' => Employee::query()->where('activo', false)->count(),
            'attendanceEmployees' => $attendanceEmployees,
            'attendanceRate' => $totalEmployees > 0 ? round(($attendanceEmployees / $totalEmployees) * 100, 1) : 0,
            'pendingIncidents' => $pendingIncidents,
            'overtimeHours' => number_format($overtimeMinutes / 60, 1),
            'incomplete' => $incomplete,
            'completeRate' => $calculated > 0 ? round(($complete / $calculated) * 100) : 0,
            'authorizationRate' => $approvedIncidents > 0 ? round(($approvedOnly / $approvedIncidents) * 100) : 0,
            'traceabilityRate' => $allCheckIns > 0 ? min(100, round(($auditCheckIns / $allCheckIns) * 100)) : 0,
            'alerts' => $alerts,
            'latestCheckIns' => $checkIns->with(['employee', 'workCenter'])->latest()->limit(6)->get(),
            'latestIncidents' => Incident::query()->with('employee')->latest()->limit(5)->get(),
            'latestCorrections' => CorrectionRequest::query()->with('employee')->latest()->limit(5)->get(),
        ];
    }
}
