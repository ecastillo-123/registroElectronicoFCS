<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del usuario')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation) => $operation === 'create')
                            ->rule(Password::defaults())
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'Déjalo vacío para no cambiar la contraseña.' : null),
                        Select::make('employee_id')
                            ->label('Empleado')
                            ->options(fn () => Employee::query()
                                ->get()
                                ->mapWithKeys(fn (Employee $employee) => [$employee->id => "{$employee->numero_empleado} — {$employee->nombre_completo}"])
                                ->all())
                            ->searchable()
                            ->placeholder('Sin empleado')
                            ->helperText('El correo electrónico del usuario se toma del correo registrado en el empleado.'),
                        Select::make('roles')
                            ->label('Rol')
                            ->relationship('roles', 'name')
                            ->searchable()
                            ->preload()
                            ->multiple()
                            ->default(fn (): array => array_filter([
                                Role::query()->where('name', 'consulta')->value('id'),
                            ])),
                        Toggle::make('is_active')
                            ->label('Usuario activo')
                            ->default(true),
                    ]),
            ]);
    }
}
