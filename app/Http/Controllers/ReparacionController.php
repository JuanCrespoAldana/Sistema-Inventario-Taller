<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Tecnico;
use App\Models\OrdenReparacion;
use App\Models\OrdenMaquina;
use Illuminate\Support\Facades\DB;

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
        if ($request->filled('desde')) {
            $query->whereHas('maquinas', function ($q) use ($request) {
                $q->whereDate('fecha_salida', '>=', $request->desde);
            });
        }

        // 🔍 Filtro por fecha de salida hasta
        if ($request->filled('hasta')) {
            $query->whereHas('maquinas', function ($q) use ($request) {
                $q->whereDate('fecha_salida', '<=', $request->hasta);
            });
        }

        // 🔍 Filtro por teléfono
        if ($request->filled('telefono')) {
            $query->where('telefono', 'like', '%' . $request->telefono . '%');
        }

        $ordenes = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(5)
            ->withQueryString();

        $tecnicos = Tecnico::where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return view('reparaciones.index', compact('ordenes', 'tecnicos'));
    }

    public function create()
    {
        $tecnicos = Tecnico::where('activo', 1)->orderBy('nombre')->get();

        return view('reparaciones.create', compact('tecnicos'));
    }

    public function store(Request $request)
    {
        // ❌ Validar número de orden repetido
        $existe = OrdenReparacion::where('numero_orden', $request->numero_orden)->exists();

        if ($existe) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'El número de orden ya existe. Por favor usa otro.');
        }

        $request->validate([
            'numero_orden' => 'required|unique:ordenes_reparacion,numero_orden',
            'cliente_nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'fecha_entrada' => 'required|date',

            'maquinas' => 'required|array|min:1',
            'maquinas.*.descripcion' => 'required|string|max:255',
            'maquinas.*.estado' => 'required|string',
            'maquinas.*.tecnico_id' => 'nullable|exists:tecnicos,id',
            'maquinas.*.mano_obra' => 'nullable|numeric',
            'maquinas.*.total' => 'nullable|numeric',
            'maquinas.*.fecha_salida' => 'nullable|date',
        ], [
            'numero_orden.required' => 'El número de orden es obligatorio.',
            'numero_orden.unique' => 'El número de orden ya existe.',
            'cliente_nombre.required' => 'El nombre del cliente es obligatorio.',
            'cliente_nombre.max' => 'El nombre del cliente no puede exceder los 255 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder los 50 caracteres.',
            'fecha_entrada.required' => 'La fecha de entrada es obligatoria.',
            'fecha_entrada.date' => 'La fecha de entrada debe ser una fecha válida.',
            'maquinas.required' => 'Debe agregar al menos una máquina.',
            'maquinas.*.descripcion.required' => 'La descripción de la máquina es obligatoria.',
            'maquinas.*.descripcion.max' => 'La descripción de la máquina no puede exceder los 255 caracteres.',
            'maquinas.*.estado.required' => 'El estado de la máquina es obligatorio.',
            'maquinas.*.tecnico_id.exists' => 'El técnico seleccionado no es válido.',
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
                    'mano_obra' => $maquina['mano_obra'] ?? 0,
                    'total' => $maquina['total'] ?? 0,
                    'fecha_salida' => $maquina['fecha_salida'] ?? null,
                ]);

                $letra++; // B, C, D...
            }
            });

            return redirect()
                ->back()
                ->with('success', 'Orden de reparación guardada correctamente');

        } catch (\Exception $e) {
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

            'maquinas' => 'required|array|min:1',
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

                // Limpiar valores monetarios
                $m['mano_obra'] = str_replace('.', '', $m['mano_obra'] ?? 0);
                $m['total'] = str_replace('.', '', $m['total'] ?? 0);

                if (isset($m['id'])) {

                    // Actualizar existente (ya cargada arriba, sin volver a consultar)
                    $maquina = $maquinasExistentes->get($m['id']);

                    if ($maquina) {

                        $nuevoEstado = $m['estado'] ?? 'pendiente';

                        // Fecha de salida: usar la que ingresó el usuario;
                        // si marcó "entregada" y no escribió fecha, poner hoy.
                        $fechaSalida = !empty($m['fecha_salida'])
                            ? $m['fecha_salida']
                            : ($nuevoEstado === 'entregada' ? now()->toDateString() : null);

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

                        if ($nuevoEstado === 'entregada' && $maquina->estado !== 'entregada') {
                            $maquina->update($dataUpdate);
                            $maquina->refresh();
                        } else {
                            // Actualizar directamente sin restricciones de flujo
                            $maquina->update($dataUpdate);
                            $maquina->refresh();
                        }

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
                        'fecha_salida' => $m['fecha_salida'] ?? null,
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

        $orden->maquinas()
            ->whereNotIn('id', $idsActuales)
            ->delete();

            });

            return redirect()
                ->route('reparaciones.index')
                ->with('success', 'Orden actualizada correctamente');

        } catch (\Exception $e) {
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