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
            'repHoy' => $this->service->reparacionesHoy(),
            'repMes' => $this->service->reparacionesMes(),
            'repAnio' => $this->service->reparacionesAnio(),
            'ingRepHoy' => $this->service->ingresosReparacionesHoy(),
            'ingRepMes' => $this->service->ingresosReparacionesMes(),
            'ingRepAnio' => $this->service->ingresosReparacionesAnio(),
            'repActivas' => $this->service->reparacionesActivas(),
            'repPendientes' => $this->service->reparacionesPendientes(),
            'repEnReparacion' => $this->service->reparacionesEnReparacion(),
            'repEntregadas' => $this->service->reparacionesEntregadas(),
            'estados' => $this->service->estadosReparaciones(),

            // ================= VENTAS =================
            'ventasHoy' => $this->service->ventasHoy(),
            'ventasMes' => $this->service->ventasMes(),
            'ventasAnio' => $this->service->ventasAnio(),
            'ingVentasHoy' => $this->service->ingresosVentasHoy(),
            'ingVentasMes' => $this->service->ingresosVentasMes(),
            'ingVentasAnio' => $this->service->ingresosVentasAnio(),
            'facturacion' => $this->service->facturacionTotal(),
            'topProductos' => $this->service->topProductos(),
            'topMaquinas' => $this->service->topMaquinasVendidas(),

            // ================= INVENTARIO =================
            'valorCompra' => $this->service->valorInventarioCompra(),
            'valorVenta' => $this->service->valorInventarioVenta(),
            'criticos' => $this->service->productosCriticos(),
            'movimientos' => $this->service->movimientosRecientes(),

            // ================= TECNICOS =================
            'ranking' => $this->service->rankingTecnicos(),
            'productividad' => $this->service->productividadTecnicos(),

            // ================= GRAFICAS =================
            'grafica' => $this->service->graficaVentasReparacionesMes(),
            'grafica12meses' => $this->service->ventasReparacionesUltimos12Meses(),

        ]);
    }
}
