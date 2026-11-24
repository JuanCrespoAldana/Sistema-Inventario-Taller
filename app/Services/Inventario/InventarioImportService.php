<?php

namespace App\Services\Inventario;

use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InventarioImportService
{
    public const MAX_FILAS = 5000;

    private const ALIAS = [
        'codigo' => ['codigo', 'cod', 'codigodelproducto', 'codigoproducto', 'sku', 'referenciainterna'],
        'nombre' => ['nombre', 'producto', 'nombredelproducto', 'descripcion', 'articulo', 'item'],
        'modelo' => ['modelo', 'referencia', 'ref', 'marca'],
        'categoria' => ['categoria', 'grupo', 'linea', 'familia', 'tipo'],
        'ubicacion' => ['ubicacion', 'estante', 'bodega', 'ubicacionenbodega', 'lugar'],
        'proveedor' => ['proveedor', 'proveedorhabitual', 'distribuidor'],
        'precio_compra' => ['preciodecompra', 'preciocompra', 'costo', 'costounitario', 'compra', 'valorcompra'],
        'precio_venta' => ['preciodeventa', 'precioventa', 'precio', 'venta', 'pvp', 'valorventa'],
        'stock' => ['stock', 'existencias', 'existencia', 'cantidad', 'inventario', 'unidades', 'stockactual'],
        'stock_minimo' => ['stockminimo', 'minimo', 'stockmin', 'minimodestock', 'stockdeseguridad'],
    ];

    private const ETIQUETAS = [
        'nombre' => 'Nombre', 'modelo' => 'Modelo', 'categoria' => 'Categoría', 'ubicacion' => 'Ubicación',
        'proveedor' => 'Proveedor', 'precio_compra' => 'Precio de compra', 'precio_venta' => 'Precio de venta',
        'stock_minimo' => 'Stock mínimo',
    ];

    /**
     * Lee el archivo y devuelve, fila por fila, qué pasaría al importarlo (sin guardar nada).
     *
     * @param array{stock?: string, actualizar_datos?: bool} $opciones
     */
    public function analizar(string $ruta, array $opciones = []): array
    {
        $modoStock = $opciones['stock'] ?? 'sumar';
        $actualizarDatos = (bool) ($opciones['actualizar_datos'] ?? true);

        $matriz = $this->leerArchivo($ruta);
        [$filaEncabezado, $mapa] = $this->detectarEncabezado($matriz);

        $existentes = Producto::query()->get()->keyBy(fn($p) => mb_strtolower(trim($p->codigo)));

        $filas = [];
        $vistos = [];
        $resumen = ['crear' => 0, 'actualizar' => 0, 'sin_cambios' => 0, 'errores' => 0, 'total' => 0];

        for ($i = $filaEncabezado + 1; $i < count($matriz); $i++) {
            $celdas = $matriz[$i];
            $fila = $i + 1;

            if ($this->filaVacia($celdas)) {
                continue;
            }

            if ($resumen['total'] >= self::MAX_FILAS) {
                throw new \RuntimeException('El archivo tiene más de ' . number_format(self::MAX_FILAS, 0, ',', '.') . ' filas con datos. Divídalo en varios archivos e impórtelos por separado.');
            }

            $resumen['total']++;
            $registro = $this->analizarFila($fila, $celdas, $mapa, $existentes, $vistos, $modoStock, $actualizarDatos);

            match ($registro['accion']) {
                'crear' => $resumen['crear']++,
                'actualizar' => $resumen['actualizar']++,
                'sin_cambios' => $resumen['sin_cambios']++,
                default => $resumen['errores']++,
            };

            $filas[] = $registro;
        }

        if ($resumen['total'] === 0) {
            throw new \RuntimeException('El archivo no tiene productos para importar. Llene la plantilla a partir de la fila 2 e intente de nuevo.');
        }

        $detectadas = array_keys($mapa);
        $ignoradas = [];
        foreach ($matriz[$filaEncabezado] as $col => $texto) {
            if (trim((string) $texto) !== '' && !in_array($col, $mapa, true) && $this->normalizar((string) $texto) !== 'estado') {
                $ignoradas[] = trim((string) $texto);
            }
        }

        return [
            'filas' => $filas,
            'resumen' => $resumen,
            'columnas' => $detectadas,
            'ignoradas' => $ignoradas,
            'opciones' => ['stock' => $modoStock, 'actualizar_datos' => $actualizarDatos],
        ];
    }

    /**
     * Guarda en la base de datos las filas válidas (todo o nada).
     *
     * @return array{creados: int, actualizados: int, movimientos: int, omitidos: int}
     */
    public function aplicar(array $analisis): array
    {
        $inventario = app(InventarioService::class);
        $resultado = ['creados' => 0, 'actualizados' => 0, 'movimientos' => 0, 'omitidos' => 0];

        DB::transaction(function () use ($analisis, $inventario, &$resultado) {
            foreach ($analisis['filas'] as $f) {
                if (!in_array($f['accion'], ['crear', 'actualizar'], true)) {
                    $resultado['omitidos']++;
                    continue;
                }

                $d = $f['datos'];

                if ($f['accion'] === 'crear') {
                    $producto = Producto::create([
                        'codigo' => $d['codigo'],
                        'nombre' => $d['nombre'],
                        'modelo' => $d['modelo'],
                        'categoria' => $d['categoria'],
                        'ubicacion' => $d['ubicacion'],
                        'proveedor' => $d['proveedor'],
                        'precio_compra' => $d['precio_compra'],
                        'precio_venta' => $d['precio_venta'],
                        'stock_minimo' => $d['stock_minimo'],
                        'stock' => 0,
                        'activo' => 1,
                    ]);

                    if ($d['stock_delta'] > 0) {
                        $inventario->entrada($producto->id, $d['stock_delta'], 'Stock inicial (importación Excel)', null);
                        $resultado['movimientos']++;
                    }

                    $resultado['creados']++;
                    continue;
                }

                $producto = Producto::findOrFail($f['producto_id']);
                $cambios = $d['campos'];

                if ($d['reactivar']) {
                    $cambios['activo'] = 1;
                }

                if ($cambios) {
                    $producto->update($cambios);
                }

                if ($d['stock_delta'] !== 0) {
                    if ($d['stock_modo'] === 'fijar') {
                        $inventario->ajuste($producto->id, $d['stock_delta'], 'Conteo físico (importación Excel)', null);
                    } else {
                        $inventario->entrada($producto->id, $d['stock_delta'], 'Importación Excel', null);
                    }
                    $resultado['movimientos']++;
                }

                $resultado['actualizados']++;
            }
        });

        return $resultado;
    }

    private function analizarFila(int $fila, array $celdas, array $mapa, $existentes, array &$vistos, string $modoStock, bool $actualizarDatos): array
    {
        $errores = [];
        $advertencias = [];
        $cambios = [];

        $valor = fn(string $campo) => isset($mapa[$campo]) ? ($celdas[$mapa[$campo]] ?? null) : null;
        $texto = fn(string $campo) => $this->texto($valor($campo));

        $codigo = $this->codigo($valor('codigo'));
        $nombre = $texto('nombre');

        $registro = [
            'fila' => $fila,
            'codigo' => $codigo,
            'nombre' => $nombre,
            'accion' => 'error',
            'cambios' => [],
            'errores' => [],
            'advertencias' => [],
            'stock_antes' => null,
            'stock_despues' => null,
            'producto_id' => null,
            'datos' => null,
        ];

        if ($codigo === '') {
            $registro['errores'][] = 'Falta el código.';
            return $registro;
        }

        if (mb_strlen($codigo) > 50) {
            $errores[] = 'El código supera los 50 caracteres.';
        }

        $clave = mb_strtolower($codigo);
        if (isset($vistos[$clave])) {
            $errores[] = "El código ya aparece en la fila {$vistos[$clave]} de este archivo.";
        } else {
            $vistos[$clave] = $fila;
        }

        $limites = ['nombre' => 150, 'modelo' => 100, 'categoria' => 80, 'ubicacion' => 80, 'proveedor' => 120];
        foreach ($limites as $campo => $max) {
            if (mb_strlen($texto($campo)) > $max) {
                $errores[] = self::ETIQUETAS[$campo] . " supera los {$max} caracteres.";
            }
        }

        [$compra, $errCompra] = $this->numero($valor('precio_compra'), 'El precio de compra');
        [$venta, $errVenta] = $this->numero($valor('precio_venta'), 'El precio de venta');
        [$stock, $errStock] = $this->entero($valor('stock'), 'El stock');
        [$minimo, $errMinimo] = $this->entero($valor('stock_minimo'), 'El stock mínimo');

        foreach ([$errCompra, $errVenta, $errStock, $errMinimo] as $e) {
            if ($e) {
                $errores[] = $e;
            }
        }

        $producto = $existentes->get($clave);
        $registro['producto_id'] = $producto?->id;

        if (!$producto) {
            if ($nombre === '') {
                $errores[] = 'Falta el nombre (es obligatorio para productos nuevos).';
            }
            if ($compra === null && !$errCompra) {
                $errores[] = 'Falta el precio de compra (es obligatorio para productos nuevos).';
            }
            if ($venta === null && !$errVenta) {
                $errores[] = 'Falta el precio de venta (es obligatorio para productos nuevos).';
            }

            if ($errores) {
                $registro['errores'] = $errores;
                return $registro;
            }

            if ($venta < $compra) {
                $advertencias[] = 'El precio de venta es menor que el de compra.';
            }

            $stockInicial = $stock ?? 0;

            $registro['accion'] = 'crear';
            $registro['cambios'] = ['Producto nuevo' . ($stockInicial > 0 ? " con stock {$stockInicial}" : '')];
            $registro['stock_antes'] = 0;
            $registro['stock_despues'] = $stockInicial;
            $registro['advertencias'] = $advertencias;
            $registro['datos'] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'modelo' => $texto('modelo') ?: null,
                'categoria' => $texto('categoria') ?: null,
                'ubicacion' => $texto('ubicacion') ?: null,
                'proveedor' => $texto('proveedor') ?: null,
                'precio_compra' => $compra,
                'precio_venta' => $venta,
                'stock_minimo' => $minimo ?? 2,
                'stock_delta' => $stockInicial,
                'stock_modo' => 'sumar',
                'campos' => [],
                'reactivar' => false,
            ];

            return $registro;
        }

        if ($errores) {
            $registro['errores'] = $errores;
            return $registro;
        }

        $reactivar = (int) $producto->activo === 0;
        $aplicaDatos = $actualizarDatos || $reactivar;
        $campos = [];

        if ($reactivar) {
            $cambios[] = 'Se reactivará (estaba eliminado)';
        }

        if ($aplicaDatos) {
            foreach (['nombre', 'modelo', 'categoria', 'ubicacion', 'proveedor'] as $campo) {
                $nuevo = $texto($campo);
                if ($nuevo !== '' && $nuevo !== (string) $producto->{$campo}) {
                    $campos[$campo] = $nuevo;
                    $cambios[] = self::ETIQUETAS[$campo] . ': ' . ($producto->{$campo} ?: '—') . ' → ' . $nuevo;
                }
            }

            foreach (['precio_compra' => $compra, 'precio_venta' => $venta] as $campo => $nuevo) {
                if ($nuevo !== null && $nuevo !== (int) round((float) $producto->{$campo})) {
                    $campos[$campo] = $nuevo;
                    $cambios[] = self::ETIQUETAS[$campo] . ': ' . $this->dinero((float) $producto->{$campo}) . ' → ' . $this->dinero($nuevo);
                }
            }

            if ($minimo !== null && $minimo !== (int) $producto->stock_minimo) {
                $campos['stock_minimo'] = $minimo;
                $cambios[] = "Stock mínimo: {$producto->stock_minimo} → {$minimo}";
            }
        }

        $antes = (int) $producto->stock;
        $delta = 0;

        if ($stock !== null && $modoStock !== 'ignorar') {
            $delta = $modoStock === 'fijar' ? $stock - $antes : $stock;
        }

        $despues = $antes + $delta;

        if ($delta !== 0) {
            $cambios[] = "Stock: {$antes} → {$despues}";
        }

        $precioCompraFinal = $campos['precio_compra'] ?? (int) round((float) $producto->precio_compra);
        $precioVentaFinal = $campos['precio_venta'] ?? (int) round((float) $producto->precio_venta);
        if ($precioVentaFinal < $precioCompraFinal && (isset($campos['precio_compra']) || isset($campos['precio_venta']))) {
            $advertencias[] = 'El precio de venta quedaría menor que el de compra.';
        }

        $registro['producto_id'] = $producto->id;
        $registro['nombre'] = $nombre !== '' ? $nombre : $producto->nombre;
        $registro['stock_antes'] = $antes;
        $registro['stock_despues'] = $despues;
        $registro['advertencias'] = $advertencias;
        $registro['cambios'] = $cambios;

        if (!$cambios) {
            $registro['accion'] = 'sin_cambios';
            return $registro;
        }

        $registro['accion'] = 'actualizar';
        $registro['datos'] = [
            'codigo' => $codigo,
            'campos' => $campos,
            'stock_delta' => $delta,
            'stock_modo' => $modoStock,
            'reactivar' => $reactivar,
        ];

        return $registro;
    }

    private function leerArchivo(string $ruta): array
    {
        try {
            $lector = IOFactory::createReaderForFile($ruta);
        } catch (\Throwable $e) {
            throw new \RuntimeException('No se pudo reconocer el archivo. Use el formato .xlsx, .xls o .csv.');
        }

        try {
            if ($lector instanceof Csv) {
                $lector->setInputEncoding(Csv::GUESS_ENCODING);
                $lector->setDelimiter($this->detectarSeparador($ruta));
            }

            if (method_exists($lector, 'setReadDataOnly')) {
                $lector->setReadDataOnly(true);
            }

            $libro = $lector->load($ruta);
        } catch (\Throwable $e) {
            throw new \RuntimeException('No se pudo leer el archivo. Verifique que no esté dañado ni protegido con contraseña.');
        }

        $hoja = $libro->getSheetByName('Productos') ?? $libro->getSheet(0);
        $matriz = $hoja->toArray(null, true, false, false);
        $libro->disconnectWorksheets();

        return array_values($matriz);
    }

    private function detectarSeparador(string $ruta): string
    {
        $primera = (string) fgets(fopen($ruta, 'r'), 4096);
        $conteo = [';' => substr_count($primera, ';'), ',' => substr_count($primera, ','), "\t" => substr_count($primera, "\t")];
        arsort($conteo);

        return array_key_first($conteo) ?: ',';
    }

    private function detectarEncabezado(array $matriz): array
    {
        $limite = min(15, count($matriz));

        for ($i = 0; $i < $limite; $i++) {
            $mapa = [];
            foreach ($matriz[$i] as $col => $celda) {
                $clave = $this->normalizar((string) $celda);
                if ($clave === '') {
                    continue;
                }
                foreach (self::ALIAS as $campo => $alias) {
                    if (!isset($mapa[$campo]) && in_array($clave, $alias, true)) {
                        $mapa[$campo] = $col;
                        break;
                    }
                }
            }

            if (isset($mapa['codigo'])) {
                return [$i, $mapa];
            }
        }

        throw new \RuntimeException('No encontramos la columna "Código" en las primeras filas del archivo. Descargue la plantilla y use sus encabezados sin modificarlos.');
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return preg_replace('/[^a-z0-9]/', '', $texto);
    }

    private function filaVacia(array $celdas): bool
    {
        foreach ($celdas as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }

    private function texto(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        if (is_float($valor) && floor($valor) === $valor) {
            $valor = (int) $valor;
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $valor));
    }

    private function codigo(mixed $valor): string
    {
        return $this->texto($valor);
    }

    /**
     * @return array{0: ?int, 1: ?string} [valor entero en pesos o null si está vacío, mensaje de error]
     */
    private function numero(mixed $valor, string $etiqueta): array
    {
        $decimal = $this->aDecimal($valor);

        if ($decimal === false) {
            return [null, "{$etiqueta} no es un número válido."];
        }

        if ($decimal === null) {
            return [null, null];
        }

        if ($decimal < 0) {
            return [null, "{$etiqueta} no puede ser negativo."];
        }

        if ($decimal > 99999999) {
            return [null, "{$etiqueta} es demasiado grande."];
        }

        return [(int) round($decimal), null];
    }

    private function entero(mixed $valor, string $etiqueta): array
    {
        $decimal = $this->aDecimal($valor);

        if ($decimal === false) {
            return [null, "{$etiqueta} no es un número válido."];
        }

        if ($decimal === null) {
            return [null, null];
        }

        if ($decimal < 0) {
            return [null, "{$etiqueta} no puede ser negativo."];
        }

        if (floor($decimal) !== (float) $decimal) {
            return [null, "{$etiqueta} debe ser un número entero."];
        }

        if ($decimal > 100000000) {
            return [null, "{$etiqueta} es demasiado grande."];
        }

        return [(int) $decimal, null];
    }

    /**
     * Convierte "85.000", "$ 85.000,50", "1,250" o 85000 en número.
     * Devuelve null si está vacío y false si no se puede interpretar.
     */
    private function aDecimal(mixed $valor): float|false|null
    {
        if ($valor === null) {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }

        $texto = preg_replace('/[\s$]|cop/iu', '', $texto);

        if (!preg_match('/^-?[\d.,]+$/', $texto)) {
            return false;
        }

        $tienePunto = str_contains($texto, '.');
        $tieneComa = str_contains($texto, ',');

        if ($tienePunto && $tieneComa) {
            $decimalEsComa = strrpos($texto, ',') > strrpos($texto, '.');
            $texto = $decimalEsComa
                ? str_replace(',', '.', str_replace('.', '', $texto))
                : str_replace(',', '', $texto);
        } elseif ($tienePunto) {
            if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $texto)) {
                $texto = str_replace('.', '', $texto);
            } elseif (substr_count($texto, '.') > 1) {
                return false;
            }
        } elseif ($tieneComa) {
            if (preg_match('/^-?\d{1,3}(,\d{3})+$/', $texto)) {
                $texto = str_replace(',', '', $texto);
            } elseif (substr_count($texto, ',') === 1) {
                $texto = str_replace(',', '.', $texto);
            } else {
                return false;
            }
        }

        return is_numeric($texto) ? (float) $texto : false;
    }

    private function dinero(float|int $valor): string
    {
        return '$' . number_format($valor, 0, ',', '.');
    }
}
