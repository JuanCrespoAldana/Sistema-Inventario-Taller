@extends('layouts.app')

@section('title', 'Dashboard')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@push('styles')
    <style>
        :root {
            --primary: #2563eb;
            --success: #16a34a;
            --warning: #ea580c;
            --danger: #dc2626;
            --info: #0891b2;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #f9fafb 100%);
            min-height: 100vh;
        }

        /* ============================================
           MAIN LAYOUT
           ============================================ */
        .dashboard-container {
            padding: 0;
            max-width: 100%;
        }

        /* ============================================
           HEADER
           ============================================ */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .dashboard-header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .dashboard-header-title i {
            font-size: 2.5rem;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .dashboard-header-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .dashboard-header-date {
            font-size: 0.95rem;
            opacity: 0.85;
        }

        /* ============================================
           KPI GRID
           ============================================ */
        .kpi-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            font-size: 1.5rem;
            min-width: 32px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
        }

        .kpi-card.blue {
            border-left-color: var(--primary);
        }

        .kpi-card.success {
            border-left-color: var(--success);
        }

        .kpi-card.warning {
            border-left-color: var(--warning);
        }

        .kpi-card.info {
            border-left-color: var(--info);
        }

        .kpi-card.danger {
            border-left-color: var(--danger);
        }

        .kpi-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--gray-600);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .kpi-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .kpi-subtext {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        /* ============================================
           CARD SECTION
           ============================================ */
        .card-section {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--gray-200);
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            margin-bottom: 20px;
        }

        .card-header {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            padding: 18px 20px;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }

        /* ============================================
           TABLE STYLES
           ============================================ */
        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .table th {
            background: var(--gray-100);
            border-bottom: 2px solid var(--gray-200);
            padding: 12px 16px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--gray-700);
            text-transform: uppercase;
        }

        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--gray-200);
        }

        .table tbody tr:hover {
            background: var(--gray-50);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* ============================================
           BADGE & STATUS
           ============================================ */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-info {
            background: #dbeafe;
            color: var(--primary);
        }

        .badge-success {
            background: #dcfce7;
            color: var(--success);
        }

        .badge-warning {
            background: #fed7aa;
            color: var(--warning);
        }

        .badge-danger {
            background: #fee2e2;
            color: var(--danger);
        }

        .badge-stock-critical {
            background: #fee2e2;
            color: var(--danger);
        }

        .badge-stock-low {
            background: #fef9c3;
            color: #ca8a04;
        }

        .badge-stock-normal {
            background: #dcfce7;
            color: var(--success);
        }

        /* ============================================
           GRID LAYOUT FOR SECTIONS
           ============================================ */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        @media (max-width: 768px) {

            .grid-2,
            .grid-3 {
                grid-template-columns: 1fr;
            }

            .dashboard-header {
                flex-direction: column;
                text-align: center;
            }

            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        /* ============================================
           ALERT BOX
           ============================================ */
        .alert-box {
            padding: 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .alert-info {
            background: #dbeafe;
            color: var(--primary);
            border-left: 4px solid var(--primary);
        }

        .alert-warning {
            background: #fef9c3;
            color: #ca8a04;
            border-left: 4px solid #ca8a04;
        }

        .alert-danger {
            background: #fee2e2;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* ============================================
           STAT ITEM
           ============================================ */
        .stat-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: var(--gray-600);
            font-weight: 500;
        }

        .stat-value {
            font-weight: 700;
            color: var(--gray-900);
            font-size: 1.1rem;
        }

        /* ============================================
           PROGRESS BAR
           ============================================ */
        .progress-bar {
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 6px;
        }

        .progress-fill {
            height: 100%;
            background: var(--success);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-container">

        <!-- HEADER -->
        <div class="dashboard-header">
            <div class="dashboard-header-title">
                <i class="bi bi-speedometer2"></i>
                <div>
                    <h1>Dashboard Gerencial</h1>
                    <small class="dashboard-header-subtitle">Sistema de Gestión - Taller</small>
                </div>
            </div>
            <div class="dashboard-header-date">
                <i class="bi bi-calendar3"></i>
                {{ \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
            </div>
        </div>

        <!-- ============================================
             KPI PRINCIPALES
             ============================================ -->
        <div class="kpi-section">
            <div class="kpi-grid">
                <div class="kpi-card blue">
                    <div class="kpi-label"><i class="bi bi-cash-coin"></i> Ventas Mes</div>
                    <div class="kpi-value">${{ number_format($ingVentasMes ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="kpi-card success">
                    <div class="kpi-label"><i class="bi bi-wrench-adjustable"></i> Reparaciones Mes</div>
                    <div class="kpi-value">${{ number_format($ingRepMes ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="kpi-card info">
                    <div class="kpi-label"><i class="bi bi-box-seam"></i> Stock Total</div>
                    <div class="kpi-value">${{ number_format($valorVenta ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="kpi-card warning">
                    <div class="kpi-label"><i class="bi bi-slash-circle"></i> Productos en 0</div>
                    <div class="kpi-value">
                        @php $criticos = $criticos ?? collect();
                        $countCriticos = is_countable($criticos) ? count($criticos) : 0; @endphp
                        {{ $countCriticos }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================
             GRÁFICA VENTAS VS REPARACIONES (12 MESES)
             ============================================ -->
        <div class="card-section" style="margin-bottom: 40px;">
            <div class="card-header">
                <i class="bi bi-graph-up"></i>Ventas vs Reparaciones - Últimos 12 Meses
            </div>
            <div class="card-body" style="position: relative; height: 350px;">
                <canvas id="grafica12meses"></canvas>
            </div>
        </div>

        <!-- ============================================
             INVENTARIO vs TOP PRODUCTOS (2 COLUMNAS)
             ============================================ -->
        <div class="grid-2" style="margin-bottom: 40px;">

            <!-- INVENTARIO -->
            <div>
                <div class="card-section">
                    <div class="card-header">
                        <i class="bi bi-calculator"></i>Valor Total del Inventario
                    </div>
                    <div class="card-body">
                        @php
                            $vCompra = $valorCompra ?? 0;
                            $vVenta = $valorVenta ?? 0;
                            $margen = ($vCompra > 0) ? round((($vVenta - $vCompra) / $vCompra) * 100, 2) : 0;
                        @endphp
                        <div class="stat-item">
                            <span class="stat-label">Valor Compra</span>
                            <span class="stat-value">${{ number_format($vCompra, 0, ',', '.') }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Valor Venta</span>
                            <span class="stat-value"
                                style="color: var(--success);">${{ number_format($vVenta, 0, ',', '.') }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Margen Ganancia</span>
                            <span class="stat-value" style="color: var(--primary);">{{ $margen }}%</span>
                        </div>
                    </div>
                </div>

                <!-- ALERTAS DE STOCK -->
                <div class="card-section" style="margin-top: 20px;">
                    <div class="card-header">
                        <i class="bi bi-exclamation-circle-fill"></i>Productos en Stock = 0
                    </div>
                    <div class="card-body">
                        @php
                            $tieneCriticos = false;
                            if (is_array($criticos) || is_object($criticos)) {
                                $tieneCriticos = (count((array) $criticos) > 0);
                            }
                        @endphp
                        @if($tieneCriticos)
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                @foreach($criticos as $prod)
                                    <span class="badge badge-stock-critical" style="font-size: 0.75rem; padding: 8px 10px;">
                                        <i class="bi bi-exclamation-circle-fill"></i> {{ $prod->nombre ?? 'Desconocido' }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="alert-box alert-info">
                                <i class="bi bi-check-circle"></i>
                                <span>Sin productos en stock cero</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- TOP 10 PRODUCTOS VENDIDOS -->
            <div class="card-section">
                <div class="card-header">
                    <i class="bi bi-trophy"></i>Top 10 Productos Vendidos (Mes)
                </div>
                <div class="card-body">
                    @php
                        $tieneMaquinas = false;
                        if (is_array($topMaquinas) || is_object($topMaquinas)) {
                            $tieneMaquinas = (count((array) $topMaquinas) > 0);
                        }
                    @endphp
                    @if($tieneMaquinas)
                        <div class="table-container">
                            <table class="table" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th style="width: 60%;">Producto</th>
                                        <th class="text-center">Unidades</th>
                                        <th class="text-end">Ingresos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topMaquinas as $idx => $m)
                                        <tr>
                                            <td>
                                                <strong>#{{ $idx + 1 }}</strong> {{ substr($m->nombre ?? 'Desconocido', 0, 40) }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-info">{{ $m->total_vendido ?? 0 }}</span>
                                            </td>
                                            <td class="text-end">
                                                <strong>${{ number_format($m->total_ingresos ?? 0, 0, ',', '.') }}</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert-box alert-info">
                            <i class="bi bi-info-circle"></i>
                            <span>Sin datos disponibles</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- ============================================
             PRODUCTIVIDAD DE TÉCNICOS
             ============================================ -->
        <div class="card-section" style="margin-bottom: 40px;">
            <div class="card-header">
                <i class="bi bi-graph-up"></i>Productividad de Técnicos y Ranking
            </div>
            <div class="card-body">
                @php
                    $tieneProductividad = false;
                    if (is_array($productividad) || is_object($productividad)) {
                        $tieneProductividad = (count((array) $productividad) > 0);
                    }
                @endphp
                @if($tieneProductividad)
                    <div class="table-container">
                        <table class="table" id="tbl-productividad">
                            <thead>
                                <tr>
                                    <th>Técnico</th>
                                    <th class="text-center">Entregadas</th>
                                    <th class="text-center">Activas</th>
                                    <th class="text-center">Eficiencia</th>
                                    <th class="text-end">Ingresos Mes</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-tec-dash">
                                @foreach($productividad as $idx => $tec)
                                    @php
                                        $eficiencia = $tec->eficiencia ?? 0;
                                        $eficienciaColor = $eficiencia >= 80 ? 'badge-success' : ($eficiencia >= 60 ? 'badge-warning' : 'badge-danger');
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $idx + 1 }}.</strong> {{ $tec->nombre ?? 'Desconocido' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-success">{{ $tec->entregadas ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $tec->activas ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $eficienciaColor }}">{{ $eficiencia }}%</span>
                                        </td>
                                        <td class="text-end">
                                            <strong>${{ number_format($tec->ingresos ?? 0, 0, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{-- Paginación dashboard técnicos --}}
                    <div id="dash-tec-pag" style="display:none;justify-content:space-between;align-items:center;margin-top:12px;padding-top:10px;border-top:1px solid var(--gray-200);">
                        <span id="dash-tec-info" style="font-size:0.82rem;color:var(--gray-600);"></span>
                        <div style="display:flex;gap:6px;">
                            <button id="dash-tec-prev" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;padding:4px 12px;font-size:0.82rem;">‹ Anterior</button>
                            <span id="dash-tec-num" style="align-self:center;font-size:0.82rem;color:var(--gray-600);padding:0 8px;"></span>
                            <button id="dash-tec-next" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;padding:4px 12px;font-size:0.82rem;">Siguiente ›</button>
                        </div>
                    </div>
                @else
                    <div class="alert-box alert-info">
                        <i class="bi bi-info-circle"></i>
                        <span>Sin datos de técnicos disponibles</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- FOOTER -->
        <div
            style="text-align: center; color: var(--gray-600); font-size: 0.85rem; padding-top: 20px; border-top: 1px solid var(--gray-200);">
            <p>Última actualización: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>

    </div>

    <script>
        // Paginación de técnicos en dashboard (6 por página)
        document.addEventListener('DOMContentLoaded', function () {
            const tbody = document.getElementById('tbody-tec-dash');
            if (!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const PAGE_SIZE = 10;
            if (rows.length <= PAGE_SIZE) return;

            let page = 1;
            const total = rows.length;
            const pages = Math.ceil(total / PAGE_SIZE);
            const pagDiv = document.getElementById('dash-tec-pag');
            const info   = document.getElementById('dash-tec-info');
            const num    = document.getElementById('dash-tec-num');
            const prev   = document.getElementById('dash-tec-prev');
            const next   = document.getElementById('dash-tec-next');

            pagDiv.style.display = 'flex';

            function render() {
                rows.forEach((r, i) => {
                    r.style.display = (i >= (page-1)*PAGE_SIZE && i < page*PAGE_SIZE) ? '' : 'none';
                });
                const desde = (page-1)*PAGE_SIZE + 1;
                const hasta = Math.min(page*PAGE_SIZE, total);
                info.textContent = `Mostrando ${desde}–${hasta} de ${total} técnicos`;
                num.textContent  = `${page} / ${pages}`;
                prev.disabled = page === 1;
                next.disabled = page === pages;
            }

            prev.addEventListener('click', () => { if (page > 1) { page--; render(); } });
            next.addEventListener('click', () => { if (page < pages) { page++; render(); } });
            render();
        });
        document.addEventListener('DOMContentLoaded', function () {
            const grafica12meses = @json($grafica12meses ?? collect());

            const ctx = document.getElementById('grafica12meses');
            if (!ctx) return;

            const chartCtx = ctx.getContext('2d');

            if (grafica12meses && grafica12meses.length > 0) {
                const meses = grafica12meses.map(d => d.mes);
                const ventas = grafica12meses.map(d => parseFloat(d.ventas || 0));
                const reparaciones = grafica12meses.map(d => parseFloat(d.reparaciones || 0));

                new Chart(chartCtx, {
                    type: 'line',
                    data: {
                        labels: meses,
                        datasets: [
                            {
                                label: 'Ventas',
                                data: ventas,
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.3,
                                pointRadius: 5,
                                pointBackgroundColor: '#2563eb',
                                pointHoverRadius: 7
                            },
                            {
                                label: 'Reparaciones',
                                data: reparaciones,
                                borderColor: '#16a34a',
                                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.3,
                                pointRadius: 5,
                                pointBackgroundColor: '#16a34a',
                                pointHoverRadius: 7
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    font: { size: 12, weight: 'bold' },
                                    color: '#374151',
                                    padding: 15
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                // Mostrar mensaje sin datos
                chartCtx.fillStyle = '#f3f4f6';
                chartCtx.fillRect(0, 0, chartCtx.canvas.width, chartCtx.canvas.height);
                chartCtx.fillStyle = '#9ca3af';
                chartCtx.font = '16px Arial';
                chartCtx.textAlign = 'center';
                chartCtx.fillText('Sin datos disponibles en los últimos 12 meses', chartCtx.canvas.width / 2, chartCtx.canvas.height / 2);
            }
        });
    </script>

@endsection