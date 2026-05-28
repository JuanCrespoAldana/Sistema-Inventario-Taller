<?php

namespace App\Http\Controllers;

use App\Models\Tecnico;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    public function index(Request $request)
    {
        $query = Tecnico::orderByDesc('created_at');

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        if ($request->input('estado') !== null && $request->input('estado') !== '') {
            $query->where('activo', $request->estado);
        }

        $tecnicos = $query->paginate(10)->withQueryString();

        return view('tecnicos.index', compact('tecnicos'));
    }

    public function create()
    {
        return view('tecnicos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        ]);

        Tecnico::create([
            'nombre' => $request->nombre,
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
}