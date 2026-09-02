<?php

namespace App\Filament\Resources\CheckIns\Tables;

use App\Models\CheckIn;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CheckInsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->weight('bold')
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('hora')
                    ->label('Hora')
                    ->getStateUsing(fn (CheckIn $record): string => $record->created_at?->format('H:i:s') ?? '')
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === CheckIn::TIPO_ENTRADA ? 'success' : 'warning')
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('employee.numero_empleado')
                    ->label('No. empleado')
                    ->searchable()
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('employee.nombre_completo')
                    ->label('Empleado')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn (CheckIn $record) => $record->employee?->nombre_completo)
                    ->description(fn (CheckIn $record) => $record->user?->email)
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('workCenter.company.nombre')
                    ->label('Empresa')
                    ->placeholder('—')
                    ->limit(20)
                    ->tooltip(fn (CheckIn $record) => $record->workCenter?->company?->nombre)
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('device.modelo')
                    ->label('Dispositivo')
                    ->placeholder('—')
                    ->limit(15)
                    ->tooltip(fn (CheckIn $record) => $record->device?->modelo)
                    ->description(fn (CheckIn $record) => $record->device?->marca)
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('workCenter.direccion')
                    ->label('Dirección')
                    ->placeholder('—')
                    ->limit(25)
                    ->tooltip(fn (CheckIn $record) => $record->workCenter?->direccion)
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('workCenter.nombre')
                    ->label('Centro de trabajo')
                    ->placeholder('—')
                    ->limit(20)
                    ->tooltip(fn (CheckIn $record) => $record->workCenter?->nombre)
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('lat')
                    ->label('Ubicación')
                    ->formatStateUsing(fn (CheckIn $record) => number_format((float) $record->lat, 3).', '.number_format((float) $record->lng, 3))
                    ->description(fn (CheckIn $record) => $record->distancia_metros !== null ? number_format((float) $record->distancia_metros, 0).' m' : null)
                    ->placeholder('—')
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('dentro_rango')
                    ->label('¿Dentro del área?')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Dentro' : 'Fuera')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger')
                    ->extraAttributes(['class' => 'text-xs']),
                TextColumn::make('validado')
                    ->label('Estatus')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        true => 'Aprobado',
                        false => 'Rechazado',
                        default => 'Pendiente',
                    })
                    ->color(fn ($state) => match ($state) {
                        true => 'success',
                        false => 'danger',
                        default => 'gray',
                    })
                    ->extraAttributes(['class' => 'text-xs']),
            ])
            ->filters([
                Filter::make('rango_fechas')
                    ->label('Rango de fechas')
                    ->schema([
                        DatePicker::make('desde')
                            ->label('Desde')
                            ->native(false),
                        DatePicker::make('hasta')
                            ->label('Hasta')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['desde'] ?? null),
                                fn (Builder $query) => $query->whereDate('created_at', '>=', $data['desde']),
                            )
                            ->when(
                                filled($data['hasta'] ?? null),
                                fn (Builder $query) => $query->whereDate('created_at', '<=', $data['hasta']),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (filled($data['desde'] ?? null)) {
                            $indicators['desde'] = 'Desde: '.date('d/m/Y', strtotime($data['desde']));
                        }
                        if (filled($data['hasta'] ?? null)) {
                            $indicators['hasta'] = 'Hasta: '.date('d/m/Y', strtotime($data['hasta']));
                        }

                        return $indicators;
                    }),
                Filter::make('numero_empleado')
                    ->label('No. de empleado')
                    ->schema([
                        TextInput::make('value')
                            ->label('No. de empleado')
                            ->placeholder('Ej. EMP-001')
                            ->autocomplete('off'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! filled($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('employee', function (Builder $query) use ($data): void {
                            $query->where('numero_empleado', 'like', '%'.$data['value'].'%');
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        return filled($data['value'] ?? null)
                            ? ['No. de empleado: "'.$data['value'].'"']
                            : [];
                    }),
                Filter::make('nombre_empleado')
                    ->label('Nombre de empleado')
                    ->schema([
                        TextInput::make('value')
                            ->label('Nombre de empleado')
                            ->placeholder('Nombre o apellidos')
                            ->autocomplete('off'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! filled($data['value'] ?? null)) {
                            return $query;
                        }
                        $busqueda = '%'.$data['value'].'%';

                        return $query->whereHas('employee', function (Builder $query) use ($busqueda): void {
                            $query->where(function (Builder $query) use ($busqueda): void {
                                $query->where('nombre', 'like', $busqueda)
                                    ->orWhere('apellido_paterno', 'like', $busqueda)
                                    ->orWhere('apellido_materno', 'like', $busqueda);
                            });
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        return filled($data['value'] ?? null)
                            ? ['Nombre: "'.$data['value'].'"']
                            : [];
                    }),
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                    ]),
                SelectFilter::make('work_center_id')
                    ->label('Centro de trabajo')
                    ->relationship('workCenter', 'nombre')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('employee_id')
                    ->label('Empleado')
                    ->relationship('employee', 'numero_empleado')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('dentro_rango')
                    ->label('Dentro del área'),
                TernaryFilter::make('validado')
                    ->label('Estatus')
                    ->trueLabel('Aprobado')
                    ->falseLabel('Rechazado')
                    ->placeholder('Pendiente'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver detalle'),
                self::aprobarAction(),
                self::rechazarAction(),
            ]);
    }

    private static function aprobarAction(): Action
    {
        return Action::make('aprobar')
            ->label('Aprobar')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (): bool => Auth::user()?->can('validar_checadas') ?? false)
            ->requiresConfirmation()
            ->modalHeading('Aprobar checada')
            ->modalDescription('¿Confirmas la aprobación de esta checada?')
            ->action(function (CheckIn $record): void {
                $record->update([
                    'validado' => true,
                    'validado_por' => Auth::id(),
                    'validado_at' => now(),
                ]);
            })
            ->after(fn () => Notification::make()->title('Checada aprobada')->success()->send());
    }

    private static function rechazarAction(): Action
    {
        return Action::make('rechazar')
            ->label('Rechazar')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (): bool => Auth::user()?->can('validar_checadas') ?? false)
            ->requiresConfirmation()
            ->modalHeading('Rechazar checada')
            ->modalDescription('Indica el motivo del rechazo.')
            ->form([
                Textarea::make('nota')
                    ->label('Motivo')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
            ])
            ->action(function (CheckIn $record, array $data): void {
                $record->update([
                    'validado' => false,
                    'validado_por' => Auth::id(),
                    'validado_at' => now(),
                    'nota' => $data['nota'] ?? null,
                ]);
            })
            ->after(fn () => Notification::make()->title('Checada rechazada')->danger()->send());
    }
}
