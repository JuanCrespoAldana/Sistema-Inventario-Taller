@extends('layouts.app')

@section('title', 'Panel general')
@section('page-icon', 'speedometer2')
@section('page-title', 'Panel general')
@section('page-subtitle')
    {{ ucfirst(\Carbon\Carbon::now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}
@endsection

@section('page-actions')
    <a href="{{ route('ventas.create') }}" class="btn btn-primary">
        <i class="bi bi-cart-plus"></i> Nueva venta
    </a>
    <a href="{{ route('reparaciones.create') }}" class="btn btn-outline-secondary">
        <i class="bi bi-wrench-adjustable"></i> Nueva orden
    </a>
    <a href="{{ route('inventario.productos.create') }}" class="btn btn-outline-secondary">
        <i class="bi bi-box-seam"></i> Nuevo producto
    </a>
@endsection

@section('content')

    @php
        $hora = (int) now()->format('G');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
        $mesActual = ucfirst(\Carbon\Carbon::now()->locale('es')->isoFormat('MMMM'));

        $ventasMes = $ventasMes ?? 0;
        $repMes = $repMes ?? 0;
        $ingVentasMes = (float) ($ingVentasMes ?? 0);
        $ingRepMes = (float) ($ingRepMes ?? 0);
        $ingresosMes = $ingVentasMes + $ingRepMes;
        $ingresosMesAnt = (float) ($ventasMesAnt ?? 0) + (float) ($ingRepMesAnt ?? 0);

        $variacion = function ($actual, $anterior) {
            return $anterior > 0 ? (int) round((($actual - $anterior) / $anterior) * 100) : null;
        };

        $vTotal = $variacion($ingresosMes, $ingresosMesAnt);
        $vVentas = $variacion($ingVentasMes, (float) ($ventasMesAnt ?? 0));
        $vRep = $variacion($ingRepMes, (float) ($ingRepMesAnt ?? 0));

        $totalCero = (int) ($totalCriticos ?? 0);
        $totalBajos = (int) ($totalBajos ?? 0);
        $totalProductos = (int) ($totalProductos ?? 0);
        $normales = max(0, $totalProductos - $totalCero - $totalBajos);

        $vCompra = (float) ($valorCompra ?? 0);
        $vVenta = (float) ($valorVenta ?? 0);
        $margen = $vCompra > 0 ? round((($vVenta - $vCompra) / $vCompra) * 100, 1) : 0;

        $porReponer = $porReponer ?? collect();
        $sinRot = $sinRotacion ?? (object) ['cantidad' => 0, 'valor' => 0];
        $antiguas = $ordenesAntiguas ?? collect();
        $antiguasTotal = (int) ($ordenesAntiguasTotal ?? 0);
        $diasAntigua = (int) ($diasOrdenAntigua ?? 15);

        $topHist = $topProductosHistorico ?? collect();
        $tieneTop = is_countable($topHist) && count($topHist) > 0;
        $maxTop = $tieneTop ? max(1, collect($topHist)->max('total_vendido')) : 1;

        $tieneProductividad = is_countable($productividad ?? null) && count($productividad) > 0;
        $tieneProductividad3m = is_countable($productividad3m ?? null) && count($productividad3m) > 0;

        $deltaHtml = function ($valor) {
            if ($valor === null) {
                return '<span class="delta">Sin datos del mes anterior</span>';
            }
            $clase = $valor > 0 ? 'up' : ($valor < 0 ? 'down' : '');
            $signo = $valor > 0 ? '▲ +' : ($valor < 0 ? '▼ −' : '= ');

            return '<span class="delta ' . $clase . '">' . $signo . abs($valor) . ' %</span> vs. mismo período del mes anterior';
        };
    @endphp

    <section class="dash-hero">
        <div class="dash-hero-texto">
            <span class="dash-hero-saludo">{{ $saludo }}</span>
            <h2>Así va {{ $mesActual }}</h2>
            <p>
                @if (($ventasHoy ?? 0) > 0 || ($entregasHoy ?? 0) > 0)
                    Hoy van <strong>{{ $ventasHoy ?? 0 }} {{ ($ventasHoy ?? 0) == 1 ? 'venta' : 'ventas' }}</strong>
                    por ${{ number_format($ingVentasHoy ?? 0, 0, ',', '.') }}
                    y <strong>{{ $entregasHoy ?? 0 }} {{ ($entregasHoy ?? 0) == 1 ? 'máquina entregada' : 'máquinas entregadas' }}</strong>.
                @else
                    Todavía no hay ventas ni entregas registradas hoy.
                @endif
            </p>
        </div>

        <div class="dash-hero-datos">
            <div>
                <span>Ingresos del mes</span>
                <strong>${{ number_format($ingresosMes, 0, ',', '.') }}</strong>
            </div>
            <div>
                <span>Máquinas en el taller</span>
                <strong>{{ $repActivas ?? 0 }}</strong>
            </div>
            <div>
                <span>Productos por reponer</span>
                <strong>{{ $totalCero + $totalBajos }}</strong>
            </div>
        </div>
    </section>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Ingresos del mes</div>
                <div class="kpi-value">${{ number_format($ingresosMes, 0, ',', '.') }}</div>
                <div class="kpi-sub">{!! $deltaHtml($vTotal) !!}</div>
            </div>
        </div>

        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Ventas de productos</div>
                <div class="kpi-value">${{ number_format($ingVentasMes, 0, ',', '.') }}</div>
                <div class="kpi-sub">{{ $ventasMes }} {{ $ventasMes == 1 ? 'venta' : 'ventas' }} · {!! $deltaHtml($vVentas) !!}</div>
            </div>
        </div>

        <div class="kpi kpi-violet">
            <div class="kpi-icon"><i class="bi bi-wrench-adjustable"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Reparaciones entregadas</div>
                <div class="kpi-value">${{ number_format($ingRepMes, 0, ',', '.') }}</div>
                <div class="kpi-sub">{{ $repMes }} {{ $repMes == 1 ? 'máquina' : 'máquinas' }} · {!! $deltaHtml($vRep) !!}</div>
            </div>
        </div>

        <div class="kpi kpi-amber">
            <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">En el taller ahora</div>
                <div class="kpi-value">{{ $repActivas ?? 0 }}</div>
                <div class="kpi-sub">
                    @if ($antiguasTotal > 0)
                        <span class="delta down">{{ $antiguasTotal }} con más de {{ $diasAntigua }} días</span>
                    @else
                        Ninguna lleva más de {{ $diasAntigua }} días
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dash-fila dash-fila-2-1">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-graph-up"></i> Ventas y reparaciones de los últimos 12 meses</span>
            </div>
            <div class="card-body chart-box">
                <canvas id="grafica12meses"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-exclamation-triangle"></i> Requiere atención</span>
            </div>
            <ul class="atencion-lista">
                <li>
                    <a href="{{ route('inventario.index', ['filtro_stock' => 'critico']) }}" class="atencion-item {{ $totalCero > 0 ? 'rojo' : 'ok' }}">
                        <span class="atencion-icono"><i class="bi bi-{{ $totalCero > 0 ? 'exclamation-octagon' : 'check-circle-fill' }}"></i></span>
                        <span class="atencion-texto">
                            <strong>{{ $totalCero > 0 ? $totalCero . ($totalCero == 1 ? ' producto agotado' : ' productos agotados') : 'Sin productos agotados' }}</strong>
                            <small>{{ $totalCero > 0 ? 'No se pueden vender hasta que ingresen' : 'Todo tiene existencias' }}</small>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('inventario.reposicion') }}" class="atencion-item {{ $totalBajos > 0 ? 'ambar' : 'ok' }}">
                        <span class="atencion-icono"><i class="bi bi-{{ $totalBajos > 0 ? 'exclamation-circle' : 'check-circle-fill' }}"></i></span>
                        <span class="atencion-texto">
                            <strong>{{ $totalBajos > 0 ? $totalBajos . ($totalBajos == 1 ? ' producto con stock bajo' : ' productos con stock bajo') : 'Stock al día' }}</strong>
                            <small>{{ $totalBajos > 0 ? 'En o por debajo de su mínimo · ver lista de compra' : 'Ningún producto bajo su mínimo' }}</small>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reparaciones.index', ['antiguedad' => $diasAntigua]) }}" class="atencion-item {{ $antiguasTotal > 0 ? 'ambar' : 'ok' }}">
                        <span class="atencion-icono"><i class="bi bi-{{ $antiguasTotal > 0 ? 'hourglass-split' : 'check-circle-fill' }}"></i></span>
                        <span class="atencion-texto">
                            <strong>{{ $antiguasTotal > 0 ? $antiguasTotal . ($antiguasTotal == 1 ? ' máquina' : ' máquinas') . " con más de {$diasAntigua} días" : 'Taller al día' }}</strong>
                            <small>{{ $antiguasTotal > 0 ? 'Siguen sin entregarse · conviene avisar al cliente' : 'Ninguna máquina lleva demasiado tiempo' }}</small>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('inventario.index', ['filtro_stock' => 'sin_rotacion']) }}" class="atencion-item {{ $sinRot->cantidad > 0 ? 'azul' : 'ok' }}">
                        <span class="atencion-icono"><i class="bi bi-{{ $sinRot->cantidad > 0 ? 'clock-history' : 'check-circle-fill' }}"></i></span>
                        <span class="atencion-texto">
                            <strong>{{ $sinRot->cantidad > 0 ? $sinRot->cantidad . ($sinRot->cantidad == 1 ? ' producto' : ' productos') . ' sin ventas en 90 días' : 'Inventario con movimiento' }}</strong>
                            <small>{{ $sinRot->cantidad > 0 ? '$' . number_format($sinRot->valor, 0, ',', '.') . ' en costo inmovilizado' : 'Todo lo que hay en bodega ha rotado' }}</small>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="dash-fila dash-fila-1-1">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-calculator"></i> Valor del inventario</span>
                <a href="{{ route('inventario.index') }}" class="card-enlace">Ver inventario</a>
            </div>
            <div class="card-body">
                <ul class="stat-list">
                    <li class="stat-item">
                        <span class="stat-label">A precio de compra</span>
                        <span class="stat-value">${{ number_format($vCompra, 0, ',', '.') }}</span>
                    </li>
                    <li class="stat-item">
                        <span class="stat-label">A precio de venta</span>
                        <span class="stat-value text-success">${{ number_format($vVenta, 0, ',', '.') }}</span>
                    </li>
                    <li class="stat-item">
                        <span class="stat-label">Margen potencial</span>
                        <span class="stat-value text-primary">{{ number_format($margen, 1, ',', '.') }}%</span>
                    </li>
                </ul>

                <div class="dist-titulo">Estado de los {{ number_format($totalProductos, 0, ',', '.') }} productos activos</div>
                @if ($totalProductos > 0)
                    <div class="dist-barra" role="img" aria-label="{{ $normales }} normales, {{ $totalBajos }} con stock bajo, {{ $totalCero }} agotados">
                        @if ($normales > 0)<span class="dist-ok" style="flex: {{ $normales }}"></span>@endif
                        @if ($totalBajos > 0)<span class="dist-bajo" style="flex: {{ $totalBajos }}"></span>@endif
                        @if ($totalCero > 0)<span class="dist-cero" style="flex: {{ $totalCero }}"></span>@endif
                    </div>
                    <div class="dist-leyenda">
                        <span><i class="dist-punto dist-ok"></i> Normal <strong>{{ $normales }}</strong></span>
                        <span><i class="dist-punto dist-bajo"></i> Stock bajo <strong>{{ $totalBajos }}</strong></span>
                        <span><i class="dist-punto dist-cero"></i> Agotados <strong>{{ $totalCero }}</strong></span>
                    </div>
                @else
                    <div class="cell-sub">Aún no hay productos registrados.</div>
                @endif
            </div>
        </div>

        <div class="card card-flat">
            <div class="card-header">
                <span><i class="bi bi-cart-plus"></i> Por reponer</span>
                <span class="count-pill {{ ($totalCero + $totalBajos) > 0 ? 'has-items' : '' }}" title="Agotados y con stock bajo">{{ $totalCero + $totalBajos }}</span>
            </div>

            @if (count($porReponer) > 0)
                <ul class="stock-list">
                    @foreach ($porReponer as $prod)
                        <li class="stock-row">
                            <span class="stock-dot {{ $prod->stock <= 0 ? '' : 'bajo' }}" aria-hidden="true"></span>
                            <span class="stock-name" title="{{ $prod->nombre }}">{{ $prod->nombre }}</span>
                            <span class="stock-code">{{ $prod->codigo }}</span>
                            @if ($prod->stock <= 0)
                                <span class="badge bg-danger">Agotado</span>
                            @else
                                <span class="badge bg-warning">{{ $prod->stock }} / mín. {{ $prod->stock_minimo }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
                    <span>
                        @if (($totalCero + $totalBajos) > count($porReponer))
                            Mostrando {{ count($porReponer) }} de {{ $totalCero + $totalBajos }}
                        @else
                            {{ $totalCero + $totalBajos }} {{ ($totalCero + $totalBajos) == 1 ? 'producto' : 'productos' }}
                        @endif
                    </span>
                    <a href="{{ route('inventario.reposicion') }}" class="fw-semibold">Ver lista de compra</a>
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    <strong>Todo con existencias</strong>
                    <span>Ningún producto está agotado ni por debajo de su mínimo.</span>
                </div>
            @endif
        </div>
    </div>

    <div class="dash-fila dash-fila-1-1">
        <div class="card card-flat">
            <div class="card-header">
                <span><i class="bi bi-trophy"></i> Los 10 más vendidos</span>
                <small>Histórico</small>
            </div>

            @if ($tieneTop)
                <ul class="top-lista">
                    @foreach ($topHist as $idx => $m)
                        <li class="top-item">
                            <span class="rank-chip {{ $idx < 3 ? 'rank-' . ($idx + 1) : '' }}">{{ $idx + 1 }}</span>
                            <div class="top-cuerpo">
                                <div class="top-linea">
                                    <span class="cell-title" title="{{ $m->nombre }}">{{ \Illuminate\Support\Str::limit($m->nombre ?? 'Desconocido', 40) }}</span>
                                    <span class="top-unidades">{{ $m->total_vendido ?? 0 }} u.</span>
                                </div>
                                <div class="top-barra"><span style="width: {{ round((($m->total_vendido ?? 0) / $maxTop) * 100) }}%"></span></div>
                            </div>
                            <span class="top-monto">${{ number_format($m->total_ingresos ?? 0, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="empty-state">
                    <i class="bi bi-bar-chart"></i>
                    <strong>Sin datos todavía</strong>
                    <span>Aquí aparecerán los productos más vendidos.</span>
                </div>
            @endif
        </div>

        <div class="card card-flat">
            <div class="card-header">
                <span><i class="bi bi-hourglass-split"></i> Máquinas que llevan más tiempo en el taller</span>
                <a href="{{ route('reparaciones.index', ['estado' => 'pendiente']) }}" class="card-enlace">Ver órdenes</a>
            </div>

            @if (count($antiguas) > 0)
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Cliente y máquina</th>
                                <th>Técnico</th>
                                <th class="text-center">Días</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($antiguas as $o)
                                <tr class="fila-estado {{ $o->dias >= $diasAntigua ? 'fila-agotado' : ($o->dias >= 7 ? 'fila-bajo' : '') }}">
                                    <td class="cell-code">
                                        <a href="{{ route('reparaciones.index', ['numero_orden' => $o->numero_orden]) }}">#{{ $o->numero_orden }}</a>
                                    </td>
                                    <td>
                                        <div class="cell-title">{{ \Illuminate\Support\Str::limit($o->cliente, 30) }}</div>
                                        <div class="cell-sub">{{ \Illuminate\Support\Str::limit($o->maquina !== '' ? $o->maquina : 'Sin descripción', 36) }}</div>
                                    </td>
                                    <td>{{ $o->tecnico ?: 'Sin asignar' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $o->dias >= $diasAntigua ? 'bg-danger' : ($o->dias >= 7 ? 'bg-warning' : 'bg-secondary') }}">{{ $o->dias }} {{ $o->dias == 1 ? 'día' : 'días' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    <strong>No hay máquinas pendientes</strong>
                    <span>Todo lo que ingresó al taller ya fue entregado.</span>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-people"></i> Productividad de técnicos</span>
            <div class="btn-group btn-group-sm" role="group" aria-label="Período">
                <button type="button" id="btn-periodo-historico" class="btn btn-primary"
                    onclick="cambiarPeriodoTecnicos('historico')">Histórico</button>
                <button type="button" id="btn-periodo-3m" class="btn btn-outline-primary"
                    onclick="cambiarPeriodoTecnicos('3m')">Últimos 3 meses</button>
            </div>
        </div>

        <div id="panel-tec-historico">
            @if ($tieneProductividad)
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="col-rank">#</th>
                                <th>Técnico</th>
                                <th class="text-center">Entregadas</th>
                                <th class="text-center">Activas</th>
                                <th class="text-end">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody data-paginar="10">
                            @foreach ($productividad as $idx => $tec)
                                <tr>
                                    <td><span class="rank-chip {{ $idx < 3 ? 'rank-' . ($idx + 1) : '' }}">{{ $idx + 1 }}</span></td>
                                    <td class="cell-title">{{ $tec->nombre ?? 'Desconocido' }}</td>
                                    <td class="text-center"><span class="badge bg-success">{{ $tec->entregadas ?? 0 }}</span></td>
                                    <td class="text-center"><span class="badge bg-info">{{ $tec->activas ?? 0 }}</span></td>
                                    <td class="text-end fw-semibold">${{ number_format($tec->ingresos ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-person-x"></i>
                    <strong>Sin datos de técnicos</strong>
                </div>
            @endif
        </div>

        <div id="panel-tec-3m" class="d-none">
            @if ($tieneProductividad3m)
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="col-rank">#</th>
                                <th>Técnico</th>
                                <th class="text-center">Entregadas</th>
                                <th class="text-center">Activas</th>
                                <th class="text-end">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody data-paginar="10">
                            @foreach ($productividad3m as $idx => $tec)
                                <tr>
                                    <td><span class="rank-chip {{ $idx < 3 ? 'rank-' . ($idx + 1) : '' }}">{{ $idx + 1 }}</span></td>
                                    <td class="cell-title">{{ $tec->nombre ?? 'Desconocido' }}</td>
                                    <td class="text-center"><span class="badge bg-success">{{ $tec->entregadas ?? 0 }}</span></td>
                                    <td class="text-center"><span class="badge bg-info">{{ $tec->activas ?? 0 }}</span></td>
                                    <td class="text-end fw-semibold">${{ number_format($tec->ingresos ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <strong>Sin entregas en los últimos 3 meses</strong>
                </div>
            @endif
        </div>

        <div class="card-footer">
            <i class="bi bi-info-circle"></i>
            «Activas» siempre muestra el total actual de órdenes pendientes o en reparación, sin importar el período elegido.
        </div>
    </div>

    <p class="text-center text-muted small mb-0">Última actualización: {{ now()->format('d/m/Y H:i') }}</p>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/chartjs/chart.umd.js') }}" defer></script>
    <script>
        function cambiarPeriodoTecnicos(periodo) {
            const mostrarHistorico = periodo === 'historico';

            document.getElementById('panel-tec-historico').classList.toggle('d-none', !mostrarHistorico);
            document.getElementById('panel-tec-3m').classList.toggle('d-none', mostrarHistorico);

            const btnHistorico = document.getElementById('btn-periodo-historico');
            const btn3m = document.getElementById('btn-periodo-3m');

            btnHistorico.classList.toggle('btn-primary', mostrarHistorico);
            btnHistorico.classList.toggle('btn-outline-primary', !mostrarHistorico);
            btn3m.classList.toggle('btn-primary', !mostrarHistorico);
            btn3m.classList.toggle('btn-outline-primary', mostrarHistorico);
        }

        window.addEventListener('load', function () {
            const datos = @json($grafica12meses ?? collect());
            const lienzo = document.getElementById('grafica12meses');

            if (!lienzo || !datos || datos.length === 0 || typeof Chart === 'undefined') return;

            const ctx = lienzo.getContext('2d');
            const base = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
            Chart.defaults.font.size = Math.round(base * 0.8);

            const degradado = function (rgb) {
                const g = ctx.createLinearGradient(0, 0, 0, lienzo.height || 320);
                g.addColorStop(0, 'rgba(' + rgb + ', 0.22)');
                g.addColorStop(1, 'rgba(' + rgb + ', 0)');
                return g;
            };

            const serie = function (etiqueta, valores, color, rgb) {
                return {
                    label: etiqueta,
                    data: valores,
                    borderColor: color,
                    backgroundColor: degradado(rgb),
                    fill: true,
                    tension: 0.42,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: color,
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: color,
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2
                };
            };

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: datos.map(function (d) { return d.mes; }),
                    datasets: [
                        serie('Ventas', datos.map(function (d) { return parseFloat(d.ventas || 0); }), '#3b72d9', '59, 114, 217'),
                        serie('Reparaciones', datos.map(function (d) { return parseFloat(d.reparaciones || 0); }), '#1f9d5b', '31, 157, 91')
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    animation: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                padding: 18,
                                font: { size: Math.round(base * 0.82), weight: '600' },
                                color: '#44536b'
                            }
                        },
                        tooltip: {
                            backgroundColor: '#10264b',
                            titleColor: '#f8fafc',
                            bodyColor: '#e2e8f0',
                            padding: 12,
                            cornerRadius: 10,
                            usePointStyle: true,
                            boxPadding: 4,
                            callbacks: {
                                label: function (item) {
                                    return item.dataset.label + ': $' + formatoPesos(item.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#62718a', font: { size: Math.round(base * 0.78) } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#e9eef6', drawTicks: false },
                            border: { display: false },
                            ticks: {
                                color: '#62718a',
                                font: { size: Math.round(base * 0.78) },
                                padding: 8,
                                callback: function (v) { return Number.isInteger(v) ? '$' + formatoPesos(v) : ''; }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
