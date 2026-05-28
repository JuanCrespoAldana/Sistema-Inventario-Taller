<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ReparacionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ParametroController;

/*
|--------------------------------------------------------------------------
| DASHBOARD (NÚCLEO DEL SISTEMA)
|--------------------------------------------------------------------------
*/
Route::get('/', [DashboardController::class, 'index'])
    ->name('dashboard');


/*
|--------------------------------------------------------------------------
| VENTAS
|--------------------------------------------------------------------------
*/
Route::prefix('ventas')->name('ventas.')->group(function () {

    Route::get('/', [VentaController::class, 'index'])
        ->name('index');

    Route::get('/create', [VentaController::class, 'create'])
        ->name('create');

    Route::post('/', [VentaController::class, 'store'])
        ->name('store');

    Route::get('/{venta}', [VentaController::class, 'show'])
        ->name('show');

});


/*
|--------------------------------------------------------------------------
| REPARACIONES
|--------------------------------------------------------------------------
*/
Route::prefix('reparaciones')->name('reparaciones.')->group(function () {

    Route::get('/', [ReparacionController::class, 'index'])
        ->name('index');

    Route::get('/create', [ReparacionController::class, 'create'])
        ->name('create');

    Route::post('/', [ReparacionController::class, 'store'])
        ->name('store');

    Route::get('/{orden}', [ReparacionController::class, 'show'])
        ->name('show');

    Route::get('/{orden}/edit', [ReparacionController::class, 'edit'])
        ->name('edit');

    Route::put('/{orden}', [ReparacionController::class, 'update'])
        ->name('update');

    Route::delete('/{orden}', [ReparacionController::class, 'destroy'])
        ->name('destroy');

    Route::delete(
        '/{orden}/maquinas/{maquina}',
        [ReparacionController::class, 'eliminarMaquina']
    )->name('maquinas.destroy');

});


/*
|--------------------------------------------------------------------------
| INVENTARIO
|--------------------------------------------------------------------------
*/
Route::prefix('inventario')->name('inventario.')->group(function () {

    Route::get('/', [InventarioController::class, 'productos'])
        ->name('index');

    Route::get(
        '/productos/create',
        [InventarioController::class, 'create']  
    )->name('productos.create');

    Route::post(
        '/productos',
        [InventarioController::class, 'store']    
    )->name('productos.store');

    Route::put(
        '/productos/{producto}',
        [InventarioController::class, 'update']
    )->name('productos.update');

    Route::delete(
        '/productos/{producto}',
        [InventarioController::class, 'destroy']
    )->name('productos.destroy');

    Route::get(
        '/movimientos',
        [InventarioController::class, 'movimientos']
    )->name('movimientos.index');

    Route::get(
        '/ajustes',
        [InventarioController::class, 'ajustes']
    )->name('ajustes.index');

    Route::post(
        '/ajustes',
        [InventarioController::class, 'guardarAjuste']
    )->name('ajustes.store');

    Route::get(
        '/buscar-producto',
        [InventarioController::class, 'buscarProducto']
    )->name('buscar.producto');

});


/*
|--------------------------------------------------------------------------
| TÉCNICOS
|--------------------------------------------------------------------------
*/
Route::prefix('tecnicos')->name('tecnicos.')->group(function () {

    Route::get('/', [TecnicoController::class, 'index'])
        ->name('index');

    Route::get('/create', [TecnicoController::class, 'create'])
        ->name('create');

    Route::post('/', [TecnicoController::class, 'store'])
        ->name('store');

    Route::patch('/{id}/toggle', [TecnicoController::class, 'toggle'])
        ->name('toggle');

});


/*
|--------------------------------------------------------------------------
| REPORTES
|--------------------------------------------------------------------------
*/
Route::prefix('reportes')->name('reportes.')->group(function () {

    Route::get('/', [ReporteController::class, 'index'])
        ->name('index');

    Route::get('/ventas', [ReporteController::class, 'ventas'])
        ->name('ventas');

    Route::get('/reparaciones', [ReporteController::class, 'reparaciones'])
        ->name('reparaciones');

    Route::get('/inventario', [ReporteController::class, 'inventario'])
        ->name('inventario');

    Route::get('/tecnicos', [ReporteController::class, 'tecnicos'])
        ->name('tecnicos');

    Route::get('/financiero', [ReporteController::class, 'financiero'])
        ->name('financiero');

    Route::get('/anual', [ReporteController::class, 'anual'])
        ->name('anual');

    Route::get('/resumen', [ReporteController::class, 'resumen'])
        ->name('resumen');

});