<?php

namespace App\Services\Dashboard;

use App\Models\Producto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /** Segundos que se guarda el resultado del dashboard (se borra antes si se guarda algo). */
    private const CACHE_SEGUNDOS = 30;

    /** Días en el taller a partir de los cuales una máquina se marca como "antigua". */
    public const DIAS_ORDEN_ANTIGUA = 15;

    /**
     * Rango [inicio, fin) del mes actual como fechas 'Y-m-d'.
     * Se filtra con >= inicio y < fin en vez de whereMonth()/whereYear(): da el mismo
     * resultado, pero MySQL puede usar el índice de la columna en lugar de recorrer la tabla.
     */
    private function rangoMes(): array
    {
        $inicio = Carbon::now()->startOfMonth();

        return [$inicio->toDateString(), $inicio->copy()->addMonth()->toDateString()];
    }

    private static function claveCache(): string
    {
        return 'dashboard:datos:' . Carbon::now()->format('Y-m');
    }

    /** Borra el dashboard guardado para que la próxima visita lo recalcule. */
    public static function limpiarCache(): void
    {
        try {
            Cache::forget(self::claveCache());
        } catch (\Throwable $e) {
            // Si el caché falla, el dashboard simplemente se calcula de nuevo: no es un error grave.
        }
    }

    /**
     * Todos los datos que necesita la vista dashboard.index.
     * Se guarda 30 s en caché y se borra solo al guardar ventas, órdenes, productos o movimientos.
     */
    public function datosDashboard(): array
    {
        return Cache::remember(self::claveCache(), self::CACHE_SEGUNDOS, function () {
            $rep = $this->resumenReparacionesMes();
            $ven = $this->resumenVentasMes();
            $inv = $this->resumenInventario();
            $ant = $this->resumenMesAnterior();
            $hoy = $this->resumenHoy();
            $antiguas = $this->ordenesAntiguas();

            return [
                // ================= REPARACIONES =================
                'repMes' => $rep->cantidad,
                'repActivas' => $this->reparacionesActivas(),
                'ingRepMes' => $rep->ingresos,
                'ordenesAntiguas' => $antiguas['lista'],
                'ordenesAntiguasTotal' => $antiguas['total'],
                'diasOrdenAntigua' => self::DIAS_ORDEN_ANTIGUA,

                // ================= VENTAS =================
                'ventasMes' => $ven->cantidad,
                'ingVentasMes' => $ven->ingresos,
                'topProductosHistorico' => $this->topProductosVendidosHistorico(),

                // ================= COMPARACIÓN =================
                'ventasMesAnt' => $ant->ventas,
                'ingRepMesAnt' => $ant->reparaciones,
                'ventasHoy' => $hoy->ventas,
                'ingVentasHoy' => $hoy->ingresos,
                'entregasHoy' => $hoy->entregas,

                // ================= INVENTARIO =================
                'valorCompra' => $inv->compra,
                'valorVenta' => $inv->venta,
                'totalProductos' => $inv->total,
                'totalCriticos' => $inv->sin_stock,
                'totalBajos' => $inv->bajos,
                'porReponer' => $this->productosPorReponer(),
                'sinRotacion' => $this->productosSinRotacion(),

                // ================= TECNICOS =================
                'productividad' => $this->productividadTecnicos(),
                'productividad3m' => $this->productividadTecnicos(3),

                // ================= GRAFICAS =================
                'grafica12meses' => $this->ventasReparacionesUltimos12Meses(),
            ];
        });
    }

    /* =====================================================
       Resúmenes (una sola consulta en lugar de dos)
    ===================================================== */

    private function resumenReparacionesMes(): object
    {
        [$ini, $fin] = $this->rangoMes();

        $r = DB::table('orden_maquinas')
            ->where('estado', 'entregada')
            ->where('fecha_salida', '>=', $ini)
            ->where('fecha_salida', '<', $fin)
            ->selectRaw('COUNT(*) as cantidad, SUM(total) as ingresos')
            ->first();

        return (object) [
            'cantidad' => $r->cantidad,
            'ingresos' => $r->ingresos ?: 0,
        ];
    }

    private function resumenVentasMes(): object
    {
        [$ini, $fin] = $this->rangoMes();

        $r = DB::table('ventas')
            ->where('fecha', '>=', $ini)
            ->where('fecha', '<', $fin)
            ->selectRaw('COUNT(*) as cantidad, SUM(total_final) as ingresos')
            ->first();

        return (object) [
            'cantidad' => $r->cantidad,
            'ingresos' => $r->ingresos ?: 0,
        ];
    }

    private function resumenInventario(): object
    {
        $r = DB::table('productos')
            ->where('activo', 1)
            ->selectRaw('
                COUNT(*) as total,
                SUM(stock * precio_compra) as compra,
                SUM(stock * precio_venta) as venta,
                COUNT(CASE WHEN stock <= 0 THEN 1 END) as sin_stock,
                COUNT(CASE WHEN stock > 0 AND stock <= stock_minimo THEN 1 END) as bajos
            ')
            ->first();

        return (object) [
            'total' => (int) ($r->total ?? 0),
            'compra' => $r->compra ?? 0,
            'venta' => $r->venta ?? 0,
            'sin_stock' => (int) ($r->sin_stock ?? 0),
            'bajos' => (int) ($r->bajos ?? 0),
        ];
    }

    /**
     * Ventas y reparaciones del mes anterior hasta el mismo día del mes
     * (para comparar "peras con peras" a mitad de mes).
     */
    private function resumenMesAnterior(): object
    {
        $inicio = Carbon::now()->startOfMonth();
        $inicioAnt = $inicio->copy()->subMonthNoOverflow();
        $finAnt = $inicioAnt->copy()->addDays(Carbon::now()->day);

        if ($finAnt->gt($inicio)) {
            $finAnt = $inicio->copy();
        }

        $v = DB::table('ventas')
            ->where('fecha', '>=', $inicioAnt->toDateString())
            ->where('fecha', '<', $finAnt->toDateString())
            ->sum('total_final');

        $r = DB::table('orden_maquinas')
            ->where('estado', 'entregada')
            ->where('fecha_salida', '>=', $inicioAnt->toDateString())
            ->where('fecha_salida', '<', $finAnt->toDateString())
            ->sum('total');

        return (object) ['ventas' => (float) $v, 'reparaciones' => (float) $r];
    }

    private function resumenHoy(): object
    {
        $hoy = Carbon::today();
        $manana = $hoy->copy()->addDay();

        $v = DB::table('ventas')
            ->where('fecha', '>=', $hoy->toDateString())
            ->where('fecha', '<', $manana->toDateString())
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(total_final), 0) as ingresos')
            ->first();

        $entregas = DB::table('orden_maquinas')
            ->where('estado', 'entregada')
            ->where('fecha_salida', '>=', $hoy->toDateString())
            ->where('fecha_salida', '<', $manana->toDateString())
            ->count();

        return (object) [
            'ventas' => (int) ($v->cantidad ?? 0),
            'ingresos' => (float) ($v->ingresos ?? 0),
            'entregas' => (int) $entregas,
        ];
    }

    /* =====================================================
       🔧 REPARACIONES
    ===================================================== */

    public function reparacionesMes()
    {
        return $this->resumenReparacionesMes()->cantidad;
    }

    public function reparacionesActivas()
    {
        return DB::table('orden_maquinas')
            ->whereIn('estado', ['pendiente', 'en_reparacion'])
            ->count();
    }

    public function ingresosReparacionesMes()
    {
        return $this->resumenReparacionesMes()->ingresos;
    }

    /* =====================================================
       🛒 VENTAS
    ===================================================== */

    public function ventasMes()
    {
        return $this->resumenVentasMes()->cantidad;
    }

    public function ingresosVentasMes()
    {
        return $this->resumenVentasMes()->ingresos;
    }

    /**
     * Top 10 productos más vendidos de forma histórica (todo el tiempo,
     * sin filtrar por mes).
     */
    public function topProductosVendidosHistorico()
    {
        try {
            $data = collect(DB::select("
                SELECT 
                    p.id,
                    p.nombre,
                    SUM(vd.cantidad) as total_vendido,
                    SUM(vd.total) as total_ingresos
                FROM venta_detalle vd
                JOIN productos p ON vd.producto_id = p.id
                WHERE p.activo = 1
                GROUP BY p.id, p.nombre
                ORDER BY total_vendido DESC
                LIMIT 10
            "));

            return $data->map(function ($item) {
                return (object) [
                    'id' => $item->id,
                    'nombre' => $item->nombre ?? 'Desconocido',
                    'total_vendido' => intval($item->total_vendido ?? 0),
                    'total_ingresos' => floatval($item->total_ingresos ?? 0)
                ];
            });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /* =====================================================
       📦 INVENTARIO
    ===================================================== */

    public function valorInventarioCompra()
    {
        return $this->resumenInventario()->compra;
    }

    public function valorInventarioVenta()
    {
        return $this->resumenInventario()->venta;
    }

    /** Agotados primero y luego los de menos stock; máximo 8 para el panel. */
    public function productosPorReponer()
    {
        try {
            return collect(DB::select("
                SELECT id, codigo, nombre, stock, stock_minimo
                FROM productos
                WHERE activo = 1
                  AND (stock <= 0 OR stock <= stock_minimo)
                ORDER BY stock ASC, nombre ASC
                LIMIT 8
            "))->map(fn($item) => (object) [
                'id' => $item->id,
                'codigo' => $item->codigo ?? '',
                'nombre' => $item->nombre ?? 'Desconocido',
                'stock' => (int) ($item->stock ?? 0),
                'stock_minimo' => (int) ($item->stock_minimo ?? 0),
            ]);
        } catch (\Exception $e) {
            return collect();
        }
    }

    /** Productos con stock que no se venden hace 90 días y el dinero que tienen inmovilizado. */
    public function productosSinRotacion(): object
    {
        try {
            $r = Producto::activos()->sinRotacion(90)
                ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(stock * precio_compra), 0) as valor')
                ->first();

            return (object) ['cantidad' => (int) ($r->cantidad ?? 0), 'valor' => (float) ($r->valor ?? 0)];
        } catch (\Exception $e) {
            return (object) ['cantidad' => 0, 'valor' => 0.0];
        }
    }

    /**
     * Las máquinas que llevan más tiempo en el taller sin entregarse.
     *
     * @return array{lista: \Illuminate\Support\Collection, total: int}
     */
    public function ordenesAntiguas(): array
    {
        try {
            $base = DB::table('orden_maquinas as om')
                ->join('ordenes_reparacion as o', 'o.id', '=', 'om.orden_id')
                ->whereIn('om.estado', ['pendiente', 'en_reparacion']);

            $limite = Carbon::today()->subDays(self::DIAS_ORDEN_ANTIGUA)->toDateString();
            $total = (clone $base)->where('o.fecha_entrada', '<=', $limite)->count();

            $hoy = Carbon::today();

            $lista = (clone $base)
                ->join('clientes as c', 'c.id', '=', 'o.cliente_id')
                ->leftJoin('tecnicos as t', 't.id', '=', 'om.tecnico_id')
                ->orderBy('o.fecha_entrada')
                ->orderBy('om.id')
                ->limit(6)
                ->get([
                    'om.orden_id', 'om.etiqueta_maquina', 'om.descripcion', 'om.estado',
                    'o.numero_orden', 'o.fecha_entrada', 'c.nombre as cliente', 't.nombre as tecnico',
                ])
                ->map(fn($m) => (object) [
                    'orden_id' => $m->orden_id,
                    'numero_orden' => $m->numero_orden,
                    'cliente' => $m->cliente,
                    'maquina' => trim(($m->etiqueta_maquina ? $m->etiqueta_maquina . ' · ' : '') . ($m->descripcion ?? '')),
                    'estado' => $m->estado,
                    'tecnico' => $m->tecnico,
                    'dias' => (int) abs(Carbon::parse($m->fecha_entrada)->startOfDay()->diffInDays($hoy)),
                ]);

            return ['lista' => $lista, 'total' => (int) $total];
        } catch (\Exception $e) {
            return ['lista' => collect(), 'total' => 0];
        }
    }

    /* =====================================================
       👨‍🔧 TÉCNICOS
    ===================================================== */

    /**
     * Productividad por técnico.
     *
     * @param int|null $ultimosMeses  Si es null: histórico completo (sin
     *   filtro de fecha). Si se indica un número, solo cuenta/suma las
     *   entregas cuya fecha_salida cae en esos últimos N meses.
     *   "Activas" (pendiente/en_reparación) siempre es un conteo en vivo,
     *   sin importar el período elegido, porque son órdenes que aún no
     *   tienen fecha de salida.
     */
    public function productividadTecnicos(?int $ultimosMeses = null)
    {
        try {
            $filtroFecha = $ultimosMeses
                ? 'AND om.fecha_salida >= DATE_SUB(NOW(), INTERVAL ' . (int) $ultimosMeses . ' MONTH)'
                : '';

            $data = collect(DB::select("
                SELECT 
                    t.id,
                    t.nombre,
                    COUNT(CASE WHEN om.estado = 'entregada' {$filtroFecha} THEN 1 END) as entregadas,
                    COUNT(CASE WHEN om.estado IN ('pendiente', 'en_reparacion') THEN 1 END) as activas,
                    COALESCE(SUM(CASE WHEN om.estado = 'entregada' AND om.fecha_salida IS NOT NULL {$filtroFecha} THEN om.mano_obra ELSE 0 END), 0) as ingresos
                FROM tecnicos t
                LEFT JOIN orden_maquinas om ON t.id = om.tecnico_id
                WHERE t.activo = 1
                GROUP BY t.id, t.nombre
                ORDER BY ingresos DESC
            "));

            return $data->map(function ($item) {
                return (object) [
                    'id' => $item->id,
                    'nombre' => $item->nombre ?? 'Desconocido',
                    'entregadas' => intval($item->entregadas ?? 0),
                    'activas' => intval($item->activas ?? 0),
                    'ingresos' => floatval($item->ingresos ?? 0),
                ];
            });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /* =====================================================
       📈 GRÁFICAS
    ===================================================== */

    public function ventasReparacionesUltimos12Meses()
    {
        try {
            $mesesArray = [];
            $mesesEs = [
                1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
            ];
            for ($i = 11; $i >= 0; $i--) {
                $fecha = Carbon::now()->startOfMonth()->subMonths($i);
                $mesKey = $fecha->format('Y-m');
                $mesesArray[$mesKey] = [
                    'mes' => $mesesEs[(int) $fecha->format('n')],
                    'ventas' => 0,
                    'reparaciones' => 0
                ];
            }

            try {
                $ventas = DB::select("
                    SELECT 
                        DATE_FORMAT(fecha, '%Y-%m') as mes_key,
                        COALESCE(SUM(total_final), 0) as total
                    FROM ventas
                    WHERE fecha >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                ");

                foreach ($ventas as $v) {
                    if (isset($mesesArray[$v->mes_key])) {
                        $mesesArray[$v->mes_key]['ventas'] = floatval($v->total);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error al obtener ventas: ' . $e->getMessage());
            }

            try {
                $reparaciones = DB::select("
                    SELECT 
                        DATE_FORMAT(om.fecha_salida, '%Y-%m') as mes_key,
                        COALESCE(SUM(om.total), 0) as total
                    FROM orden_maquinas om
                    WHERE om.fecha_salida IS NOT NULL
                      AND om.estado = 'entregada'
                      AND om.fecha_salida >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(om.fecha_salida, '%Y-%m')
                ");

                foreach ($reparaciones as $r) {
                    if (isset($mesesArray[$r->mes_key])) {
                        $mesesArray[$r->mes_key]['reparaciones'] = floatval($r->total);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error al obtener reparaciones: ' . $e->getMessage());
            }

            return collect($mesesArray)->values();
        } catch (\Exception $e) {
            \Log::error('Error en ventasReparacionesUltimos12Meses: ' . $e->getMessage());
            return collect();
        }
    }
}
