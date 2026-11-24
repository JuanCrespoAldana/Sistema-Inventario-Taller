<?php

namespace App\Services\Reportes;

use Carbon\Carbon;
use App\Models\Venta;
use App\Models\OrdenMaquina;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    /**
     * Genera reporte mensual completo.
     * IMPORTANTE: reparaciones se filtra por fecha_salida de la máquina
     * (y estado = 'entregada'), NO por fecha_entrada ni por created_at
     * de orden_maquinas (que es la fecha de inserción en BD).
     */
    public function generarReporteMensual($mes, $anio)
    {
        // Rango [inicio, inicio del mes siguiente): se filtra con >= y < en vez de BETWEEN
        // para que sea correcto tanto si la columna es DATE como DATETIME.
        $inicio      = Carbon::create($anio, $mes, 1)->startOfMonth();
        $finExclusivo = $inicio->copy()->addMonth();

        // ================= VENTAS =================
        $ventas = Venta::where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $finExclusivo->toDateString())
            ->orderByDesc('fecha')
            ->get(['id', 'numero_factura', 'fecha', 'total', 'descuento', 'total_final']);

        $totalVentasMes = $ventas->sum('total_final');

        // ================= REPARACIONES =================
        // Filtrar por fecha_salida de la máquina, no por fecha de creación de la orden.
        $reparaciones = OrdenMaquina::with(['orden.cliente', 'tecnico'])
            ->whereNotNull('fecha_salida')
            ->where('estado', 'entregada')
            ->where('fecha_salida', '>=', $inicio->toDateString())
            ->where('fecha_salida', '<', $finExclusivo->toDateString())
            ->orderByDesc('fecha_salida')
            ->get();

        $totalReparacionesMes = $reparaciones->sum('total');

        // ================= PRODUCTIVIDAD TÉCNICOS =================
        $productividad = [];

        $reparaciones
            ->whereNotNull('tecnico_id')
            ->groupBy('tecnico_id')
            ->each(function ($items, $tecnicoId) use (&$productividad) {
                // El técnico ya viene precargado en cada máquina (with('tecnico') arriba),
                // así que lo tomamos de ahí en vez de volver a consultar la BD por cada
                // grupo (antes: Tecnico::find($tecnicoId) dentro del loop = N+1 queries).
                $tecnico = $items->first()->tecnico;
                if ($tecnico) {
                    $productividad[$tecnico->nombre] = [
                        'total'     => $items->sum('total'),
                        'maquinas'  => $items->count(),
                        'mano_obra' => $items->sum('mano_obra'),
                    ];
                }
            });

        arsort($productividad);

        // ================= FINANCIERO =================
        $totalGeneral = $totalVentasMes + $totalReparacionesMes;

        return [
            'ventas'               => $ventas,
            'totalVentasMes'       => $totalVentasMes,
            'reparaciones'         => $reparaciones,
            'totalReparacionesMes' => $totalReparacionesMes,
            'productividad'        => $productividad,
            'totalGeneral'         => $totalGeneral,
        ];
    }

    /**
     * Genera resumen anual mes a mes para el contador.
     */
    public function generarResumenAnual($anio)
    {
        $mesesEs = [
            1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',
            5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',
            9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
        ];

        $resumen = [];
        $totalAnioVentas = 0;
        $totalAnioRep    = 0;

        // Dos consultas agrupadas por mes (antes eran 24: 2 por cada mes del año).
        $desde = Carbon::create($anio, 1, 1)->startOfYear();
        $hasta = $desde->copy()->addYear();

        $ventasPorMes = DB::table('ventas')
            ->where('fecha', '>=', $desde->toDateString())
            ->where('fecha', '<', $hasta->toDateString())
            ->selectRaw('MONTH(fecha) as mes, COUNT(*) as count, COALESCE(SUM(total_final),0) as total')
            ->groupBy(DB::raw('MONTH(fecha)'))
            ->get()
            ->keyBy('mes');

        $repPorMes = DB::table('orden_maquinas')
            ->whereNotNull('fecha_salida')
            ->where('estado', 'entregada')
            ->where('fecha_salida', '>=', $desde->toDateString())
            ->where('fecha_salida', '<', $hasta->toDateString())
            ->selectRaw('MONTH(fecha_salida) as mes, COUNT(id) as count, COALESCE(SUM(total),0) as total')
            ->groupBy(DB::raw('MONTH(fecha_salida)'))
            ->get()
            ->keyBy('mes');

        for ($m = 1; $m <= 12; $m++) {
            $ventas       = $ventasPorMes->get($m);
            $reparaciones = $repPorMes->get($m);

            $tv = floatval($ventas->total ?? 0);
            $tr = floatval($reparaciones->total ?? 0);

            $resumen[] = [
                'mes'            => $mesesEs[$m],
                'mes_num'        => $m,
                'ventas_count'   => intval($ventas->count ?? 0),
                'ventas_total'   => $tv,
                'rep_count'      => intval($reparaciones->count ?? 0),
                'rep_total'      => $tr,
                'total_mes'      => $tv + $tr,
            ];

            $totalAnioVentas += $tv;
            $totalAnioRep    += $tr;
        }

        return [
            'resumen'         => $resumen,
            'totalAnioVentas' => $totalAnioVentas,
            'totalAnioRep'    => $totalAnioRep,
            'totalAnio'       => $totalAnioVentas + $totalAnioRep,
            'anio'            => $anio,
        ];
    }

    /**
     * $paginar = true (pantalla): los totales se calculan en SQL y las dos tablas vienen paginadas,
     * así no se cargan miles de productos en memoria ni se genera una página gigante.
     * $paginar = false (exportar a Excel): devuelve las listas completas, igual que antes.
     */
    public function generarReporteInventario($mes, $anio, bool $paginar = false, array $filtros = [])
    {
        $movimientos = MovimientoInventario::with('producto');

        try {
            $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth();
            $movimientos->where('created_at', '>=', $inicioMes->toDateTimeString())
                ->where('created_at', '<', $inicioMes->copy()->addMonth()->toDateTimeString());
        } catch (\Throwable $e) {
            // Mes o año inválido: antes simplemente no devolvía movimientos.
            $movimientos->whereRaw('1 = 0');
        }

        $movimientos->orderByDesc('created_at');

        if ($paginar) {
            $stats = Producto::where('activo', 1)
                ->selectRaw('
                    COALESCE(SUM(stock * precio_compra), 0) as total_compra,
                    COALESCE(SUM(stock * precio_venta), 0) as total_venta,
                    COUNT(CASE WHEN stock <= 0 THEN 1 END) as criticos,
                    COUNT(CASE WHEN stock > 0 AND stock <= stock_minimo THEN 1 END) as bajos
                ')
                ->first();

            $totalCompra = $stats->total_compra;
            $totalVenta  = $stats->total_venta;
            $criticos    = (int) $stats->criticos;
            $bajos       = (int) $stats->bajos;

            $consultaProductos = Producto::where('activo', 1);

            $buscar = trim((string) ($filtros['buscar'] ?? ''));
            if ($buscar !== '') {
                $consultaProductos->where(function ($q) use ($buscar) {
                    $q->where('codigo', 'like', "%{$buscar}%")
                        ->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('modelo', 'like', "%{$buscar}%");
                });

                $movimientos->whereHas('producto', function ($q) use ($buscar) {
                    $q->where('codigo', 'like', "%{$buscar}%")
                        ->orWhere('nombre', 'like', "%{$buscar}%");
                });
            }

            match ($filtros['estado'] ?? null) {
                'agotado' => $consultaProductos->agotados(),
                'bajo' => $consultaProductos->bajos(),
                'normal' => $consultaProductos->disponibles(),
                default => null,
            };

            if (in_array($filtros['tipo'] ?? null, ['entrada', 'salida', 'ajuste'], true)) {
                $movimientos->where('tipo', $filtros['tipo']);
            }

            $productos = $consultaProductos
                ->orderBy('nombre')
                ->paginate(15, ['*'], 'prod_page')
                ->withQueryString();

            $movimientos = $movimientos->paginate(15, ['*'], 'mov_page')->withQueryString();
        } else {
            $productos = Producto::where('activo', 1)
                ->orderBy('nombre')
                ->get();

            $totalCompra = $productos->sum(fn($p) => $p->precio_compra * $p->stock);
            $totalVenta  = $productos->sum(fn($p) => $p->precio_venta * $p->stock);
            $criticos    = $productos->where('stock', '<=', 0)->count();
            $bajos       = $productos->filter(fn($p) => $p->stock > 0 && $p->stock <= $p->stock_minimo)->count();

            $movimientos = $movimientos->get();
        }

        return [
            'productos'    => $productos,
            'movimientos'  => $movimientos,
            'totalCompra'  => $totalCompra,
            'totalVenta'   => $totalVenta,
            'margenPot'    => $totalVenta - $totalCompra,
            'criticos'     => $criticos,
            'bajos'        => $bajos,
        ];
    }
}
