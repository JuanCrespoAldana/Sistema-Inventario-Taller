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
        'stock',
        'precio_compra',
        'precio_venta',
        'activo'
    ];

    /**
     * RELACIÓN:
     * Un producto tiene MUCHOS movimientos de inventario
     */
    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    /**
     * Estado visual del stock
     */
    public function getEstadoStockAttribute()
    {
        if ($this->stock <= 0) {
            return 'critico';
        } elseif ($this->stock <= 2) {
            return 'bajo';
        }

        return 'normal';
    }
}
