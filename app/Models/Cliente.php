<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    public $timestamps = false; // 👈 CLAVE, TU BD NO TIENE timestamps

    protected $fillable = [
        'nombre',
        'telefono',
    ];
}
