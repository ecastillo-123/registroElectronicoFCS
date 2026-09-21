{{--
    Login del panel Checador.

    THESIS: split-screen de entrada: a la izquierda la tarea (acceder), a la derecha
    la marca. Se rechaza el "card centrado sobre fondo plano" genérico de Filament.

    OWN-WORLD: paleta Committed en azul profundo (#1D4ED8). La marca vive en una
    columna lateral de degradado navy -> royal -> sky con cristal esmerilado,
    reloj en vivo y seis capacidades del checador. El formulario vive en una tarjeta
    translúcida sobre un fondo de luz azul-gris suave.

    STORY: el visitante entiende al instante qué es el sistema (asistencia), ve que
    es de confianza (azul, orden, reloj real), y firma.

    FIRST VIEWPORT: columnas 50/50; tarjeta de login centrada a la izquierda con
    switch de tema, y columna de marca con reloj en vivo + grilla de features.

    FORM: extensión del SimplePage de Filament con vista propia (filament.auth.login),
    seed d3ecf1 (n/a, extensión dirigida).

    FINISH: unreviewed and undocumented is unfinished; this build ends with the finish
    review, the verdict, and DESIGN.md.
--}}
@php
    $brandName = filament()->getBrandName();
    $heading = $this->getHeading();
    $subheading = $this->getSubheading();
@endphp

<div class="fi-login">
    <style>
        .fi-login {
            --lp-navy: #172554;
            --lp-royal: #1D4ED8;
            --lp-sky: #0EA5E9;
            --lp-ink: #0F172A;
            --lp-muted: #64748B;
            --lp-line: #E2E8F0;
            min-height: 100dvh;
            display: flex;
            align-items: stretch;
            font-family: var(--font-sans, ui-sans-serif, system-ui, sans-serif);
        }

        main.fi-simple-main {
            max-width: none !important;
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            min-height: 100dvh;
        }

        .fi-simple-main-ctn { align-items: stretch !important; }

        .fi-login-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
            width: 100%;
        }

        /* ---------- Columna del formulario ---------- */
        .fi-login-form-col {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            overflow: hidden;
            background:
                radial-gradient(1100px 560px at -10% -10%, rgba(29, 78, 216, 0.10), transparent 60%),
                radial-gradient(900px 520px at 110% 110%, rgba(14, 165, 233, 0.12), transparent 60%),
                linear-gradient(160deg, #F8FAFC 0%, #EFF6FF 100%);
        }

        .fi-login-form-card {
            position: relative;
            width: 100%;
            max-width: 430px;
            padding: 40px 40px 28px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 24px 48px -22px rgba(15, 23, 42, 0.22);
            animation: fi-login-rise 0.6s ease-out both;
        }

        .fi-login-eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #1D4ED8;
        }

        .fi-login-heading {
            margin-top: 8px;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--lp-ink);
        }

        .fi-login-subheading {
            margin-top: 6px;
            font-size: 14px;
            color: var(--lp-muted);
        }

        .fi-login-form { margin-top: 26px; }

        .fi-login-form-footer {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 12.5px;
            color: #94A3B8;
        }

        .fi-login-form-footer .fi-login-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #1D4ED8;
            flex-shrink: 0;
        }

        /* ---------- Columna de marca ---------- */
        .fi-login-brand {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 64px 56px;
            color: #fff;
            background: linear-gradient(155deg, #172554 0%, #1E3A8A 36%, #1D4ED8 66%, #2563EB 88%, #0EA5E9 100%);
        }

        .fi-login-brand::before,
        .fi-login-brand::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            pointer-events: none;
        }

        .fi-login-brand::before {
            width: 460px;
            height: 460px;
            left: -140px;
            top: -140px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.5), transparent 70%);
        }

        .fi-login-brand::after {
            width: 420px;
            height: 420px;
            right: -140px;
            bottom: -140px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.16), transparent 70%);
        }

        .fi-login-brand-inner {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            animation: fi-login-rise 0.6s ease-out 0.08s both;
        }

        .fi-login-brand-mark {
            width: 66px;
            height: 66px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.22);
        }

        .fi-login-brand-mark svg {
            width: 36px;
            height: 36px;
            color: #fff;
        }

        .fi-login-brand-name {
            margin-top: 20px;
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .fi-login-brand-tagline {
            margin-top: 10px;
            max-width: 40ch;
            font-size: 16px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.86);
        }

        .fi-login-clock {
            margin-top: 34px;
            display: inline-flex;
            flex-direction: column;
            gap: 3px;
            padding: 18px 24px;
            border-radius: 18px;
            background: rgba(2, 6, 23, 0.24);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(8px);
        }

        .fi-login-clock-time {
            font-size: 34px;
            font-weight: 700;
            letter-spacing: 0.01em;
            font-variant-numeric: tabular-nums;
            line-height: 1.1;
        }

        .fi-login-clock-date {
            font-size: 13px;
            text-transform: capitalize;
            color: rgba(255, 255, 255, 0.76);
        }

        .fi-login-features {
            margin-top: 38px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .fi-login-feature {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(6px);
            transition: background-color 0.2s ease;
        }

        .fi-login-feature:hover { background: rgba(255, 255, 255, 0.17); }

        .fi-login-feature-icon {
            flex-shrink: 0;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
        }

        .fi-login-feature-icon svg { width: 20px; height: 20px; }

        .fi-login-feature-text {
            font-size: 13px;
            font-weight: 600;
            line-height: 1.3;
            color: rgba(255, 255, 255, 0.94);
        }

        /* ---------- Switch de tema (fijo, arriba a la derecha) ---------- */
        .fi-login-theme-switch {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 60;
        }

        .fi-login-theme-switch .fi-theme-switch {
            display: inline-flex;
            align-items: center;
            position: relative;
            flex-shrink: 0;
            width: 44px;
            height: 24px;
            padding: 0;
            cursor: pointer;
            border: none;
            border-radius: 9999px;
            outline: none;
            background-color: #d1d5db;
            box-shadow: 0 1px 4px rgba(2, 6, 23, 0.25);
            transition: background-color 0.2s;
        }

        .fi-login-theme-switch .fi-theme-switch:hover { background-color: #c3c7cb; }
        .fi-login-theme-switch .fi-theme-switch:focus-visible { box-shadow: 0 0 0 2px var(--primary-500); }

        .fi-login-theme-switch .fi-theme-switch-thumb {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 2px;
            left: 2px;
            height: 20px;
            width: 20px;
            border-radius: 9999px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s;
        }

        .fi-login-theme-switch .fi-theme-switch-thumb svg { width: 12px; height: 12px; }
        .fi-login-theme-switch .fi-theme-switch--dark { background-color: #4b5563; }
        .fi-login-theme-switch .fi-theme-switch--dark:hover { background-color: #374151; }
        .fi-login-theme-switch .fi-theme-switch--dark .fi-theme-switch-thumb { transform: translateX(20px); }

        /* ---------- Tema oscuro ---------- */
        .dark .fi-login-form-col {
            background:
                radial-gradient(1100px 560px at -10% -10%, rgba(37, 99, 235, 0.16), transparent 60%),
                radial-gradient(900px 520px at 110% 110%, rgba(14, 165, 233, 0.10), transparent 60%),
                linear-gradient(160deg, #0B1120 0%, #0F1B33 100%);
        }

        .dark .fi-login-form-card {
            background: rgba(15, 23, 42, 0.92);
            border-color: rgba(51, 65, 85, 0.7);
            box-shadow: 0 24px 48px -20px rgba(0, 0, 0, 0.55);
        }

        .dark .fi-login-eyebrow { color: #60A5FA; }
        .dark .fi-login-heading { color: #F8FAFC; }
        .dark .fi-login-subheading { color: #94A3B8; }
        .dark .fi-login-form-footer { border-top-color: rgba(51, 65, 85, 0.7); color: #64748B; }

        /* ---------- Movimiento ---------- */
        @keyframes fi-login-rise {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .fi-login-form-card,
            .fi-login-brand-inner { animation: none; }
        }

        /* ---------- Responsivo ---------- */
        @media (max-width: 900px) {
            .fi-login-shell { grid-template-columns: 1fr; }

            .fi-login-brand {
                order: -1;
                align-items: flex-start;
                padding: 42px 26px 46px;
            }

            .fi-login-brand-inner { max-width: none; }

            .fi-login-brand-mark { width: 54px; height: 54px; border-radius: 16px; }
            .fi-login-brand-mark svg { width: 30px; height: 30px; }

            .fi-login-brand-name { margin-top: 14px; font-size: 30px; }
            .fi-login-brand-tagline { font-size: 14.5px; }
            .fi-login-clock { margin-top: 24px; padding: 14px 18px; }
            .fi-login-clock-time { font-size: 26px; }
            .fi-login-features { display: none; }

            .fi-login-form-col { padding: 34px 20px 44px; }
            .fi-login-form-card { padding: 32px 24px 24px; }
        }
    </style>

    <div class="fi-login-shell">
        {{-- Columna del formulario --}}
        <div class="fi-login-form-col">
            <div class="fi-login-form-card">
                <div class="fi-login-eyebrow">Panel de administración</div>
                <h1 class="fi-login-heading">{!! $heading !!}</h1>
                @if ($subheading)
                    <div class="fi-login-subheading">{!! $subheading !!}</div>
                @endif

                <div class="fi-login-form">
                    {{ $this->content }}
                </div>

                <div class="fi-login-form-footer">
                    <span class="fi-login-dot"></span>
                    Sistema de control de asistencia
                </div>
            </div>
        </div>

        {{-- Columna de marca (derecha) --}}
        <aside class="fi-login-brand">
            <div class="fi-login-brand-inner">
                <div class="fi-login-brand-mark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>

                <div class="fi-login-brand-name">{!! $brandName !!}</div>
                <p class="fi-login-brand-tagline">
                    Registros de entrada y salida del personal, con ubicación verificada y control en tiempo real.
                </p>

                <div
                    class="fi-login-clock"
                    x-data="{ now: new Date() }"
                    x-init="setInterval(() => { now = new Date() }, 1000)"
                >
                    <div
                        class="fi-login-clock-time"
                        x-text="now.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true })"
                    ></div>
                    <div
                        class="fi-login-clock-date"
                        x-text="now.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })"
                    ></div>
                </div>

                <div class="fi-login-features">
                    <div class="fi-login-feature">
                        <span class="fi-login-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        <span class="fi-login-feature-text">Entradas y salidas</span>
                    </div>

                    <div class="fi-login-feature">
                        <span class="fi-login-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                        </span>
                        <span class="fi-login-feature-text">Geolocalización GPS</span>
                    </div>

                    <div class="fi-login-feature">
                        <span class="fi-login-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33" />
                            </svg>
                        </span>
                        <span class="fi-login-feature-text">Identificación segura</span>
                    </div>

                    <div class="fi-login-feature">
                        <span class="fi-login-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </span>
                        <span class="fi-login-feature-text">Control de personal</span>
                    </div>

                    <div class="fi-login-feature">
                        <span class="fi-login-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-4.5-7.5h.008v.008h-.008v-.008Z" />
                            </svg>
                        </span>
                        <span class="fi-login-feature-text">Asistencia por día</span>
                    </div>

                    <div class="fi-login-feature">
                        <span class="fi-login-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-9.75 3h9.75m-9.75 3h9.75M9 12.75l2.25 2.25L15 11.25" />
                            </svg>
                        </span>
                        <span class="fi-login-feature-text">Reportes y validación</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    @if (! $this instanceof \Filament\Tables\Contracts\HasTable)
        <x-filament-actions::modals />
    @endif

    {{-- Switch de tema --}}
    <div class="fi-login-theme-switch">
        <button
            type="button"
            role="switch"
            aria-label="Cambiar tema claro/oscuro"
            class="fi-theme-switch"
            x-data="{ theme: null }"
            x-init="theme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light'"
            x-bind:aria-checked="theme === 'dark' ? 'true' : 'false'"
            x-bind:class="theme === 'dark' ? 'fi-theme-switch--dark' : ''"
            x-on:click="
                theme = theme === 'dark' ? 'light' : 'dark'
                localStorage.setItem('theme', theme)
                $dispatch('theme-changed', theme)
            "
        >
            <span class="fi-theme-switch-thumb">
                <svg x-show="theme === 'light'" viewBox="0 0 20 20" fill="currentColor" style="color: #1D4ED8;" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2ZM10 15a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15ZM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM15.657 5.404a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM6.464 14.596a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10ZM14.596 15.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.061 1.06l1.06 1.06Z" />
                </svg>
                <svg x-show="theme === 'dark'" viewBox="0 0 20 20" fill="currentColor" style="color: #4338CA;" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd" />
                </svg>
            </span>
        </button>
    </div>
</div>
