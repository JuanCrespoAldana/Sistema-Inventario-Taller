<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    private function mes()
    {
        return Carbon::now()->month;
    }

    private function anio()
    {
        return Carbon::now()->year;
    }

    /* =====================================================
       🔧 REPARACIONES
    ===================================================== */

    public function reparacionesMes()
    {
        return DB::table('orden_maquinas')
            ->whereMonth('fecha_salida', $this->mes())
            ->whereYear('fecha_salida', $this->anio())
            ->count();
    }

    public function reparacionesActivas()
    {
        return DB::table('orden_maquinas')
            ->whereIn('estado', ['pendiente', 'en_reparacion'])
            ->count();
    }

    public function ingresosReparacionesMes()
    {
        return DB::table('orden_maquinas')
            ->whereMonth('fecha_salida', $this->mes())
            ->whereYear('fecha_salida', $this->anio())
            ->where('estado', 'entregada')
            ->sum('total');
    }

    /* =====================================================
       🛒 VENTAS
    ===================================================== */

    public function ventasMes()
    {
        return DB::table('ventas')
            ->whereMonth('fecha', $this->mes())
            ->whereYear('fecha', $this->anio())
            ->count();
    }

    public function ingresosVentasMes()
    {
        $total = DB::table('ventas')
            ->whereMonth('fecha', $this->mes())
            ->whereYear('fecha', $this->anio())
            ->sum('total_final');

        return $total ?? 0;
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
        $total = DB::table('productos')
            ->selectRaw('SUM(stock * precio_compra) as total')
            ->where('activo', 1)
            ->value('total');

        return $total ?? 0;
    }

    public function valorInventarioVenta()
    {
        $total = DB::table('productos')
            ->selectRaw('SUM(stock * precio_venta) as total')
            ->where('activo', 1)
            ->value('total');

        return $total ?? 0;
    }

    public function productosCriticos()
    {
        try {
            $data = collect(DB::select("
                SELECT id, nombre, stock, codigo
                FROM productos
                WHERE stock = 0
                  AND activo = 1
                ORDER BY nombre ASC
                LIMIT 50
            "));

            return $data->map(function ($item) {
                return (object) [
                    'id' => $item->id,
                    'nombre' => $item->nombre ?? 'Desconocido',
                    'stock' => intval($item->stock ?? 0),
                    'codigo' => $item->codigo ?? ''
                ];
            });
        } catch (\Exception $e) {
            return collect();
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
                $fecha = Carbon::now()->subMonths($i);
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
