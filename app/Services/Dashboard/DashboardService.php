<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    private function hoy()
    {
        return Carbon::today();
    }

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

    public function reparacionesHoy()
    {
        return DB::table('ordenes_reparacion')
            ->whereDate('fecha_entrada', $this->hoy())
            ->count();
    }

    public function reparacionesMes()
    {
        return DB::table('ordenes_reparacion')
            ->whereMonth('fecha_entrada', $this->mes())
            ->whereYear('fecha_entrada', $this->anio())
            ->count();
    }

    public function reparacionesAnio()
    {
        return DB::table('ordenes_reparacion')
            ->whereYear('fecha_entrada', $this->anio())
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
        $total = DB::table('orden_maquinas')
            ->join('ordenes_reparacion', 'orden_maquinas.orden_id', '=', 'ordenes_reparacion.id')
            ->whereMonth('ordenes_reparacion.fecha_entrada', $this->mes())
            ->whereYear('ordenes_reparacion.fecha_entrada', $this->anio())
            ->sum('orden_maquinas.mano_obra');

        return $total ?? 0;
    }

    public function ingresosReparacionesAnio()
    {
        $total = DB::table('orden_maquinas')
            ->join('ordenes_reparacion', 'orden_maquinas.orden_id', '=', 'ordenes_reparacion.id')
            ->whereYear('ordenes_reparacion.fecha_entrada', $this->anio())
            ->sum('orden_maquinas.mano_obra');

        return $total ?? 0;
    }

    public function estadosReparaciones()
    {
        return DB::table('orden_maquinas')
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado') ?? collect();
    }

    /* =====================================================
       🛒 VENTAS
    ===================================================== */

    public function ventasHoy()
    {
        return DB::table('ventas')
            ->whereDate('fecha', $this->hoy())
            ->count();
    }

    public function ventasMes()
    {
        return DB::table('ventas')
            ->whereMonth('fecha', $this->mes())
            ->whereYear('fecha', $this->anio())
            ->count();
    }

    public function ventasAnio()
    {
        return DB::table('ventas')
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

    public function ingresosVentasAnio()
    {
        $total = DB::table('ventas')
            ->whereYear('fecha', $this->anio())
            ->sum('total_final');

        return $total ?? 0;
    }

    public function topProductos()
    {
        try {
            $data = collect(DB::select("
                SELECT 
                    p.id,
                    p.nombre,
                    SUM(vd.cantidad) as total
                FROM venta_detalle vd
                JOIN productos p ON vd.producto_id = p.id
                GROUP BY p.id, p.nombre
                ORDER BY total DESC
                LIMIT 5
            "));

            return $data->map(function ($item) {
                return (object) [
                    'id' => $item->id,
                    'nombre' => $item->nombre ?? 'Desconocido',
                    'total' => intval($item->total ?? 0)
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
            ->value('total');

        return $total ?? 0;
    }

    public function valorInventarioVenta()
    {
        $total = DB::table('productos')
            ->selectRaw('SUM(stock * precio_venta) as total')
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

    public function movimientosRecientes()
    {
        try {
            $data = collect(DB::select("
                SELECT 
                    p.nombre,
                    im.tipo,
                    im.cantidad,
                    im.created_at
                FROM inventario_movimientos im
                JOIN productos p ON im.producto_id = p.id
                ORDER BY im.created_at DESC
                LIMIT 5
            "));

            return $data->map(function ($item) {
                return (object) [
                    'nombre' => $item->nombre ?? 'Desconocido',
                    'tipo' => $item->tipo ?? 'entrada',
                    'cantidad' => intval($item->cantidad ?? 0),
                    'created_at' => $item->created_at
                ];
            });
        } catch (\Exception $e) {
            return collect();
        }
    }

    public function topMaquinasVendidas()
    {
        try {
            $mes = $this->mes();
            $anio = $this->anio();

            $data = collect(DB::select("
                SELECT 
                    p.id,
                    p.nombre,
                    SUM(vd.cantidad) as total_vendido,
                    SUM(vd.total) as total_ingresos
                FROM venta_detalle vd
                JOIN ventas v ON vd.venta_id = v.id
                JOIN productos p ON vd.producto_id = p.id
                WHERE MONTH(v.fecha) = ? 
                  AND YEAR(v.fecha) = ? 
                  AND p.activo = 1
                GROUP BY p.id, p.nombre
                ORDER BY total_vendido DESC
                LIMIT 10
            ", [$mes, $anio]));

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
       👨‍🔧 TÉCNICOS
    ===================================================== */

    public function rankingTecnicos()
    {
        try {
            $data = collect(DB::select("
                SELECT 
                    t.id,
                    t.nombre,
                    COUNT(om.id) as maquinas,
                    COALESCE(SUM(om.mano_obra), 0) as total
                FROM tecnicos t
                LEFT JOIN orden_maquinas om ON t.id = om.tecnico_id
                WHERE t.activo = 1
                GROUP BY t.id, t.nombre
                ORDER BY total DESC
                LIMIT 5
            "));

            return $data->map(function ($item) {
                return (object) [
                    'id' => $item->id,
                    'nombre' => $item->nombre ?? 'Desconocido',
                    'maquinas' => intval($item->maquinas ?? 0),
                    'total' => floatval($item->total ?? 0)
                ];
            });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /* =====================================================
       📈 GRÁFICAS
    ===================================================== */

    public function graficaVentasReparacionesMes()
    {
        return [
            'ventas' => $this->ingresosVentasMes(),
            'reparaciones' => $this->ingresosReparacionesMes(),
        ];
    }

    public function ingresosReparacionesHoy()
    {
        $total = DB::table('orden_maquinas')
            ->join('ordenes_reparacion', 'orden_maquinas.orden_id', '=', 'ordenes_reparacion.id')
            ->whereDate('ordenes_reparacion.fecha_entrada', $this->hoy())
            ->sum('orden_maquinas.mano_obra');

        return $total ?? 0;
    }

    public function reparacionesPendientes()
    {
        return DB::table('orden_maquinas')
            ->where('estado', 'pendiente')
            ->count() ?? 0;
    }

    public function reparacionesEnReparacion()
    {
        return DB::table('orden_maquinas')
            ->where('estado', 'en_reparacion')
            ->count() ?? 0;
    }

    public function reparacionesEntregadas()
    {
        return DB::table('orden_maquinas')
            ->where('estado', 'entregada')
            ->count() ?? 0;
    }

    public function facturacionTotal()
    {
        $hoy = DB::table('ventas')
            ->whereDate('fecha', $this->hoy())
            ->sum('total_final') ?? 0;

        $mes = DB::table('ventas')
            ->whereMonth('fecha', $this->mes())
            ->whereYear('fecha', $this->anio())
            ->sum('total_final') ?? 0;

        return (object) [
            'hoy' => $hoy,
            'mes' => $mes
        ];
    }

    public function productividadTecnicos()
    {
        try {
            $data = collect(DB::select("
                SELECT 
                    t.id,
                    t.nombre,
                    COUNT(CASE WHEN om.estado = 'entregada' THEN 1 END) as entregadas,
                    COUNT(CASE WHEN om.estado IN ('pendiente', 'en_reparacion') THEN 1 END) as activas,
                    COALESCE(SUM(om.mano_obra), 0) as ingresos,
                    ROUND(COUNT(CASE WHEN om.estado = 'entregada' THEN 1 END) / NULLIF(COUNT(om.id), 0) * 100, 2) as eficiencia
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
                    'eficiencia' => floatval($item->eficiencia ?? 0)
                ];
            });
        } catch (\Exception $e) {
            return collect();
        }
    }

    public function ingresosVentasHoy()
    {
        $total = DB::table('ventas')
            ->whereDate('fecha', $this->hoy())
            ->sum('total_final');

        return $total ?? 0;
    }

    public function comparativoAnual()
    {
        return DB::table('ventas')
            ->selectRaw('YEAR(fecha) as anio, SUM(total) as total')
            ->groupBy('anio')
            ->orderBy('anio')
            ->pluck('total', 'anio');
    }

    public function ventasReparacionesUltimos12Meses()
    {
        try {
            // Generar últimos 12 meses
            $mesesArray = [];
            $mesesEs = [
                1 => 'Ene',
                2 => 'Feb',
                3 => 'Mar',
                4 => 'Abr',
                5 => 'May',
                6 => 'Jun',
                7 => 'Jul',
                8 => 'Ago',
                9 => 'Sep',
                10 => 'Oct',
                11 => 'Nov',
                12 => 'Dic'
            ];
            for ($i = 11; $i >= 0; $i--) {
                $fecha = Carbon::now()->subMonths($i);
                $mesKey = $fecha->format('Y-m');
                $mesesArray[$mesKey] = [
                    'mes' => $mesesEs[(int) $fecha->format('n')] . ' ' . $fecha->format('y'),
                    'ventas' => 0,
                    'reparaciones' => 0
                ];
            }

            // Obtener ventas por mes
            try {
                $ventas = DB::select("
                    SELECT 
                        DATE_FORMAT(fecha, '%Y-%m') as mes_key,
                        COALESCE(SUM(total), 0) as total
                    FROM ventas
                    WHERE fecha >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                ");

                foreach ($ventas as $v) {
                    $mesKey = $v->mes_key;
                    if (isset($mesesArray[$mesKey])) {
                        $mesesArray[$mesKey]['ventas'] = floatval($v->total);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error al obtener ventas: ' . $e->getMessage());
            }

            // Obtener reparaciones por mes
            try {
                $reparaciones = DB::select("
                    SELECT 
                        DATE_FORMAT(or2.fecha_entrada, '%Y-%m') as mes_key,
                        COALESCE(SUM(om.mano_obra), 0) as total
                    FROM ordenes_reparacion or2
                    LEFT JOIN orden_maquinas om ON or2.id = om.orden_id
                    WHERE or2.fecha_entrada >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(or2.fecha_entrada, '%Y-%m')
                ");

                foreach ($reparaciones as $r) {
                    $mesKey = $r->mes_key;
                    if (isset($mesesArray[$mesKey])) {
                        $mesesArray[$mesKey]['reparaciones'] = floatval($r->total);
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
