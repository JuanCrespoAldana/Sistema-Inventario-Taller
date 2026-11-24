<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema Taller')</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link href="{{ asset('vendor/bootstrap/bootstrap.lite.min.css') }}?v={{ filemtime(public_path('vendor/bootstrap/bootstrap.lite.min.css')) }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons-lite/bootstrap-icons.min.css') }}?v={{ filemtime(public_path('vendor/bootstrap-icons-lite/bootstrap-icons.min.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">

    @stack('styles')
</head>

<body>

    <div class="app-layout">

        <div class="topbar">
            <button type="button" id="sidebarToggle" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <strong>Sistema Taller</strong>
        </div>

        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <aside class="sidebar" id="sidebar">

            <div class="sidebar-brand">
                <img src="{{ asset('img/logo.jpeg') }}" alt="Logo de la empresa">
                <div>
                    <div class="sidebar-brand-name">Sistema Taller</div>
                    <div class="sidebar-brand-sub">Gestión del negocio</div>
                </div>
            </div>

            <nav class="sidebar-menu">

                <div class="menu-label">Principal</div>

                <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Panel general</span>
                </a>

                <div class="menu-label">Operación</div>

                @php $ventasActive = request()->routeIs('ventas.*'); @endphp
                <div class="menu-group {{ $ventasActive ? 'open' : '' }}">
                    <div class="menu-item toggle {{ $ventasActive ? 'active' : '' }}" data-bs-toggle="collapse"
                        data-bs-target="#ventasMenu">
                        <i class="bi bi-cash-coin"></i>
                        <span>Ventas</span>
                        <i class="bi bi-chevron-down arrow"></i>
                    </div>
                    <div id="ventasMenu" class="collapse menu-sub {{ $ventasActive ? 'show' : '' }}">
                        <a href="{{ route('ventas.create') }}"
                            class="menu-item {{ request()->routeIs('ventas.create') ? 'active' : '' }}">
                            Nueva venta
                        </a>
                        <a href="{{ route('ventas.index') }}"
                            class="menu-item {{ request()->routeIs('ventas.index', 'ventas.show') ? 'active' : '' }}">
                            Historial de ventas
                        </a>
                    </div>
                </div>

                @php $repActive = request()->routeIs('reparaciones.*'); @endphp
                <div class="menu-group {{ $repActive ? 'open' : '' }}">
                    <div class="menu-item toggle {{ $repActive ? 'active' : '' }}" data-bs-toggle="collapse"
                        data-bs-target="#reparacionesMenu">
                        <i class="bi bi-wrench-adjustable"></i>
                        <span>Reparaciones</span>
                        <i class="bi bi-chevron-down arrow"></i>
                    </div>
                    <div id="reparacionesMenu" class="collapse menu-sub {{ $repActive ? 'show' : '' }}">
                        <a href="{{ route('reparaciones.create') }}"
                            class="menu-item {{ request()->routeIs('reparaciones.create') ? 'active' : '' }}">
                            Nueva orden
                        </a>
                        <a href="{{ route('reparaciones.index') }}"
                            class="menu-item {{ request()->routeIs('reparaciones.index') ? 'active' : '' }}">
                            Órdenes
                        </a>
                    </div>
                </div>

                @php $invActive = request()->routeIs('inventario.*'); @endphp
                <div class="menu-group {{ $invActive ? 'open' : '' }}">
                    <div class="menu-item toggle {{ $invActive ? 'active' : '' }}" data-bs-toggle="collapse"
                        data-bs-target="#inventarioMenu">
                        <i class="bi bi-box-seam"></i>
                        <span>Inventario</span>
                        <i class="bi bi-chevron-down arrow"></i>
                    </div>
                    <div id="inventarioMenu" class="collapse menu-sub {{ $invActive ? 'show' : '' }}">
                        <a href="{{ route('inventario.index') }}"
                            class="menu-item {{ request()->routeIs('inventario.index') ? 'active' : '' }}">
                            Productos
                        </a>
                        <a href="{{ route('inventario.productos.create') }}"
                            class="menu-item {{ request()->routeIs('inventario.productos.*') ? 'active' : '' }}">
                            Nuevo producto
                        </a>
                        <a href="{{ route('inventario.movimientos.index') }}"
                            class="menu-item {{ request()->routeIs('inventario.movimientos.*') ? 'active' : '' }}">
                            Movimientos
                        </a>
                        <a href="{{ route('inventario.ajustes.index') }}"
                            class="menu-item {{ request()->routeIs('inventario.ajustes.*') ? 'active' : '' }}">
                            Ajustes de stock
                        </a>
                        <a href="{{ route('inventario.reposicion') }}"
                            class="menu-item {{ request()->routeIs('inventario.reposicion*') ? 'active' : '' }}">
                            Reposición
                        </a>
                        <a href="{{ route('inventario.importar.index') }}"
                            class="menu-item {{ request()->routeIs('inventario.importar.*') ? 'active' : '' }}">
                            Importar / Exportar
                        </a>
                    </div>
                </div>

                <div class="menu-label">Gestión</div>

                <a href="{{ route('tecnicos.index') }}"
                    class="menu-item {{ request()->routeIs('tecnicos.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i>
                    <span>Técnicos</span>
                </a>

                <a href="{{ route('reportes.index') }}"
                    class="menu-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Reportes</span>
                </a>

            </nav>
        </aside>

        <main class="content">
            <div class="content-wrapper">

                @hasSection('page-title')
                    <div class="page-header">
                        <div class="page-header-main">
                            @hasSection('page-icon')
                                <div class="page-icon"><i class="bi bi-@yield('page-icon')"></i></div>
                            @endif
                            <div>
                                <h1 class="page-title">@yield('page-title')</h1>
                                @hasSection('page-subtitle')
                                    <p class="page-subtitle">@yield('page-subtitle')</p>
                                @endif
                            </div>
                        </div>
                        @hasSection('page-actions')
                            <div class="page-actions">@yield('page-actions')</div>
                        @endif
                    </div>
                @endif

                @yield('content')

            </div>
        </main>

    </div>

    <div id="app-alert-container"></div>

    @stack('modals')

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>

    @if ($errors->any() || session('success') || session('error') || session('warning') || session('info'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                @if (session('success'))
                    showAppAlert(@json(session('success')), 'success');
                @endif
                @if (session('info'))
                    showAppAlert(@json(session('info')), 'info');
                @endif
                @if (session('warning'))
                    showAppAlert(@json(session('warning')), 'warning', true, 7000);
                @endif
                @if (session('error'))
                    showAppAlert(@json(session('error')), 'danger', true, 8000);
                @endif
                @if ($errors->any())
                    showValidationErrors(@json($errors->all()));
                @endif
            });
        </script>
    @endif

    @stack('scripts')

</body>

</html>
