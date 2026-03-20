<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Sistema Taller')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    {{-- CSS del sistema --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('styles')
</head>

<body>

    <div class="app-layout d-flex">

        {{-- ================= SIDEBAR ================= --}}
        <aside class="sidebar">

            {{-- LOGO --}}
            <div class="sidebar-logo">
                <img src="{{ asset('img/logo.jpeg') }}" alt="Logo Empresa">
            </div>

            {{-- MENU --}}
            <nav class="sidebar-menu">

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}"
                    class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>

                {{-- ================= VENTAS ================= --}}
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
                            class="menu-item {{ request()->routeIs('ventas.index') ? 'active' : '' }}">
                            Historial ventas
                        </a>
                    </div>
                </div>

                {{-- ================= REPARACIONES ================= --}}
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

                {{-- ================= INVENTARIO ================= --}}
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
                            Agregar
                        </a>

                        <a href="{{ route('inventario.movimientos.index') }}"
                            class="menu-item {{ request()->routeIs('inventario.movimientos.*') ? 'active' : '' }}">
                            Movimientos
                        </a>

                        <a href="{{ route('inventario.ajustes.index') }}"
                            class="menu-item {{ request()->routeIs('inventario.ajustes.*') ? 'active' : '' }}">
                            Ajustes
                        </a>
                    </div>
                </div>

                {{-- Técnicos --}}
                <a href="{{ route('tecnicos.index') }}"
                    class="menu-item {{ request()->routeIs('tecnicos.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i>
                    <span>Técnicos</span>
                </a>

                {{-- Reportes --}}
                <a href="{{ route('reportes.index') }}"
                    class="menu-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Reportes</span>
                </a>

            </nav>
        </aside>


        {{-- ================= CONTENIDO ================= --}}
        <main class="content">
            <div class="content-wrapper">

                @hasSection('page-title')
                    <h1 class="page-title mb-4">@yield('page-title')</h1>
                @endif

                @yield('content')

            </div>
        </main>

    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Script para animación del menú --}}
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            document.querySelectorAll('.collapse').forEach(function (collapseEl) {

                collapseEl.addEventListener('show.bs.collapse', function () {
                    this.closest('.menu-group').classList.add('open');
                });

                collapseEl.addEventListener('hide.bs.collapse', function () {
                    this.closest('.menu-group').classList.remove('open');
                });

            });

        });
    </script>

    @stack('scripts')

</body>

</html>