<?php

namespace App\Filament\Widgets;

use App\Models\CheckIn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestCheckIns extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Últimos registros')
            ->query(
                CheckIn::query()
                    ->visibleTo(auth()->user())
                    ->with(['employee', 'workCenter'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === CheckIn::TIPO_ENTRADA ? 'success' : 'warning'),
                TextColumn::make('employee.numero_empleado')
                    ->label('No. empleado'),
                TextColumn::make('employee.nombre_completo')
                    ->label('Empleado'),
                TextColumn::make('workCenter.nombre')
                    ->label('Centro de trabajo')
                    ->placeholder('—'),
                TextColumn::make('distancia_metros')
                    ->label('Distancia')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1) . ' m' : '—'),
                TextColumn::make('dentro_rango')
                    ->label('Área')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Dentro' : 'Fuera')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),
            ]);
    }
}
