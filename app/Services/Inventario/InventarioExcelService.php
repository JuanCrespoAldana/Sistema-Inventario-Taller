<?php

namespace App\Services\Inventario;

use App\Models\Producto;
use App\Services\Excel\ExcelHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InventarioExcelService
{
    public const ENCABEZADOS = [
        'Código', 'Nombre', 'Modelo', 'Categoría', 'Ubicación', 'Proveedor',
        'Precio de compra', 'Precio de venta', 'Stock', 'Stock mínimo',
    ];

    private const FORMATOS = ['texto', 'texto', 'texto', 'texto', 'texto', 'texto', 'dinero', 'dinero', 'entero', 'entero'];

    public function plantilla(): Spreadsheet
    {
        ExcelHelper::preparar();

        [$libro, $hoja] = ExcelHelper::nuevoLibro('Productos');

        foreach (self::ENCABEZADOS as $i => $titulo) {
            $hoja->setCellValue([$i + 1, 1], $titulo);
        }

        $ultima = Coordinate::stringFromColumnIndex(count(self::ENCABEZADOS));
        $estilo = $hoja->getStyle("A1:{$ultima}1");
        $estilo->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ExcelHelper::AZUL_OSCURO);
        $estilo->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $hoja->getRowDimension(1)->setRowHeight(24);

        $anchos = [16, 38, 18, 18, 16, 22, 16, 16, 10, 14];
        foreach ($anchos as $i => $ancho) {
            $hoja->getColumnDimensionByColumn($i + 1)->setWidth($ancho);
        }

        $hoja->getStyle('A2:F1000')->getNumberFormat()->setFormatCode('@');
        $hoja->getStyle('G2:H1000')->getNumberFormat()->setFormatCode('#,##0');
        $hoja->getStyle('I2:J1000')->getNumberFormat()->setFormatCode('#,##0');
        $hoja->freezePane('A2');

        $guia = ExcelHelper::hojaNueva($libro, 'Instrucciones');
        $guia->getColumnDimension('A')->setWidth(22);
        $guia->getColumnDimension('B')->setWidth(14);
        $guia->getColumnDimension('C')->setWidth(88);

        $guia->setCellValue('A1', 'Cómo llenar la plantilla de productos');
        $guia->mergeCells('A1:C1');
        $guia->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB(ExcelHelper::AZUL_OSCURO);

        $guia->setCellValue('A2', 'Escriba un producto por fila en la hoja "Productos", empezando en la fila 2. No cambie los nombres de las columnas de la fila 1. Después cargue el archivo en Inventario › Importar / Exportar.');
        $guia->mergeCells('A2:C2');
        $guia->getStyle('A2')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $guia->getRowDimension(2)->setRowHeight(34);

        $reglas = [
            ['Columna', 'Obligatoria', 'Qué debe contener'],
            ['Código', 'Sí', 'Código único del producto (por ejemplo EL-1001). Si ya existe en el sistema, la fila actualiza ese producto; si no existe, lo crea.'],
            ['Nombre', 'Sí al crear', 'Nombre del producto. Si el producto ya existe y lo deja vacío, se conserva el nombre actual.'],
            ['Modelo', 'No', 'Referencia o modelo del fabricante.'],
            ['Categoría', 'No', 'Grupo del producto (por ejemplo Válvulas, Mangueras, Cilindros). Sirve para filtrar y para los reportes.'],
            ['Ubicación', 'No', 'Dónde está guardado (por ejemplo Estante A-2).'],
            ['Proveedor', 'No', 'A quién se le compra normalmente. Se usa en la lista de reposición.'],
            ['Precio de compra', 'Sí al crear', 'Costo por unidad, solo números (por ejemplo 85000 o 85.000).'],
            ['Precio de venta', 'Sí al crear', 'Precio de venta por unidad, solo números.'],
            ['Stock', 'No', 'Unidades que hay en este momento. Si lo deja vacío en un producto nuevo, queda en 0.'],
            ['Stock mínimo', 'No', 'Cantidad por debajo de la cual el producto aparece como "Stock bajo". Si lo deja vacío, se usa 2.'],
        ];

        $fila = 4;
        foreach ($reglas as $n => $regla) {
            foreach ($regla as $i => $valor) {
                $guia->setCellValue([$i + 1, $fila], $valor);
            }
            $guia->getStyle("C{$fila}")->getAlignment()->setWrapText(true);
            $guia->getStyle("A{$fila}:C{$fila}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            if ($n === 0) {
                $e = $guia->getStyle("A{$fila}:C{$fila}");
                $e->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $e->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ExcelHelper::AZUL_OSCURO);
            } else {
                $guia->getRowDimension($fila)->setRowHeight(32);
            }
            $fila++;
        }

        $fila++;
        $guia->setCellValue("A{$fila}", 'Ejemplo de cómo se ve una fila');
        $guia->getStyle("A{$fila}")->getFont()->setBold(true);
        $fila++;

        foreach (self::ENCABEZADOS as $i => $titulo) {
            $guia->setCellValue([$i + 1, $fila], $titulo);
        }
        $e = $guia->getStyle("A{$fila}:J{$fila}");
        $e->getFont()->setBold(true);
        $e->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ExcelHelper::AZUL_SUAVE);
        $fila++;

        $ejemplo = ['EL-1001', 'Electroválvula 24 V', 'VF-3100', 'Válvulas', 'Estante A-2', 'Distribuidora Andina', 85000, 130000, 12, 3];
        foreach ($ejemplo as $i => $valor) {
            $guia->setCellValueExplicit([$i + 1, $fila], $valor, is_int($valor) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
        }

        foreach (['D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
            if ($guia->getColumnDimension($col)->getWidth() < 0) {
                $guia->getColumnDimension($col)->setWidth(16);
            }
        }

        $fila += 2;
        $notas = [
            'Qué hace el sistema al importar',
            '• Antes de guardar nada, usted ve una vista previa con lo que se va a crear, actualizar y los errores encontrados.',
            '• Las filas con errores no se importan; las demás sí, y puede corregir las que faltan y volver a cargar el archivo.',
            '• Cada cambio de stock queda registrado en Movimientos con la referencia "Importación Excel".',
            '• Puede usar este mismo formato para hacer un conteo físico: exporte el inventario, corrija la columna Stock y cárguelo eligiendo "Dejar el stock igual al archivo".',
            '• Archivos admitidos: .xlsx, .xls y .csv (máximo 5 MB y 5.000 filas).',
        ];
        foreach ($notas as $n => $nota) {
            $guia->setCellValue("A{$fila}", $nota);
            $guia->mergeCells("A{$fila}:C{$fila}");
            if ($n === 0) {
                $guia->getStyle("A{$fila}")->getFont()->setBold(true);
            } else {
                $guia->getStyle("A{$fila}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $guia->getRowDimension($fila)->setRowHeight(30);
            }
            $fila++;
        }

        $libro->setActiveSheetIndex(0);

        return $libro;
    }

    /**
     * Inventario exportado con el mismo formato de la plantilla (más la columna Estado)
     * para poder corregirlo en Excel y volver a cargarlo.
     */
    public function productos(Collection $productos): Spreadsheet
    {
        ExcelHelper::preparar();

        [$libro, $hoja] = ExcelHelper::nuevoLibro('Productos');

        $filas = [];
        foreach ($productos as $p) {
            $filas[] = [
                $p->codigo, $p->nombre, $p->modelo ?? '', $p->categoria ?? '', $p->ubicacion ?? '', $p->proveedor ?? '',
                $p->precio_compra, $p->precio_venta, $p->stock, $p->stock_minimo,
                match ($p->estado_stock) { 'agotado' => 'Agotado', 'bajo' => 'Stock bajo', default => 'Normal' },
            ];
        }

        ExcelHelper::tabla($hoja, 1, [...self::ENCABEZADOS, 'Estado'], $filas, [...self::FORMATOS, 'texto']);

        return $libro;
    }

    public function listaCompra(Collection $filas): Spreadsheet
    {
        ExcelHelper::preparar();

        [$libro, $hoja] = ExcelHelper::nuevoLibro('Lista de compra');
        $fila = ExcelHelper::encabezadoDocumento(
            $hoja,
            'Lista de compra sugerida',
            'Productos agotados o por debajo del stock mínimo · Generado el ' . Carbon::now()->format('d/m/Y H:i'),
            10
        );

        $formatos = ['texto', 'texto', 'texto', 'texto', 'entero', 'entero', 'entero', 'entero', 'dinero', 'dinero'];
        $datos = [];
        foreach ($filas as $f) {
            $datos[] = [
                $f->codigo, $f->nombre, $f->proveedor ?: 'Sin proveedor', $f->categoria ?: '',
                $f->stock, $f->stock_minimo, $f->vendidos, $f->sugerido, $f->precio_compra, $f->costo_estimado,
            ];
        }

        $siguiente = ExcelHelper::tabla(
            $hoja,
            $fila,
            ['Código', 'Producto', 'Proveedor', 'Categoría', 'Stock', 'Mínimo', 'Vendidos (30 días)', 'Cantidad a comprar', 'Costo unitario', 'Costo estimado'],
            $datos,
            $formatos
        );

        ExcelHelper::filaTotales($hoja, $siguiente, [
            'TOTAL', count($datos) . ' productos', '', '', '', '', '', $filas->sum('sugerido'), '', $filas->sum('costo_estimado'),
        ], $formatos, 10);

        return $libro;
    }

    public function kardex(Producto $producto, Collection $movimientos): Spreadsheet
    {
        ExcelHelper::preparar();

        [$libro, $hoja] = ExcelHelper::nuevoLibro('Kardex');
        $fila = ExcelHelper::encabezadoDocumento(
            $hoja,
            'Kardex · ' . $producto->nombre,
            'Código ' . $producto->codigo . ' · Stock actual ' . $producto->stock . ' · Generado el ' . Carbon::now()->format('d/m/Y H:i'),
            7
        );

        $datos = [];
        foreach ($movimientos as $m) {
            $tipo = match ($m->tipo) { 'entrada' => 'Entrada', 'salida' => 'Salida', default => 'Ajuste' };
            $efecto = $m->tipo === 'salida' ? -abs((int) $m->cantidad) : (int) $m->cantidad;
            $datos[] = [$m->created_at, $tipo, $efecto, $m->saldo, $m->referencia ?? '', $m->usuario ?? ''];
        }

        ExcelHelper::tabla(
            $hoja,
            $fila,
            ['Fecha', 'Tipo', 'Cantidad', 'Saldo', 'Referencia', 'Usuario'],
            $datos,
            ['fechahora', 'texto', 'entero', 'entero', 'texto', 'texto']
        );

        return $libro;
    }
}
