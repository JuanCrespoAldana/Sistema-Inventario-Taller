<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\Inventario\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventarioController extends Controller
{
    public function productos()
    {
        $query = Producto::where('activo', 1);

        // Filtro por búsqueda
        if (request('buscar')) {
            $buscar = request('buscar');

            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        // Filtro por stock bajo o crítico
        if (request('filtro_stock') === 'bajo') {
            $query->whereBetween('stock', [1, 2]);
        } elseif (request('filtro_stock') === 'critico') {
            $query->where('stock', 0);
        }

        $productos = $query->orderByDesc('created_at')
            ->paginate(8)
            ->withQueryString();

        // Mini KPIs (sin afectar el filtro): una sola consulta con agregación
        // SQL en vez de traer todos los productos a PHP para sumarlos ahí.
        $stats = Producto::where('activo', 1)
            ->selectRaw('
                COALESCE(SUM(stock * precio_compra), 0) as valor_compra,
                COALESCE(SUM(stock * precio_venta), 0) as valor_venta,
                COUNT(CASE WHEN stock = 0 THEN 1 END) as criticos,
                COUNT(CASE WHEN stock BETWEEN 1 AND 2 THEN 1 END) as bajos
            ')
            ->first();

        $valorCompra = $stats->valor_compra;
        $valorVenta = $stats->valor_venta;
        $productosCriticos = $stats->criticos;
        $productosBajos = $stats->bajos;

        return view('inventario.index', compact(
            'productos',
            'valorCompra',
            'valorVenta',
            'productosCriticos',
            'productosBajos'
        ));
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
        if (request('tipo')) {
            $query->where('tipo', request('tipo'));
        }

        $movimientos = $query->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('inventario.movimientos', compact('movimientos'));
    }


    public function ajustes()
    {
        $productos = Producto::where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return view('inventario.ajustes', compact('productos'));
    }

    public function guardarAjuste(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|array|min:1',
            'producto_id.*' => 'required|distinct|exists:productos,id',
            'cantidad' => 'required|array|min:1',
            'cantidad.*' => 'required|integer|min:1',
            'tipo_operacion' => 'required|in:sumar,restar',
            'referencia' => 'required|string|in:Venta,Compra,Devolución,Uso interno,Ajuste',
        ], [
            'producto_id.required' => 'Debe seleccionar al menos un producto.',
            'producto_id.*.exists' => 'El producto seleccionado no es válido.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.*.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.*.min' => 'La cantidad debe ser al menos 1.',
            'tipo_operacion.required' => 'Debe seleccionar el tipo de operación.',
            'referencia.required' => 'Debe seleccionar un motivo para el ajuste.',
        ]);

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
            ->get();

        return response()->json($productos);
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'codigo' => ['required', Rule::unique('productos', 'codigo')->ignore($producto->id)->where('activo', 1)],
            'nombre' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
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
        return view('inventario.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => ['required', Rule::unique('productos', 'codigo')->where('activo', 1)],
            'nombre' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
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
            // Si existe un producto inactivo con el mismo código, lo reactivamos
            $existing = Producto::where('codigo', $request->codigo)->first();

            if ($existing && $existing->activo == 0) {
                $existing->update([
                    'nombre' => $request->nombre,
                    'modelo' => $request->modelo,
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
