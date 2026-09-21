<?php
namespace App\Filament\Resources\ShiftResource\Pages;
use App\Filament\Resources\ShiftResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListShifts extends ListRecords { protected static string $resource = ShiftResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }
