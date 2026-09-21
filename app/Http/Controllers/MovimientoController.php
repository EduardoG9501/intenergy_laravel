<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\MovimientoDetalleTmp;
use App\Models\StockProducto;
use App\Models\StockProductoBodega;
use App\Models\TipoMovimiento;
use App\Models\SubTipoMovimiento;
use App\Models\Bodega;
use App\Models\Proveedor;
use App\Models\Articulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MovimientoController extends Controller
{
    public function index(Request $request)
    {
        $query = Movimiento::with(['bodega', 'proveedor', 'tipoMovimiento', 'subTipoMovimiento']);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('no_documento', 'like', "%{$buscar}%")
                  ->orWhere('observacion', 'like', "%{$buscar}%");
        }

        $movimientos = $query->orderBy('id_movimiento', 'desc')->paginate(15);

        // Catalogos para formulario de cabecera
        $bodegasPrincipales = Bodega::where('es_bodega_secundaria', 0)->where('estado', 1)->get();
        $tiposMovimiento = TipoMovimiento::where('estado', 1)->get();
        $proveedores = Proveedor::where('estado', 1)->get();

        return view('movimientos.index', compact('movimientos', 'bodegasPrincipales', 'tiposMovimiento', 'proveedores'));
    }

    public function getSubtypes($id_tipo)
    {
        $subtipos = SubTipoMovimiento::where('id_tipo_movimiento', $id_tipo)
                                    ->where('estado', 1)
                                    ->get();
        return response()->json([
            'success' => true,
            'subtipos' => $subtipos
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'id_bodega' => 'nullable|exists:bodegas,id_bodega',
            'id_tipo_movimiento' => 'required|exists:tipo_movimiento,id_tipo_movimiento',
            'id_sub_tipo_movimiento' => 'required|exists:sub_tipo_movimiento,id_sub_tipo_movimiento',
            'id_proveedor' => 'nullable|exists:proveedor,id_proveedor',
            'fechaDocumento' => 'required|date',
            'cant_dia_pago' => 'nullable|integer',
            'observacion' => 'nullable|max:1000',
        ];

        if ($request->id_sub_tipo_movimiento == 2 || $request->id_sub_tipo_movimiento == 3) {
            $rules['no_documento'] = 'nullable|max:200';
            $rules['id_forma_pago'] = 'nullable|integer';
        } else {
            $rules['no_documento'] = 'required|max:200';
            $rules['id_forma_pago'] = 'required|integer';
        }

        $request->validate($rules);

        // Validación de Factura Única Global para Entrada/Compras (subtipo 1)
        if ($request->id_sub_tipo_movimiento == 1 || ($request->id_tipo_movimiento == 1 && $request->id_sub_tipo_movimiento == 1)) {
            $noDoc = trim($request->no_documento);
            $facturaExiste = Movimiento::where('no_documento', $noDoc)
                                       ->where('id_sub_tipo_movimiento', 1)
                                       ->exists();
            if ($facturaExiste) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'El Número de Documento/Factura (' . $noDoc . ') ya se encuentra registrado en el sistema.'
                ]);
            }
        }

        try {
            $id_bodega = session('bodega_seleccionada') ?? $request->id_bodega;
            if (!$id_bodega) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No se ha seleccionado ninguna bodega activa en el sistema.'
                ]);
            }

            $no_doc = trim($request->no_documento);
            if (($request->id_sub_tipo_movimiento == 2 || $request->id_sub_tipo_movimiento == 3) && empty($no_doc)) {
                $prefix = ($request->id_sub_tipo_movimiento == 2) ? 'CARGO-' : 'DESCARGO-';
                
                $movimientosExistentes = Movimiento::where('id_sub_tipo_movimiento', $request->id_sub_tipo_movimiento)
                    ->where('no_documento', 'like', $prefix . '%')
                    ->pluck('no_documento');

                $maxNum = 0;
                foreach ($movimientosExistentes as $doc) {
                    if (preg_match('/' . preg_quote($prefix, '/') . '(\d+)/i', $doc, $matches)) {
                        $num = intval($matches[1]);
                        if ($num > $maxNum) {
                            $maxNum = $num;
                        }
                    }
                }

                if ($maxNum == 0) {
                    $totalCount = Movimiento::where('id_sub_tipo_movimiento', $request->id_sub_tipo_movimiento)->count();
                    $maxNum = $totalCount;
                }

                $nextNum = $maxNum + 1;
                $no_doc = $prefix . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
            }

            $forma_pago = $request->id_forma_pago;
            if (($request->id_sub_tipo_movimiento == 2 || $request->id_sub_tipo_movimiento == 3) && empty($forma_pago)) {
                $forma_pago = 1; // Contado
            }

            $ivaDefault = DB::table('iva')->where('estado', 1)->value('monto') ?? 15.00;

            $mov = Movimiento::create([
                'id_usuario' => Auth::id(),
                'id_bodega' => $id_bodega,
                'id_tipo_movimiento' => $request->id_tipo_movimiento,
                'id_sub_tipo_movimiento' => $request->id_sub_tipo_movimiento,
                'id_proveedor' => $request->id_proveedor,
                'no_documento' => $no_doc,
                'fechaDocumento' => $request->fechaDocumento,
                'id_forma_pago' => $forma_pago,
                'cant_dia_pago' => $request->cant_dia_pago ?? 0,
                'fechaPago' => $forma_pago == 2 ? now()->addDays($request->cant_dia_pago ?? 0)->toDateString() : $request->fechaDocumento,
                'fechaCaptura' => now()->toDateString(),
                'observacion' => $request->observacion,
                'estado' => 1,
                'guardarDefinitivo' => 0,
                'iva_mto' => 0,
                'desc_mto' => 0,
                'subtotal_mto' => 0,
                'total_mto' => 0,
                'iva_porc' => $ivaDefault, // IVA dinámico desde la tabla de base de datos (generalmente 15.00)
                'desc_porc' => 0.00
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => 'Cabecera de movimiento creada correctamente.',
                'id_movimiento' => $mov->id_movimiento
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al crear el movimiento: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $movimiento = Movimiento::with(['bodega', 'proveedor', 'tipoMovimiento', 'subTipoMovimiento'])->findOrFail($id);

        if ($movimiento->guardarDefinitivo == 1) {
            // Si ya es definitivo, mostramos los detalles fijos
            $detalles = MovimientoDetalle::with(['articulo', 'bodegaLugar'])
                                         ->where('id_movimiento', $id)
                                         ->get();
        } else {
            // Si está en borrador, mostramos los detalles temporales
            $detalles = MovimientoDetalleTmp::with(['articulo', 'bodegaLugar'])
                                            ->where('id_movimiento', $id)
                                            ->get();
        }

        $articulos = Articulo::where('estado', 1)->get();
        // Las bodegas secundarias son para el detalle del movimiento
        $bodegasSecundarias = Bodega::where('es_bodega_secundaria', 1)->where('estado', 1)->get();

        return view('movimientos.detalle', compact('movimiento', 'detalles', 'articulos', 'bodegasSecundarias'));
    }

    public function storeDetail(Request $request, $id)
    {
        $mov = Movimiento::findOrFail($id);
        if ($mov->guardarDefinitivo == 1) {
            return response()->json(['success' => false, 'mensaje' => 'Este movimiento ya fue guardado definitivamente y no se puede modificar.']);
        }

        // Si no se especifica la bodega de lugar (por ejemplo en CARGOS), asignar la bodega secundaria correspondiente o la primera activa
        if (empty($request->id_bodega_lugar)) {
            $bodegaLugar = Bodega::where('id_bodega_principal', $mov->id_bodega)
                                 ->orWhere('id_bodega', $mov->id_bodega)
                                 ->value('id_bodega');
            if (!$bodegaLugar) {
                $bodegaLugar = Bodega::where('es_bodega_secundaria', 1)->where('estado', 1)->value('id_bodega');
            }
            if (!$bodegaLugar) {
                $bodegaLugar = $mov->id_bodega;
            }
            $request->merge(['id_bodega_lugar' => $bodegaLugar]);
        }

        // Si no se especifica el precio (por estar oculto), asignar el precio registrado en el catálogo del artículo
        if ($request->precio === null || $request->precio === '') {
            $precioArticulo = Articulo::where('id_producto', $request->id_articulo)->value('precio') ?? 0;
            $request->merge(['precio' => $precioArticulo]);
        }

        $request->validate([
            'id_articulo' => 'required|exists:articulos,id_producto',
            'id_bodega_lugar' => 'required',
            'precio' => 'required|numeric|min:0',
            'cantidad' => 'required|numeric|min:0.01',
            'lote' => 'nullable|max:200'
        ]);

        // Validación de Stock si es SALIDA (tipo = 2)
        if ($mov->id_tipo_movimiento == 2) {
            $stockDisponible = StockProductoBodega::where('id_producto', $request->id_articulo)
                                                   ->where('id_bodega_principal', $request->id_bodega_lugar)
                                                   ->value('cantidad') ?? 0;
            if ($request->cantidad > $stockDisponible) {
                return response()->json([
                    'success' => false,
                    'mensaje' => "Stock insuficiente en la bodega seleccionada. Stock disponible: " . number_format($stockDisponible, 2)
                ]);
            }
        }

        try {
            DB::transaction(function() use ($request, $mov) {
                // Porcentajes: usar los del artículo si se proporcionan, si no los de la cabecera
                $ivaArticulo = $request->filled('iva_porc') ? floatval($request->iva_porc) : $mov->iva_porc;
                $descArticulo = $request->filled('desc_porc') ? floatval($request->desc_porc) : $mov->desc_porc;

                $porcIva = $ivaArticulo / 100;
                $porcDesc = $descArticulo / 100;

                $subtotal = $request->cantidad * $request->precio;
                $descuento = round($subtotal * $porcDesc, 4);
                $iva = round(($subtotal - $descuento) * $porcIva, 4);
                $total = round(($subtotal - $descuento) + $iva, 4);

                MovimientoDetalleTmp::create([
                    'id_movimiento' => $mov->id_movimiento,
                    'id_articulo' => $request->id_articulo,
                    'id_bodega_lugar' => $request->id_bodega_lugar,
                    'precio' => $request->precio,
                    'cantidad' => $request->cantidad,
                    'lote' => trim($request->lote),
                    'bonificacion' => 0,
                    'subtotal' => $subtotal,
                    'decuento' => $descuento,
                    'iva' => $iva,
                    'total' => $total,
                    'iva_porc' => $ivaArticulo,
                    'desc_porc' => $descArticulo
                ]);

                $this->recalcularCabecera($mov);
            });

            return response()->json(['success' => true, 'mensaje' => 'Artículo añadido al detalle.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al añadir artículo: ' . $e->getMessage()]);
        }
    }

    public function deleteDetail($id_mov, $id_detail)
    {
        $mov = Movimiento::findOrFail($id_mov);
        if ($mov->guardarDefinitivo == 1) {
            return response()->json(['success' => false, 'mensaje' => 'Este movimiento es definitivo y no se puede modificar.']);
        }

        try {
            DB::transaction(function() use ($id_detail, $mov) {
                $detail = MovimientoDetalleTmp::where('id_movimientos_detalle_tmp', $id_detail)
                                              ->where('id_movimiento', $mov->id_movimiento)
                                              ->firstOrFail();
                $detail->delete();

                $this->recalcularCabecera($mov);
            });

            return response()->json(['success' => true, 'mensaje' => 'Artículo eliminado del detalle.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al eliminar artículo: ' . $e->getMessage()]);
        }
    }

    public function updateDetailCantidad(Request $request, $id_mov, $id_detail)
    {
        $mov = Movimiento::findOrFail($id_mov);
        if ($mov->guardarDefinitivo == 1) {
            return response()->json(['success' => false, 'mensaje' => 'Este movimiento es definitivo y no se puede modificar.']);
        }

        $request->validate([
            'cantidad' => 'required|numeric|min:0.01'
        ]);

        try {
            DB::transaction(function() use ($request, $id_detail, $mov) {
                $detail = MovimientoDetalleTmp::where('id_movimientos_detalle_tmp', $id_detail)
                                              ->where('id_movimiento', $mov->id_movimiento)
                                              ->firstOrFail();

                // Si es salida de stock (tipo 2), verificar stock disponible
                if ($mov->id_tipo_movimiento == 2) {
                    $stockDisponible = StockProductoBodega::where('id_producto', $detail->id_articulo)
                                                           ->where('id_bodega_principal', $detail->id_bodega_lugar)
                                                           ->value('cantidad') ?? 0;
                    if ($request->cantidad > $stockDisponible) {
                        throw new \Exception("Stock insuficiente en la bodega seleccionada. Disponible: " . number_format($stockDisponible, 2));
                    }
                }

                // Usar los porcentajes propios del artículo (almacenados en el detalle)
                $porcIva = $detail->iva_porc / 100;
                $porcDesc = $detail->desc_porc / 100;

                $subtotal = $request->cantidad * $detail->precio;
                $descuento = round($subtotal * $porcDesc, 4);
                $iva = round(($subtotal - $descuento) * $porcIva, 4);
                $total = round(($subtotal - $descuento) + $iva, 4);

                $detail->update([
                    'cantidad' => $request->cantidad,
                    'subtotal' => $subtotal,
                    'decuento' => $descuento,
                    'iva' => $iva,
                    'total' => $total
                ]);

                $this->recalcularCabecera($mov);
            });

            return response()->json(['success' => true, 'mensaje' => 'Cantidad actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar cantidad: ' . $e->getMessage()]);
        }
    }

    public function guardarDefinitivo($id)
    {
        $mov = Movimiento::findOrFail($id);
        if ($mov->guardarDefinitivo == 1) {
            return response()->json(['success' => false, 'mensaje' => 'Este movimiento ya es definitivo.']);
        }

        $tmps = MovimientoDetalleTmp::where('id_movimiento', $id)->get();
        if ($tmps->isEmpty()) {
            return response()->json(['success' => false, 'mensaje' => 'Debe agregar al menos un artículo antes de guardar definitivamente.']);
        }

        try {
            DB::transaction(function() use ($mov, $tmps) {
                $fecha = now()->toDateString();

                // 1. Insertar detalle permanente
                foreach ($tmps as $tmp) {
                    $det = MovimientoDetalle::create([
                        'id_movimiento' => $mov->id_movimiento,
                        'id_articulo' => $tmp->id_articulo,
                        'id_bodega_lugar' => $tmp->id_bodega_lugar,
                        'precio' => $tmp->precio,
                        'cantidad' => $tmp->cantidad,
                        'lote' => $tmp->lote,
                        'bonificacion' => 0,
                        'subtotal' => $tmp->subtotal,
                        'decuento' => $tmp->decuento,
                        'iva' => $tmp->iva,
                        'total' => $tmp->total,
                        'iva_porc' => $tmp->iva_porc,
                        'desc_porc' => $tmp->desc_porc,
                        'estado' => 1
                    ]);

                    // 2. Insertar en stock_productos (esto dispara el trigger de actualización de stock_productos_bodega)
                    StockProducto::create([
                        'id_movimiento' => $mov->id_movimiento,
                        'id_tipo_movimiento' => $mov->id_tipo_movimiento,
                        'id_sub_tipo_movimiento' => $mov->id_sub_tipo_movimiento,
                        'no_documento' => $mov->no_documento,
                        'id_bodega_principal' => $mov->id_bodega,
                        'id_bodega_secundaria' => $tmp->id_bodega_lugar,
                        'tipo_movimiento' => $mov->id_tipo_movimiento == 1 ? 'ENTRADA' : 'SALIDA',
                        'sub_tipo_movimiento' => $mov->subTipoMovimiento->sub_tipo,
                        'id_movimiento_detalle' => $det->id_movimiento_detalle,
                        'id_producto' => $tmp->id_articulo,
                        'producto' => $tmp->articulo->nombre,
                        'lote' => $tmp->lote,
                        'precio' => $tmp->precio,
                        'cantidad' => $tmp->cantidad,
                        'subtotal' => $tmp->subtotal,
                        'decuento' => $tmp->decuento,
                        'iva' => $tmp->iva,
                        'total' => $tmp->total,
                        'stock' => $tmp->cantidad,
                        'id_stock_credito' => 0,
                        'estado' => 1,
                        'id_usuario' => Auth::id(),
                        'fecha_captura' => $fecha
                    ]);
                }

                // 3. Simular el SP: actualizar_cantidad_articulos_por_movimiento
                // Esto calcula la cantidad consolidada de stock de todos los movimientos de ese articulo y actualiza la tabla 'articulos'
                DB::statement("
                    UPDATE articulos a
                    JOIN (
                      SELECT sp.id_producto,
                        COALESCE(SUM(
                          CASE
                            WHEN sp.tipo_movimiento = 'ENTRADA' AND sp.cantidad > 0 THEN sp.cantidad
                            WHEN sp.tipo_movimiento = 'SALIDA' AND sp.cantidad > 0 THEN -sp.cantidad
                            ELSE 0
                          END
                        ), 0) AS stock_real
                      FROM stock_productos sp
                      WHERE sp.id_producto IN (
                        SELECT id_producto FROM stock_productos WHERE id_movimiento = ?
                      )
                      GROUP BY sp.id_producto
                    ) s ON a.id_producto = s.id_producto
                    SET a.cantidad = s.stock_real
                ", [$mov->id_movimiento]);

                // 4. Marcar cabecera como definitivo
                $mov->guardarDefinitivo = 1;
                $mov->save();

                // 5. Borrar temporales
                MovimientoDetalleTmp::where('id_movimiento', $mov->id_movimiento)->delete();
            });

            return response()->json(['success' => true, 'mensaje' => 'Movimiento guardado y procesado de forma definitiva con éxito.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar el movimiento definitivo: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $mov = Movimiento::findOrFail($id);
            if ($mov->guardarDefinitivo == 1) {
                return response()->json(['success' => false, 'mensaje' => 'No se pueden desactivar movimientos guardados definitivamente.']);
            }
            $mov->estado = 0;
            $mov->save();
            return response()->json(['success' => true, 'mensaje' => 'Movimiento borrador desactivado.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar: ' . $e->getMessage()]);
        }
    }

    private function recalcularCabecera(Movimiento $mov)
    {
        $totales = MovimientoDetalleTmp::where('id_movimiento', $mov->id_movimiento)
            ->selectRaw('SUM(subtotal) as sub, SUM(decuento) as desc_mto, SUM(iva) as iva, SUM(total) as tot')
            ->first();

        $mov->subtotal_mto = round($totales->sub ?? 0, 4);
        $mov->desc_mto = round($totales->desc_mto ?? 0, 4);
        $mov->iva_mto = round($totales->iva ?? 0, 4);
        $mov->total_mto = round($totales->tot ?? 0, 4);
        $mov->save();
    }

    public function updateIvaDescuento(Request $request, $id)
    {
        $mov = Movimiento::findOrFail($id);
        if ($mov->guardarDefinitivo == 1) {
            return response()->json(['success' => false, 'mensaje' => 'Este movimiento es definitivo y no se puede modificar.']);
        }

        $request->validate([
            'iva_porc' => 'required|numeric|min:0|max:100',
            'desc_porc' => 'required|numeric|min:0|max:100',
        ]);

        try {
            DB::transaction(function() use ($request, $mov) {
                $mov->iva_porc = $request->iva_porc;
                $mov->desc_porc = $request->desc_porc;
                $mov->save();

                // Recalcular los totales de cada detalle usando los porcentajes propios de cada artículo
                $detalles = MovimientoDetalleTmp::where('id_movimiento', $mov->id_movimiento)->get();
                foreach ($detalles as $det) {
                    $porcIva = $det->iva_porc / 100;
                    $porcDesc = $det->desc_porc / 100;

                    $subtotal = $det->cantidad * $det->precio;
                    $descuento = round($subtotal * $porcDesc, 4);
                    $iva = round(($subtotal - $descuento) * $porcIva, 4);
                    $total = round(($subtotal - $descuento) + $iva, 4);

                    $det->update([
                        'subtotal' => $subtotal,
                        'decuento' => $descuento,
                        'iva' => $iva,
                        'total' => $total
                    ]);
                }

                $this->recalcularCabecera($mov);
            });

            return response()->json(['success' => true, 'mensaje' => 'Porcentajes de IVA y Descuento actualizados correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar porcentajes: ' . $e->getMessage()]);
        }
    }

    public function selectBodegaActiva(Request $request)
    {
        $request->validate([
            'id_bodega' => 'required|exists:bodegas,id_bodega'
        ]);

        session(['bodega_seleccionada' => $request->id_bodega]);

        return response()->json(['success' => true]);
    }
}
