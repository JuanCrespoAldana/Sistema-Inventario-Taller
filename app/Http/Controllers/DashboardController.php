<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    protected $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        // Los datos se calculan en el servicio (13 consultas -> 10) y se guardan 30 s en caché;
        // el caché se borra solo cuando se guarda una venta, orden, producto o movimiento.
        return view('dashboard.index', $this->service->datosDashboard());
    }
}
