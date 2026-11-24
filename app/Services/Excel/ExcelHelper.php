<?php

namespace App\Services\Excel;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExcelHelper
{
    public const AZUL_OSCURO = '10264B';
    public const AZUL_SUAVE = 'DDE9FC';
    public const GRIS_SUAVE = 'F2F5FA';

    public const FORMATOS = [
        'texto' => '@',
        'entero' => '#,##0',
        'dinero' => '"$"#,##0',
        'fecha' => 'dd/mm/yyyy',
        'fechahora' => 'dd/mm/yyyy hh:mm',
        'porcentaje' => '0.0"%"',
    ];

    public static function preparar(): void
    {
        @ini_set('display_errors', '0');
        @set_time_limit(180);

        $limite = ini_get('memory_limit');
        if ($limite !== '-1' && self::aBytes($limite) < 536870912) {
            @ini_set('memory_limit', '512M');
        }
    }

    private static function aBytes(string $valor): int
    {
        $valor = trim($valor);
        $numero = (int) $valor;

        return match (strtolower(substr($valor, -1))) {
            'g' => $numero * 1073741824,
            'm' => $numero * 1048576,
            'k' => $numero * 1024,
            default => $numero,
        };
    }

    public static function nuevoLibro(string $tituloHoja): array
    {
        $libro = new Spreadsheet();
        $libro->getProperties()->setCreator('Sistema Taller')->setTitle($tituloHoja);
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle(self::tituloHoja($tituloHoja));

        return [$libro, $hoja];
    }

    public static function hojaNueva(Spreadsheet $libro, string $titulo): Worksheet
    {
        $hoja = $libro->createSheet();
        $hoja->setTitle(self::tituloHoja($titulo));

        return $hoja;
    }

    private static function tituloHoja(string $titulo): string
    {
        $limpio = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $titulo);

        return mb_substr(trim($limpio), 0, 31);
    }

    public static function encabezadoDocumento(Worksheet $hoja, string $titulo, string $subtitulo, int $columnas): int
    {
        $ultima = Coordinate::stringFromColumnIndex(max(1, $columnas));

        $hoja->setCellValue('A1', $titulo);
        $hoja->mergeCells("A1:{$ultima}1");
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB(self::AZUL_OSCURO);
        $hoja->getRowDimension(1)->setRowHeight(24);

        $hoja->setCellValue('A2', $subtitulo);
        $hoja->mergeCells("A2:{$ultima}2");
        $hoja->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('62718A');

        return 4;
    }

    /**
     * Escribe una tabla completa: encabezado, filas con formato por columna,
     * filtros automáticos, fila congelada y anchos calculados.
     *
     * @param array<int,string> $encabezados
     * @param array<int,array<int,mixed>> $filas
     * @param array<int,string> $formatos clave de self::FORMATOS por columna (por defecto 'texto')
     * @return int fila siguiente a la última fila escrita
     */
    public static function tabla(Worksheet $hoja, int $filaEncabezado, array $encabezados, array $filas, array $formatos = [], bool $filtros = true): int
    {
        $columnas = count($encabezados);
        $ultima = Coordinate::stringFromColumnIndex($columnas);
        $anchos = [];

        foreach ($encabezados as $i => $texto) {
            $col = $i + 1;
            $hoja->setCellValue([$col, $filaEncabezado], $texto);
            $anchos[$col] = mb_strlen((string) $texto) + 3;
        }

        $rangoEnc = "A{$filaEncabezado}:{$ultima}{$filaEncabezado}";
        $estiloEnc = $hoja->getStyle($rangoEnc);
        $estiloEnc->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $estiloEnc->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::AZUL_OSCURO);
        $estiloEnc->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $hoja->getRowDimension($filaEncabezado)->setRowHeight(22);

        $fila = $filaEncabezado + 1;

        foreach ($filas as $datos) {
            foreach (array_values($datos) as $i => $valor) {
                $col = $i + 1;
                $formato = $formatos[$i] ?? 'texto';
                self::escribirCelda($hoja, $col, $fila, $valor, $formato);

                $largo = self::largoVisible($valor, $formato);
                if ($largo > ($anchos[$col] ?? 0)) {
                    $anchos[$col] = $largo;
                }
            }
            $fila++;
        }

        $ultimaFila = $fila - 1;

        foreach ($formatos as $i => $formato) {
            $letra = Coordinate::stringFromColumnIndex($i + 1);
            if ($ultimaFila > $filaEncabezado) {
                $rango = "{$letra}" . ($filaEncabezado + 1) . ":{$letra}{$ultimaFila}";
                $hoja->getStyle($rango)->getNumberFormat()->setFormatCode(self::FORMATOS[$formato] ?? '@');

                if (in_array($formato, ['entero', 'dinero', 'porcentaje'], true)) {
                    $hoja->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif (in_array($formato, ['fecha', 'fechahora'], true)) {
                    $hoja->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }

        if ($ultimaFila > $filaEncabezado) {
            $cuerpo = $hoja->getStyle("A" . ($filaEncabezado + 1) . ":{$ultima}{$ultimaFila}");
            $cuerpo->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E3E9F2');
            $cuerpo->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        foreach ($anchos as $col => $ancho) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(min(max($ancho + 2, 9), 55));
        }

        if ($filtros && $ultimaFila > $filaEncabezado) {
            $hoja->setAutoFilter("A{$filaEncabezado}:{$ultima}{$ultimaFila}");
        }

        $hoja->freezePane('A' . ($filaEncabezado + 1));
        $hoja->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
        $hoja->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($filaEncabezado, $filaEncabezado);
        $hoja->getSheetView()->setZoomScale(100);

        return $fila;
    }

    public static function filaTotales(Worksheet $hoja, int $fila, array $valores, array $formatos, int $columnas): void
    {
        $ultima = Coordinate::stringFromColumnIndex($columnas);

        foreach ($valores as $indice => $valor) {
            self::escribirCelda($hoja, $indice + 1, $fila, $valor, $formatos[$indice] ?? 'texto');
            $letra = Coordinate::stringFromColumnIndex($indice + 1);
            $hoja->getStyle("{$letra}{$fila}")->getNumberFormat()->setFormatCode(self::FORMATOS[$formatos[$indice] ?? 'texto'] ?? '@');
        }

        $estilo = $hoja->getStyle("A{$fila}:{$ultima}{$fila}");
        $estilo->getFont()->setBold(true)->getColor()->setRGB(self::AZUL_OSCURO);
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::AZUL_SUAVE);
        $estilo->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::AZUL_OSCURO);

        foreach ($formatos as $i => $formato) {
            if (in_array($formato, ['entero', 'dinero', 'porcentaje'], true)) {
                $hoja->getStyle(Coordinate::stringFromColumnIndex($i + 1) . $fila)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }
    }

    private static function escribirCelda(Worksheet $hoja, int $col, int $fila, mixed $valor, string $formato): void
    {
        if ($valor === null || $valor === '') {
            return;
        }

        if (in_array($formato, ['fecha', 'fechahora'], true)) {
            $fecha = $valor instanceof CarbonInterface ? $valor : (is_string($valor) ? \Carbon\Carbon::parse($valor) : null);

            if ($fecha) {
                $hoja->setCellValue([$col, $fila], Date::PHPToExcel($fecha));

                return;
            }
        }

        if (in_array($formato, ['entero', 'dinero', 'porcentaje'], true)) {
            $hoja->setCellValue([$col, $fila], (float) $valor);

            return;
        }

        $hoja->getCell([$col, $fila])->setValueExplicit((string) $valor, DataType::TYPE_STRING);
    }

    private static function largoVisible(mixed $valor, string $formato): int
    {
        if ($valor === null) {
            return 0;
        }

        if ($formato === 'dinero') {
            return mb_strlen(number_format((float) $valor, 0, ',', '.')) + 2;
        }

        if (in_array($formato, ['fecha', 'fechahora'], true)) {
            return $formato === 'fecha' ? 10 : 16;
        }

        return mb_strlen((string) $valor);
    }

    public static function descargar(Spreadsheet $libro, string $nombre): BinaryFileResponse
    {
        $libro->setActiveSheetIndex(0);

        $temporal = tempnam(sys_get_temp_dir(), 'st_xlsx_');
        $escritor = IOFactory::createWriter($libro, 'Xlsx');
        $escritor->setPreCalculateFormulas(false);
        $escritor->save($temporal);
        $libro->disconnectWorksheets();

        if (!app()->runningUnitTests()) {
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
        }

        $nombreSeguro = Str::ascii($nombre);

        return response()->download($temporal, $nombreSeguro, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ], ResponseHeaderBag::DISPOSITION_ATTACHMENT)->deleteFileAfterSend(true);
    }
}
