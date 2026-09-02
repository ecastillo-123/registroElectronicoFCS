<?php

namespace App\Filament\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_empleado')
                    ->label('No. empleado')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('nombre_completo')
                    ->label('Nombre completo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('workCenter.nombre')
                    ->label('Centro de trabajo')
                    ->searchable()
                    ->placeholder('—'),
                ToggleColumn::make('activo')
                    ->label('Activo'),
            ])
            ->filters([
                SelectFilter::make('work_center_id')
                    ->label('Centro de trabajo')
                    ->relationship('workCenter', 'nombre')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('activo')
                    ->label('Activo'),
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
