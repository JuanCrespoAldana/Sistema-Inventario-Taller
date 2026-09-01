<?php

namespace App\Services\Reportes;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
     * Genera el archivo .xlsx para el tipo de reporte indicado.
     * Usa exactamente los mismos métodos del ReporteService que alimentan
     * las vistas en pantalla, así que siempre exporta TODOS los registros
     * del período filtrado, sin depender de la paginación visual.
     *
     * @return array{0: Spreadsheet, 1: string} [$spreadsheet, $nombreArchivo]
     */
    public function exportar(string $tipo, int $mes, int $anio): array
    {
        $spreadsheet = new Spreadsheet();

        match ($tipo) {
            'ventas'       => $this->hojaVentas($spreadsheet, $mes, $anio),
            'reparaciones' => $this->hojaReparaciones($spreadsheet, $mes, $anio),
            'tecnicos'     => $this->hojaTecnicos($spreadsheet, $mes, $anio),
            'financiero'   => $this->hojaFinanciero($spreadsheet, $mes, $anio),
            'inventario'   => $this->hojaInventario($spreadsheet, $mes, $anio),
            'anual'        => $this->hojaAnual($spreadsheet, $anio),
            default        => throw new \InvalidArgumentException('Tipo de reporte no reconocido.'),
        };

        $nombreArchivo = $tipo === 'anual'
            ? "reporte_anual_{$anio}.xlsx"
            : 'reporte_' . $tipo . '_' . self::MESES_ES[$mes] . "_{$anio}.xlsx";

        return [$spreadsheet, $nombreArchivo];
    }

    private function estiloEncabezado($sheet, string $rango): void
    {
        $sheet->getStyle($rango)->getFont()->setBold(true);
        $sheet->getStyle($rango)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');
    }

    private function autoAjustarColumnas($sheet, int $totalColumnas): void
    {
        for ($i = 1; $i <= $totalColumnas; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    private function hojaVentas(Spreadsheet $spreadsheet, int $mes, int $anio): void
    {
        $data = $this->reporteService->generarReporteMensual($mes, $anio);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ventas');
        $sheet->fromArray(['Factura', 'Cliente', 'Fecha', 'Total'], null, 'A1');
        $this->estiloEncabezado($sheet, 'A1:D1');

        $fila = 2;
        foreach ($data['ventas'] as $venta) {
            $sheet->setCellValue("A{$fila}", $venta->numero_factura);
            $sheet->setCellValue("B{$fila}", $venta->cliente->nombre ?? 'Sin cliente');
            $sheet->setCellValue("C{$fila}", optional($venta->fecha)->format('d/m/Y'));
            $sheet->setCellValue("D{$fila}", (float) ($venta->total_final ?? $venta->total));
            $fila++;
        }

        $sheet->setCellValue("C{$fila}", 'TOTAL');
        $sheet->setCellValue("D{$fila}", (float) $data['totalVentasMes']);
        $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true);

        $this->autoAjustarColumnas($sheet, 4);
    }

    private function hojaReparaciones(Spreadsheet $spreadsheet, int $mes, int $anio): void
    {
        $data = $this->reporteService->generarReporteMensual($mes, $anio);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reparaciones');
        $sheet->fromArray(['Orden', 'Técnico', 'Fecha de salida', 'Total'], null, 'A1');
        $this->estiloEncabezado($sheet, 'A1:D1');

        $fila = 2;
        foreach ($data['reparaciones'] as $rep) {
            $sheet->setCellValue("A{$fila}", $rep->orden->numero_orden ?? ('#' . $rep->orden_id));
            $sheet->setCellValue("B{$fila}", $rep->tecnico->nombre ?? 'Sin técnico');
            $sheet->setCellValue("C{$fila}", optional($rep->fecha_salida)->format('d/m/Y'));
            $sheet->setCellValue("D{$fila}", (float) $rep->total);
            $fila++;
        }

        $sheet->setCellValue("C{$fila}", 'TOTAL');
        $sheet->setCellValue("D{$fila}", (float) $data['totalReparacionesMes']);
        $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true);

        $this->autoAjustarColumnas($sheet, 4);
    }

    private function hojaTecnicos(Spreadsheet $spreadsheet, int $mes, int $anio): void
    {
        $data = $this->reporteService->generarReporteMensual($mes, $anio);
        $prod = $data['productividad'];
        $sumaTotal = array_sum(array_column($prod, 'total'));

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Técnicos');
        $sheet->fromArray(
            ['Posición', 'Técnico', 'Máquinas', 'Mano de obra', 'Total cobrado', 'Participación %'],
            null,
            'A1'
        );
        $this->estiloEncabezado($sheet, 'A1:F1');

        $fila = 2;
        $pos = 1;
        foreach ($prod as $nombre => $datos) {
            $pct = $sumaTotal > 0 ? round(($datos['total'] / $sumaTotal) * 100, 1) : 0;
            $sheet->setCellValue("A{$fila}", $pos);
            $sheet->setCellValue("B{$fila}", $nombre);
            $sheet->setCellValue("C{$fila}", $datos['maquinas']);
            $sheet->setCellValue("D{$fila}", (float) $datos['mano_obra']);
            $sheet->setCellValue("E{$fila}", (float) $datos['total']);
            $sheet->setCellValue("F{$fila}", $pct);
            $fila++;
            $pos++;
        }

        $this->autoAjustarColumnas($sheet, 6);
    }

    private function hojaFinanciero(Spreadsheet $spreadsheet, int $mes, int $anio): void
    {
        $data = $this->reporteService->generarReporteMensual($mes, $anio);
        $v = (float) $data['totalVentasMes'];
        $r = (float) $data['totalReparacionesMes'];
        $t = (float) $data['totalGeneral'];
        $pctV = $t > 0 ? round(($v / $t) * 100, 1) : 0;
        $pctR = $t > 0 ? round(($r / $t) * 100, 1) : 0;

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Financiero');
        $sheet->fromArray(['Concepto', 'Monto', 'Participación %'], null, 'A1');
        $this->estiloEncabezado($sheet, 'A1:C1');

        $sheet->fromArray([
            ['Ventas', $v, $pctV],
            ['Reparaciones', $r, $pctR],
            ['TOTAL GENERAL', $t, 100],
        ], null, 'A2');

        $sheet->getStyle('A4:C4')->getFont()->setBold(true);

        $this->autoAjustarColumnas($sheet, 3);
    }

    private function hojaInventario(Spreadsheet $spreadsheet, int $mes, int $anio): void
    {
        $data = $this->reporteService->generarReporteInventario($mes, $anio);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');
        $sheet->fromArray(
            ['Código', 'Producto', 'Stock', 'Estado', 'P. Compra', 'P. Venta', 'Valor Total'],
            null,
            'A1'
        );
        $this->estiloEncabezado($sheet, 'A1:G1');

        $fila = 2;
        foreach ($data['productos'] as $p) {
            $estado = $p->stock == 0 ? 'CRÍTICO' : ($p->stock <= 2 ? 'BAJO' : 'Normal');
            $sheet->setCellValue("A{$fila}", $p->codigo);
            $sheet->setCellValue("B{$fila}", $p->nombre . ($p->modelo ? " — {$p->modelo}" : ''));
            $sheet->setCellValue("C{$fila}", $p->stock);
            $sheet->setCellValue("D{$fila}", $estado);
            $sheet->setCellValue("E{$fila}", (float) $p->precio_compra);
            $sheet->setCellValue("F{$fila}", (float) $p->precio_venta);
            $sheet->setCellValue("G{$fila}", (float) ($p->precio_venta * $p->stock));
            $fila++;
        }
        $sheet->setCellValue("F{$fila}", 'VALOR TOTAL');
        $sheet->setCellValue("G{$fila}", (float) $data['totalVenta']);
        $sheet->getStyle("F{$fila}:G{$fila}")->getFont()->setBold(true);
        $this->autoAjustarColumnas($sheet, 7);

        // Segunda hoja: movimientos del mes, en el mismo archivo.
        $hojaMov = $spreadsheet->createSheet();
        $hojaMov->setTitle('Movimientos');
        $hojaMov->fromArray(['Producto', 'Tipo', 'Cantidad', 'Referencia', 'Fecha'], null, 'A1');
        $this->estiloEncabezado($hojaMov, 'A1:E1');

        $fila = 2;
        foreach ($data['movimientos'] as $mov) {
            $hojaMov->setCellValue("A{$fila}", $mov->producto->nombre ?? '—');
            $hojaMov->setCellValue("B{$fila}", $mov->tipo === 'entrada' ? 'Entrada' : 'Salida');
            $hojaMov->setCellValue("C{$fila}", $mov->cantidad);
            $hojaMov->setCellValue("D{$fila}", $mov->referencia ?? '—');
            $hojaMov->setCellValue("E{$fila}", optional($mov->created_at)->format('d/m/Y H:i'));
            $fila++;
        }
        $this->autoAjustarColumnas($hojaMov, 5);

        $spreadsheet->setActiveSheetIndex(0);
    }

    private function hojaAnual(Spreadsheet $spreadsheet, int $anio): void
    {
        $data = $this->reporteService->generarResumenAnual($anio);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen anual');
        $sheet->fromArray(
            ['Mes', 'Ventas (#)', 'Ingresos ventas', 'Reparaciones (#)', 'Ingresos reparaciones', 'Total mes'],
            null,
            'A1'
        );
        $this->estiloEncabezado($sheet, 'A1:F1');

        $fila = 2;
        foreach ($data['resumen'] as $m) {
            $sheet->setCellValue("A{$fila}", $m['mes']);
            $sheet->setCellValue("B{$fila}", $m['ventas_count']);
            $sheet->setCellValue("C{$fila}", (float) $m['ventas_total']);
            $sheet->setCellValue("D{$fila}", $m['rep_count']);
            $sheet->setCellValue("E{$fila}", (float) $m['rep_total']);
            $sheet->setCellValue("F{$fila}", (float) $m['total_mes']);
            $fila++;
        }

        $sheet->setCellValue("A{$fila}", 'TOTAL AÑO');
        $sheet->setCellValue("C{$fila}", (float) $data['totalAnioVentas']);
        $sheet->setCellValue("E{$fila}", (float) $data['totalAnioRep']);
        $sheet->setCellValue("F{$fila}", (float) $data['totalAnio']);
        $sheet->getStyle("A{$fila}:F{$fila}")->getFont()->setBold(true);

        $this->autoAjustarColumnas($sheet, 6);
    }
}
