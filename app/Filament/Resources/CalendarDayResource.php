<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalendarDayResource\Pages;
use App\Models\CalendarDay;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CalendarDayResource extends Resource
{
    protected static ?string $model = CalendarDay::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static string|\UnitEnum|null $navigationGroup = 'Operación';
    protected static ?string $navigationLabel = 'Calendario laboral';
    protected static ?string $modelLabel = 'Día laboral';
    protected static ?string $pluralModelLabel = 'Calendario laboral';
    public static function canViewAny(): bool { return auth()->user()?->can('gestionar_calendario') ?? false; }
    public static function form(Schema $schema): Schema { return $schema->components([DatePicker::make('fecha')->label('Fecha')->required()->native(false)->unique(ignoreRecord: true), Select::make('tipo')->label('Tipo')->required()->options(['laboral' => 'Día laboral', 'descanso' => 'Descanso', 'festivo' => 'Festivo', 'vacaciones' => 'Vacaciones']), TextInput::make('etiqueta')->label('Etiqueta')]); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('fecha')->label('Fecha')->date('d/m/Y')->sortable(), TextColumn::make('tipo')->label('Tipo')->badge(), TextColumn::make('etiqueta')->label('Etiqueta')->placeholder('—')])->defaultSort('fecha'); }
    public static function getPages(): array { return ['index' => Pages\ListCalendarDays::route('/'), 'create' => Pages\CreateCalendarDay::route('/create'), 'edit' => Pages\EditCalendarDay::route('/{record}/edit')]; }
}
