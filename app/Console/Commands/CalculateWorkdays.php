<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\WorkdayEngine;
use Illuminate\Console\Command;

class CalculateWorkdays extends Command
{
    protected $signature = 'workday:calculate {--date= : Fecha YYYY-MM-DD} {--employee= : ID del empleado}';
    protected $description = 'Calcula y persiste la jornada diaria de los empleados';

    public function handle(WorkdayEngine $engine): int
    {
        $date = $this->option('date') ?: now()->toDateString();
        $employees = Employee::query()->when($this->option('employee'), fn ($query, $id) => $query->whereKey($id))->get();
        foreach ($employees as $employee) {
            $engine->persist($employee, now()->parse($date));
        }
        $this->info("Se calcularon {$employees->count()} empleados para {$date}.");
        return self::SUCCESS;
    }
}
