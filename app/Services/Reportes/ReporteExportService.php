<?php

namespace App\Services\Reportes;

use App\Models\Venta;
use App\Services\Excel\ExcelHelper;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteExportService
{
    protected const MESES_ES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function __construct(protected ReporteService $reporteService)
    {
    }

    /**
     * Genera el libro de Excel del reporte pedido con TODOS los registros del período
     * (no depende de la paginación de la pantalla).
     *
     * @return array{0: Spreadsheet, 1: string} [$libro, $nombreArchivo]
     */
    public function exportar(string $tipo, int $mes, int $anio): array
    {
        ExcelHelper::preparar();

        $periodo = self::MESES_ES[$mes] . " {$anio}";

        $libro = match ($tipo) {
            'ventas' => $this->libroVentas($mes, $anio, $periodo),
            'reparaciones' => $this->libroReparaciones($mes, $anio, $periodo),
            'tecnicos' => $this->libroTecnicos($mes, $anio, $periodo),
            'financiero' => $this->libroFinanciero($mes, $anio, $periodo),
            'inventario' => $this->libroInventario($mes, $anio, $periodo),
            'anual' => $this->libroAnual($anio),
            default => throw new \InvalidArgumentException('Tipo de reporte no reconocido.'),
        };

        $nombre = $tipo === 'anual'
            ? "reporte_anual_{$anio}.xlsx"
            : 'reporte_' . $tipo . '_' . self::MESES_ES[$mes] . "_{$anio}.xlsx";

        return [$libro, $nombre];
    }

    private function cabecera(Worksheet $hoja, string $titulo, string $detalle, int $columnas): int
    {
        return ExcelHelper::encabezadoDocumento(
            $hoja,
            $titulo,
            $detalle . ' · Generado el ' . Carbon::now()->format('d/m/Y H:i'),
            $columnas
        );
    }

    private function libroVentas(int $mes, int $anio, string $periodo): Spreadsheet
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();

        $ventas = Venta::with('detalles.producto')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $inicio->copy()->addMonth()->toDateString())
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        [$libro, $hoja] = ExcelHelper::nuevoLibro('Ventas');
        $fila = $this->cabecera($hoja, 'Reporte de ventas', $periodo, 7);

        $formatos = ['texto', 'fecha', 'texto', 'entero', 'dinero', 'dinero', 'dinero'];
        $filas = [];

        foreach ($ventas as $v) {
            $productos = $v->detalles->map(fn($d) => ($d->producto->nombre ?? 'Producto eliminado') . ' ×' . $d->cantidad)->implode('; ');
            $filas[] = [
                $v->numero_factura,
                $v->fecha,
                $productos,
                $v->detalles->sum('cantidad'),
                $v->total,
                $v->descuento ?? 0,
                $v->total_final ?? $v->total,
            ];
        }

        $siguiente = ExcelHelper::tabla($hoja, $fila, ['Factura', 'Fecha', 'Productos', 'Unidades', 'Subtotal', 'Descuento', 'Total'], $filas, $formatos);

        ExcelHelper::filaTotales($hoja, $siguiente, [
            'TOTAL', '', count($filas) . ' ventas', $ventas->flatMap->detalles->sum('cantidad'),
            $ventas->sum('total'), $ventas->sum('descuento'), $ventas->sum('total_final'),
        ], $formatos, 7);

        $detalle = ExcelHelper::hojaNueva($libro, 'Detalle por producto');
        $fila = $this->cabecera($detalle, 'Detalle de ventas por producto', $periodo, 7);

        $formatosDetalle = ['texto', 'fecha', 'texto', 'texto', 'entero', 'dinero', 'dinero'];
        $filasDetalle = [];

        foreach ($ventas as $v) {
            foreach ($v->detalles as $d) {
                $filasDetalle[] = [
                    $v->numero_factura,
                    $v->fecha,
                    $d->producto->codigo ?? '',
                    $d->producto->nombre ?? 'Producto eliminado',
                    $d->cantidad,
                    $d->precio_unitario,
                    $d->total,
                ];
            }
        }

        $siguiente = ExcelHelper::tabla($detalle, $fila, ['Factura', 'Fecha', 'Código', 'Producto', 'Cantidad', 'Precio unitario', 'Total línea'], $filasDetalle, $formatosDetalle);

        ExcelHelper::filaTotales($detalle, $siguiente, [
            'TOTAL', '', '', '', array_sum(array_column($filasDetalle, 4)), '', array_sum(array_map(fn($f) => (float) $f[6], $filasDetalle)),
        ], $formatosDetalle, 7);

        return $libro;
    }

    private function libroReparaciones(int $mes, int $anio, string $periodo): Spreadsheet
    {
        $data = $this->reporteService->generarReporteMensual($mes, $anio);
        $maquinas = $data['reparaciones']->sortBy('fecha_salida')->values();

        [$libro, $hoja] = ExcelHelper::nuevoLibro('Reparaciones');
        $fila = $this->cabecera($hoja, 'Reporte de reparaciones', $periodo . ' · máquinas entregadas', 10);

        $formatos = ['texto', 'texto', 'texto', 'texto', 'fecha', 'fecha', 'texto', 'texto', 'dinero', 'dinero'];
        $filas = [];

        foreach ($maquinas as $m) {
            $filas[] = [
                $m->orden->numero_orden ?? ('#' . $m->orden_id),
                $m->orden->cliente->nombre ?? 'Sin cliente',
                trim(($m->etiqueta_maquina ? $m->etiqueta_maquina . ' · ' : '') . ($m->descripcion ?? '')),
                $m->tecnico->nombre ?? 'Sin técnico',
                $m->orden->fecha_entrada ?? null,
                $m->fecha_salida,
                $m->es_garantia ? ($m->tipo_garantia ?: 'Sí') : 'No',
                $m->factura ?: '',
                $m->mano_obra,
                $m->total,
            ];
        }

        $siguiente = ExcelHelper::tabla(
            $hoja,
            $fila,
            ['Orden', 'Cliente', 'Máquina', 'Técnico', 'Fecha de ingreso', 'Fecha de salida', 'Garantía', 'Factura', 'Mano de obra', 'Total'],
            $filas,
            $formatos
        );

        ExcelHelper::filaTotales($hoja, $siguiente, [
            'TOTAL', '', count($filas) . ' máquinas', '', '', '', '', '', $maquinas->sum('mano_obra'), $maquinas->sum('total'),
        ], $formatos, 10);

        return $libro;
    }

    private function libroTecnicos(int $mes, int $anio, string $periodo): Spreadsheet
    {
        $data = $this->reporteService->generarReporteMensual($mes, $anio);
        $productividad = $data['productividad'];
        $sumaTotal = array_sum(array_column($productividad, 'total'));

        [$libro, $hoja] = ExcelHelper::nuevoLibro('Técnicos');
        $fila = $this->cabecera($hoja, 'Productividad de técnicos', $periodo, 6);

        $formatos = ['entero', 'texto', 'entero', 'dinero', 'dinero', 'porcentaje'];
        $filas = [];
        $posicion = 1;

        foreach ($productividad as $nombre => $datos) {
            $filas[] = [
                $posicion++,
                $nombre,
                $datos['maquinas'],
                $datos['mano_obra'],
                $datos['total'],
                $sumaTotal > 0 ? round(($datos['total'] / $sumaTotal) * 100, 1) : 0,
            ];
        }

        $siguiente = ExcelHelper::tabla($hoja, $fila, ['Posición', 'Técnico', 'Máquinas', 'Mano de obra', 'Total cobrado', 'Participación'], $filas, $formatos);

        ExcelHelper::filaTotales($hoja, $siguiente, [
            '', 'TOTAL', array_sum(array_column($productividad, 'maquinas')), array_sum(array_column($productividad, 'mano_obra')), $sumaTotal, $sumaTotal > 0 ? 100 : 0,
        ], $formatos, 6);

        return $libro;
    }

    private function libroFinanciero(int $mes, int $anio, string $periodo): Spreadsheet
    {
        $data = $this->reporteService->generarReporteMensual($mes, $anio);

        $ventas = (float) $data['totalVentasMes'];
        $reparaciones = (float) $data['totalReparacionesMes'];
        $total = (float) $data['totalGeneral'];
        $cantidadVentas = $data['ventas']->count();
        $cantidadMaquinas = $data['reparaciones']->count();

        [$libro, $hoja] = ExcelHelper::nuevoLibro('Resumen financiero');
        $fila = $this->cabecera($hoja, 'Resumen financiero', $periodo, 4);

        $formatos = ['texto', 'entero', 'dinero', 'porcentaje'];

        $siguiente = ExcelHelper::tabla($hoja, $fila, ['Concepto', 'Cantidad', 'Ingresos', 'Participación'], [
            ['Ventas de productos', $cantidadVentas, $ventas, $total > 0 ? round($ventas / $total * 100, 1) : 0],
            ['Reparaciones entregadas', $cantidadMaquinas, $reparaciones, $total > 0 ? round($reparaciones / $total * 100, 1) : 0],
        ], $formatos, false);

        ExcelHelper::filaTotales($hoja, $siguiente, ['TOTAL GENERAL', $cantidadVentas + $cantidadMaquinas, $total, $total > 0 ? 100 : 0], $formatos, 4);

        $indicadores = ExcelHelper::hojaNueva($libro, 'Indicadores');
        $fila = $this->cabecera($indicadores, 'Indicadores del período', $periodo, 2);

        ExcelHelper::tabla($indicadores, $fila, ['Indicador', 'Valor'], [
            ['Ticket promedio de venta', $cantidadVentas > 0 ? round($ventas / $cantidadVentas) : 0],
            ['Ticket promedio por máquina reparada', $cantidadMaquinas > 0 ? round($reparaciones / $cantidadMaquinas) : 0],
            ['Mano de obra del período', $data['reparaciones']->sum('mano_obra')],
            ['Descuentos otorgados en ventas', $data['ventas']->sum('descuento')],
        ], ['texto', 'dinero'], false);

        return $libro;
    }

    private function libroInventario(int $mes, int $anio, string $periodo): Spreadsheet
    {
        $data = $this->reporteService->generarReporteInventario($mes, $anio);

        [$libro, $hoja] = ExcelHelper::nuevoLibro('Productos');
        $fila = $this->cabecera($hoja, 'Reporte de inventario', 'Estado actual de los productos activos', 13);

        $formatos = ['texto', 'texto', 'texto', 'texto', 'texto', 'texto', 'entero', 'entero', 'texto', 'dinero', 'dinero', 'dinero', 'dinero'];
        $filas = [];

        foreach ($data['productos'] as $p) {
            $estado = match ($p->estado_stock) {
                'agotado' => 'Agotado',
                'bajo' => 'Bajo',
                default => 'Normal',
            };

            $filas[] = [
                $p->codigo, $p->nombre, $p->modelo ?? '', $p->categoria ?? '', $p->ubicacion ?? '', $p->proveedor ?? '',
                $p->stock, $p->stock_minimo, $estado,
                $p->precio_compra, $p->precio_venta,
                $p->precio_compra * $p->stock, $p->precio_venta * $p->stock,
            ];
        }

        $siguiente = ExcelHelper::tabla(
            $hoja,
            $fila,
            ['Código', 'Producto', 'Modelo', 'Categoría', 'Ubicación', 'Proveedor', 'Stock', 'Stock mínimo', 'Estado', 'Costo', 'Precio de venta', 'Valor a costo', 'Valor a venta'],
            $filas,
            $formatos
        );

        ExcelHelper::filaTotales($hoja, $siguiente, [
            'TOTAL', count($filas) . ' productos', '', '', '', '', $data['productos']->sum('stock'), '', '', '', '', $data['totalCompra'], $data['totalVenta'],
        ], $formatos, 13);

        $hojaMov = ExcelHelper::hojaNueva($libro, 'Movimientos del mes');
        $fila = $this->cabecera($hojaMov, 'Movimientos de inventario', $periodo, 6);

        $filasMov = [];
        foreach ($data['movimientos']->sortBy('created_at') as $mov) {
            $filasMov[] = [
                $mov->created_at,
                $mov->producto->codigo ?? '',
                $mov->producto->nombre ?? '—',
                match ($mov->tipo) { 'entrada' => 'Entrada', 'salida' => 'Salida', default => 'Ajuste' },
                $mov->tipo === 'salida' ? -abs($mov->cantidad) : $mov->cantidad,
                $mov->referencia ?? '',
            ];
        }

        ExcelHelper::tabla($hojaMov, $fila, ['Fecha', 'Código', 'Producto', 'Tipo', 'Cantidad', 'Referencia'], $filasMov, ['fechahora', 'texto', 'texto', 'texto', 'entero', 'texto']);

        return $libro;
    }

    private function libroAnual(int $anio): Spreadsheet
    {
        $data = $this->reporteService->generarResumenAnual($anio);

        [$libro, $hoja] = ExcelHelper::nuevoLibro('Resumen anual');
        $fila = $this->cabecera($hoja, "Resumen anual {$anio}", 'Ventas y reparaciones mes a mes', 6);

        $formatos = ['texto', 'entero', 'dinero', 'entero', 'dinero', 'dinero'];
        $filas = [];

        foreach ($data['resumen'] as $m) {
            $filas[] = [$m['mes'], $m['ventas_count'], $m['ventas_total'], $m['rep_count'], $m['rep_total'], $m['total_mes']];
        }

        $siguiente = ExcelHelper::tabla($hoja, $fila, ['Mes', 'Ventas (#)', 'Ingresos por ventas', 'Reparaciones (#)', 'Ingresos por reparaciones', 'Total del mes'], $filas, $formatos, false);

        ExcelHelper::filaTotales($hoja, $siguiente, [
            'TOTAL AÑO', array_sum(array_column($filas, 1)), $data['totalAnioVentas'], array_sum(array_column($filas, 3)), $data['totalAnioRep'], $data['totalAnio'],
        ], $formatos, 6);

        return $libro;
    }
}
