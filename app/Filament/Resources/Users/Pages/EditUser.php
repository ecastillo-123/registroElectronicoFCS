<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $employee = Employee::find($data['employee_id'] ?? null);

        if ($employee?->email) {
            $query = User::query()
                ->where('email', $employee->email)
                ->whereKeyNot($this->record->getKey());

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'employee_id' => "Ya existe un usuario con el correo «{$employee->email}».",
                ]);
            }

            $data['email'] = $employee->email;
        }

        return $data;
    }
}
