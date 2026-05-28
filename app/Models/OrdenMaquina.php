<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenMaquina extends Model
{
    protected $table = 'orden_maquinas';

    public $timestamps = false; // 👈 tu BD NO usa updated_at

    protected $fillable = [
        'orden_id',
        'etiqueta_maquina', // 🔥 ESTO ERA LO QUE FALTABA
        'descripcion',
        'cotizacion',
        'estado',
        'tecnico_id',
        'es_garantia',
        'tipo_garantia',
        'factura',
        'mano_obra',
        'total',
        'fecha_salida',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'fecha_entrada' => 'date',
        'fecha_salida' => 'datetime',
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenReparacion::class, 'orden_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }
}