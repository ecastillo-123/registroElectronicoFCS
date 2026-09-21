<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Models\Shift;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';
    protected static ?string $navigationLabel = 'Jornadas';
    protected static ?string $modelLabel = 'Jornada';
    protected static ?string $pluralModelLabel = 'Jornadas';
    public static function canViewAny(): bool { return auth()->user()?->can('gestionar_jornadas') ?? false; }
    public static function form(Schema $schema): Schema { return $schema->components([
        TextInput::make('codigo')->label('Código')->required()->unique(ignoreRecord: true), TextInput::make('nombre')->label('Nombre')->required(),
        TimePicker::make('hora_inicio')->label('Hora de entrada')->required()->seconds(false), TimePicker::make('hora_fin')->label('Hora de salida')->required()->seconds(false),
        TextInput::make('minutos_descanso')->label('Descanso (minutos)')->numeric()->default(0)->required(), TextInput::make('tolerancia_minutos')->label('Tolerancia (minutos)')->numeric()->default(10)->required(),
        TextInput::make('horas_semanales')->label('Horas semanales')->numeric()->default(40)->required(), TextInput::make('horas_diarias')->label('Horas diarias')->numeric()->default(8)->required(), Toggle::make('activo')->label('Activo')->default(true),
    ]); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('codigo')->label('Código')->searchable(), TextColumn::make('nombre')->label('Nombre')->searchable(), TextColumn::make('hora_inicio')->label('Entrada'), TextColumn::make('hora_fin')->label('Salida'), TextColumn::make('employees_count')->counts('employees')->label('Empleados'), ToggleColumn::make('activo')->label('Activo')])->defaultSort('nombre'); }
    public static function getPages(): array { return ['index' => Pages\ListShifts::route('/'), 'create' => Pages\CreateShift::route('/create'), 'edit' => Pages\EditShift::route('/{record}/edit')]; }
}
