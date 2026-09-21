<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CorrectionRequestResource\Pages;
use App\Models\CheckIn;
use App\Models\CorrectionRequest;
use App\Models\Employee;
use App\Services\AuditService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CorrectionRequestResource extends Resource
{
    protected static ?string $model = CorrectionRequest::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;
    protected static string|\UnitEnum|null $navigationGroup = 'Operación';
    protected static ?string $navigationLabel = 'Solicitudes de corrección';
    protected static ?string $modelLabel = 'Solicitud de corrección';
    protected static ?string $pluralModelLabel = 'Solicitudes de corrección';
    public static function canViewAny(): bool { return auth()->user()?->can('gestionar_correcciones') ?? false; }
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('employee_id')
                ->label('Empleado')
                ->relationship('employee', 'numero_empleado')
                ->getOptionLabelFromRecordUsing(fn (Employee $employee): string => "{$employee->numero_empleado} — {$employee->nombre_completo}")
                ->searchable(['numero_empleado', 'nombre', 'apellido_paterno', 'apellido_materno'])
                ->preload()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('check_in_id', null))
                ->required(),
            Select::make('check_in_id')
                ->label('Registro original')
                ->relationship(
                    'checkIn',
                    'id',
                    fn (Builder $query, Get $get): Builder => filled($get('employee_id'))
                        ? $query->where('employee_id', $get('employee_id'))->latest('fecha_dispositivo')
                        : $query->whereRaw('1 = 0'),
                )
                ->getOptionLabelFromRecordUsing(fn (CheckIn $checkIn): string => ($checkIn->fecha_dispositivo?->format('d/m/Y H:i') ?? 'Sin fecha').' — '.ucfirst((string) $checkIn->tipo).' — '.$checkIn->estado_label)
                ->searchable()
                ->preload()
                ->disabled(fn (Get $get): bool => blank($get('employee_id')))
                ->helperText('Selecciona primero un empleado para ver sus registros.')
                ->required(),
            Select::make('tipo_propuesto')->label('Tipo propuesto')->options(['entrada' => 'Entrada', 'salida' => 'Salida'])->required(), DateTimePicker::make('fecha_hora_propuesta')->label('Fecha y hora propuesta')->required(), Textarea::make('motivo')->label('Motivo')->required(), Select::make('estado')->label('Estado')->options(['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado', 'cancelado' => 'Cancelado'])->default('pendiente')->required()]); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('employee.numero_empleado')->label('Empleado')->searchable(), TextColumn::make('check_in_id')->label('Registro original'), TextColumn::make('tipo_propuesto')->label('Tipo'), TextColumn::make('fecha_hora_propuesta')->label('Fecha propuesta')->dateTime('d/m/Y H:i'), TextColumn::make('estado')->label('Estado')->badge(), TextColumn::make('created_at')->label('Solicitada')->dateTime('d/m/Y H:i')])->recordActions([Action::make('aprobar')->label('Aprobar')->color('success')->visible(fn (CorrectionRequest $record) => $record->estado === 'pendiente')->requiresConfirmation()->action(fn (CorrectionRequest $record) => self::transition($record, 'aprobado')), Action::make('rechazar')->label('Rechazar')->color('danger')->visible(fn (CorrectionRequest $record) => $record->estado === 'pendiente')->requiresConfirmation()->action(fn (CorrectionRequest $record) => self::transition($record, 'rechazado'))]); }
    private static function transition(CorrectionRequest $record, string $status): void { $old = $record->estado; AuditService::withoutGenericEvents(fn () => $record->update(['estado' => $status, 'aprobado_por' => auth()->id(), 'aprobado_at' => now()])); if ($status === 'aprobado') { $record->effect()->create(['check_in_id' => $record->check_in_id, 'tipo' => $record->tipo_propuesto, 'fecha_hora' => $record->fecha_hora_propuesta]); } AuditService::append('status_changed', 'CorrectionRequest', $record->id, ['previous' => ['estado' => $old], 'current' => ['estado' => $status]]); }
    public static function getPages(): array { return ['index' => Pages\ListCorrectionRequests::route('/'), 'create' => Pages\CreateCorrectionRequest::route('/create'), 'edit' => Pages\EditCorrectionRequest::route('/{record}/edit')]; }
}
