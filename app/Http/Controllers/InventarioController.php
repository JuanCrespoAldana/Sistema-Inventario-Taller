<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $productos = $query->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        // Mini KPIs (sin afectar el filtro)
        $productosAll = Producto::where('activo', 1)->get();

        $valorCompra = $productosAll->sum(fn($p) => $p->stock * $p->precio_compra);
        $valorVenta = $productosAll->sum(fn($p) => $p->stock * $p->precio_venta);

        $productosCriticos = $productosAll->where('stock', 0)->count();
        $productosBajos = $productosAll->whereBetween('stock', [1, 2])->count();

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
            'producto_id' => 'required|exists:productos,id',
            'tipo_operacion' => 'required|in:sumar,restar',
            'cantidad' => 'required|integer|min:1',
            'referencia' => 'required|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request) {

                $producto = Producto::findOrFail($request->producto_id);

                $cantidad = $request->cantidad;

                if ($request->tipo_operacion == 'restar') {

                    if ($producto->stock < $cantidad) {
                        throw new \Exception("Stock insuficiente. Stock actual: {$producto->stock} unidades.");
                    }

                    $producto->decrement('stock', $cantidad);

                    $tipoMovimiento = 'salida';

                } else {

                    $producto->increment('stock', $cantidad);

                    $tipoMovimiento = 'entrada';
                }

                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'tipo' => $tipoMovimiento,
                    'cantidad' => $cantidad,
                    'referencia' => $request->referencia,
                ]);
            });

            return redirect()
                ->back()
                ->with('success', 'Ajuste realizado correctamente.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function buscarProducto(Request $request)
    {
        $productos = Producto::where('codigo', 'like', '%' . $request->q . '%')
            ->orWhere('nombre', 'like', '%' . $request->q . '%')
            ->limit(10)
            ->get();

        return response()->json($productos);
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'codigo' => 'required|unique:productos,codigo,' . $producto->id,
            'nombre' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'precio_compra' => 'required',
            'precio_venta' => 'required',
        ]);

        // 🔥 LIMPIAR PUNTOS
        $precioCompra = str_replace('.', '', $request->precio_compra);
        $precioVenta = str_replace('.', '', $request->precio_venta);

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
            'codigo' => 'required|unique:productos,codigo',
            'nombre' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'precio_compra' => 'required',
            'precio_venta' => 'required',
            'stock' => 'nullable|integer|min:0'
        ]);

        $precioCompra = (int) str_replace('.', '', $request->precio_compra);
        $precioVenta = (int) str_replace('.', '', $request->precio_venta);

        $producto = Producto::create([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'modelo' => $request->modelo,
            'precio_compra' => $precioCompra,
            'precio_venta' => $precioVenta,
            'stock' => $request->stock ?? 0,
            'activo' => 1
        ]);

        return redirect()
            ->route('inventario.index')
            ->with('success', 'Producto creado correctamente.');
    }
}
