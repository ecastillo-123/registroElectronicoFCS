<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditEventResource\Pages;
use App\Models\AuditEvent;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditEventResource extends Resource
{
    protected static ?string $model = AuditEvent::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';
    protected static ?string $navigationLabel = 'Auditoría';
    protected static ?string $modelLabel = 'Evento de auditoría';
    protected static ?string $pluralModelLabel = 'Auditoría';
    public static function canViewAny(): bool { return auth()->user()?->can('ver_auditoria') ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function form(Schema $schema): Schema { return $schema->components([]); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('ocurrido_at')->label('Fecha')->dateTime('d/m/Y H:i:s')->sortable(), TextColumn::make('tipo_evento')->label('Evento')->badge(), TextColumn::make('tipo_entidad')->label('Entidad'), TextColumn::make('entidad_id')->label('ID'), TextColumn::make('actor.name')->label('Usuario')->placeholder('Sistema'), TextColumn::make('hash')->label('Hash')->copyable()->limit(18), TextColumn::make('hash_anterior')->label('Hash anterior')->limit(18)])->defaultSort('id', 'desc'); }
    public static function getPages(): array { return ['index' => Pages\ListAuditEvents::route('/')]; }
}
