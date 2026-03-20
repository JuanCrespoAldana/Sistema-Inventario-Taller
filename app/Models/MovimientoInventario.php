<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'inventario_movimientos';

    public $timestamps = false; // Correcto en tu caso

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'referencia',
        'usuario',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}