<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Categoria::query();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('nombreCategoria', 'like', "%{$buscar}%");
        }

        $categorias = $query->orderBy('id_categoria', 'desc')->paginate(15);

        return view('categorias.index', compact('categorias'));
    }

    public function show($id)
    {
        $categoria = Categoria::findOrFail($id);
        return response()->json([
            'success' => true,
            'categoria' => $categoria
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombreCategoria' => 'required|max:150',
            'subcategoria' => 'required|boolean',
        ]);

        // Validar nombre único globalmente en toda la tabla categorias
        $nombreExiste = Categoria::where('nombreCategoria', trim($request->nombreCategoria))->exists();
        if ($nombreExiste) {
            return response()->json(['success' => false, 'mensaje' => 'La categoría o subcategoría ya existe en el sistema.']);
        }

        try {
            Categoria::create([
                'id_usuario' => Auth::id(),
                'nombreCategoria' => trim($request->nombreCategoria),
                'subcategoria' => $request->subcategoria,
                'fechaCaptura' => now()->toDateString(),
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Categoría guardada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar la categoría: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $request->validate([
            'nombreCategoriaU' => 'required|max:150',
            'subcategoriaU' => 'required|boolean',
        ]);

        // Validar nombre único excluyendo actual globalmente
        $nombreExiste = Categoria::where('nombreCategoria', trim($request->nombreCategoriaU))
                                 ->where('id_categoria', '!=', $id)
                                 ->exists();
        if ($nombreExiste) {
            return response()->json(['success' => false, 'mensaje' => 'Ya existe otra categoría o subcategoría registrada con este nombre.']);
        }

        try {
            $categoria->update([
                'nombreCategoria' => trim($request->nombreCategoriaU),
                'subcategoria' => $request->subcategoriaU,
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Categoría actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar la categoría: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $categoria = Categoria::findOrFail($id);
            $categoria->estado = 0; // Desactivar
            $categoria->save();

            return response()->json(['success' => true, 'mensaje' => 'Categoría desactivada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar la categoría: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $categoria = Categoria::findOrFail($id);
            $categoria->estado = 1; // Activar
            $categoria->save();

            return response()->json(['success' => true, 'mensaje' => 'Categoría restaurada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al restaurar la categoría: ' . $e->getMessage()]);
        }
    }
}
