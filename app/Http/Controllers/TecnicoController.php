<?php

namespace App\Http\Controllers;

use App\Models\Tecnico;
use App\Services\Tecnicos\TecnicoService;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    public function index(Request $request)
    {
        $query = Tecnico::orderByDesc('created_at')->orderByDesc('id');

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        if ($request->input('estado') !== null && $request->input('estado') !== '') {
            $query->where('activo', $request->estado);
        }

        $tecnicos = $query->paginate(6)->withQueryString();

        return view('tecnicos.index', compact('tecnicos'));
    }

    public function create()
    {
        return view('tecnicos.create');
    }

    public function store(Request $request, TecnicoService $tecnicoService)
    {
        $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
                function ($attribute, $value, $fail) use ($tecnicoService) {
                    if ($tecnicoService->buscarDuplicado($value)) {
                        $fail('Ya existe un técnico registrado con ese nombre.');
                    }
                },
            ],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder los 150 caracteres.',
        ]);

        Tecnico::create([
            'nombre' => trim($request->nombre),
            'activo' => 1,
        ]);

        return redirect()
            ->route('tecnicos.index')
            ->with('success', 'Técnico creado correctamente.');
    }

    public function toggle($id)
    {
        $tecnico = Tecnico::findOrFail($id);

        $tecnico->update([
            'activo' => !$tecnico->activo
        ]);

        return redirect()
            ->route('tecnicos.index')
            ->with('success', 'Estado del técnico actualizado.');
    }

    public function update(Request $request, Tecnico $tecnico, TecnicoService $tecnicoService)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder los 150 caracteres.',
        ]);

        $confirmarFusion = $request->boolean('confirmar_fusion');

        $resultado = $tecnicoService->actualizarNombre($tecnico, $request->input('nombre'), $confirmarFusion);

        if ($resultado['tipo'] === 'requiere_confirmacion') {
            return response()->json([
                'requiere_confirmacion' => true,
                'mensaje' => $resultado['mensaje'],
            ], 409);
        }

        return response()->json([
            'mensaje' => $resultado['mensaje'],
        ]);
    }
}