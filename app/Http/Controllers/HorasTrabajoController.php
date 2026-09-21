<?php

namespace App\Http\Controllers;

use App\Models\HorasTrabajo;
use App\Models\HorasTrabajoEmpleado;
use App\Models\Bodega;
use App\Models\Empleado;
use Illuminate\Http\Request;

class HorasTrabajoController extends Controller
{
    public function index(Request $request)
    {
        $query = HorasTrabajo::with(['bodegaPrincipal', 'bodegaSecundaria', 'responsable']);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('observacion', 'like', "%{$buscar}%");
        }

        $horas = $query->orderBy('id_horas_trabajo', 'desc')->paginate(15);

        // Catalogos
        $bodegasPrincipales = Bodega::where('es_bodega_secundaria', 0)->where('estado', 1)->get();
        $bodegasSecundarias = Bodega::where('es_bodega_secundaria', 1)->where('estado', 1)->get();
        $empleados = Empleado::where('estado', 1)->get();

        return view('horas.index', compact('horas', 'bodegasPrincipales', 'bodegasSecundarias', 'empleados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'id_bodega_principal' => 'required|exists:bodegas,id_bodega',
            'id_bodega_secundaria' => 'required|exists:bodegas,id_bodega',
            'id_responsable_obra' => 'required|exists:empleados,id_empleados',
            'observacion' => 'nullable|max:1000'
        ]);

        try {
            $ht = HorasTrabajo::create([
                'fecha' => $request->fecha,
                'id_bodega_principal' => $request->id_bodega_principal,
                'id_bodega_secundaria' => $request->id_bodega_secundaria,
                'id_responsable_obra' => $request->id_responsable_obra,
                'observacion' => $request->observacion,
                'fecha_creacion' => now(),
                'estado' => 1
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => 'Control de horas creado correctamente.',
                'id' => $ht->id_horas_trabajo
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al crear: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $hora = HorasTrabajo::with(['bodegaPrincipal', 'bodegaSecundaria', 'responsable'])->findOrFail($id);
        $detalles = HorasTrabajoEmpleado::with('empleado')
                                        ->where('id_horas_trabajo', $id)
                                        ->get();

        $empleados = Empleado::where('estado', 1)->get();

        return view('horas.diario', compact('hora', 'detalles', 'empleados'));
    }

    public function storeDetail(Request $request, $id)
    {
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id_empleados',
            'numero_horas' => 'required|integer|min:1',
            'costo_por_hora' => 'required|numeric|min:0'
        ]);

        try {
            $total = $request->numero_horas * $request->costo_por_hora;

            $existe = HorasTrabajoEmpleado::where('id_horas_trabajo', $id)
                                          ->where('id_empleado', $request->id_empleado)
                                          ->first();
            if ($existe) {
                $existe->numero_horas += $request->numero_horas;
                $existe->total = $existe->numero_horas * $existe->costo_por_hora;
                $existe->save();
            } else {
                HorasTrabajoEmpleado::create([
                    'id_horas_trabajo' => $id,
                    'id_empleado' => $request->id_empleado,
                    'numero_horas' => $request->numero_horas,
                    'costo_por_hora' => $request->costo_por_hora,
                    'total' => $total,
                    'activo' => 1
                ]);
            }

            return response()->json(['success' => true, 'mensaje' => 'Registro de horas del empleado guardado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar horas: ' . $e->getMessage()]);
        }
    }

    public function deleteDetail($id_horas, $id_detail)
    {
        try {
            $detalle = HorasTrabajoEmpleado::where('id_horas_trabajo_empleado', $id_detail)
                                           ->where('id_horas_trabajo', $id_horas)
                                           ->firstOrFail();
            $detalle->delete();
            return response()->json(['success' => true, 'mensaje' => 'Registro de horas removido correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al remover horas: ' . $e->getMessage()]);
        }
    }
}
