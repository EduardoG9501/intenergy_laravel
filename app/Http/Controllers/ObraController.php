<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\Proyecto;
use Illuminate\Http\Request;

class ObraController extends Controller
{
    public function index(Request $request)
    {
        $query = Obra::with('proyecto');
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', "%{$request->buscar}%");
        }
        $obras = $query->orderBy('id', 'desc')->paginate(15);
        $proyectos = Proyecto::where('estado', 1)->get();
        return view('obras.index', compact('obras', 'proyectos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|max:250',
            'id_proyecto' => 'nullable|exists:proyecto,id'
        ]);

        // Normalizar el nombre (trim, uppercase para comparación)
        $nombre = trim($request->nombre);
        $nombreNormalizado = strtoupper($nombre);
        
        // Buscar si existe un nombre similar (sin importar mayúsculas/minúsculas)
        $existe = Obra::whereRaw('UPPER(TRIM(nombre)) = ?', [$nombreNormalizado])->exists();
        
        if ($existe) {
            return response()->json([
                'success' => false, 
                'mensaje' => 'El Nombre de la Obra ya está registrado en el sistema. Por favor, utilice un nombre diferente.'
            ]);
        }

        try {
            Obra::create([
                'nombre' => $nombre,
                'id_proyecto' => $request->id_proyecto ?: null,
                'estado' => 1,
                'fecha_registro' => now()
            ]);
            
            return response()->json(['success' => true, 'mensaje' => 'Obra guardada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar la obra: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $obra = Obra::with('proyecto')->findOrFail($id);
        return response()->json(['success' => true, 'obra' => $obra]);
    }

    public function update(Request $request, $id)
    {
        $obra = Obra::findOrFail($id);
        $request->validate([
            'nombre' => 'required|max:250',
            'id_proyecto' => 'nullable|exists:proyecto,id'
        ]);

        // Normalizar el nombre
        $nombre = trim($request->nombre);
        $nombreNormalizado = strtoupper($nombre);
        
        // Verificar si existe otro registro con el mismo nombre (ignorando mayúsculas)
        $existe = Obra::whereRaw('UPPER(nombre) = ?', [$nombreNormalizado])
                      ->where('id', '!=', $id)
                      ->exists();
        
        if ($existe) {
            return response()->json([
                'success' => false, 
                'mensaje' => 'El Nombre de la Obra ya está registrado por otra obra. Por favor, utilice un nombre diferente.'
            ]);
        }

        try {
            $obra->update([
                'nombre' => $nombre,
                'id_proyecto' => $request->id_proyecto ?: null
            ]);
            return response()->json(['success' => true, 'mensaje' => 'Obra actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar la obra: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $obra = Obra::findOrFail($id);
            $obra->estado = 0;
            $obra->save();
            return response()->json(['success' => true, 'mensaje' => 'Obra desactivada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar: ' . $e->getMessage()]);
        }
    }

    public function eliminar($id)
    {
        try {
            $obra = Obra::findOrFail($id);

            // Verificar si la obra tiene movimientos o registros asociados
            $tieneOrdenes = \App\Models\OrdenTrabajo::where('id_obra', $id)->exists();
            $tieneLiquidaciones = \Illuminate\Support\Facades\DB::table('liquidacion_trabajo')->where('id_obra', $id)->exists();

            $tieneMovimientos = false;
            if (\Illuminate\Support\Facades\Schema::hasColumn('movimientos', 'id_obra')) {
                $tieneMovimientos = \Illuminate\Support\Facades\DB::table('movimientos')->where('id_obra', $id)->exists();
            }

            if ($tieneOrdenes || $tieneLiquidaciones || $tieneMovimientos) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No se puede eliminar la obra porque ya tiene movimientos asociados.'
                ]);
            }

            $obra->delete();
            return response()->json(['success' => true, 'mensaje' => 'Obra eliminada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al eliminar la obra: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $obra = Obra::findOrFail($id);
            $obra->estado = 1;
            $obra->save();
            return response()->json(['success' => true, 'mensaje' => 'Obra restaurada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al restaurar: ' . $e->getMessage()]);
        }
    }
}
