<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    // 🔴 IMPORTANTE: tu tabla NO tiene created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'numero_factura',
        'fecha',
        'total',
        'descuento',
        'total_final',
    ];

    protected $casts = [
        'fecha' => 'datetime',     // mejor datetime que date
        'total' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total_final' => 'decimal:2',
    ];

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class, 'venta_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
