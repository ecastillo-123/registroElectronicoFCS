<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\RuleSet;
use App\Models\WorkdayCalculation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkdayEngine
{
    public function calculate(Employee $employee, Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $start->copy()->addDay();
        $rule = RuleSet::query()->effectiveFor($start)->first();
        $dailyHours = (float) ($rule?->horas_diarias ?? $employee->shift?->horas_diarias ?? 8);
        $tolerance = (int) ($rule?->tolerancia_retardo_minutos ?? $employee->shift?->tolerancia_minutos ?? 10);
        $punches = $employee->checkIns()->whereBetween('created_at', [$start, $end])->orderBy('created_at')->get();
        $corrections = $employee->correctionRequests()->where('estado', 'aprobado')->with('effect')->get()->keyBy('check_in_id');

        $effective = $punches->map(function ($punch) use ($corrections): array {
            $effect = $corrections->get($punch->id)?->effect;
            return ['tipo' => $effect?->tipo ?? $punch->tipo, 'fecha' => $effect?->fecha_hora ?? $punch->created_at];
        });

        $entry = $effective->firstWhere('tipo', 'entrada');
        $exit = $effective->reverse()->firstWhere('tipo', 'salida');
        $gross = $entry && $exit ? max(0, $entry['fecha']->diffInMinutes($exit['fecha'])) : 0;
        $break = $employee->descanso_inicio && $employee->descanso_fin
            ? Carbon::parse($employee->descanso_inicio)->diffInMinutes(Carbon::parse($employee->descanso_fin))
            : 0;
        $worked = max(0, $gross - $break);
        $ordinary = min($worked, (int) round($dailyHours * 60));
        $threshold = (int) ($rule?->umbral_extra_minutos ?? 0);
        $overtime = $worked > $ordinary + $threshold ? $worked - $ordinary : 0;
        $late = 0;
        if ($entry && $employee->hora_entrada) {
            $expected = $start->copy()->setTimeFromTimeString((string) $employee->hora_entrada);
            $late = max(0, $expected->diffInMinutes($entry['fecha'], false) - $tolerance);
        }

        return compact('rule', 'punches', 'effective', 'gross', 'break', 'worked', 'ordinary', 'overtime', 'late', 'dailyHours');
    }

    public function persist(Employee $employee, Carbon $date): WorkdayCalculation
    {
        $result = $this->calculate($employee, $date);
        $rule = $result['rule'];
        return WorkdayCalculation::updateOrCreate(
            ['employee_id' => $employee->id, 'fecha' => $date->toDateString()],
            [
                'rule_set_id' => $rule?->id,
                'version_regla' => $rule?->version,
                'minutos_programados' => (int) round($result['dailyHours'] * 60),
                'minutos_brutos' => $result['gross'],
                'minutos_descanso' => $result['break'],
                'minutos_trabajados' => $result['worked'],
                'minutos_ordinarios' => $result['ordinary'],
                'minutos_extra' => $result['overtime'],
                'minutos_retardo' => $result['late'],
                'estado' => $result['effective']->contains(fn (array $punch) => $punch['tipo'] === 'entrada') && $result['effective']->contains(fn (array $punch) => $punch['tipo'] === 'salida') ? 'calculado' : 'incompleto',
                'detalles' => ['regla' => $rule?->parametros, 'chequeos' => $result['effective']->map(fn (array $punch) => ['tipo' => $punch['tipo'], 'fecha' => $punch['fecha']->toISOString()])->values()],
                'calculado_at' => now(),
            ],
        );
    }
}
