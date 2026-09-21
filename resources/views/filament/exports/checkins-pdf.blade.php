<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Registros de Jornada</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #1f2937;
            margin: 12px;
        }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .subtitulo { font-size: 9px; color: #6b7280; margin-bottom: 12px; }
        .filtros { font-size: 8px; margin-bottom: 10px; color: #374151; }
        .filtros strong { font-weight: bold; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #1d4ed8;
            color: #ffffff;
            text-align: left;
            padding: 5px 4px;
            font-size: 7.5px;
            text-transform: uppercase;
        }
        td {
            padding: 4px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        tr:nth-child(even) td { background: #f9fafb; }
        .tipo-entrada { color: #166534; font-weight: bold; }
        .tipo-salida { color: #b45309; font-weight: bold; }
        .dentro { color: #166534; font-weight: bold; }
        .fuera { color: #b91c1c; font-weight: bold; }
        .estado-aprobado { color: #166534; font-weight: bold; }
        .estado-rechazado { color: #b91c1c; font-weight: bold; }
        .estado-pendiente { color: #6b7280; }
        .vacio { color: #9ca3af; }
        .firma {
            margin-top: 40px;
            font-size: 9px;
        }
        .firma .linea { border-bottom: 1px solid #374151; width: 280px; }
        footer {
            margin-top: 20px;
            font-size: 7px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Reporte de Registros de Jornada</h1>
    <div class="subtitulo">Generado el {{ $generado_en->format('d/m/Y H:i') }} · Total: {{ $records->count() }} registro(s)</div>

    <div class="filtros">
        @if ($filtros['desde'] || $filtros['hasta'])
            <div>Rango de fechas: <strong>{{ $filtros['desde'] ? date('d/m/Y', strtotime($filtros['desde'])) : 'Inicio' }}</strong> a <strong>{{ $filtros['hasta'] ? date('d/m/Y', strtotime($filtros['hasta'])) : 'Hoy' }}</strong></div>
        @endif
        @if ($filtros['numero_empleado'])
            <div>No. de empleado: <strong>{{ $filtros['numero_empleado'] }}</strong></div>
        @endif
        @if ($filtros['nombre_empleado'])
            <div>Nombre de empleado: <strong>{{ $filtros['nombre_empleado'] }}</strong></div>
        @endif
        @unless ($filtros['desde'] || $filtros['hasta'] || $filtros['numero_empleado'] || $filtros['nombre_empleado'])
            <div>Sin filtros aplicados.</div>
        @endunless
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Tipo</th>
                <th>No. empleado</th>
                <th>Empleado</th>
                <th>Empresa</th>
                <th>Centro</th>
                <th>Disp.</th>
                <th>Dirección</th>
                <th>Coordenadas</th>
                <th>Dist.</th>
                <th>Área</th>
                <th>Estatus</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $checkIn)
                <tr>
                    <td>{{ $checkIn->created_at?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $checkIn->created_at?->format('H:i:s') ?? '—' }}</td>
                    <td class="tipo-{{ $checkIn->tipo }}">{{ ucfirst((string) $checkIn->tipo) }}</td>
                    <td>{{ $checkIn->employee?->numero_empleado ?? '—' }}</td>
                    <td>{{ $checkIn->employee?->nombre_completo ?? '—' }}</td>
                    <td>{{ $checkIn->workCenter?->company?->nombre ?? '—' }}</td>
                    <td>{{ $checkIn->workCenter?->nombre ?? '—' }}</td>
                    <td>{{ $checkIn->device ? $checkIn->device->modelo : '—' }}</td>
                    <td>{{ $checkIn->workCenter?->direccion ?? '—' }}</td>
                    <td>
                        {{ $checkIn->lat !== null && $checkIn->lng !== null
                            ? number_format((float) $checkIn->lat, 4) . ', ' . number_format((float) $checkIn->lng, 4)
                            : '—' }}
                    </td>
                    <td>{{ $checkIn->distancia_metros !== null ? number_format((float) $checkIn->distancia_metros, 0) . ' m' : '—' }}</td>
                    <td class="{{ $checkIn->dentro_rango ? 'dentro' : 'fuera' }}">{{ $checkIn->dentro_rango ? 'Dentro' : 'Fuera' }}</td>
                    <td class="estado-{{ match ($checkIn->validado) { true => 'aprobado', false => 'rechazado', default => 'pendiente' } }}">
                        {{ $checkIn->estado_label }}
                    </td>
                    <td>{{ $checkIn->nota ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="vacio">No se encontraron registros con los criterios indicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="firma">
        <div class="linea"></div>
        <div>Nombre y firma del responsable</div>
    </div>

    <footer>Checador · Reporte generado por el sistema</footer>
</body>
</html>