<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ExpedientePage extends Page
{
    protected static ?string $navigationLabel = 'Expediente de Empleado';

    protected static ?string $title = 'Expediente de Empleado';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.expediente';

    public static function getNavigationGroup(): ?string
    {
        return 'Reportes';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-chart-bar';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ver_auditoria') ?? false;
    }
}
