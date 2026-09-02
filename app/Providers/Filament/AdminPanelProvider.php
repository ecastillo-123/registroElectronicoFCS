<?php

namespace App\Providers\Filament;

use App\Filament\Resources\CheckIns\Pages\ListCheckIns;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Auth\Login::class)
            ->brandName('Checador')
            ->brandLogo('')
            ->colors([
                'primary' => Color::hex('#1D4ED8'),
            ])
            ->navigationGroups([
                NavigationGroup::make('Operación'),
                NavigationGroup::make('Catálogos'),
                NavigationGroup::make('Sistema'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): HtmlString => new HtmlString('<link rel="stylesheet" href="' . asset('css/checador-admin.css') . '" />'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    <style>
                        .fi-resource-check-ins .fi-ta-content-ctn .fi-ta-actions {
                            flex-direction: column;
                            align-items: stretch;
                            gap: 0.375rem;
                            width: 8rem;
                        }
                        .fi-resource-check-ins .fi-ta-content-ctn .fi-ta-actions .fi-btn {
                            width: 100%;
                            justify-content: center;
                        }
                    </style>
                    HTML),
                scopes: [ListCheckIns::class],
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                    <script>
                        (function () {
                            function init(el) {
                                const lat = parseFloat(el.dataset.lat);
                                const lng = parseFloat(el.dataset.lng);

                                if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat === 0 || lng === 0) {
                                    el.textContent = 'Sin coordenadas.';
                                    return;
                                }

                                if (!window.L || el._checadorMap) {
                                    return;
                                }

                                const map = L.map(el.id).setView([lat, lng], 18);

                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    maxZoom: 19,
                                    attribution: '&copy; OpenStreetMap',
                                }).addTo(map);

                                L.marker([lat, lng], { draggable: false }).addTo(map);

                                el._checadorMap = map;
                                map.invalidateSize();
                            }

                            function watch(el) {
                                if (el._checadorWatching) {
                                    return;
                                }

                                el._checadorWatching = true;

                                const check = () => {
                                    if (!el.isConnected) {
                                        const map = el._checadorMap;
                                        if (map) {
                                            map.remove();
                                            delete el._checadorMap;
                                        }
                                        delete el._checadorWatching;
                                        return;
                                    }

                                    if (el.offsetHeight > 0 && el.offsetWidth > 0) {
                                        if (el._checadorMap) {
                                            el._checadorMap.invalidateSize();
                                        } else {
                                            init(el);
                                        }
                                    }

                                    setTimeout(check, 300);
                                };

                                check();
                            }

                            function scan(root) {
                                root.querySelectorAll('[id^="checkin-map-"]').forEach((el) => {
                                    if (!el._checadorMap && !el._checadorWatching) {
                                        watch(el);
                                    }
                                });
                            }

                            const observer = new MutationObserver(() => scan(document));
                            observer.observe(document.documentElement, { childList: true, subtree: true });
                            scan(document);
                        })();
                    </script>
                    HTML),
                scopes: [ListCheckIns::class],
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <style>
                        .fi-topbar-theme-switch {
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
                            transition: background-color 0.2s;
                        }
                        .fi-topbar-theme-switch:hover { background-color: #c3c7cb; }
                        .fi-topbar-theme-switch:focus-visible { box-shadow: 0 0 0 2px var(--primary-500); }
                        .fi-topbar-theme-switch .fi-topbar-theme-thumb {
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
                        .fi-topbar-theme-switch .fi-topbar-theme-thumb svg { width: 12px; height: 12px; }
                        .fi-topbar-theme-switch--dark { background-color: #4b5563; }
                        .fi-topbar-theme-switch--dark:hover { background-color: #374151; }
                        .fi-topbar-theme-switch--dark .fi-topbar-theme-thumb { transform: translateX(20px); }
                        .fi-topbar-theme-switch-ctn {
                            display: flex;
                            align-items: center;
                            margin-inline-end: 0.25rem;
                        }
                    </style>
                    <div class="fi-topbar-theme-switch-ctn">
                        <button
                            type="button"
                            role="switch"
                            aria-label="Cambiar tema claro/oscuro"
                            class="fi-topbar-theme-switch"
                            x-data="{ theme: null }"
                            x-init="theme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light'"
                            x-bind:aria-checked="theme === 'dark' ? 'true' : 'false'"
                            x-bind:class="theme === 'dark' ? 'fi-topbar-theme-switch--dark' : ''"
                            x-on:click="
                                theme = theme === 'dark' ? 'light' : 'dark'
                                localStorage.setItem('theme', theme)
                                $dispatch('theme-changed', theme)
                            "
                        >
                            <span class="fi-topbar-theme-thumb">
                                <svg x-show="theme === 'light'" viewBox="0 0 20 20" fill="currentColor" style="color: #1D4ED8;" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2ZM10 15a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15ZM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM15.657 5.404a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM6.464 14.596a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10ZM14.596 15.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.061 1.06l1.06 1.06Z" />
                                </svg>
                                <svg x-show="theme === 'dark'" viewBox="0 0 20 20" fill="currentColor" style="color: #4338CA;" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </button>
                    </div>
                    HTML),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <style>
                        .fi-simple-page-theme-switch {
                            display: inline-flex;
                            align-items: center;
                            position: fixed;
                            top: 1.25rem;
                            right: 1.25rem;
                            z-index: 50;
                        }
                        .fi-simple-page-theme-switch .fi-theme-switch {
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
                            transition: background-color 0.2s;
                        }
                        .fi-simple-page-theme-switch .fi-theme-switch:hover { background-color: #c3c7cb; }
                        .fi-simple-page-theme-switch .fi-theme-switch:focus-visible { box-shadow: 0 0 0 2px var(--primary-500); }
                        .fi-simple-page-theme-switch .fi-theme-switch-thumb {
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
                        .fi-simple-page-theme-switch .fi-theme-switch-thumb svg { width: 12px; height: 12px; }
                        .fi-simple-page-theme-switch .fi-theme-switch--dark { background-color: #4b5563; }
                        .fi-simple-page-theme-switch .fi-theme-switch--dark:hover { background-color: #374151; }
                        .fi-simple-page-theme-switch .fi-theme-switch--dark .fi-theme-switch-thumb { transform: translateX(20px); }
                    </style>
                    <div class="fi-simple-page-theme-switch">
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
                    HTML),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
