<?php

namespace App\Http\Controllers;

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
        $mes  = $request->mes  ?? date('m');
        $anio = $request->anio ?? date('Y');
        return [$mes, $anio];
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
        $data = $this->service->generarReporteInventario($mes, $anio);
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
        $anio = $request->anio ?? date('Y');
        $data = $this->service->generarResumenAnual($anio);
        return view('reportes.anual', array_merge($data, compact('anio')));
    }

    public function exportar(string $tipo, Request $request, ReporteExportService $exportService)
    {
        $tiposValidos = ['ventas', 'reparaciones', 'tecnicos', 'financiero', 'inventario', 'anual'];

        if (!in_array($tipo, $tiposValidos)) {
            abort(404);
        }

        $mes  = (int) ($request->mes  ?? date('m'));
        $anio = (int) ($request->anio ?? date('Y'));

        try {
            [$spreadsheet, $nombreArchivo] = $exportService->exportar($tipo, $mes, $anio);

            $writer = new Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $nombreArchivo, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No fue posible generar el archivo de exportación. Intenta nuevamente.');
        }
    }
}
