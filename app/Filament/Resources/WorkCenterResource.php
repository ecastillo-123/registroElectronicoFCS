<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkCenterResource\Pages;
use App\Models\WorkCenter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

class WorkCenterResource extends Resource
{
    protected static ?string $model = WorkCenter::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $navigationLabel = 'Centros de Trabajo';

    protected static ?string $modelLabel = 'Centro de Trabajo';

    protected static ?string $pluralModelLabel = 'Centros de Trabajo';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('gestionar_centros') ?? false;
    }

    /**
     * Normaliza lat/lng por si el cliente envió la coordenada sin el punto
     * decimal (ej. -991815521 en lugar de -99.1815521). Una latitud válida
     * nunca excede 90 y una longitud nunca excede 180 en valor absoluto.
     */
    public static function normalizeCoordinates(array $data): array
    {
        $data['lat'] = self::normalizar($data['lat'] ?? null, 90);
        $data['lng'] = self::normalizar($data['lng'] ?? null, 180);

        return $data;
    }

    private static function normalizar(mixed $value, float $maxAbs): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $n = (float) $value;

        if (abs($n) > $maxAbs) {
            $n = $n / 1e7;
        }

        return round($n, 7);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del centro')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Empresa')
                            ->relationship('company', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Selecciona una empresa'),
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('direccion')
                            ->label('Dirección')
                            ->maxLength(255),
                        TextInput::make('radio_metros')
                            ->label('Radio de geocerca')
                            ->numeric()
                            ->required()
                            ->minValue(10)
                            ->maxValue(5000)
                            ->default(100)
                            ->suffix('m')
                            ->helperText('Distancia máxima (metros) desde el centro en la que se considera "dentro del área".'),
                        Toggle::make('activo')
                            ->label('Centro activo')
                            ->default(true),
                    ]),
                Section::make('Ubicación (geocerca)')
                    ->schema([
                        View::make('filament.components.map-picker')
                            ->columnSpanFull(),
                        TextInput::make('lat')
                            ->label('Latitud')
                            ->numeric()
                            ->required()
                            ->step('0.0000001')
                            ->live(),
                        TextInput::make('lng')
                            ->label('Longitud')
                            ->numeric()
                            ->required()
                            ->step('0.0000001')
                            ->live(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.nombre')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('direccion')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('lat')
                    ->label('Latitud')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 6))
                    ->toggleable(),
                TextColumn::make('lng')
                    ->label('Longitud')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 6))
                    ->toggleable(),
                TextColumn::make('radio_metros')
                    ->label('Radio (m)')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state) . ' m'),
                TextColumn::make('employees_count')
                    ->label('Empleados')
                    ->counts('employees')
                    ->sortable(),
                ToggleColumn::make('activo')
                    ->label('Activo'),
            ])
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Activo'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkCenters::route('/'),
            'create' => Pages\CreateWorkCenter::route('/create'),
            'edit' => Pages\EditWorkCenter::route('/{record}/edit'),
        ];
    }
}
