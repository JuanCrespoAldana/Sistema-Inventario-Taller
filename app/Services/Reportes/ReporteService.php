<?php

namespace App\Services\Reportes;

use Carbon\Carbon;
use App\Models\Venta;
use App\Models\OrdenMaquina;
use App\Models\Tecnico;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    /**
     * Genera reporte mensual completo.
     * IMPORTANTE: reparaciones se filtra por fecha_entrada de la orden,
     * NO por created_at de orden_maquinas (que es la fecha de inserción en BD).
     */
    public function generarReporteMensual($mes, $anio)
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin    = Carbon::create($anio, $mes, 1)->endOfMonth();

        // ================= VENTAS =================
        $ventas = Venta::whereBetween('fecha', [
            $inicio->toDateString(),
            $fin->toDateString(),
        ])
            ->orderByDesc('fecha')
            ->get(['id', 'numero_factura', 'fecha', 'total', 'descuento', 'total_final']);

        $totalVentasMes = $ventas->sum('total_final');

        // ================= REPARACIONES =================
        // Filtrar por fecha_entrada de la orden (campo real de negocio),
        // NO por created_at de orden_maquinas (solo fecha de importación a BD).
        $reparaciones = OrdenMaquina::with(['orden.cliente', 'tecnico'])
            ->join('ordenes_reparacion', 'orden_maquinas.orden_id', '=', 'ordenes_reparacion.id')
            ->whereBetween('ordenes_reparacion.fecha_entrada', [
                $inicio->toDateString(),
                $fin->toDateString(),
            ])
            ->orderByDesc('ordenes_reparacion.fecha_entrada')
            ->select('orden_maquinas.*')
            ->get();

        $totalReparacionesMes = $reparaciones->sum('total');

        // ================= PRODUCTIVIDAD TÉCNICOS =================
        $productividad = [];

        $reparaciones
            ->whereNotNull('tecnico_id')
            ->groupBy('tecnico_id')
            ->each(function ($items, $tecnicoId) use (&$productividad) {
                $tecnico = Tecnico::find($tecnicoId);
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

        for ($m = 1; $m <= 12; $m++) {
            $inicio = Carbon::create($anio, $m, 1)->startOfMonth();
            $fin    = Carbon::create($anio, $m, 1)->endOfMonth();

            $ventas = DB::table('ventas')
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_final),0) as total')
                ->first();

            $reparaciones = DB::table('orden_maquinas')
                ->join('ordenes_reparacion', 'orden_maquinas.orden_id', '=', 'ordenes_reparacion.id')
                ->whereBetween('ordenes_reparacion.fecha_entrada', [$inicio->toDateString(), $fin->toDateString()])
                ->selectRaw('COUNT(orden_maquinas.id) as count, COALESCE(SUM(orden_maquinas.total),0) as total')
                ->first();

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
}
