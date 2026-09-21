<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use App\Models\Proyecto;
use App\Models\Obra;
use App\Models\Bodega;
use App\Models\EstadoOrden;
use Illuminate\Http\Request;

class OrdenTrabajoController extends Controller
{
    public function index(Request $request)
    {
        $query = OrdenTrabajo::with(['proyecto', 'obra', 'bodegaPrincipal', 'bodegaSecundaria', 'estadoOrden']);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                // Buscar por identificador
                $q->where('identificador', 'like', "%{$buscar}%")
                  // Buscar por observación
                  ->orWhere('observacion', 'like', "%{$buscar}%")
                  // Buscar por nombre del proyecto
                  ->orWhereHas('proyecto', function($subq) use ($buscar) {
                      $subq->where('nombre', 'like', "%{$buscar}%");
                  })
                  // Buscar por nombre de la obra
                  ->orWhereHas('obra', function($subq) use ($buscar) {
                      $subq->where('nombre', 'like', "%{$buscar}%");
                  });
            });
        }

        $ordenes = $query->orderBy('id_orden', 'desc')->paginate(15);

        // Catalogos
        $proyectos = Proyecto::where('estado', 1)->get();
        $obras = Obra::where('estado', 1)->get();
        $bodegasPrincipales = Bodega::where('es_bodega_secundaria', 0)->where('estado', 1)->get();
        $bodegasSecundarias = Bodega::where('es_bodega_secundaria', 1)->get();
        $estados = EstadoOrden::where('activo', 1)->get();

        return view('ordenes.index', compact('ordenes', 'proyectos', 'obras', 'bodegasPrincipales', 'bodegasSecundarias', 'estados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_proyecto' => 'required|exists:proyecto,id',
            'id_obra' => 'required|exists:obra,id',
            'costo' => 'required|numeric|min:0',
            'fecha_inicial' => 'required|date',
            'fecha_final' => 'required|date|after_or_equal:fecha_inicial',
            'id_bodega_principal' => 'required|exists:bodegas,id_bodega',
            'id_bodega_secundaria' => 'nullable|exists:bodegas,id_bodega',
            'id_estado_orden' => 'required|exists:estado_ordenes,id_estado_orden',
            'observacion' => 'nullable|max:1000'
        ]);

        try {
            // Validar que no exista ya una orden con la misma combinación Proyecto + Obra
            $existeOrden = OrdenTrabajo::where('id_proyecto', $request->id_proyecto)
                                       ->where('id_obra', $request->id_obra)
                                       ->exists();
            
            if ($existeOrden) {
                $proyecto = Proyecto::find($request->id_proyecto);
                $obra = Obra::find($request->id_obra);
                
                return response()->json([
                    'success' => false,
                    'mensaje' => "Ya existe una Orden de Trabajo para el proyecto '{$proyecto->nombre}' con la obra '{$obra->nombre}'. No se pueden crear órdenes duplicadas para la misma combinación."
                ]);
            }

            // Generar identificador automáticamente basado en el proyecto
            $proyecto = Proyecto::findOrFail($request->id_proyecto);
            $nombreProyecto = strtoupper(substr($proyecto->nombre, 0, 3)); // Primeros 3 caracteres del nombre
            
            // Contar cuántas órdenes tiene este proyecto
            $conteo = OrdenTrabajo::where('id_proyecto', $request->id_proyecto)->count() + 1;
            
            // Generar identificador único
            $identificador = $nombreProyecto . str_pad($conteo, 3, '0', STR_PAD_LEFT);
            
            // Verificar si ya existe, si existe incrementar hasta encontrar uno único
            while (OrdenTrabajo::where('identificador', $identificador)->exists()) {
                $conteo++;
                $identificador = $nombreProyecto . str_pad($conteo, 3, '0', STR_PAD_LEFT);
            }

            OrdenTrabajo::create([
                'identificador' => $identificador,
                'id_proyecto' => $request->id_proyecto,
                'id_obra' => $request->id_obra,
                'costo' => $request->costo,
                'fecha_inicial' => $request->fecha_inicial,
                'fecha_final' => $request->fecha_final,
                'id_bodega_principal' => $request->id_bodega_principal,
                'id_bodega_secundaria' => $request->id_bodega_secundaria,
                'observacion' => $request->observacion,
                'id_estado_orden' => $request->id_estado_orden,
                'fecha_graba' => now(),
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Orden de Trabajo creada correctamente con identificador: ' . $identificador]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $orden = OrdenTrabajo::findOrFail($id);
        return response()->json(['success' => true, 'orden' => $orden]);
    }

    public function update(Request $request, $id)
    {
        $orden = OrdenTrabajo::findOrFail($id);
        $request->validate([
            'identificador' => 'required|max:10|unique:ordenes_trabajo,identificador,' . $id . ',id_orden',
            'id_proyecto' => 'required|exists:proyecto,id',
            'id_obra' => 'required|exists:obra,id',
            'fecha_inicial' => 'required|date',
            'id_bodega_principal' => 'required|exists:bodegas,id_bodega',
            'id_bodega_secundaria' => 'nullable|exists:bodegas,id_bodega',
            'id_estado_orden' => 'required|exists:estado_ordenes,id_estado_orden',
            'observacion' => 'nullable|max:1000'
        ]);

        try {
            // Validar que no exista otra orden con la misma combinación Proyecto + Obra
            $existeOrden = OrdenTrabajo::where('id_proyecto', $request->id_proyecto)
                                       ->where('id_obra', $request->id_obra)
                                       ->where('id_orden', '!=', $id)
                                       ->exists();
            
            if ($existeOrden) {
                $proyecto = Proyecto::find($request->id_proyecto);
                $obra = Obra::find($request->id_obra);
                
                return response()->json([
                    'success' => false,
                    'mensaje' => "Ya existe otra Orden de Trabajo para el proyecto '{$proyecto->nombre}' con la obra '{$obra->nombre}'. No se pueden tener órdenes duplicadas para la misma combinación."
                ]);
            }

            $orden->update([
                'identificador' => trim($request->identificador),
                'id_proyecto' => $request->id_proyecto,
                'id_obra' => $request->id_obra,
                'costo' => $request->costo ?? 0,
                'fecha_inicial' => $request->fecha_inicial,
                'fecha_final' => $request->fecha_final ?? $request->fecha_inicial,
                'id_bodega_principal' => $request->id_bodega_principal,
                'id_bodega_secundaria' => $request->id_bodega_secundaria,
                'observacion' => $request->observacion,
                'id_estado_orden' => $request->id_estado_orden
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Orden de Trabajo actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $orden = OrdenTrabajo::findOrFail($id);
            $orden->estado = 0;
            $orden->save();
            return response()->json(['success' => true, 'mensaje' => 'Orden de Trabajo desactivada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $orden = OrdenTrabajo::findOrFail($id);
            $orden->estado = 1;
            $orden->save();
            return response()->json(['success' => true, 'mensaje' => 'Orden de Trabajo restaurada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al restaurar: ' . $e->getMessage()]);
        }
    }

    public function getObrasByProyecto($id_proyecto)
    {
        $obras = Obra::where('id_proyecto', $id_proyecto)
                     ->where('estado', 1)
                     ->get(['id', 'nombre']);

        // Si el proyecto no tiene obras asignadas, mostrar todas las activas
        if ($obras->isEmpty()) {
            $obras = Obra::where('estado', 1)->get(['id', 'nombre']);
        }

        return response()->json(['success' => true, 'obras' => $obras]);
    }
}
