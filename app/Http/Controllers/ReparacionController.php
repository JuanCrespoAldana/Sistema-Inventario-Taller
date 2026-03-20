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

        $clientes = Cliente::orderBy('nombre')->get(); // 👈 FALTABA ESTO

        return view('reparaciones.edit', compact('orden', 'tecnicos', 'clientes'));
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
            ->orderByRaw('CAST(numero_orden AS UNSIGNED) DESC')
            ->paginate(20)
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
        ]);

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
    }

    public function update(Request $request, OrdenReparacion $orden)
    {
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

        if ($request->has('maquinas')) {

            foreach ($request->maquinas as $m) {

                // Limpiar valores monetarios
                $m['mano_obra'] = str_replace('.', '', $m['mano_obra'] ?? 0);
                $m['total'] = str_replace('.', '', $m['total'] ?? 0);

                if (isset($m['id'])) {

                    // Actualizar existente
                    $maquina = OrdenMaquina::find($m['id']);

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

                        // Si acaba de marcarse como entregada, disparar el evento
                        if ($nuevoEstado === 'entregada' && $maquina->estado !== 'entregada') {
                            $maquina->update($dataUpdate);
                            $maquina->refresh();
                            event(new \App\Events\ReparacionEntregada($maquina));
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

        return redirect()
            ->route('reparaciones.index')
            ->with('success', 'Orden actualizada correctamente');
    }


    public function destroy($id)
    {
        $orden = OrdenReparacion::with('maquinas')->findOrFail($id);

        // Eliminamos primero las máquinas asociadas
        foreach ($orden->maquinas as $maquina) {
            $maquina->delete();
        }

        // Eliminamos la orden
        $orden->delete();

        return redirect()
            ->route('reparaciones.index')
            ->with('success', 'Orden de reparación eliminada correctamente');
    }
}