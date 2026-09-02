<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Employee;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $employee = Employee::find($data['employee_id'] ?? null);

        if (! $employee?->email) {
            throw ValidationException::withMessages([
                'employee_id' => 'El empleado seleccionado debe tener un correo electrónico registrado.',
            ]);
        }

        if (User::query()->where('email', $employee->email)->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => "Ya existe un usuario con el correo «{$employee->email}».",
            ]);
        }

        $data['email'] = $employee->email;

        return $data;
    }
}
