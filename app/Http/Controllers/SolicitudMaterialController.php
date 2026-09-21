<?php

namespace App\Http\Controllers;

use App\Models\SolicitudMaterial;
use App\Models\SolicitudMaterialDetalle;
use App\Models\OrdenTrabajo;
use App\Models\Bodega;
use App\Models\Empleado;
use App\Models\Articulo;
use App\Models\EstadoSolicitudMaterial;
use Illuminate\Http\Request;

class SolicitudMaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = SolicitudMaterial::with(['orden', 'bodegaPrincipal', 'bodegaSecundaria', 'responsable', 'estadoSolicitud']);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('orden', function($q) use ($buscar) {
                $q->where('identificador', 'like', "%{$buscar}%");
            })->orWhere('observacion', 'like', "%{$buscar}%");
        }

        $solicitudes = $query->orderBy('id_solicitud_material', 'desc')->paginate(15);

        // Catalogos
        $ordenes = OrdenTrabajo::where('estado', 1)->get();
        $bodegasPrincipales = Bodega::where('es_bodega_secundaria', 0)->where('estado', 1)->get();
        $bodegasSecundarias = Bodega::where('es_bodega_secundaria', 1)->where('estado', 1)->get();
        $empleados = Empleado::where('estado', 1)->get();
        $estados = EstadoSolicitudMaterial::where('activo', 1)->get();

        return view('solicitudes.index', compact('solicitudes', 'ordenes', 'bodegasPrincipales', 'bodegasSecundarias', 'empleados', 'estados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_orden' => 'required|exists:ordenes_trabajo,id_orden',
            'fecha_solicitud' => 'required|date',
            'id_bodega_principal' => 'required|exists:bodegas,id_bodega',
            'id_bodega_secundaria' => 'required|exists:bodegas,id_bodega',
            'id_responsable_obra' => 'required|exists:empleados,id_empleados',
            'id_entrega_articulos' => 'required|exists:empleados,id_empleados',
            'id_recibe_articulos' => 'required|exists:empleados,id_empleados',
            'observacion' => 'nullable|max:1000'
        ]);

        try {
            $solicitud = SolicitudMaterial::create([
                'id_orden' => $request->id_orden,
                'fecha_solicitud' => $request->fecha_solicitud,
                'id_bodega_principal' => $request->id_bodega_principal,
                'id_bodega_secundaria' => $request->id_bodega_secundaria,
                'id_responsable_obra' => $request->id_responsable_obra,
                'id_entrega_articulos' => $request->id_entrega_articulos,
                'id_recibe_articulos' => $request->id_recibe_articulos,
                'observacion' => $request->observacion,
                'id_estado_solicitud' => 1 // Por defecto, Creada/Pendiente
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => 'Solicitud de materiales creada correctamente.',
                'id' => $solicitud->id_solicitud_material
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al crear: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $solicitud = SolicitudMaterial::with(['orden', 'bodegaPrincipal', 'bodegaSecundaria', 'responsable', 'entrega', 'recibe', 'estadoSolicitud'])->findOrFail($id);
        $detalles = SolicitudMaterialDetalle::with('producto')
                                            ->where('id_solicitud_material', $id)
                                            ->get();

        $articulos = Articulo::where('estado', 1)->get();
        $estados = EstadoSolicitudMaterial::where('activo', 1)->get();

        return view('solicitudes.detalle', compact('solicitud', 'detalles', 'articulos', 'estados'));
    }

    public function storeDetail(Request $request, $id)
    {
        $request->validate([
            'id_producto' => 'required|exists:articulos,id_producto',
            'cantidad' => 'required|numeric|min:0.01'
        ]);

        try {
            $existe = SolicitudMaterialDetalle::where('id_solicitud_material', $id)
                                              ->where('id_producto', $request->id_producto)
                                              ->first();
            if ($existe) {
                $existe->cantidad += $request->cantidad;
                $existe->save();
            } else {
                SolicitudMaterialDetalle::create([
                    'id_solicitud_material' => $id,
                    'id_producto' => $request->id_producto,
                    'cantidad' => $request->cantidad,
                    'id_detalle_articulo' => 1
                ]);
            }

            return response()->json(['success' => true, 'mensaje' => 'Artículo añadido correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al añadir artículo: ' . $e->getMessage()]);
        }
    }

    public function deleteDetail($id_solicitud, $id_detail)
    {
        try {
            $detalle = SolicitudMaterialDetalle::where('id_solicitud_material_detalle', $id_detail)
                                               ->where('id_solicitud_material', $id_solicitud)
                                               ->firstOrFail();
            $detalle->delete();
            return response()->json(['success' => true, 'mensaje' => 'Artículo removido correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al remover artículo: ' . $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'id_estado_solicitud' => 'required|exists:estado_solicitud_materiales,id_estado_solicitud'
        ]);

        try {
            $solicitud = SolicitudMaterial::findOrFail($id);
            $solicitud->id_estado_solicitud = $request->id_estado_solicitud;
            $solicitud->save();

            return response()->json(['success' => true, 'mensaje' => 'Estado de la solicitud actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar el estado: ' . $e->getMessage()]);
        }
    }
}
