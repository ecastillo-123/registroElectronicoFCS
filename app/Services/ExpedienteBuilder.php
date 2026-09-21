<?php

namespace App\Services;

use App\Models\CheckIn;
use App\Models\Employee;
use Carbon\CarbonInterface;

class ExpedienteBuilder
{
    /**
     * Build the workday expediente for an employee inside a date range (inclusive).
     *
     * @return array<string, mixed>
     */
    public function build(Employee $employee, CarbonInterface $start, CarbonInterface $end): array
    {
        $from = $start->copy()->startOfDay();
        $to   = $end->copy()->endOfDay();

        $checkIns = CheckIn::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('fecha_dispositivo', [$from, $to])
            ->with(['device', 'effect'])
            ->orderBy('fecha_dispositivo')
            ->get();

        $corrections = $checkIns->filter(fn (CheckIn $ci) => $ci->effect)->pluck('id', 'id');

        $byDate = $checkIns->groupBy(fn (CheckIn $c) => $c->fecha_dispositivo?->format('d/m/Y') ?? '?');

        $events = [];
        $totalOrdinaryMinutes = 0;

        foreach ($byDate as $date => $dayCheckIns) {
            $dayEvents = [];
            $entries   = $dayCheckIns->where('tipo', 'entrada')->values();
            $exits     = $dayCheckIns->where('tipo', 'salida')->values();

            foreach ($entries as $entry) {
                $dayEvents[] = [
                    'date'        => $date,
                    'time'        => $entry->fecha_dispositivo?->format('H:i:s') ?? '?',
                    'event'       => 'Entrada',
                    'origin'      => $entry->device?->modelo ?? $entry->checkin_type ?? '—',
                    'status'      => $corrections->has($entry->id) ? 'Corregido' : 'Original',
                    'is_original' => ! $corrections->has($entry->id),
                ];
            }

            foreach ($exits as $exit) {
                $dayEvents[] = [
                    'date'        => $date,
                    'time'        => $exit->fecha_dispositivo?->format('H:i:s') ?? '?',
                    'event'       => 'Salida',
                    'origin'      => $exit->device?->modelo ?? $exit->checkin_type ?? '—',
                    'status'      => $corrections->has($exit->id) ? 'Corregido' : 'Original',
                    'is_original' => ! $corrections->has($exit->id),
                ];
            }

            usort($dayEvents, fn (array $a, array $b) => strcmp($a['time'], $b['time']));
            $events = array_merge($events, $dayEvents);

            foreach ($entries as $i => $entry) {
                if (isset($exits[$i])) {
                    $diff = (int) $entry->fecha_dispositivo?->diffInMinutes($exits[$i]->fecha_dispositivo);
                    if ($diff > 0 && $diff < 720) {
                        $totalOrdinaryMinutes += $diff;
                    }
                }
            }
        }

        $ordinaryHours   = intdiv($totalOrdinaryMinutes, 60);
        $ordinaryMinutes = $totalOrdinaryMinutes % 60;

        return [
            'employee'          => $employee,
            'period'            => $from->format('d/m/Y') . ' — ' . $to->format('d/m/Y'),
            'start_date'        => $from->toDateString(),
            'end_date'          => $to->toDateString(),
            'events'            => $events,
            'total'             => count($events),
            'summary'           => [
                'ordinary'  => "{$ordinaryHours}h {$ordinaryMinutes}m",
                'effective' => "{$ordinaryHours}h {$ordinaryMinutes}m",
                'overtime'  => '0h 0m',
            ],
            'corrections_count' => count($corrections),
            'folio'             => 'EXP-' . now()->format('Y') . '-' . str_pad((string) $employee->id, 5, '0', STR_PAD_LEFT),
            'generated_at'      => now()->format('d/m/Y H:i'),
            'generated_by'      => auth()->user()?->name ?? 'Sistema',
        ];
    }
}
