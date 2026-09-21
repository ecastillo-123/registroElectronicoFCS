<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RuleSetResource\Pages;
use App\Models\RuleSet;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RuleSetResource extends Resource
{
    protected static ?string $model = RuleSet::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;
    protected static string|\UnitEnum|null $navigationGroup = 'Operación';
    protected static ?string $navigationLabel = 'Reglas de jornada';
    protected static ?string $modelLabel = 'Regla';
    protected static ?string $pluralModelLabel = 'Reglas';
    public static function canViewAny(): bool { return auth()->user()?->can('gestionar_reglas') ?? false; }
    public static function form(Schema $schema): Schema { return $schema->components([
        TextInput::make('nombre')->label('Nombre')->required(), TextInput::make('version')->label('Versión')->required(), DatePicker::make('vigente_desde')->label('Vigente desde')->required()->native(false), DatePicker::make('vigente_hasta')->label('Vigente hasta')->native(false),
        TextInput::make('horas_semanales')->label('Horas semanales')->numeric()->required()->default(40), TextInput::make('horas_diarias')->label('Horas diarias')->numeric()->required()->default(8), TextInput::make('umbral_extra_minutos')->label('Umbral extra (minutos)')->numeric()->required()->default(0), TextInput::make('multiplicador_extra')->label('Multiplicador extra')->numeric()->required()->default(2), TextInput::make('tolerancia_retardo_minutos')->label('Tolerancia de retardo')->numeric()->required()->default(10), TextInput::make('maximo_horas_diarias')->label('Máximo de horas diarias')->numeric()->required()->default(12), Toggle::make('activo')->label('Activa')->default(true),
    ]); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('nombre')->label('Nombre')->searchable(), TextColumn::make('version')->label('Versión'), TextColumn::make('vigente_desde')->label('Desde')->date('d/m/Y'), TextColumn::make('vigente_hasta')->label('Hasta')->date('d/m/Y')->placeholder('Sin vencimiento'), TextColumn::make('horas_diarias')->label('Horas diarias'), TextColumn::make('activo')->label('Activa')->badge()])->defaultSort('vigente_desde', 'desc'); }
    public static function getPages(): array { return ['index' => Pages\ListRuleSets::route('/'), 'create' => Pages\CreateRuleSet::route('/create'), 'edit' => Pages\EditRuleSet::route('/{record}/edit')]; }
}
