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
        return view('dashboard.index', [

            // ================= REPARACIONES =================
            'repMes' => $this->service->reparacionesMes(),
            'repActivas' => $this->service->reparacionesActivas(),
            'ingRepMes' => $this->service->ingresosReparacionesMes(),

            // ================= VENTAS =================
            'ventasMes' => $this->service->ventasMes(),
            'ingVentasMes' => $this->service->ingresosVentasMes(),
            'topProductosHistorico' => $this->service->topProductosVendidosHistorico(),

            // ================= INVENTARIO =================
            'valorCompra' => $this->service->valorInventarioCompra(),
            'valorVenta' => $this->service->valorInventarioVenta(),
            'criticos' => $this->service->productosCriticos(),

            // ================= TECNICOS =================
            'productividad' => $this->service->productividadTecnicos(),
            'productividad3m' => $this->service->productividadTecnicos(3),

            // ================= GRAFICAS =================
            'grafica12meses' => $this->service->ventasReparacionesUltimos12Meses(),

        ]);
    }
}
