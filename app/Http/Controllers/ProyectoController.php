<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\Request;

class ProyectoController extends Controller
{
    public function index(Request $request)
    {
        $query = Proyecto::query();
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', "%{$request->buscar}%");
        }
        $proyectos = $query->orderBy('id', 'desc')->paginate(15);
        return view('proyectos.index', compact('proyectos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|max:250|unique:proyecto,nombre'
        ]);

        try {
            Proyecto::create([
                'nombre' => trim($request->nombre),
                'estado' => 1,
                'fecha_registro' => now()
            ]);
            return response()->json(['success' => true, 'mensaje' => 'Proyecto guardado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $proyecto = Proyecto::findOrFail($id);
        return response()->json(['success' => true, 'proyecto' => $proyecto]);
    }

    public function update(Request $request, $id)
    {
        $proyecto = Proyecto::findOrFail($id);
        $request->validate([
            'nombre' => 'required|max:250|unique:proyecto,nombre,' . $id
        ]);

        try {
            $proyecto->update([
                'nombre' => trim($request->nombre)
            ]);
            return response()->json(['success' => true, 'mensaje' => 'Proyecto actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $proyecto = Proyecto::findOrFail($id);
            $proyecto->estado = 0;
            $proyecto->save();
            return response()->json(['success' => true, 'mensaje' => 'Proyecto desactivado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar: ' . $e->getMessage()]);
        }
    }

    public function eliminar($id)
    {
        try {
            $proyecto = Proyecto::findOrFail($id);

            // Verificar si el proyecto tiene movimientos o registros asociados
            $tieneOrdenes = \App\Models\OrdenTrabajo::where('id_proyecto', $id)->exists();
            $tieneLiquidaciones = \Illuminate\Support\Facades\DB::table('liquidacion_trabajo')->where('id_proyecto', $id)->exists();

            $tieneMovimientos = false;
            if (\Illuminate\Support\Facades\Schema::hasColumn('movimientos', 'id_proyecto')) {
                $tieneMovimientos = \Illuminate\Support\Facades\DB::table('movimientos')->where('id_proyecto', $id)->exists();
            }

            if ($tieneOrdenes || $tieneLiquidaciones || $tieneMovimientos) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No se puede eliminar el proyecto porque ya tiene movimientos asociados.'
                ]);
            }

            $proyecto->delete();
            return response()->json(['success' => true, 'mensaje' => 'Proyecto eliminado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al eliminar el proyecto: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $proyecto = Proyecto::findOrFail($id);
            $proyecto->estado = 1;
            $proyecto->save();
            return response()->json(['success' => true, 'mensaje' => 'Proyecto restaurado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al restaurar: ' . $e->getMessage()]);
        }
    }
}
