<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Http\Requests\Ventas\VentaStoreRequest;
use App\Services\Ventas\VentaService;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    protected VentaService $ventaService;

    public function __construct(VentaService $ventaService)
    {
        $this->ventaService = $ventaService;
    }

    public function index(Request $request)
    {
        $query = Venta::with('detalles.producto')->orderByDesc('id');

        // 🔍 Filtro por número de factura
        if ($request->filled('factura')) {
            $query->where('numero_factura', 'like', '%' . $request->factura . '%');
        }

        // 🔍 Filtro por fecha desde
        if ($request->filled('desde')) {
            $query->whereDate('fecha', '>=', $request->desde);
        }

        // 🔍 Filtro por fecha hasta
        if ($request->filled('hasta')) {
            $query->whereDate('fecha', '<=', $request->hasta);
        }

        $ventas = $query->paginate(15)->withQueryString();

        return view('ventas.index', compact('ventas'));
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
        }
    }

    public function show(Venta $venta)
    {
        $venta->load('detalles.producto');
        return view('ventas.show', compact('venta'));
    }
}
