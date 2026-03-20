<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioInventario extends Model
{
    protected $table = 'usuarios_inventario';

    protected $fillable = [
        'nombre',
        'activo'
    ];

    public $timestamps = true;
}
