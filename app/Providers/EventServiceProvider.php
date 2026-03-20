<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\VentaRegistrada;
use App\Events\ReparacionEntregada;
use App\Listeners\ActualizarMetricasVenta;
use App\Listeners\ActualizarMetricasReparacion;
use App\Listeners\RegistrarIngresoVenta;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        VentaRegistrada::class => [
            ActualizarMetricasVenta::class,
            RegistrarIngresoVenta::class,
        ],
        ReparacionEntregada::class => [
            ActualizarMetricasReparacion::class,
        ],
    ];
}
