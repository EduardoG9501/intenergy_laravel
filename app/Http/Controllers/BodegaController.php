<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BodegaController extends Controller
{
    public function index(Request $request)
    {
        $query = Bodega::with('principal');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('nombreBodega', 'like', "%{$buscar}%")
                  ->orWhere('identificador', 'like', "%{$buscar}%");
        }

        $bodegas = $query->orderBy('id_bodega', 'desc')->paginate(15);

        // Obtener bodegas principales para el dropdown
        $principales = Bodega::where('es_bodega_secundaria', 0)->where('estado', 1)->get();

        return view('bodegas.index', compact('bodegas', 'principales'));
    }

    public function show($id)
    {
        $bodega = Bodega::findOrFail($id);
        return response()->json([
            'success' => true,
            'bodega' => $bodega
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombreBodega' => 'required|max:150',
            'identificador' => 'required|max:10',
            'es_bodega_secundaria' => 'required|boolean',
            'id_bodega_principal' => 'nullable|required_if:es_bodega_secundaria,1',
        ]);

        // Validar nombre único globalmente
        $nombreExiste = Bodega::where('nombreBodega', trim($request->nombreBodega))->exists();
        if ($nombreExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El nombre de la bodega ya está registrado en el sistema.']);
        }

        // Validar identificador único globalmente
        $identificadorExiste = Bodega::where('identificador', trim($request->identificador))->exists();
        if ($identificadorExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El identificador ya está en uso por otra bodega.']);
        }

        try {
            Bodega::create([
                'id_usuario' => Auth::id(),
                'nombreBodega' => trim($request->nombreBodega),
                'identificador' => strtoupper(trim($request->identificador)),
                'es_bodega_secundaria' => $request->es_bodega_secundaria,
                'id_bodega_principal' => $request->es_bodega_secundaria ? $request->id_bodega_principal : null,
                'fechaCaptura' => now()->toDateString(),
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Bodega guardada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar la bodega: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $bodega = Bodega::findOrFail($id);

        $request->validate([
            'nombreBodegaU' => 'required|max:150',
            'identificadorU' => 'required|max:10',
            'es_bodega_secundariaU' => 'required|boolean',
            'id_bodega_principalU' => 'nullable|required_if:es_bodega_secundariaU,1',
        ]);

        // Validar nombre único excluyendo actual globalmente
        $nombreExiste = Bodega::where('nombreBodega', trim($request->nombreBodegaU))
                              ->where('id_bodega', '!=', $id)
                              ->exists();
        if ($nombreExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El nombre de la bodega ya está registrado en el sistema.']);
        }

        // Validar identificador único excluyendo actual globalmente
        $identificadorExiste = Bodega::where('identificador', trim($request->identificadorU))
                                     ->where('id_bodega', '!=', $id)
                                     ->exists();
        if ($identificadorExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El identificador ya está en uso por otra bodega.']);
        }

        try {
            $bodega->update([
                'nombreBodega' => trim($request->nombreBodegaU),
                'identificador' => strtoupper(trim($request->identificadorU)),
                'es_bodega_secundaria' => $request->es_bodega_secundariaU,
                'id_bodega_principal' => $request->es_bodega_secundariaU ? $request->id_bodega_principalU : null,
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Bodega actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar la bodega: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $bodega = Bodega::findOrFail($id);
            $bodega->estado = 0; // Desactivar bodega
            $bodega->save();

            return response()->json(['success' => true, 'mensaje' => 'Bodega desactivada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar la bodega: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $bodega = Bodega::findOrFail($id);
            $bodega->estado = 1; // Activar bodega
            $bodega->save();

            return response()->json(['success' => true, 'mensaje' => 'Bodega restaurada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al restaurar la bodega: ' . $e->getMessage()]);
        }
    }
}
