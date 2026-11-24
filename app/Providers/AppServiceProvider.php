<?php

namespace App\Providers;

use App\Models\MovimientoInventario;
use App\Models\OrdenMaquina;
use App\Models\OrdenReparacion;
use App\Models\Producto;
use App\Models\Tecnico;
use App\Models\Venta;
use App\Services\Dashboard\DashboardService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        // El dashboard se guarda 30 s en caché. Cada vez que se crea, cambia o borra algo
        // que el dashboard muestra, se borra ese caché para que nunca se vean datos viejos.
        // DB::afterCommit espera a que termine la transacción (si hay una) antes de borrarlo.
        $limpiar = function () {
            DB::afterCommit(fn () => DashboardService::limpiarCache());
        };

        foreach ([Venta::class, OrdenMaquina::class, OrdenReparacion::class, Producto::class, MovimientoInventario::class, Tecnico::class] as $modelo) {
            $modelo::saved($limpiar);
            $modelo::deleted($limpiar);
        }
    }
}
