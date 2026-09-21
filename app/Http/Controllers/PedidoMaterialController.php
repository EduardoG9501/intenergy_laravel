<?php

namespace App\Http\Controllers;

use App\Models\PedidoMaterial;
use App\Models\PedidoMaterialDetalle;
use App\Models\OrdenTrabajo;
use App\Models\Articulo;
use App\Models\EstadoPedidoMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoMaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = PedidoMaterial::with(['orden', 'estadoPedido']);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('orden', function($q) use ($buscar) {
                $q->where('identificador', 'like', "%{$buscar}%");
            })->orWhere('observacion', 'like', "%{$buscar}%");
        }

        $pedidos = $query->orderBy('id_pedido_material', 'desc')->paginate(15);

        // Catalogos
        $ordenes = OrdenTrabajo::where('estado', 1)->get();
        $estados = EstadoPedidoMaterial::where('activo', 1)->get();

        return view('pedidos.index', compact('pedidos', 'ordenes', 'estados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_orden' => 'required|exists:ordenes_trabajo,id_orden',
            'fecha_solicitud' => 'required|date',
            'observacion' => 'nullable|max:1000'
        ]);

        try {
            $pedido = PedidoMaterial::create([
                'id_orden' => $request->id_orden,
                'fecha_solicitud' => $request->fecha_solicitud,
                'observacion' => $request->observacion,
                'id_estado_pedido_material' => 1 // Por defecto, Creado/Pendiente
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => 'Pedido de materiales creado correctamente.',
                'id' => $pedido->id_pedido_material
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al crear: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        // Si es una petición AJAX, devolver JSON para edición
        if (request()->ajax() || request()->wantsJson()) {
            $pedido = PedidoMaterial::findOrFail($id);
            return response()->json(['success' => true, 'pedido' => $pedido]);
        }

        // Si no es AJAX, mostrar la vista normal
        $pedido = PedidoMaterial::with(['orden', 'estadoPedido'])->findOrFail($id);
        $detalles = PedidoMaterialDetalle::with('producto')
                                         ->where('id_pedido_material', $id)
                                         ->get();

        $articulos = Articulo::where('estado', 1)->get();
        $estados = EstadoPedidoMaterial::where('activo', 1)->get();

        return view('pedidos.detalle', compact('pedido', 'detalles', 'articulos', 'estados'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_orden' => 'required|exists:ordenes_trabajo,id_orden',
            'fecha_solicitud' => 'required|date',
            'observacion' => 'nullable|max:1000',
            'id_estado_pedido_material' => 'required|exists:estado_pedido_materiales,id_estado_pedido_material'
        ]);

        try {
            $pedido = PedidoMaterial::findOrFail($id);
            $pedido->update([
                'id_orden' => $request->id_orden,
                'fecha_solicitud' => $request->fecha_solicitud,
                'observacion' => $request->observacion,
                'id_estado_pedido_material' => $request->id_estado_pedido_material
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Pedido actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function storeDetail(Request $request, $id)
    {
        $request->validate([
            'id_producto' => 'required|exists:articulos,id_producto',
            'cantidad' => 'required|numeric|min:0.01'
        ]);

        try {
            // Validar si ya existe el articulo en el pedido
            $existe = PedidoMaterialDetalle::where('id_pedido_material', $id)
                                            ->where('id_producto', $request->id_producto)
                                            ->first();
            if ($existe) {
                $existe->cantidad += $request->cantidad;
                $existe->save();
            } else {
                PedidoMaterialDetalle::create([
                    'id_pedido_material' => $id,
                    'id_producto' => $request->id_producto,
                    'cantidad' => $request->cantidad,
                    'Estado' => 1
                ]);
            }

            return response()->json(['success' => true, 'mensaje' => 'Artículo añadido correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al añadir artículo: ' . $e->getMessage()]);
        }
    }

    public function deleteDetail($id_pedido, $id_detail)
    {
        try {
            $detalle = PedidoMaterialDetalle::where('id_pedido_material_detalle', $id_detail)
                                            ->where('id_pedido_material', $id_pedido)
                                            ->firstOrFail();
            $detalle->delete();
            return response()->json(['success' => true, 'mensaje' => 'Artículo removido correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al remover artículo: ' . $e->getMessage()]);
        }
    }

    public function updateDetailCantidad(Request $request, $id_pedido, $id_detail)
    {
        $request->validate([
            'cantidad' => 'required|numeric|min:0.01'
        ]);

        try {
            $detalle = PedidoMaterialDetalle::where('id_pedido_material_detalle', $id_detail)
                                            ->where('id_pedido_material', $id_pedido)
                                            ->firstOrFail();

            $detalle->cantidad = $request->cantidad;
            $detalle->save();

            return response()->json(['success' => true, 'mensaje' => 'Cantidad actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar cantidad: ' . $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'id_estado_pedido_material' => 'required|exists:estado_pedido_materiales,id_estado_pedido_material'
        ]);

        try {
            $pedido = PedidoMaterial::findOrFail($id);
            $pedido->id_estado_pedido_material = $request->id_estado_pedido_material;
            $pedido->save();

            return response()->json(['success' => true, 'mensaje' => 'Estado del pedido actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar el estado: ' . $e->getMessage()]);
        }
    }
}
