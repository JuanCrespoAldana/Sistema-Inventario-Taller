<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\Excel\ExcelHelper;
use App\Services\Inventario\InventarioExcelService;
use App\Services\Inventario\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InventarioController extends Controller
{
    public function productos()
    {
        $query = Producto::activos();

        if (request('buscar')) {
            $buscar = request('buscar');

            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('modelo', 'like', "%{$buscar}%");
            });
        }

        $this->aplicarFiltrosProducto($query);

        switch (request('orden')) {
            case 'nombre':
                $query->orderBy('nombre');
                break;
            case 'stock_asc':
                $query->orderBy('stock')->orderBy('nombre');
                break;
            case 'stock_desc':
                $query->orderByDesc('stock')->orderBy('nombre');
                break;
            case 'precio_desc':
                $query->orderByDesc('precio_venta')->orderBy('nombre');
                break;
            case 'reciente':
            default:
                $query->orderByDesc('created_at')->orderByDesc('id');
                break;
        }

        $productos = $query
            ->paginate(15)
            ->withQueryString();

        $stats = Producto::activos()
            ->selectRaw('
                COALESCE(SUM(stock * precio_compra), 0) as valor_compra,
                COALESCE(SUM(stock * precio_venta), 0) as valor_venta,
                COUNT(*) as total,
                COUNT(CASE WHEN stock <= 0 THEN 1 END) as criticos,
                COUNT(CASE WHEN stock > 0 AND stock <= stock_minimo THEN 1 END) as bajos
            ')
            ->first();

        $valorCompra = $stats->valor_compra;
        $valorVenta = $stats->valor_venta;
        $productosCriticos = (int) $stats->criticos;
        $productosBajos = (int) $stats->bajos;
        $totalProductos = (int) $stats->total;

        $sinRotacion = Producto::activos()->sinRotacion()->count();

        $categorias = Producto::activos()
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        $proveedores = Producto::activos()
            ->whereNotNull('proveedor')
            ->where('proveedor', '!=', '')
            ->distinct()
            ->orderBy('proveedor')
            ->pluck('proveedor');

        return view('inventario.index', compact(
            'productos',
            'valorCompra',
            'valorVenta',
            'productosCriticos',
            'productosBajos',
            'totalProductos',
            'categorias',
            'proveedores',
            'sinRotacion'
        ));
    }

    public function aplicarFiltrosProducto($query): void
    {
        $filtroStock = request('filtro_stock');

        if ($filtroStock === 'bajo') {
            $query->bajos();
        } elseif ($filtroStock === 'critico') {
            $query->agotados();
        } elseif ($filtroStock === 'disponible') {
            $query->disponibles();
        } elseif ($filtroStock === 'sin_rotacion') {
            $query->sinRotacion();
        }

        if (request()->filled('categoria')) {
            if (request('categoria') === '__sin') {
                $query->where(function ($q) {
                    $q->whereNull('categoria')->orWhere('categoria', '');
                });
            } else {
                $query->where('categoria', request('categoria'));
            }
        }

        if (request()->filled('proveedor')) {
            $query->where('proveedor', request('proveedor'));
        }

        if (request()->filled('ubicacion')) {
            $query->where('ubicacion', 'like', '%' . request('ubicacion') . '%');
        }

        $precioMin = preg_replace('/\D/', '', (string) request('precio_min'));
        if ($precioMin !== '') {
            $query->where('precio_venta', '>=', (int) $precioMin);
        }

        $precioMax = preg_replace('/\D/', '', (string) request('precio_max'));
        if ($precioMax !== '') {
            $query->where('precio_venta', '<=', (int) $precioMax);
        }
    }

    private function textoOpcional($valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    private function datosExtra(Request $request, int $minimoPorDefecto = 2): array
    {
        return [
            'categoria' => $this->textoOpcional($request->categoria),
            'ubicacion' => $this->textoOpcional($request->ubicacion),
            'proveedor' => $this->textoOpcional($request->proveedor),
            'stock_minimo' => $request->filled('stock_minimo') ? (int) $request->stock_minimo : $minimoPorDefecto,
        ];
    }

    public function kardex(Producto $producto)
    {
        $porPagina = 20;
        $pagina = max(1, (int) request('page', 1));

        $base = MovimientoInventario::where('producto_id', $producto->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $total = (clone $base)->count();

        $movimientos = (clone $base)
            ->skip(($pagina - 1) * $porPagina)
            ->take($porPagina)
            ->get();

        $efecto = fn($m) => $m->tipo === 'salida' ? -(int) $m->cantidad : (int) $m->cantidad;

        $saldo = (int) $producto->stock;
        if ($pagina > 1) {
            $saldo -= (clone $base)->take(($pagina - 1) * $porPagina)->get()->sum($efecto);
        }

        foreach ($movimientos as $m) {
            $m->saldo = $saldo;
            $saldo -= $efecto($m);
        }

        $esUltimaPagina = $pagina * $porPagina >= $total;
        $saldoInicial = $esUltimaPagina ? $saldo : null;

        $paginador = new \Illuminate\Pagination\LengthAwarePaginator(
            $movimientos,
            $total,
            $porPagina,
            $pagina,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $totales = MovimientoInventario::where('producto_id', $producto->id)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END), 0) as entradas,
                COALESCE(SUM(CASE WHEN tipo = 'salida' THEN cantidad ELSE 0 END), 0) as salidas
            ")
            ->first();

        return view('inventario.kardex', [
            'producto' => $producto,
            'movimientos' => $paginador,
            'totales' => $totales,
            'saldoInicial' => $saldoInicial,
        ]);
    }

    public function exportar(InventarioExcelService $excel)
    {
        try {
            $query = Producto::activos();

            if (request('buscar')) {
                $buscar = request('buscar');
                $query->where(function ($q) use ($buscar) {
                    $q->where('codigo', 'like', "%{$buscar}%")
                        ->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('modelo', 'like', "%{$buscar}%");
                });
            }

            $this->aplicarFiltrosProducto($query);

            $libro = $excel->productos($query->orderBy('nombre')->get());

            return ExcelHelper::descargar($libro, 'inventario_' . now()->format('Y-m-d') . '.xlsx');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No fue posible generar el archivo de Excel. Intenta nuevamente.');
        }
    }

    public function reposicionExportar(Request $request, InventarioExcelService $excel)
    {
        try {
            $filas = app(\App\Services\Inventario\ReposicionService::class)->sugerencias(
                $request->input('proveedor'),
                $request->input('categoria')
            );

            return ExcelHelper::descargar($excel->listaCompra($filas), 'lista_de_compra_' . now()->format('Y-m-d') . '.xlsx');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No fue posible generar la lista de compra. Intenta nuevamente.');
        }
    }

    public function kardexExportar(Producto $producto, InventarioExcelService $excel)
    {
        try {
            $movimientos = MovimientoInventario::where('producto_id', $producto->id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            $saldo = (int) $producto->stock;
            foreach ($movimientos as $m) {
                $m->saldo = $saldo;
                $saldo -= $m->tipo === 'salida' ? -(int) $m->cantidad : (int) $m->cantidad;
            }

            $nombre = 'kardex_' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $producto->codigo) . '.xlsx';

            return ExcelHelper::descargar($excel->kardex($producto, $movimientos), $nombre);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No fue posible generar el kardex. Intenta nuevamente.');
        }
    }

    public function reposicion(Request $request)
    {
        $filas = app(\App\Services\Inventario\ReposicionService::class)->sugerencias(
            $request->input('proveedor'),
            $request->input('categoria')
        );

        $proveedores = Producto::activos()->whereNotNull('proveedor')->where('proveedor', '!=', '')
            ->distinct()->orderBy('proveedor')->pluck('proveedor');
        $categorias = Producto::activos()->whereNotNull('categoria')->where('categoria', '!=', '')
            ->distinct()->orderBy('categoria')->pluck('categoria');

        $totalEstimado = $filas->sum('costo_estimado');

        return view('inventario.reposicion', compact('filas', 'proveedores', 'categorias', 'totalEstimado'));
    }

    public function movimientos()
    {
        $query = MovimientoInventario::with('producto');

        // 🔎 Filtro por código o nombre del producto
        if (request('buscar')) {

            $buscar = request('buscar');

            $query->whereHas('producto', function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        // Filtro por tipo
        if (in_array(request('tipo'), ['entrada', 'salida', 'ajuste'], true)) {
            $query->where('tipo', request('tipo'));
        }

        // Filtro por referencia (factura, compra, motivo del ajuste)
        if (request('referencia')) {
            $query->where('referencia', 'like', '%' . request('referencia') . '%');
        }

        // Filtro por rango de fechas
        $desde = request('desde');
        if (is_string($desde) && \Carbon\Carbon::hasFormat(trim($desde), 'Y-m-d')) {
            $query->where('created_at', '>=', trim($desde));
        }

        $hasta = request('hasta');
        if (is_string($hasta) && \Carbon\Carbon::hasFormat(trim($hasta), 'Y-m-d')) {
            $query->where('created_at', '<', \Carbon\Carbon::parse($hasta)->addDay()->toDateString());
        }

        $orden = request('orden') === 'antiguo' ? 'asc' : 'desc';

        $movimientos = $query->orderBy('created_at', $orden)
            ->orderBy('id', $orden)
            ->paginate(12)
            ->withQueryString();

        return view('inventario.movimientos', compact('movimientos'));
    }

    public function ajustes()
    {
        // Nota: antes aquí se cargaban TODOS los productos activos, pero la vista
        // inventario.ajustes no usa esa lista (los productos se buscan por AJAX).

        // Si venimos de un intento fallido (ej. faltó elegir el motivo),
        // recuperamos los productos que ya se habían seleccionado para
        // no hacerle perder al usuario el trabajo ya hecho.
        $productoIdsAnteriores = old('producto_id', []);
        $cantidadesAnteriores = old('cantidad', []);

        $productosSeleccionadosAnteriores = [];

        if (!empty($productoIdsAnteriores)) {
            $productosPorId = Producto::whereIn('id', array_filter($productoIdsAnteriores))
                ->get()
                ->keyBy('id');

            foreach ($productoIdsAnteriores as $index => $id) {
                $producto = $productosPorId->get($id);

                $productosSeleccionadosAnteriores[] = [
                    'id' => $id,
                    'codigo' => $producto->codigo ?? '',
                    'nombre' => $producto->nombre ?? '',
                    'stock' => $producto->stock ?? null,
                    'cantidad' => $cantidadesAnteriores[$index] ?? '',
                ];
            }
        }

        return view('inventario.ajustes', compact('productosSeleccionadosAnteriores'));
    }

    public function guardarAjuste(Request $request)
    {
        // El motivo permitido depende de si es una entrada o una salida de stock.
        $motivosPermitidos = $request->input('tipo_operacion') === 'restar'
            ? ['Venta', 'Uso interno', 'Ajuste']
            : ['Compra', 'Devolución', 'Ajuste'];

        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|array|min:1',
            'producto_id.*' => 'required|distinct|exists:productos,id',
            'cantidad' => 'required|array|min:1',
            'cantidad.*' => 'required|integer|min:1',
            'tipo_operacion' => 'required|in:sumar,restar',
            'referencia' => ['required', 'string', Rule::in($motivosPermitidos)],
        ], [
            'producto_id.required' => 'Debe seleccionar al menos un producto.',
            'producto_id.*.required' => 'Elige el producto de la lista de sugerencias antes de guardar (no basta con escribir el nombre).',
            'producto_id.*.distinct' => 'No puedes seleccionar el mismo producto en dos filas.',
            'producto_id.*.exists' => 'El producto seleccionado no es válido.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.*.required' => 'Indica la cantidad para cada producto seleccionado.',
            'cantidad.*.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.*.min' => 'La cantidad debe ser al menos 1.',
            'tipo_operacion.required' => 'Debe seleccionar el tipo de operación.',
            'referencia.required' => 'Debe seleccionar un motivo para el ajuste.',
            'referencia.in' => 'Ese motivo no es válido para la operación seleccionada.',
        ]);

        // Nos aseguramos de que cada producto tenga su cantidad correspondiente
        // (por si llegaran desalineados, para no saltear un producto en silencio).
        $validator->after(function ($validator) use ($request) {
            $productoIds = $request->input('producto_id', []);
            $cantidades = $request->input('cantidad', []);

            if (is_array($productoIds) && is_array($cantidades) && count($productoIds) !== count($cantidades)) {
                $validator->errors()->add('producto_id', 'Cada producto debe tener su cantidad indicada correctamente.');
            }
        });

        $validator->validate();

        $productoIds = $request->input('producto_id', []);
        $cantidades = $request->input('cantidad', []);

        try {
            DB::transaction(function () use ($productoIds, $cantidades, $request) {
                foreach ($productoIds as $index => $productoId) {
                    $cantidad = isset($cantidades[$index]) ? (int) $cantidades[$index] : 0;
                    if ($cantidad <= 0) {
                        continue;
                    }

                    if ($request->tipo_operacion == 'restar') {
                        app(InventarioService::class)->salida(
                            $productoId,
                            $cantidad,
                            $request->referencia,
                            null
                        );
                    } else {
                        app(InventarioService::class)->entrada(
                            $productoId,
                            $cantidad,
                            $request->referencia,
                            null
                        );
                    }
                }
            });

            return redirect()
                ->back()
                ->with('success', 'Ajuste realizado correctamente.');

        } catch (\RuntimeException $e) {
            // Mensajes de negocio ya redactados de forma segura por InventarioService
            // (ej. "Stock insuficiente para X"), sí se le pueden mostrar al usuario.
            return redirect()
                ->back()
                ->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', 'No fue posible realizar el ajuste. Intenta nuevamente.');
        }
    }

    public function buscarProducto(Request $request)
    {
        $productos = Producto::where('activo', 1)
            ->where(function ($query) use ($request) {
                $query->where('codigo', 'like', '%' . $request->q . '%')
                    ->orWhere('nombre', 'like', '%' . $request->q . '%');
            })
            ->orderBy('nombre')
            ->limit(10)
            ->get(['id', 'codigo', 'nombre', 'modelo', 'stock', 'stock_minimo', 'precio_venta']);

        return response()->json($productos);
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'codigo' => ['required', Rule::unique('productos', 'codigo')->ignore($producto->id)->where('activo', 1)],
            'nombre' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'categoria' => 'nullable|string|max:80',
            'ubicacion' => 'nullable|string|max:80',
            'proveedor' => 'nullable|string|max:120',
            'stock_minimo' => 'nullable|integer|min:0|max:100000',
            'precio_compra' => 'required',
            'precio_venta' => 'required',
        ], [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya está en uso por otro producto activo.',
            'nombre.required' => 'El nombre es obligatorio.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
        ]);

        // 🔥 LIMPIAR PUNTOS
        $precioCompra = str_replace('.', '', $request->precio_compra);
        $precioVenta = str_replace('.', '', $request->precio_venta);

        try {
            $producto->update([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'modelo' => $request->modelo,
                ...$this->datosExtra($request, (int) $producto->stock_minimo),
                'precio_compra' => $precioCompra,
                'precio_venta' => $precioVenta,
            ]);

            return redirect()
                ->route('inventario.index')
                ->with('success', 'Producto actualizado correctamente.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se pudo actualizar el producto. Por favor intente nuevamente.');
        }
    }

    public function destroy(Producto $producto)
    {
        $producto->update([
            'activo' => 0
        ]);

        return redirect()
            ->route('inventario.index')
            ->with('success', 'Producto eliminado correctamente.');
    }

    public function create()
    {
        $categorias = Producto::activos()->whereNotNull('categoria')->where('categoria', '!=', '')
            ->distinct()->orderBy('categoria')->pluck('categoria');
        $proveedores = Producto::activos()->whereNotNull('proveedor')->where('proveedor', '!=', '')
            ->distinct()->orderBy('proveedor')->pluck('proveedor');

        return view('inventario.create', compact('categorias', 'proveedores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => ['required', Rule::unique('productos', 'codigo')->where('activo', 1)],
            'nombre' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'categoria' => 'nullable|string|max:80',
            'ubicacion' => 'nullable|string|max:80',
            'proveedor' => 'nullable|string|max:120',
            'stock_minimo' => 'nullable|integer|min:0|max:100000',
            'precio_compra' => 'required',
            'precio_venta' => 'required',
            'stock' => 'nullable|integer|min:0'
        ], [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya está en uso por otro producto activo.',
            'nombre.required' => 'El nombre es obligatorio.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
        ]);

        try {
            $precioCompra = (int) str_replace('.', '', $request->precio_compra);
            $precioVenta = (int) str_replace('.', '', $request->precio_venta);

            DB::transaction(function () use ($request, $precioCompra, $precioVenta) {
                // Si existe un producto inactivo con el mismo código, lo reactivamos
                $existing = Producto::where('codigo', $request->codigo)->first();

                if ($existing && $existing->activo == 0) {
                $existing->update([
                    'nombre' => $request->nombre,
                    'modelo' => $request->modelo,
                    ...$this->datosExtra($request),
                    'precio_compra' => $precioCompra,
                    'precio_venta' => $precioVenta,
                    'activo' => 1,
                ]);

                // Ajustar stock si se proporcionó un stock inicial
                $stockSolicitado = (int) ($request->stock ?? 0);
                if ($stockSolicitado > $existing->stock) {
                    $diferencia = $stockSolicitado - $existing->stock;
                    app(InventarioService::class)->entrada(
                        $existing->id,
                        $diferencia,
                        'Reactivación - stock inicial',
                        null
                    );
                } elseif ($stockSolicitado < $existing->stock) {
                    $diferencia = $existing->stock - $stockSolicitado;
                    // Intentamos restar la diferencia vía salida (validará stock)
                    app(InventarioService::class)->salida(
                        $existing->id,
                        $diferencia,
                        'Reactivación - ajuste stock',
                        null
                    );
                }

                $producto = $existing;

            } else {
                $producto = Producto::create([
                    'codigo' => $request->codigo,
                    'nombre' => $request->nombre,
                    'modelo' => $request->modelo,
                    ...$this->datosExtra($request),
                    'precio_compra' => $precioCompra,
                    'precio_venta' => $precioVenta,
                    'stock' => 0,
                    'activo' => 1
                ]);

                if (($request->stock ?? 0) > 0) {
                    app(InventarioService::class)->entrada(
                        $producto->id,
                        (int) $request->stock,
                        'Stock inicial',
                        null
                    );
                }
            }
            });

            return redirect()
                ->route('inventario.index')
                ->with('success', 'Producto creado correctamente.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se pudo crear el producto. Por favor intente nuevamente.');
        }
    }
}
