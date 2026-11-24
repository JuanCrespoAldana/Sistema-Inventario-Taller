<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Tecnico;
use App\Models\OrdenReparacion;
use App\Models\OrdenMaquina;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReparacionController extends Controller
{
    // 👁️ Ver orden (modal)
    public function show(OrdenReparacion $orden)
    {
        $orden->load(['cliente', 'maquinas.tecnico']);

        return view('reparaciones.show', compact('orden'));
    }

    // ✏️ Editar orden (modal)
    public function edit(OrdenReparacion $orden)
    {
        $orden->load(['cliente', 'maquinas.tecnico']);

        $tecnicos = Tecnico::where('activo', 1)
            ->orderBy('nombre')
            ->get();

        // Nota: se quitó aquí una consulta que traía TODOS los clientes
        // (Cliente::orderBy('nombre')->get()) porque la vista reparaciones.edit
        // no usa esa variable en ningún punto — era una consulta completa
        // ejecutada en cada apertura del modal sin ningún uso real.

        return view('reparaciones.edit', compact('orden', 'tecnicos'));
    }
    public function eliminarMaquina($ordenId, $maquinaId)
    {
        $maquina = OrdenMaquina::where('orden_id', $ordenId)
            ->where('id', $maquinaId)
            ->firstOrFail();

        // Regla de seguridad opcional
        if ($maquina->estado === 'entregada') {
            return back()->with('error', 'No se puede eliminar una máquina entregada');
        }

        $maquina->delete();

        return back()->with('success', 'Máquina eliminada de la orden');
    }

    public function index(Request $request)
    {
        $query = OrdenReparacion::with(['cliente', 'maquinas.tecnico']);

        // 🔍 Filtro por número de orden
        if ($request->filled('numero_orden')) {
            $query->where('numero_orden', $request->numero_orden);
        }

        // 🔍 Filtro por cliente
        if ($request->filled('cliente')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->cliente . '%');
            });
        }

        // 🔍 Filtro por técnico
        if ($request->filled('tecnico_id')) {
            $query->whereHas('maquinas', function ($q) use ($request) {
                $q->where('tecnico_id', $request->tecnico_id);
            });
        }

        // 🔍 Filtro por estado de máquina
        if ($request->filled('estado')) {
            $query->whereHas('maquinas', function ($q) use ($request) {
                $q->where('estado', $request->estado);
            });
        }

        // 🔍 Filtro por fecha de salida desde
        if ($request->filled('desde') && $this->fechaValida($request->desde)) {
            $query->whereHas('maquinas', function ($q) use ($request) {
                $q->where('fecha_salida', '>=', trim($request->desde));
            });
        }

        // 🔍 Filtro por fecha de salida hasta
        if ($request->filled('hasta') && ($hastaExclusivo = $this->diaSiguiente($request->hasta))) {
            $query->whereHas('maquinas', function ($q) use ($hastaExclusivo) {
                $q->where('fecha_salida', '<', $hastaExclusivo);
            });
        }

        // 🔍 Filtro por teléfono
        if ($request->filled('telefono')) {
            $query->where('telefono', 'like', '%' . $request->telefono . '%');
        }

        // 🔍 Filtro por descripción de la máquina
        if ($request->filled('maquina')) {
            $query->whereHas('maquinas', function ($q) use ($request) {
                $q->where('descripcion', 'like', '%' . $request->maquina . '%');
            });
        }

        // 🔍 Filtro por garantía
        if (in_array($request->input('garantia'), ['0', '1'], true)) {
            $query->whereHas('maquinas', function ($q) use ($request) {
                $q->where('es_garantia', $request->garantia);
            });
        }

        // 🔍 Máquinas que siguen en el taller desde hace más de N días
        if (in_array((string) $request->input('antiguedad'), ['7', '15', '30'], true)) {
            $limite = Carbon::today()->subDays((int) $request->antiguedad)->toDateString();

            $query->where('fecha_entrada', '<=', $limite)
                ->whereHas('maquinas', function ($q) {
                    $q->whereIn('estado', ['pendiente', 'en_reparacion']);
                });
        }

        // 🔍 Filtro por fecha de entrada
        if ($request->filled('entrada_desde') && $this->fechaValida($request->entrada_desde)) {
            $query->where('fecha_entrada', '>=', trim($request->entrada_desde));
        }

        if ($request->filled('entrada_hasta') && ($entradaHasta = $this->diaSiguiente($request->entrada_hasta))) {
            $query->where('fecha_entrada', '<', $entradaHasta);
        }

        // 🔽 Orden de la tabla (por defecto: N° Orden de mayor a menor)
        $orden = $request->input('orden', 'orden_desc');

        switch ($orden) {
            case 'orden_asc':
                $query->orderByRaw('CAST(numero_orden AS UNSIGNED) ASC');
                break;
            case 'reciente':
                $query->orderByDesc('created_at')->orderByDesc('id');
                break;
            case 'orden_desc':
            default:
                $query->orderByRaw('CAST(numero_orden AS UNSIGNED) DESC');
                break;
        }

        $ordenes = $query
            ->paginate(10)
            ->withQueryString();

        $tecnicos = Tecnico::where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $conteoEstados = OrdenMaquina::selectRaw('estado, COUNT(DISTINCT orden_id) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $totalOrdenes = OrdenReparacion::count();

        return view('reparaciones.index', compact('ordenes', 'tecnicos', 'conteoEstados', 'totalOrdenes'));
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

    private function fechaSalidaSegunEstado($estado, $fecha): ?string
    {
        if ($estado !== 'entregada') {
            return null;
        }

        return !empty($fecha) ? $fecha : now()->toDateString();
    }

    private function fechaValida($valor): bool
    {
        return is_string($valor) && Carbon::hasFormat(trim($valor), 'Y-m-d');
    }

    public function create()
    {
        $tecnicos = Tecnico::where('activo', 1)->orderBy('nombre')->get();

        return view('reparaciones.create', compact('tecnicos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_orden' => 'required|unique:ordenes_reparacion,numero_orden',
            'cliente_nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'fecha_entrada' => 'required|date',

            'maquinas' => 'required|array|min:1|max:26',
            'maquinas.*.descripcion' => 'required|string|max:255',
            'maquinas.*.estado' => 'required|in:pendiente,en_reparacion,entregada',
            'maquinas.*.tecnico_id' => 'nullable|exists:tecnicos,id',
            'maquinas.*.mano_obra' => 'nullable|numeric|min:0',
            'maquinas.*.total' => 'nullable|numeric|min:0',
            'maquinas.*.fecha_salida' => 'nullable|date',
        ], [
            'numero_orden.required' => 'El número de orden es obligatorio.',
            'numero_orden.unique' => 'El número de orden ya existe. Por favor usa otro.',
            'cliente_nombre.required' => 'El nombre del cliente es obligatorio.',
            'cliente_nombre.max' => 'El nombre del cliente no puede exceder los 255 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder los 50 caracteres.',
            'fecha_entrada.required' => 'La fecha de entrada es obligatoria.',
            'fecha_entrada.date' => 'La fecha de entrada debe ser una fecha válida.',
            'maquinas.required' => 'Debe agregar al menos una máquina.',
            'maquinas.max' => 'No se pueden agregar más de 26 máquinas en una misma orden.',
            'maquinas.*.descripcion.required' => 'La descripción de la máquina es obligatoria.',
            'maquinas.*.descripcion.max' => 'La descripción de la máquina no puede exceder los 255 caracteres.',
            'maquinas.*.estado.required' => 'El estado de la máquina es obligatorio.',
            'maquinas.*.estado.in' => 'El estado de la máquina seleccionado no es válido.',
            'maquinas.*.tecnico_id.exists' => 'El técnico seleccionado no es válido.',
            'maquinas.*.mano_obra.min' => 'La mano de obra debe ser igual o mayor a cero.',
            'maquinas.*.total.min' => 'El total debe ser igual o mayor a cero.',
        ]);

        try {
            DB::transaction(function () use ($request) {

            // 1️⃣ Crear o reutilizar cliente
            $cliente = Cliente::firstOrCreate(
                ['nombre' => $request->cliente_nombre],
                ['telefono' => $request->telefono]
            );

            // 2️⃣ Crear orden de reparación
            $orden = OrdenReparacion::create([
                'numero_orden' => $request->numero_orden,
                'cliente_id' => $cliente->id,
                'telefono' => $request->telefono,
                'fecha_entrada' => $request->fecha_entrada,
            ]);

            // 3️⃣ Crear máquinas asociadas
            $letra = 'A';

            foreach ($request->maquinas as $maquina) {

                OrdenMaquina::create([
                    'orden_id' => $orden->id,
                    'etiqueta_maquina' => $letra, // 👈 OBLIGATORIO PARA LA BD
                    'descripcion' => $maquina['descripcion'],
                    'cotizacion' => $maquina['cotizacion'] ?? null,
                    'estado' => $maquina['estado'],
                    'tecnico_id' => $maquina['tecnico_id'] ?? null,
                    'es_garantia' => $maquina['es_garantia'] ?? 0,
                    'tipo_garantia' => $maquina['tipo_garantia'] ?? null,
                    'factura' => $maquina['factura'] ?? null,
                    'mano_obra' => isset($maquina['mano_obra']) ? str_replace('.', '', $maquina['mano_obra']) : 0,
                    'total' => isset($maquina['total']) ? str_replace('.', '', $maquina['total']) : 0,
                    'fecha_salida' => $this->fechaSalidaSegunEstado($maquina['estado'], $maquina['fecha_salida'] ?? null),
                ]);

                $letra++; // B, C, D...
            }
            });

            return redirect()
                ->back()
                ->with('success', 'Orden de reparación guardada correctamente');

        } catch (\Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se pudo guardar la orden de reparación. Por favor revise los datos e intente nuevamente.');
        }
    }

    public function update(Request $request, OrdenReparacion $orden)
    {
        $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'fecha_entrada' => 'required|date',

            'maquinas' => 'required|array|min:1|max:26',
            'maquinas.*.descripcion' => 'required|string|max:255',
            'maquinas.*.estado' => 'required|in:pendiente,en_reparacion,entregada',
            'maquinas.*.tecnico_id' => 'nullable|exists:tecnicos,id',
            'maquinas.*.mano_obra' => 'nullable|numeric|min:0',
            'maquinas.*.total' => 'nullable|numeric|min:0',
            'maquinas.*.fecha_salida' => 'nullable|date',
            'maquinas.*.factura' => 'nullable|string|max:255',
            'maquinas.*.tipo_garantia' => 'nullable|string|max:255',
            'maquinas.*.cotizacion' => 'nullable|string|max:1000',
        ], [
            'cliente_nombre.required' => 'El nombre del cliente es obligatorio.',
            'cliente_nombre.max' => 'El nombre del cliente no puede exceder los 255 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder los 50 caracteres.',
            'fecha_entrada.required' => 'La fecha de entrada es obligatoria.',
            'fecha_entrada.date' => 'La fecha de entrada debe ser una fecha válida.',
            'maquinas.required' => 'Debe agregar al menos una máquina.',
            'maquinas.max' => 'No se pueden agregar más de 26 máquinas en una misma orden.',
            'maquinas.*.descripcion.required' => 'La descripción de la máquina es obligatoria.',
            'maquinas.*.descripcion.max' => 'La descripción de la máquina no puede exceder los 255 caracteres.',
            'maquinas.*.estado.required' => 'El estado de la máquina es obligatorio.',
            'maquinas.*.estado.in' => 'El estado de la máquina seleccionado no es válido.',
            'maquinas.*.tecnico_id.exists' => 'El técnico seleccionado no es válido.',
            'maquinas.*.mano_obra.numeric' => 'La mano de obra debe ser un número.',
            'maquinas.*.mano_obra.min' => 'La mano de obra debe ser igual o mayor a cero.',
            'maquinas.*.total.numeric' => 'El total debe ser un número.',
            'maquinas.*.total.min' => 'El total debe ser igual o mayor a cero.',
            'maquinas.*.fecha_salida.date' => 'La fecha de salida debe ser una fecha válida.',
        ]);

        try {
            DB::transaction(function () use ($request, $orden) {

                // ==========================
                // ACTUALIZAR ORDEN
                // ==========================

                $orden->update([
                    'telefono' => $request->telefono,
                    'fecha_entrada' => $request->fecha_entrada,
                ]);

        if ($orden->cliente && $request->filled('cliente_nombre')) {
            $orden->cliente->update([
                'nombre' => $request->cliente_nombre
            ]);
        }

        $idsActuales = [];
        $maquinasExistentes = $orden->maquinas->keyBy('id');

        if ($request->has('maquinas')) {

            foreach ($request->maquinas as $m) {

                if (isset($m['id'])) {

                    // Actualizar existente (ya cargada arriba, sin volver a consultar)
                    $maquina = $maquinasExistentes->get($m['id']);

                    if ($maquina) {

                        $nuevoEstado = $m['estado'] ?? 'pendiente';

                        // Fecha de salida: solo tiene sentido si el estado es "entregada".
                        // Si se usa la que ingresó el usuario y no escribió fecha, poner hoy;
                        // si el estado no es "entregada", se limpia (aunque el form traiga una vieja).
                        $fechaSalida = $this->fechaSalidaSegunEstado($nuevoEstado, $m['fecha_salida'] ?? null);

                        // Preparar todos los datos (incluyendo estado y fecha_salida)
                        $dataUpdate = [
                            'descripcion'   => $m['descripcion'] ?? null,
                            'tecnico_id'    => $m['tecnico_id'] ?? null,
                            'es_garantia'   => $m['es_garantia'] ?? 0,
                            'tipo_garantia' => $m['tipo_garantia'] ?? null,
                            'factura'       => $m['factura'] ?? null,
                            'cotizacion'    => $m['cotizacion'] ?? null,
                            'estado'        => $nuevoEstado,
                            'fecha_salida'  => $fechaSalida,
                            'mano_obra'     => isset($m['mano_obra'])
                                ? str_replace('.', '', $m['mano_obra'])
                                : 0,
                            'total'         => isset($m['total'])
                                ? str_replace('.', '', $m['total'])
                                : 0,
                        ];

                        $maquina->update($dataUpdate);
                        $maquina->refresh();

                        $idsActuales[] = $maquina->id;
                    }

                } else {

                    // Crear nueva máquina
                    // Calcular siguiente etiqueta disponible
                    $letrasUsadas = $orden->maquinas()->pluck('etiqueta_maquina')->toArray();
                    $nuevaLetra = 'A';
                    while (in_array($nuevaLetra, $letrasUsadas)) {
                        $nuevaLetra++;
                    }

                    $nueva = $orden->maquinas()->create([
                        'etiqueta_maquina' => $nuevaLetra,
                        'descripcion' => $m['descripcion'] ?? null,
                        'tecnico_id' => $m['tecnico_id'] ?? null,
                        'estado' => $m['estado'] ?? 'pendiente',
                        'es_garantia' => $m['es_garantia'] ?? 0,
                        'tipo_garantia' => $m['tipo_garantia'] ?? null,
                        'factura' => $m['factura'] ?? null,
                        'fecha_salida' => $this->fechaSalidaSegunEstado($m['estado'] ?? 'pendiente', $m['fecha_salida'] ?? null),
                        'cotizacion' => $m['cotizacion'] ?? null,
                        'mano_obra' => isset($m['mano_obra'])
                            ? str_replace('.', '', $m['mano_obra'])
                            : 0,
                        'total' => isset($m['total'])
                            ? str_replace('.', '', $m['total'])
                            : 0,
                    ]);

                    $idsActuales[] = $nueva->id;
                }
            }
        }

        // ==========================
        // ELIMINAR LAS BORRADAS
        // ==========================

        if (!empty($idsActuales)) {
            $orden->maquinas()
                ->whereNotIn('id', $idsActuales)
                ->delete();
        }

            });

            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'Orden actualizada correctamente']);
            }

            return redirect()
                ->route('reparaciones.index')
                ->with('success', 'Orden actualizada correctamente');

        } catch (\Exception $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'mensaje' => 'No se pudo actualizar la orden. Por favor revise los datos e intente nuevamente.',
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se pudo actualizar la orden. Por favor revise los datos e intente nuevamente.');
        }
    }


    public function destroy($id)
    {
        $orden = OrdenReparacion::findOrFail($id);

        // No hace falta borrar las máquinas manualmente: la relación
        // orden_id -> ordenes_reparacion tiene cascadeOnDelete() en la
        // migración, así que la base de datos las borra automáticamente
        // al borrar la orden.
        $orden->delete();

        return redirect()
            ->route('reparaciones.index')
            ->with('success', 'Orden de reparación eliminada correctamente');
    }
}