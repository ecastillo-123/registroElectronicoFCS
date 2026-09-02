<?php

namespace App\Filament\Resources\WorkCenterResource\Pages;

use App\Filament\Resources\WorkCenterResource;
use Filament\Resources\Pages\EditRecord;

class EditWorkCenter extends EditRecord
{
    protected static string $resource = WorkCenterResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return WorkCenterResource::normalizeCoordinates($data);
    }
}
