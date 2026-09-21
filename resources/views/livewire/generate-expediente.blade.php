<div class="socap-dashboard">
    {{-- Encabezado institucional --}}
    <section class="socap-hero">
        <div>
            <p class="socap-kicker">Control · Trazabilidad · Evidencia · Auditoría</p>
            <h1>Expediente de Empleado</h1>
            <p>Genera el expediente de jornada laboral por colaborador y periodo para su exhibición ante las instancias autorizadas.</p>
        </div>
        <div class="socap-hero-meta">
            <span>{{ ucfirst(auth()->user()?->getRoleNames()->first() ?? 'Panel administrativo') }}</span>
            <strong>{{ now()->translatedFormat('l, d \d\e F \d\e Y') }}</strong>
        </div>
    </section>

    {{-- Parámetros del expediente --}}
    <section class="socap-panel socap-panel-form">
        <div class="socap-panel-heading">
            <div>
                <h2>Parámetros del expediente</h2>
                <p>Selecciona el colaborador y el rango de fechas a consultar</p>
            </div>
            <span class="socap-panel-mark">01</span>
        </div>

        <div class="socap-form">
            <div class="socap-field socap-field-employee">
                <label for="expediente-employee">Colaborador</label>
                <input id="expediente-employee" type="text" autocomplete="off"
                       placeholder="Escribe al menos {{ $minSearchLength }} caracteres..."
                       wire:model.live.debounce.350ms="employeeSearch" />

                @if($employee_id)
                    <span class="socap-field-selected">Seleccionado: {{ $employeeSearch }}</span>
                @endif

                @unless($employee_id)
                    @if($employeeResults->isNotEmpty())
                        <ul class="socap-search-results">
                            @foreach($employeeResults as $emp)
                                <li wire:key="employee-result-{{ $emp->id }}">
                                    <button type="button" wire:click="selectEmployee({{ $emp->id }})">
                                        <strong>{{ $emp->numero_empleado }}</strong>
                                        <span>{{ $emp->nombre_completo }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @elseif(mb_strlen(trim($employeeSearch)) >= $minSearchLength)
                        <span class="socap-field-hint">Sin coincidencias.</span>
                    @elseif(mb_strlen(trim($employeeSearch)) > 0)
                        <span class="socap-field-hint">Escribe al menos {{ $minSearchLength }} caracteres para ver resultados.</span>
                    @endif
                @endunless

                @error('employee_id') <span class="socap-field-error">{{ $message }}</span> @enderror
            </div>

            <div class="socap-field">
                <label for="expediente-start">Desde</label>
                <input id="expediente-start" type="date" wire:model="start_date" />
                @error('start_date') <span class="socap-field-error">{{ $message }}</span> @enderror
            </div>

            <div class="socap-field">
                <label for="expediente-end">Hasta</label>
                <input id="expediente-end" type="date" wire:model="end_date" />
                @error('end_date') <span class="socap-field-error">{{ $message }}</span> @enderror
            </div>

            <div class="socap-form-actions">
                <button type="button" wire:click="generate" wire:loading.attr="disabled" class="socap-btn">
                    <span wire:loading.remove wire:target="generate">Generar expediente</span>
                    <span wire:loading wire:target="generate">Generando...</span>
                </button>
            </div>
        </div>
    </section>

    {{-- Reporte --}}
    @if($report)
        <section class="socap-report" id="expediente-report">
            <article class="socap-panel">
                <div class="socap-panel-heading">
                    <div>
                        <h2>Expediente de Jornada Laboral</h2>
                        <p>{{ $report['employee']->nombre_completo }}</p>
                    </div>
                    <span class="socap-pill is-ok">Información íntegra</span>
                </div>

                <div class="socap-doc-meta">
                    <span>ID<b>{{ $report['employee']->numero_empleado }}</b></span>
                    <span>Puesto<b>{{ $report['employee']->puesto ?? '—' }}</b></span>
                    <span>Centro de trabajo<b>{{ $report['employee']->workCenter?->nombre ?? '—' }}</b></span>
                    <span>Periodo<b>{{ $report['period'] }}</b></span>
                </div>
            </article>

            <div class="socap-kpis socap-kpis-report">
                <div class="socap-kpi"><span>Jornada ordinaria</span><strong>{{ $report['summary']['ordinary'] }}</strong><small>Horas esperadas del periodo</small></div>
                <div class="socap-kpi"><span>Jornada efectiva</span><strong class="is-ok">{{ $report['summary']['effective'] }}</strong><small>Horas registradas</small></div>
                <div class="socap-kpi"><span>Extraordinario</span><strong class="is-warn">{{ $report['summary']['overtime'] }}</strong><small>Tiempo adicional al horario</small></div>
                <div class="socap-kpi"><span>Correcciones</span><strong>{{ $report['corrections_count'] }}</strong><small>Eventos con trazabilidad</small></div>
            </div>

            <article class="socap-panel socap-table-panel">
                <div class="socap-panel-heading">
                    <div>
                        <h2>Registros de jornada</h2>
                        <p>Eventos originales y sus correcciones autorizadas</p>
                    </div>
                    <span class="socap-panel-mark">02</span>
                </div>
                <div class="socap-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha / hora</th>
                                <th>Evento</th>
                                <th>Origen</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['events'] as $event)
                                <tr class="{{ $event['is_original'] ? 'is-original' : 'is-corrected' }}">
                                    <td>{{ $event['date'] }} {{ $event['time'] }}</td>
                                    <td><span class="socap-pill {{ $event['event'] === 'Entrada' ? 'is-ok' : 'is-warn' }}">{{ $event['event'] }}</span></td>
                                    <td>{{ $event['origin'] }}</td>
                                    <td><span class="socap-pill {{ $event['is_original'] ? 'is-info' : 'is-warn' }}">{{ $event['status'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="socap-table-empty">No se encontraron registros de jornada para este periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <div class="socap-correction-note">
                <strong>Declaración de trazabilidad.</strong> La consulta presenta el registro original y, cuando existen, sus eventos posteriores de corrección y autorización. Esta vista es de solo lectura y está diseñada para facilitar la exhibición de información ante las instancias autorizadas.
            </div>

            <footer class="socap-footnote">
                <strong>Folio de exhibición:</strong> EXP-{{ now()->format('Y') }}-{{ str_pad($report['employee']->id, 5, '0', STR_PAD_LEFT) }}
                · <strong>Generado:</strong> {{ $report['generated_at'] }}
                · <strong>Usuario:</strong> {{ $report['generated_by'] }}
            </footer>

            <div class="socap-report-actions">
                <a href="{{ $report['pdf_url'] }}" target="_blank" rel="noopener" class="socap-btn is-secondary">Generar PDF</a>
            </div>
        </section>
    @endif
</div>
