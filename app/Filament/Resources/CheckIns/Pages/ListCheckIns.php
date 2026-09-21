<?php

namespace App\Filament\Resources\CheckIns\Pages;

use App\Filament\Resources\CheckIns\CheckInResource;
use App\Services\CheckInExporter;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\Response;

class ListCheckIns extends ListRecords
{
    protected static string $resource = CheckInResource::class;

    protected static ?string $title = 'Registros de Jornada';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportar_excel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn (): Response => $this->exportarExcel()),
            Action::make('exportar_pdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(fn (): Response => $this->exportarPdf()),
        ];
    }

    protected function exportarExcel(): Response
    {
        $records = $this->getFilteredTableQuery()->get();

        return CheckInExporter::excel($records, $this->tableFilters ?? []);
    }

    protected function exportarPdf(): Response
    {
        $records = $this->getFilteredTableQuery()->get();

        return CheckInExporter::pdf($records, $this->tableFilters ?? []);
    }
}
