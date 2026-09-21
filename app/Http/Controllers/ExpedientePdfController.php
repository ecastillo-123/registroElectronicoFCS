<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\ExpedienteBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExpedientePdfController extends Controller
{
    public function show(Request $request): Response
    {
        abort_unless($request->user()?->can('ver_auditoria'), 403);

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'from'        => 'required|date',
            'to'          => 'required|date|after_or_equal:from',
        ]);

        $employee = Employee::with('workCenter.company', 'shift', 'user')->findOrFail($data['employee_id']);

        $report = app(ExpedienteBuilder::class)->build(
            $employee,
            Carbon::parse($data['from']),
            Carbon::parse($data['to']),
        );

        $pdf = Pdf::loadView('pdf.expediente', ['report' => $report])
            ->setPaper('letter')
            ->output();

        $filename = 'expediente-' . $employee->numero_empleado . '-' . now()->format('Ymd_His') . '.pdf';

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
