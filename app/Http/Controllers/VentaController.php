<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Http\Requests\Ventas\VentaStoreRequest;
use App\Services\Ventas\VentaService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VentaController extends Controller
{
    protected VentaService $ventaService;

    public function __construct(VentaService $ventaService)
    {
        $this->ventaService = $ventaService;
    }

    public function index(Request $request)
    {
        $base = Venta::query();

        if ($request->filled('factura')) {
            $base->where('numero_factura', 'like', '%' . $request->factura . '%');
        }

        if ($request->filled('producto')) {
            $texto = $request->producto;

            $base->whereHas('detalles.producto', function ($q) use ($texto) {
                $q->where(function ($q2) use ($texto) {
                    $q2->where('nombre', 'like', '%' . $texto . '%')
                        ->orWhere('codigo', 'like', '%' . $texto . '%');
                });
            });
        }

        if ($request->filled('desde') && $this->fechaValida($request->desde)) {
            $base->where('fecha', '>=', trim($request->desde));
        }

        if ($request->filled('hasta') && ($hastaExclusivo = $this->diaSiguiente($request->hasta))) {
            $base->where('fecha', '<', $hastaExclusivo);
        }

        if ($request->input('descuento') === 'con') {
            $base->where('descuento', '>', 0);
        } elseif ($request->input('descuento') === 'sin') {
            $base->where(function ($q) {
                $q->whereNull('descuento')->orWhere('descuento', '<=', 0);
            });
        }

        $montoMin = $this->soloDigitos($request->input('monto_min'));
        if ($montoMin !== null) {
            $base->where('total_final', '>=', $montoMin);
        }

        $montoMax = $this->soloDigitos($request->input('monto_max'));
        if ($montoMax !== null) {
            $base->where('total_final', '<=', $montoMax);
        }

        $resumen = (clone $base)
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(total_final), 0) as suma')
            ->first();

        $orden = $request->input('orden', 'reciente');

        $listado = (clone $base)->with('detalles.producto');

        switch ($orden) {
            case 'antigua':
                $listado->orderBy('id');
                break;
            case 'monto_desc':
                $listado->orderByDesc('total_final')->orderByDesc('id');
                break;
            case 'monto_asc':
                $listado->orderBy('total_final')->orderByDesc('id');
                break;
            case 'reciente':
            default:
                $listado->orderByDesc('id');
                break;
        }

        $ventas = $listado->paginate(15)->withQueryString();

        return view('ventas.index', compact('ventas', 'resumen'));
    }

    private function soloDigitos($valor): ?int
    {
        $digitos = preg_replace('/\D/', '', (string) $valor);

        return $digitos === '' ? null : (int) $digitos;
    }

    /**
     * Devuelve el día siguiente a $valor ('Y-m-d') o null si no es una fecha válida.
     * Sirve para filtrar "hasta el día X inclusive" con  campo < día siguiente,
     * que funciona igual con columnas DATE y DATETIME y permite usar índices.
     */
    private function diaSiguiente($valor): ?string
    {
        return $this->fechaValida($valor) ? Carbon::parse($valor)->addDay()->toDateString() : null;
    }

    private function fechaValida($valor): bool
    {
        return is_string($valor) && Carbon::hasFormat(trim($valor), 'Y-m-d');
    }

    public function create()
    {
        return view('ventas.create');
    }

    public function store(VentaStoreRequest $request)
    {
        try {
            $venta = $this->ventaService->crearVenta(
                $request->validated()
            );

            return redirect()
                ->route('ventas.show', $venta)
                ->with('success', 'Venta registrada correctamente');

        } catch (\RuntimeException $e) {

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se pudo registrar la venta. Por favor intenta nuevamente.');
        }
    }

    public function show(Venta $venta)
    {
        $venta->load('detalles.producto');
        return view('ventas.show', compact('venta'));
    }
}
