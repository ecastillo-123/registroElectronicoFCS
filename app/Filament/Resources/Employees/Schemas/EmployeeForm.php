<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del empleado')
                    ->columns(2)
                    ->schema([
                        TextInput::make('numero_empleado')
                            ->label('Número de empleado')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('apellido_paterno')
                            ->label('Apellido paterno')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('apellido_materno')
                            ->label('Apellido materno')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(50),
                        Select::make('work_center_id')
                            ->label('Centro de trabajo')
                            ->relationship('workCenter', 'nombre')
                            ->searchable()
                            ->preload()
                            ->placeholder('Sin asignar'),
                        Toggle::make('activo')
                            ->label('Empleado activo')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
                Section::make('Datos de trabajador')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('identificador_unico')
                            ->label('Identificador único')
                            ->maxLength(100)
                            ->placeholder('Ej. CURP, RFC o folio'),
                        DatePicker::make('fecha_activacion')
                            ->label('Fecha de activación')
                            ->native(false),
                        TextInput::make('puesto')
                            ->label('Puesto')
                            ->maxLength(150)
                            ->placeholder('Ej. Operador de producción'),
                        TextInput::make('area')
                            ->label('Área')
                            ->maxLength(150)
                            ->placeholder('Ej. Línea 1'),
                        TextInput::make('departamento')
                            ->label('Departamento')
                            ->maxLength(150),
                        DatePicker::make('fecha_ingreso')
                            ->label('Fecha de ingreso')
                            ->native(false),
                        DatePicker::make('fecha_baja')
                            ->label('Fecha de baja')
                            ->native(false),
                        Select::make('estado_laboral')
                            ->label('Estado laboral')
                            ->options([
                                'activo' => 'Activo',
                                'inactivo' => 'Inactivo',
                                'licencia' => 'En licencia',
                            ])
                            ->default('activo')
                            ->required(),
                        Select::make('shift_id')
                            ->label('Jornada')
                            ->relationship('shift', 'nombre')
                            ->searchable()
                            ->preload()
                            ->placeholder('Usar horario individual'),
                        TextInput::make('horario_asignado')
                            ->label('Horario asignado')
                            ->maxLength(150)
                            ->placeholder('Ej. 06:00 – 14:00'),
                        Select::make('device_id')
                            ->label('Dispositivo asignado')
                            ->relationship('device', 'uuid')
                            ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->marca} {$record->modelo}").' ('.$record->uuid.')')
                            ->searchable()
                            ->preload()
                            ->placeholder('Sin asignar'),
                    ]),
                Section::make('Horario')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TimePicker::make('hora_entrada')
                            ->label('Hora de entrada')
                            ->seconds(false)
                            ->native(false),
                        TimePicker::make('hora_salida')
                            ->label('Hora de salida')
                            ->seconds(false)
                            ->native(false),
                        TimePicker::make('descanso_inicio')
                            ->label('Inicio de descanso')
                            ->seconds(false)
                            ->native(false),
                        TimePicker::make('descanso_fin')
                            ->label('Fin de descanso')
                            ->seconds(false)
                            ->native(false),
                    ]),
            ]);
    }
}
