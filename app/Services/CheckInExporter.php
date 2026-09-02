<?php

namespace App\Services;

use App\Models\CheckIn;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;

class CheckInExporter
{
    /**
     * @return array<string, mixed>
     */
    protected static function metadatos(array $tableFilters): array
    {
        $rango = $tableFilters['rango_fechas'] ?? [];

        return [
            'desde' => $rango['desde'] ?? null,
            'hasta' => $rango['hasta'] ?? null,
            'numero_empleado' => $tableFilters['numero_empleado']['value'] ?? null,
            'nombre_empleado' => $tableFilters['nombre_empleado']['value'] ?? null,
        ];
    }

    /**
     * @param  Collection<int, CheckIn>  $records
     */
    public static function excel(Collection $records, array $tableFilters): Response
    {
        $tmp = tempnam(sys_get_temp_dir(), 'checadas_').'.xlsx';

        $writer = new Writer(new Options);
        $writer->openToFile($tmp);

        $writer->addRow(Row::fromValues([
            'Fecha',
            'Hora',
            'Tipo',
            'No. empleado',
            'Empleado',
            'Empresa',
            'Usuario',
            'Centro de trabajo',
            'Dispositivo',
            'Dirección',
            'Coordenadas',
            'Distancia (m)',
            'Dentro del área',
            'Nota',
            'Estatus',
            'Validado por',
            'Validado en',
        ]));

        foreach ($records as $checkIn) {
            $writer->addRow(Row::fromValues([
                $checkIn->created_at?->format('d/m/Y'),
                $checkIn->created_at?->format('H:i:s'),
                ucfirst((string) $checkIn->tipo),
                $checkIn->employee?->numero_empleado ?? '',
                $checkIn->employee?->nombre_completo ?? '',
                $checkIn->workCenter?->company?->nombre ?? '',
                $checkIn->user?->email ?? '',
                $checkIn->workCenter?->nombre ?? '',
                $checkIn->device ? trim($checkIn->device->marca.' '.$checkIn->device->modelo) : '',
                $checkIn->workCenter?->direccion ?? '',
                $checkIn->lat !== null && $checkIn->lng !== null
                    ? number_format((float) $checkIn->lat, 6).', '.number_format((float) $checkIn->lng, 6)
                    : '',
                $checkIn->distancia_metros !== null ? number_format((float) $checkIn->distancia_metros, 1) : '',
                $checkIn->dentro_rango ? 'Dentro' : 'Fuera',
                $checkIn->nota ?? '',
                $checkIn->estado_label,
                $checkIn->validator?->name ?? '',
                $checkIn->validado_at?->format('d/m/Y H:i:s') ?? '',
            ]));
        }

        $writer->close();

        return response()->download($tmp, 'checadas_'.now()->format('Ymd_His').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * @param  Collection<int, CheckIn>  $records
     */
    public static function pdf(Collection $records, array $tableFilters): Response
    {
        $metadatos = self::metadatos($tableFilters);

        $pdf = Pdf::loadView('filament.exports.checkins-pdf', [
            'records' => $records,
            'filtros' => $metadatos,
            'generado_en' => now(),
        ])
            ->setPaper('letter', 'landscape')
            ->output();

        $filename = 'checadas_'.now()->format('Ymd_His').'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }
}
