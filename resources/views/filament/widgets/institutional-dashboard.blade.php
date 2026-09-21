<div class="socap-dashboard">
    <section class="socap-hero">
        <div>
            <p class="socap-kicker">Control · Trazabilidad · Evidencia · Auditoría</p>
            <h1>Bienvenido, {{ $userName }}</h1>
            <p>Panel institucional de control de jornada y cumplimiento.</p>
        </div>
        <div class="socap-hero-meta">
            <span>{{ ucfirst($userRole) }}</span>
            <strong>{{ now()->translatedFormat('l, d \d\e F \d\e Y') }}</strong>
        </div>
    </section>

    <section class="socap-kpis" aria-label="Indicadores principales">
        <a class="socap-kpi" href="{{ route('filament.admin.resources.employees.index') }}"><span>Colaboradores</span><strong>{{ number_format($totalEmployees) }}</strong><small>{{ $totalEmployees - $inactiveEmployees }} activos · {{ $inactiveEmployees }} inactivos</small></a>
        <a class="socap-kpi" href="{{ route('filament.admin.resources.check-ins.index') }}"><span>Asistencias hoy</span><strong class="is-ok">{{ number_format($attendanceEmployees) }}</strong><small>{{ $attendanceRate }}% del personal activo</small></a>
        <a class="socap-kpi" href="{{ route('filament.admin.resources.incidents.index') }}"><span>Incidencias pendientes</span><strong class="is-warn">{{ number_format($pendingIncidents) }}</strong><small>Requieren autorización</small></a>
        <a class="socap-kpi" href="{{ route('filament.admin.resources.rule-sets.index') }}"><span>Horas extraordinarias</span><strong>{{ $overtimeHours }} h</strong><small>Periodo actual</small></a>
        <a class="socap-kpi" href="{{ route('filament.admin.resources.check-ins.index') }}"><span>Registros incompletos</span><strong class="is-danger">{{ number_format($incomplete) }}</strong><small>Revisión requerida</small></a>
    </section>

    <section class="socap-grid socap-grid-primary">
        <article class="socap-panel">
            <div class="socap-panel-heading"><div><h2>Resumen de cumplimiento</h2><p>Lectura del periodo actual</p></div><span class="socap-panel-mark">01</span></div>
            <div class="socap-meter"><div><b>Jornada registrada</b><strong>{{ $completeRate }}%</strong></div><span><i style="width: {{ $completeRate }}%"></i></span><small>{{ $completeRate }}% de cálculos completos</small></div>
            <div class="socap-meter"><div><b>Incidencias autorizadas</b><strong>{{ $authorizationRate }}%</strong></div><span><i style="width: {{ $authorizationRate }}%"></i></span><small>{{ $pendingIncidents }} pendientes de revisión</small></div>
            <div class="socap-meter"><div><b>Trazabilidad</b><strong>{{ $traceabilityRate }}%</strong></div><span><i style="width: {{ $traceabilityRate }}%"></i></span><small>Eventos de registros con historial</small></div>
        </article>

        <article class="socap-panel">
            <div class="socap-panel-heading"><div><h2>Alertas de control</h2><p>Acciones que requieren atención</p></div><span class="socap-panel-mark">02</span></div>
            @forelse ($alerts as $alert)
                <a class="socap-alert is-{{ $alert['tone'] }}" href="{{ $alert['url'] }}"><span></span><b>{{ $alert['title'] }}</b><em>Ver</em></a>
            @empty
                <div class="socap-empty"><strong>Sin alertas críticas</strong><span>El control operativo se encuentra al día.</span></div>
            @endforelse
            <a class="socap-action" href="{{ route('filament.admin.resources.incidents.index') }}">Revisar operación <span>→</span></a>
        </article>

        <article class="socap-panel">
            <div class="socap-panel-heading"><div><h2>Últimos registros</h2><p>Actividad de jornada más reciente</p></div><span class="socap-panel-mark">03</span></div>
            <div class="socap-record-list">
                @forelse ($latestCheckIns as $checkIn)
                    <div class="socap-record"><time>{{ $checkIn->created_at?->format('H:i') }}</time><div><b>{{ $checkIn->employee?->nombre_completo ?? 'Empleado sin asignar' }}</b><small>{{ $checkIn->workCenter?->nombre ?? 'Sin centro' }}</small></div><span class="socap-pill is-{{ $checkIn->tipo === 'entrada' ? 'ok' : 'info' }}">{{ ucfirst($checkIn->tipo) }}</span></div>
                @empty
                    <div class="socap-empty"><strong>Aún no hay registros</strong><span>Los nuevos registros aparecerán aquí.</span></div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="socap-grid socap-grid-secondary">
        <article class="socap-panel socap-table-panel"><div class="socap-panel-heading"><div><h2>Incidencias y autorizaciones</h2><p>Bandeja de seguimiento</p></div><a class="socap-link" href="{{ route('filament.admin.resources.incidents.index') }}">Ver todo</a></div><div class="socap-table-wrap"><table><thead><tr><th>Empleado</th><th>Tipo</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>@forelse ($latestIncidents as $incident)<tr><td>{{ $incident->employee?->nombre_completo ?? '—' }}</td><td>{{ ucfirst(str_replace('_', ' ', $incident->tipo)) }}</td><td><span class="socap-pill is-{{ $incident->estado === 'aprobado' ? 'ok' : ($incident->estado === 'pendiente' ? 'warn' : 'danger') }}">{{ ucfirst($incident->estado) }}</span></td><td>{{ $incident->created_at?->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="4" class="socap-table-empty">No hay incidencias registradas.</td></tr>@endforelse</tbody></table></div></article>
        <article class="socap-panel socap-table-panel"><div class="socap-panel-heading"><div><h2>Correcciones con trazabilidad</h2><p>El registro original nunca se elimina</p></div><a class="socap-link" href="{{ route('filament.admin.resources.correction-requests.index') }}">Ver todo</a></div><div class="socap-correction-note">Cada autorización conserva motivo, usuario, fecha y valor propuesto como un efecto separado.</div><div class="socap-table-wrap"><table><thead><tr><th>Empleado</th><th>Tipo</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>@forelse ($latestCorrections as $correction)<tr><td>{{ $correction->employee?->nombre_completo ?? '—' }}</td><td>{{ ucfirst($correction->tipo_propuesto) }}</td><td><span class="socap-pill is-{{ $correction->estado === 'aprobado' ? 'ok' : ($correction->estado === 'pendiente' ? 'warn' : 'danger') }}">{{ ucfirst($correction->estado) }}</span></td><td>{{ $correction->created_at?->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="4" class="socap-table-empty">No hay solicitudes de corrección.</td></tr>@endforelse</tbody></table></div></article>
    </section>

    <footer class="socap-footnote"><b>Arquitectura de evidencia:</b> el registro original se conserva; cualquier corrección genera un evento de trazabilidad con usuario, fecha, motivo y autorización.</footer>
</div>
