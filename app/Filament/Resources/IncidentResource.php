<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncidentResource\Pages;
use App\Models\Incident;
use App\Services\AuditService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;
    protected static string|\UnitEnum|null $navigationGroup = 'Operación';
    protected static ?string $navigationLabel = 'Incidencias';
    protected static ?string $modelLabel = 'Incidencia';
    protected static ?string $pluralModelLabel = 'Incidencias';
    public static function canViewAny(): bool { return auth()->user()?->can('gestionar_incidencias') ?? false; }
    public static function form(Schema $schema): Schema { return $schema->components([Select::make('employee_id')->label('Empleado')->relationship('employee', 'numero_empleado')->searchable()->preload()->required(), Select::make('tipo')->label('Tipo')->options(['retardo' => 'Retardo', 'inasistencia' => 'Inasistencia', 'permiso' => 'Permiso', 'falla_dispositivo' => 'Falla de dispositivo', 'registro_faltante' => 'Registro faltante', 'vacaciones' => 'Vacaciones', 'descanso' => 'Descanso', 'otro' => 'Otro'])->required(), Textarea::make('motivo')->label('Motivo')->required(), Select::make('estado')->label('Estado')->options(['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado', 'cancelado' => 'Cancelado'])->default('pendiente')->required()]); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('employee.numero_empleado')->label('Empleado')->searchable(), TextColumn::make('tipo')->label('Tipo')->badge(), TextColumn::make('estado')->label('Estado')->badge(), TextColumn::make('motivo')->label('Motivo')->limit(50), TextColumn::make('created_at')->label('Creada')->dateTime('d/m/Y H:i')->sortable()])->recordActions([Action::make('aprobar')->label('Aprobar')->color('success')->icon('heroicon-o-check')->visible(fn (Incident $record) => $record->estado === 'pendiente')->requiresConfirmation()->action(function (Incident $record): void { AuditService::withoutGenericEvents(fn () => $record->update(['estado' => 'aprobado', 'aprobado_por' => auth()->id(), 'aprobado_at' => now()])); AuditService::append('approved', 'Incident', $record->id, ['current' => $record->toArray()]); }), Action::make('rechazar')->label('Rechazar')->color('danger')->visible(fn (Incident $record) => $record->estado === 'pendiente')->requiresConfirmation()->action(function (Incident $record): void { AuditService::withoutGenericEvents(fn () => $record->update(['estado' => 'rechazado', 'aprobado_por' => auth()->id(), 'aprobado_at' => now()])); AuditService::append('rejected', 'Incident', $record->id, ['current' => $record->toArray()]); })]); }
    public static function getPages(): array { return ['index' => Pages\ListIncidents::route('/'), 'create' => Pages\CreateIncident::route('/create'), 'edit' => Pages\EditIncident::route('/{record}/edit')]; }
}
