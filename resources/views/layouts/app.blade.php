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

<body style="overflow-y: auto;">

    <div class="app-layout d-flex" style="min-height: 100vh;">

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

                @if(session('success') || session('error') || session('warning'))
                    <div class="mb-4">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show auto-dismiss-alert app-flash-alert" role="alert">
                                <i class="bi bi-check-circle app-alert-icon me-2"></i>
                                <div class="app-alert-message">{{ session('success') }}</div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show auto-dismiss-alert app-flash-alert" role="alert">
                                <i class="bi bi-exclamation-triangle app-alert-icon me-2"></i>
                                <div class="app-alert-message">{{ session('error') }}</div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        @endif
                        @if(session('warning'))
                            <div class="alert alert-warning alert-dismissible fade show auto-dismiss-alert app-flash-alert" role="alert">
                                <i class="bi bi-exclamation-circle app-alert-icon me-2"></i>
                                <div class="app-alert-message">{{ session('warning') }}</div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Contenedor para alertas mostradas por JS --}}
                <div id="app-alert-container"></div>

                @yield('content')

            </div>
        </main>

    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        #app-alert-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 2050;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: min(380px, calc(100% - 2rem));
            pointer-events: none;
        }

        #app-alert-container .app-alert-wrapper {
            pointer-events: auto;
            border-radius: 1rem;
            padding: 0.95rem 1rem;
            box-shadow: 0 0.9rem 1.8rem rgba(0, 0, 0, 0.12);
            transform: translateX(12px);
            opacity: 0;
            transition: transform 0.25s ease, opacity 0.25s ease;
        }

        #app-alert-container .app-alert-wrapper.show {
            transform: translateX(0);
            opacity: 1;
        }

        .app-alert-icon {
            font-size: 1.3rem;
            line-height: 1;
            min-width: 1.3rem;
        }

        .app-alert-message {
            flex: 1;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 0.95rem;
        }

        .app-flash-alert {
            border-radius: 1rem;
            padding: 1rem 1.05rem;
            box-shadow: 0 0.85rem 1.7rem rgba(0, 0, 0, 0.10);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .app-flash-alert .btn-close {
            margin-left: auto;
        }

        #app-alert-container .btn-close {
            margin-left: 0.5rem;
        }
    </style>

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

            document.querySelectorAll('.auto-dismiss-alert').forEach(function (alert) {
                setTimeout(function () {
                    var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }, 5000);
            });

        });
    </script>

    {{-- Modales globales PRIMERO para que existan en el DOM cuando los scripts corran --}}
    @stack('modals')

    {{-- Helper global para mostrar alertas desde JS de forma consistente --}}
    <script>
        function showAppAlert(message, type = 'danger', autoDismiss = true, timeout = 5000) {
            const container = document.getElementById('app-alert-container');
            if (!container) return console.warn('No container for app alerts');

            const iconMap = {
                success: 'bi-check-circle',
                warning: 'bi-exclamation-circle',
                danger: 'bi-exclamation-triangle',
                info: 'bi-info-circle'
            };

            const icon = iconMap[type] || iconMap.info;

            const wrapper = document.createElement('div');
            wrapper.className = `alert alert-${type} alert-dismissible fade app-alert-wrapper mb-0`;
            wrapper.setAttribute('role', 'alert');
            wrapper.innerHTML = `
                <i class="bi ${icon} app-alert-icon"></i>
                <div class="app-alert-message">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            `;

            container.appendChild(wrapper);
            window.requestAnimationFrame(() => wrapper.classList.add('show'));

            if (autoDismiss) {
                setTimeout(() => {
                    try {
                        const bsAlert = bootstrap.Alert.getOrCreateInstance(wrapper);
                        bsAlert.close();
                    } catch (e) {
                        wrapper.remove();
                    }
                }, timeout);
            }
        }

        function showValidationErrors(errors) {
            if (!errors) return;
            if (typeof errors === 'string') {
                showAppAlert(errors, 'danger');
                return;
            }
            if (Array.isArray(errors)) {
                const html = '<ul class="mb-0 small">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
                showAppAlert(html, 'danger', true, 8000);
                return;
            }
            // object map
            const list = Object.values(errors).flat();
            showValidationErrors(list);
        }
    </script>

    {{-- Modal global de confirmación --}}
    <div class="modal fade" id="appConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body py-4 text-center">
                    <p id="appConfirmMessage" class="mb-4"></p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" id="appConfirmOk" class="btn btn-danger">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDialog(message) {
            return new Promise((resolve) => {
                const modalEl = document.getElementById('appConfirmModal');
                const msgEl = document.getElementById('appConfirmMessage');
                const okBtn = document.getElementById('appConfirmOk');

                msgEl.textContent = message;

                const modal = new bootstrap.Modal(modalEl);

                function cleanup() {
                    okBtn.removeEventListener('click', onOk);
                    modalEl.removeEventListener('hidden.bs.modal', onHidden);
                }

                function onOk() { cleanup(); modal.hide(); resolve(true); }
                function onHidden() { cleanup(); resolve(false); }

                okBtn.addEventListener('click', onOk);
                modalEl.addEventListener('hidden.bs.modal', onHidden);

                modal.show();
            });
        }
    </script>

    @stack('scripts')

</body>

</html>