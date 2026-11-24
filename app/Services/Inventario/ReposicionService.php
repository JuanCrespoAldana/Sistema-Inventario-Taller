<?php

namespace App\Services\Inventario;

use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReposicionService
{
    /**
     * Productos activos que están agotados o por debajo de su stock mínimo,
     * con una cantidad sugerida a comprar según lo vendido en los últimos días.
     *
     * Cantidad sugerida = lo mayor entre:
     *  - llegar al doble del stock mínimo
     *  - cubrir lo que se vendió en el período menos lo que queda
     * (siempre al menos 1 unidad).
     */
    public function sugerencias(?string $proveedor = null, ?string $categoria = null, int $dias = 30): Collection
    {
        $desde = now()->subDays($dias)->startOfDay();

        $vendidos = DB::table('venta_detalle')
            ->join('ventas', 'ventas.id', '=', 'venta_detalle.venta_id')
            ->where('ventas.fecha', '>=', $desde)
            ->groupBy('venta_detalle.producto_id')
            ->selectRaw('venta_detalle.producto_id, SUM(venta_detalle.cantidad) as vendidos')
            ->pluck('vendidos', 'producto_id');

        $query = Producto::activos()
            ->where(function ($q) {
                $q->where('stock', '<=', 0)
                    ->orWhereColumn('stock', '<=', 'stock_minimo');
            })
            ->orderBy('proveedor')
            ->orderBy('nombre');

        if ($proveedor !== null && $proveedor !== '') {
            $proveedor === '__sin'
                ? $query->where(fn($q) => $q->whereNull('proveedor')->orWhere('proveedor', ''))
                : $query->where('proveedor', $proveedor);
        }

        if ($categoria !== null && $categoria !== '') {
            $categoria === '__sin'
                ? $query->where(fn($q) => $q->whereNull('categoria')->orWhere('categoria', ''))
                : $query->where('categoria', $categoria);
        }

        return $query->get()->map(function (Producto $p) use ($vendidos) {
            $vendidosPeriodo = (int) ($vendidos[$p->id] ?? 0);
            $stock = max(0, (int) $p->stock);
            $minimo = (int) $p->stock_minimo;

            $sugerido = max(1, ($minimo * 2) - $stock, $vendidosPeriodo - $stock);

            return (object) [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'modelo' => $p->modelo,
                'categoria' => $p->categoria,
                'ubicacion' => $p->ubicacion,
                'proveedor' => $p->proveedor,
                'stock' => (int) $p->stock,
                'stock_minimo' => $minimo,
                'estado' => $p->estado_stock,
                'vendidos' => $vendidosPeriodo,
                'sugerido' => $sugerido,
                'precio_compra' => (float) $p->precio_compra,
                'costo_estimado' => $sugerido * (float) $p->precio_compra,
            ];
        })->values();
    }
}
