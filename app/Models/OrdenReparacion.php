<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenReparacion extends Model
{
    protected $table = 'ordenes_reparacion';

    // La tabla NO tiene updated_at
    public $timestamps = false;

    protected $fillable = [
        'numero_orden',
        'cliente_id',
        'telefono',
        'fecha_entrada',
        'created_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function maquinas()
    {
        return $this->hasMany(OrdenMaquina::class, 'orden_id');
    }
}
