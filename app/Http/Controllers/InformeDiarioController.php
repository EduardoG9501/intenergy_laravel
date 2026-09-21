<?php

namespace App\Http\Controllers;

use App\Models\InformeDiarioEjecucion;
use App\Models\InformeDiarioEmpleado;
use App\Models\InformeDiarioArticulo;
use App\Models\InformeDiarioDetalle;
use App\Models\InformeDiarioImagen;
use App\Models\EjecucionObra;
use App\Models\Empleado;
use App\Models\Articulo;
use App\Models\Bodega;
use Illuminate\Http\Request;

class InformeDiarioController extends Controller
{
    public function index(Request $request)
    {
        $query = InformeDiarioEjecucion::with(['ejecucion.orden.obra']);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('descripcion', 'like', "%{$buscar}%")
                  ->orWhere('observacion', 'like', "%{$buscar}%")
                  ->orWhere('lugar', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('id_ejecucion_obra')) {
            $query->where('id_ejecucion_obra', $request->id_ejecucion_obra);
        }

        $informes = $query->orderBy('fecha', 'desc')
                          ->orderBy('id_informe_diario_ejecucion', 'desc')
                          ->paginate(15);

        $ejecuciones = EjecucionObra::with(['orden.obra'])
                                    ->where('id_estado_ejecucion_obra', '!=', 3)
                                    ->orderBy('id_ejecucion_obra', 'desc')
                                    ->get();

        return view('informes-diarios.index', compact('informes', 'ejecuciones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'descripcion' => 'nullable|max:1000',
            'observacion' => 'nullable|max:1000'
        ]);

        try {
            $bodega = Bodega::find(session('bodega_seleccionada'));

            $informe = InformeDiarioEjecucion::create([
                'id_ejecucion_obra' => $request->id_ejecucion_obra ?? null,
                'fecha' => $request->fecha,
                'descripcion' => $request->descripcion,
                'observacion' => $request->observacion,
                'lugar' => $bodega ? $bodega->nombreBodega : null,
                'estado' => 1
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => 'Informe diario creado correctamente.',
                'id' => $informe->id_informe_diario_ejecucion
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al crear informe: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $informe = InformeDiarioEjecucion::with([
                                            'ejecucion.orden.obra',
                                            'empleados.empleado',
                                            'articulos.producto',
                                            'imagenes',
                                            'detalles'
                                        ])->findOrFail($id);

        $empleados = Empleado::where('estado', 1)->get();
        $articulos = Articulo::where('estado', 1)->get();

        return view('informes-diarios.detalle', compact('informe', 'empleados', 'articulos'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fecha' => 'required|date',
            'descripcion' => 'nullable|max:1000',
            'observacion' => 'nullable|max:1000',
            'lugar' => 'nullable|max:255'
        ]);

        try {
            $informe = InformeDiarioEjecucion::findOrFail($id);

            $informe->update([
                'fecha' => $request->fecha,
                'descripcion' => $request->descripcion,
                'observacion' => $request->observacion,
                'lugar' => $request->lugar
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Informe actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar informe: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $informe = InformeDiarioEjecucion::findOrFail($id);

            InformeDiarioImagen::where('id_informe_diario_ejecucion', $id)->delete();

            $informe->delete();

            return response()->json(['success' => true, 'mensaje' => 'Informe eliminado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al eliminar informe: ' . $e->getMessage()]);
        }
    }

    public function storeEmpleado(Request $request, $id)
    {
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id_empleados'
        ]);

        try {
            InformeDiarioEmpleado::create([
                'id_informe_diario_ejecucion' => $id,
                'id_empleado' => $request->id_empleado,
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Empleado agregado al informe.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al agregar empleado: ' . $e->getMessage()]);
        }
    }

    public function deleteEmpleado($id, $id_empleado)
    {
        try {
            InformeDiarioEmpleado::where('id_informe_diario_empleado', $id_empleado)
                                 ->where('id_informe_diario_ejecucion', $id)
                                 ->delete();

            return response()->json(['success' => true, 'mensaje' => 'Empleado removido del informe.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al remover empleado: ' . $e->getMessage()]);
        }
    }

    public function storeArticulo(Request $request, $id)
    {
        $request->validate([
            'id_producto' => 'required|exists:articulos,id_producto',
            'cantidad' => 'required|numeric|min:0.01',
            'lote' => 'nullable|max:100'
        ]);

        try {
            InformeDiarioArticulo::create([
                'id_informe_diario_ejecucion' => $id,
                'id_producto' => $request->id_producto,
                'cantidad' => $request->cantidad,
                'lote' => $request->lote,
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Artículo agregado al informe.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al agregar artículo: ' . $e->getMessage()]);
        }
    }

    public function deleteArticulo($id, $id_articulo)
    {
        try {
            InformeDiarioArticulo::where('id_informe_diario_articulo', $id_articulo)
                                 ->where('id_informe_diario_ejecucion', $id)
                                 ->delete();

            return response()->json(['success' => true, 'mensaje' => 'Artículo removido del informe.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al remover artículo: ' . $e->getMessage()]);
        }
    }

    public function updateArticulo(Request $request, $id, $id_articulo)
    {
        $request->validate([
            'cantidad' => 'required|numeric|min:0.01'
        ]);

        try {
            $articulo = InformeDiarioArticulo::where('id_informe_diario_articulo', $id_articulo)
                                             ->where('id_informe_diario_ejecucion', $id)
                                             ->firstOrFail();
            $articulo->cantidad = $request->cantidad;
            $articulo->save();

            return response()->json(['success' => true, 'mensaje' => 'Cantidad actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar cantidad: ' . $e->getMessage()]);
        }
    }

    public function storeDescripcion(Request $request, $id)
    {
        $request->validate([
            'descripcion' => 'required|max:2000'
        ]);

        try {
            InformeDiarioDetalle::create([
                'id_informe_diario_ejecucion' => $id,
                'descripcion' => $request->descripcion,
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Descripción agregada al informe.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al agregar descripción: ' . $e->getMessage()]);
        }
    }

    public function deleteDescripcion($id, $id_descripcion)
    {
        try {
            InformeDiarioDetalle::where('id_informe_diario_detalle', $id_descripcion)
                                ->where('id_informe_diario_ejecucion', $id)
                                ->delete();

            return response()->json(['success' => true, 'mensaje' => 'Descripción eliminada del informe.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al eliminar descripción: ' . $e->getMessage()]);
        }
    }

    public function storeImagen(Request $request, $id)
    {
        $request->validate([
            'imagen' => 'required|image|max:5120',
            'descripcion' => 'nullable|max:500'
        ]);

        try {
            $archivo = $request->file('imagen');
            $nombre = 'informe_' . $id . '_' . time() . '.' . $archivo->getClientOriginalExtension();
            $imageData = base64_encode(file_get_contents($archivo->getRealPath()));
            $base64 = 'data:' . $archivo->getMimeType() . ';base64,' . $imageData;

            InformeDiarioImagen::create([
                'id_informe_diario_ejecucion' => $id,
                'ruta_imagen' => $base64,
                'descripcion' => $request->descripcion,
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Imagen subida correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al subir imagen: ' . $e->getMessage()]);
        }
    }

    public function deleteImagen($id, $id_imagen)
    {
        try {
            $img = InformeDiarioImagen::where('id_informe_diario_imagen', $id_imagen)
                                       ->where('id_informe_diario_ejecucion', $id)
                                       ->firstOrFail();

            $img->delete();

            return response()->json(['success' => true, 'mensaje' => 'Imagen eliminada del informe.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al eliminar imagen: ' . $e->getMessage()]);
        }
    }
}
