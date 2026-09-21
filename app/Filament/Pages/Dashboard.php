<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InstitutionalDashboard;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Inicio';

    public function getWidgets(): array
    {
        return [InstitutionalDashboard::class];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
