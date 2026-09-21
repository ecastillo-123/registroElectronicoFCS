<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del rol')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        CheckboxList::make('permissions')
                            ->label('Permisos')
                            ->relationship('permissions', 'name')
                            ->columns(2)
                            ->getOptionDescriptionFromRecordUsing(fn (Permission $permission): ?string => match ($permission->name) {
                                'ver_checadas' => 'Consultar el área de registros de jornada.',
                                'validar_checadas' => 'Aprobar o rechazar registros.',
                                'gestionar_centros' => 'Crear y editar centros de trabajo / sucursales.',
                                'gestionar_empresas' => 'Crear y editar cooperativas.',
                                'gestionar_empleados' => 'Crear y editar empleados.',
                                'gestionar_usuarios' => 'Crear y editar usuarios del sistema.',
                                'gestionar_roles' => 'Administrar roles y permisos.',
                                default => null,
                            }),
                    ]),
            ]);
    }
}
