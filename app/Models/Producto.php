<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'modelo',
        'categoria',
        'ubicacion',
        'proveedor',
        'stock',
        'stock_minimo',
        'precio_compra',
        'precio_venta',
        'activo'
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', 1);
    }

    public function scopeAgotados($query)
    {
        return $query->where('stock', '<=', 0);
    }

    public function scopeBajos($query)
    {
        return $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'stock_minimo');
    }

    public function scopeDisponibles($query)
    {
        return $query->where('stock', '>', 0)->whereColumn('stock', '>', 'stock_minimo');
    }

    /**
     * Productos con existencias que no se han vendido en los últimos $dias días
     * (y que ya llevan al menos ese tiempo registrados).
     */
    public function scopeSinRotacion($query, int $dias = 90)
    {
        $desde = now()->subDays($dias);

        return $query->where('stock', '>', 0)
            ->where(function ($q) use ($desde) {
                $q->whereNull('created_at')->orWhere('created_at', '<', $desde);
            })
            ->whereNotExists(function ($sub) use ($desde) {
                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('venta_detalle')
                    ->join('ventas', 'ventas.id', '=', 'venta_detalle.venta_id')
                    ->whereColumn('venta_detalle.producto_id', 'productos.id')
                    ->where('ventas.fecha', '>=', $desde->toDateString());
            });
    }

    public function getEstadoStockAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'agotado';
        }

        return $this->stock <= (int) $this->stock_minimo ? 'bajo' : 'normal';
    }
}
