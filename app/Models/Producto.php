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
}
