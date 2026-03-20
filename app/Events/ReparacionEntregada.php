<?php

namespace App\Events;

use App\Models\OrdenMaquina;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReparacionEntregada
{
    use Dispatchable, SerializesModels;

    public OrdenMaquina $maquina;

    public function __construct(OrdenMaquina $maquina)
    {
        $this->maquina = $maquina;
    }
}
