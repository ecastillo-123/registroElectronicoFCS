<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Expediente de Jornada Laboral</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #073763;
            margin: 18px 20px;
        }
        .doc-header {
            background: #073763;
            color: #ffffff;
            padding: 14px 16px;
            margin-bottom: 14px;
        }
        .doc-header h1 { font-size: 17px; margin: 0 0 3px; }
        .doc-header .subtitle { color: #b9d6ed; font-size: 9px; margin: 0 0 8px; letter-spacing: 0.06em; text-transform: uppercase; }
        .doc-header .folio { color: #ffffff; font-size: 10px; font-weight: bold; }
        .section-title { color: #073763; font-size: 10px; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 0.04em; }
        table { width: 100%; border-collapse: collapse; }
        .meta-table { margin-bottom: 14px; }
        .meta-table td { border: 1px solid #dfe8f1; padding: 6px 8px; vertical-align: top; width: 50%; }
        .meta-table .label { color: #718096; display: block; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.04em; }
        .meta-table .value { color: #073763; display: block; font-size: 10px; font-weight: bold; margin-top: 2px; }
        .summary-table { margin-bottom: 14px; }
        .summary-table td { background: #eef6ff; border: 1px solid #dfe8f1; padding: 7px 8px; text-align: center; }
        .summary-table .label { color: #718096; display: block; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.04em; }
        .summary-table .value { color: #1479e9; display: block; font-size: 13px; font-weight: bold; margin-top: 3px; }
        .records-table th { background: #1479e9; border: 1px solid #1479e9; color: #ffffff; font-size: 7.5px; padding: 6px 7px; text-align: left; text-transform: uppercase; letter-spacing: 0.04em; }
        .records-table td { border: 1px solid #dfe8f1; padding: 5px 7px; vertical-align: top; }
        .records-table tr:nth-child(even) td { background: #eef6ff; }
        .event-entrada { color: #16a36b; font-weight: bold; }
        .event-salida { color: #d98a00; font-weight: bold; }
        .state-original { color: #1479e9; }
        .state-corregido { color: #d98a00; font-weight: bold; }
        .records-empty { color: #718096; text-align: center; }
        .declaration {
            background: #eef6ff;
            border: 1px solid #dfe8f1;
            color: #27577e;
            font-size: 8px;
            line-height: 1.5;
            margin-top: 14px;
            padding: 9px 10px;
        }
        .doc-footer {
            border-top: 1px solid #dfe8f1;
            color: #718096;
            font-size: 8px;
            line-height: 1.6;
            margin-top: 16px;
            padding-top: 8px;
        }
        .doc-footer strong { color: #073763; }
    </style>
</head>
<body>
    {{-- 1. Encabezado del documento --}}
    <div class="doc-header">
        <h1>Expediente de Jornada Laboral</h1>
        <p class="subtitle">Control · Trazabilidad · Evidencia · Auditoría</p>
        <div class="folio">Folio: {{ $report['folio'] }}</div>
    </div>

    {{-- 2. Datos del colaborador y periodo --}}
    <table class="meta-table">
        <tr>
            <td>
                <span class="label">ID</span>
                <span class="value">{{ $report['employee']->numero_empleado }}</span>
            </td>
            <td>
                <span class="label">Puesto</span>
                <span class="value">{{ $report['employee']->puesto ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Centro de trabajo</span>
                <span class="value">{{ $report['employee']->workCenter?->nombre ?? '—' }}</span>
            </td>
            <td>
                <span class="label">Periodo</span>
                <span class="value">{{ $report['period'] }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label">Total de registros</span>
                <span class="value">{{ $report['total'] }}</span>
            </td>
        </tr>
    </table>

    {{-- 3. Resumen de jornada --}}
    <table class="summary-table">
        <tr>
            <td>
                <span class="label">Jornada ordinaria</span>
                <span class="value">{{ $report['summary']['ordinary'] }}</span>
            </td>
            <td>
                <span class="label">Jornada efectiva</span>
                <span class="value">{{ $report['summary']['effective'] }}</span>
            </td>
            <td>
                <span class="label">Extraordinario</span>
                <span class="value">{{ $report['summary']['overtime'] }}</span>
            </td>
            <td>
                <span class="label">Correcciones</span>
                <span class="value">{{ $report['corrections_count'] }}</span>
            </td>
        </tr>
    </table>

    {{-- 4. Registros de jornada --}}
    <p class="section-title">Registros de jornada</p>
    <table class="records-table">
        <thead>
            <tr>
                <th>Fecha / hora</th>
                <th>Evento</th>
                <th>Origen</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['events'] as $event)
                <tr>
                    <td>{{ $event['date'] }} {{ $event['time'] }}</td>
                    <td class="{{ $event['event'] === 'Entrada' ? 'event-entrada' : 'event-salida' }}">{{ $event['event'] }}</td>
                    <td>{{ $event['origin'] }}</td>
                    <td class="{{ $event['is_original'] ? 'state-original' : 'state-corregido' }}">{{ $event['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="records-empty">No se encontraron registros de jornada para este periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- 5. Declaración de trazabilidad --}}
    <div class="declaration">
        <strong>Declaración de trazabilidad.</strong> La consulta presenta el registro original y, cuando existen, sus eventos posteriores de corrección y autorización. Esta vista es de solo lectura y está diseñada para facilitar la exhibición de información ante las instancias autorizadas.
    </div>

    {{-- 6. Pie del documento --}}
    <div class="doc-footer">
        <strong>Folio de exhibición:</strong> {{ $report['folio'] }}
        · <strong>Generado:</strong> {{ $report['generated_at'] }}
        · <strong>Usuario:</strong> {{ $report['generated_by'] }}
    </div>
</body>
</html>
