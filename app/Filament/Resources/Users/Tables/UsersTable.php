<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.numero_empleado')
                    ->label('No. empleado')
                    ->placeholder('—'),
                TextColumn::make('employee.nombre_completo')
                    ->label('Empleado')
                    ->placeholder('—'),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge(),
                ToggleColumn::make('is_active')
                    ->label('Activo'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
