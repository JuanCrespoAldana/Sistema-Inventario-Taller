<?php

namespace App\Http\Controllers;

use App\Services\Excel\ExcelHelper;
use Illuminate\Http\Request;
use App\Services\Reportes\ReporteService;
use App\Services\Reportes\ReporteExportService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteController extends Controller
{
    protected $service;

    public function __construct(ReporteService $service)
    {
        $this->service = $service;
    }

    private function periodo(Request $request): array
    {
        return [
            $this->mesValido($request->input('mes')),
            $this->anioValido($request->input('anio')),
        ];
    }

    private function mesValido($valor): int
    {
        $mes = (int) $valor;

        return ($mes >= 1 && $mes <= 12) ? $mes : (int) date('n');
    }

    private function anioValido($valor): int
    {
        $anio = (int) $valor;

        return ($anio >= 2020 && $anio <= 2099) ? $anio : (int) date('Y');
    }

    public function index(Request $request)
    {
        [$mes, $anio] = $this->periodo($request);
        $data = $this->service->generarReporteMensual($mes, $anio);
        return view('reportes.index', array_merge($data, compact('mes', 'anio')));
    }

    public function ventas(Request $request)
    {
        [$mes, $anio] = $this->periodo($request);
        $data = $this->service->generarReporteMensual($mes, $anio);
        return view('reportes.ventas', array_merge($data, compact('mes', 'anio')));
    }

    public function reparaciones(Request $request)
    {
        [$mes, $anio] = $this->periodo($request);
        $data = $this->service->generarReporteMensual($mes, $anio);
        return view('reportes.reparaciones', array_merge($data, compact('mes', 'anio')));
    }

    public function inventario(Request $request)
    {
        [$mes, $anio] = $this->periodo($request);
        $data = $this->service->generarReporteInventario($mes, $anio, true, [
            'buscar' => $request->input('buscar'),
            'estado' => $request->input('estado'),
            'tipo' => $request->input('tipo'),
        ]);
        return view('reportes.inventario', array_merge($data, compact('mes', 'anio')));
    }

    public function tecnicos(Request $request)
    {
        [$mes, $anio] = $this->periodo($request);
        $data = $this->service->generarReporteMensual($mes, $anio);
        return view('reportes.tecnicos', array_merge($data, compact('mes', 'anio')));
    }

    public function financiero(Request $request)
    {
        [$mes, $anio] = $this->periodo($request);
        $data = $this->service->generarReporteMensual($mes, $anio);

        return view('reportes.financiero', array_merge($data, [
            'mes' => $mes,
            'anio' => $anio,
            'ingresosVentas' => $data['totalVentasMes'],
            'ingresosReparaciones' => $data['totalReparacionesMes'],
        ]));
    }

    public function resumen(Request $request)
    {
        $anio = $this->anioValido($request->input('anio'));
        $data = $this->service->generarResumenAnual($anio);
        return view('reportes.anual', array_merge($data, compact('anio')));
    }

    public function exportar(string $tipo, Request $request, ReporteExportService $exportService)
    {
        $tiposValidos = ['ventas', 'reparaciones', 'tecnicos', 'financiero', 'inventario', 'anual'];

        if (!in_array($tipo, $tiposValidos)) {
            abort(404);
        }

        $mes  = $this->mesValido($request->input('mes'));
        $anio = $this->anioValido($request->input('anio'));

        try {
            [$libro, $nombreArchivo] = $exportService->exportar($tipo, $mes, $anio);

            return ExcelHelper::descargar($libro, $nombreArchivo);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No fue posible generar el archivo de Excel. Intenta nuevamente; si el problema continúa, avisa al administrador del sistema.');
        }
    }
}
