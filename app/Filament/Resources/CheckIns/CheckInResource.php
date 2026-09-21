<?php

namespace App\Filament\Resources\CheckIns;

use App\Filament\Resources\CheckIns\Pages\ListCheckIns;
use App\Filament\Resources\CheckIns\Schemas\CheckInForm;
use App\Filament\Resources\CheckIns\Tables\CheckInsTable;
use App\Models\CheckIn;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CheckInResource extends Resource
{
    protected static ?string $model = CheckIn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Registros';

    protected static ?string $modelLabel = 'Registro';

    protected static ?string $pluralModelLabel = 'Registros';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('ver_checadas') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return CheckInForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Registro')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Fecha')
                            ->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('tipo')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->color(fn (string $state): string => $state === CheckIn::TIPO_ENTRADA ? 'success' : 'warning'),
                        TextEntry::make('dentro_rango')
                            ->label('¿Dentro del área?')
                            ->badge()
                            ->formatStateUsing(fn (bool $state) => $state ? 'Dentro' : 'Fuera')
                            ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                        TextEntry::make('distancia_metros')
                            ->label('Distancia al centro')
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1) . ' m' : '—'),
                        TextEntry::make('validado')
                            ->label('Estatus')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                true => 'Aprobado',
                                false => 'Rechazado',
                                default => 'Pendiente',
                            })
                            ->color(fn ($state) => match ($state) {
                                true => 'success',
                                false => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('validado_por')
                            ->label('Validado por')
                            ->formatStateUsing(fn ($state) => $state ?: '—'),
                    ]),
                Section::make('Empleado y usuario')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.numero_empleado')
                            ->label('No. empleado'),
                        TextEntry::make('employee.nombre_completo')
                            ->label('Empleado'),
                        TextEntry::make('user.email')
                            ->label('Usuario'),
                        TextEntry::make('workCenter.nombre')
                            ->label('Centro de trabajo')
                            ->placeholder('—'),
                    ]),
                Section::make('Dispositivo y coordenadas')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('device.marca')
                            ->label('Marca')
                            ->placeholder('—'),
                        TextEntry::make('device.modelo')
                            ->label('Modelo')
                            ->placeholder('—'),
                        TextEntry::make('device.uuid')
                            ->label('ID del dispositivo')
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('lat')
                            ->label('Coordenadas')
                            ->formatStateUsing(fn (CheckIn $record) => number_format((float) $record->lat, 6) . ', ' . number_format((float) $record->lng, 6)),
                        TextEntry::make('precision_metros')
                            ->label('Precisión del GPS')
                            ->formatStateUsing(fn ($state) => $state !== null ? $state . ' m' : '—'),
                        TextEntry::make('fecha_dispositivo')
                            ->label('Fecha del dispositivo')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('—'),
                    ]),
                Section::make('Nota')
                    ->schema([
                        TextEntry::make('nota')
                            ->placeholder('Sin nota.'),
                    ]),
                Section::make('Ubicación en el mapa')
                    ->schema([
                        ViewEntry::make('mapa')
                            ->label('Mapa')
                            ->view('filament.components.checkin-map')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return CheckInsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCheckIns::route('/'),
        ];
    }
}
