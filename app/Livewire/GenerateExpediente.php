<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Services\ExpedienteBuilder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class GenerateExpediente extends Component
{
    public const MIN_SEARCH_LENGTH = 3;

    public ?int $employee_id = null;

    public string $employeeSearch = '';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?array $report = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date   = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
        ];
    }

    protected function messages(): array
    {
        return [
            'employee_id.required'    => 'Selecciona un colaborador.',
            'employee_id.exists'      => 'El colaborador seleccionado no es válido.',
            'start_date.required'     => 'Selecciona la fecha inicial del periodo.',
            'start_date.date'         => 'La fecha inicial no es válida.',
            'end_date.required'       => 'Selecciona la fecha final del periodo.',
            'end_date.date'           => 'La fecha final no es válida.',
            'end_date.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ];
    }

    public function selectEmployee(int $employeeId): void
    {
        $employee = Employee::find($employeeId);

        if (! $employee) {
            return;
        }

        $this->employeeSearch = "{$employee->numero_empleado} — {$employee->nombre_completo}";
        $this->employee_id    = $employee->id;
    }

    public function updatedEmployeeSearch(): void
    {
        $this->employee_id = null;
    }

    public function generate(): void
    {
        $this->validate();

        $employee = Employee::with('workCenter.company', 'shift', 'user')->findOrFail($this->employee_id);

        $report = app(ExpedienteBuilder::class)->build(
            $employee,
            Carbon::parse($this->start_date),
            Carbon::parse($this->end_date),
        );

        $report['pdf_url'] = route('admin.expediente.pdf', [
            'employee_id' => $employee->id,
            'from'        => $report['start_date'],
            'to'          => $report['end_date'],
        ]);

        $this->report = $report;
    }

    public function render()
    {
        return view('livewire.generate-expediente', [
            'employeeResults' => $this->searchEmployees(),
            'minSearchLength' => self::MIN_SEARCH_LENGTH,
        ]);
    }

    /** @return Collection<int, Employee> */
    protected function searchEmployees(): Collection
    {
        $term = trim($this->employeeSearch);

        if (mb_strlen($term) < self::MIN_SEARCH_LENGTH) {
            return collect();
        }

        $like = '%' . $term . '%';

        return Employee::query()
            ->where('activo', true)
            ->where(function ($query) use ($like): void {
                $query->where('numero_empleado', 'like', $like)
                    ->orWhere('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like);
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->limit(15)
            ->get();
    }
}
