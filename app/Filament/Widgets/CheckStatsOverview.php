<?php

namespace App\Filament\Widgets;

use App\Models\CheckIn;
use App\Models\Employee;
use App\Models\WorkCenter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CheckStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $checkIns = CheckIn::query()->visibleTo($user);

        $stats = [
            Stat::make('Checadas de hoy', (clone $checkIns)->whereDate('created_at', today())->count())
                ->description('Registros de entrada y salida')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),
            Stat::make('Pendientes por validar', (clone $checkIns)->whereNull('validado')->count())
                ->description('Requieren revisión manual')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Dentro del área (hoy)', (clone $checkIns)->whereDate('created_at', today())->where('dentro_rango', true)->count())
                ->description('Checadas en rango válido')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];

        if ($user && $user->hasAnyRole(['admin', 'revisor'])) {
            $stats[] = Stat::make('Centros de trabajo activos', WorkCenter::query()->where('activo', true)->count())
                ->description('Empleados activos: ' . Employee::query()->where('activo', true)->count())
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info');
        }

        return $stats;
    }
}
