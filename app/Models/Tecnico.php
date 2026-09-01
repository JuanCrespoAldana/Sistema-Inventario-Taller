<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    protected $table = 'tecnicos';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    // Laravel usará created_at pero no updated_at
    const UPDATED_AT = null;
}
