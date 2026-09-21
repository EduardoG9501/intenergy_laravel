<?php

namespace App\Http\Controllers;

use App\Models\EjecucionObra;
use App\Models\EjecucionObraDetalle;
use App\Models\HorasTrabajoDiarioDetalle;
use App\Models\OrdenTrabajo;
use App\Models\Articulo;
use App\Models\Bodega;
use App\Models\Empleado;
use App\Models\EstadoEjecucionObra;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\StockProducto;
use App\Models\StockProductoBodega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EjecucionObraController extends Controller
{
    public function index(Request $request)
    {
        $query = EjecucionObra::with(['orden.proyecto', 'orden.obra', 'estadoEjecucion']);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('orden', function($q) use ($buscar) {
                $q->where('identificador', 'like', "%{$buscar}%");
            })->orWhere('observacion', 'like', "%{$buscar}%");
        }

        $ejecuciones = $query->orderBy('id_ejecucion_obra', 'desc')->paginate(15);

        // Catalogos
        $ordenes = OrdenTrabajo::where('estado', 1)->get();
        $estados = EstadoEjecucionObra::where('activo', 1)->get();

        return view('ejecuciones.index', compact('ejecuciones', 'ordenes', 'estados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_orden' => 'required|exists:ordenes_trabajo,id_orden',
            'fecha_solicitud' => 'required|date',
            'feriado' => 'required|boolean',
            'observacion' => 'nullable|max:1000'
        ]);

        try {
            $ejec = EjecucionObra::create([
                'id_orden' => $request->id_orden,
                'fecha_solicitud' => $request->fecha_solicitud,
                'feriado' => $request->feriado,
                'observacion' => $request->observacion,
                'id_estado_ejecucion_obra' => 1 // Pendiente
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => 'Ejecución de obra creada correctamente.',
                'id' => $ejec->id_ejecucion_obra
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al crear: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $ejec = EjecucionObra::findOrFail($id);

        if ($ejec->id_estado_ejecucion_obra == 2) {
            return response()->json(['success' => false, 'mensaje' => 'No se puede editar una ejecución que ya está TERMINADA.']);
        }

        $request->validate([
            'fecha_solicitud' => 'required|date',
            'feriado' => 'required|boolean',
            'observacion' => 'nullable|max:1000',
            'id_estado_ejecucion_obra' => 'required|exists:estado_ejecucion_obra,id_estado_ejecucion_obra'
        ]);

        try {
            $ejec->update([
                'fecha_solicitud' => $request->fecha_solicitud,
                'feriado' => $request->feriado,
                'observacion' => $request->observacion,
                'id_estado_ejecucion_obra' => $request->id_estado_ejecucion_obra
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Ejecución actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $ejecucion = EjecucionObra::with(['orden.proyecto', 'orden.obra', 'estadoEjecucion'])->findOrFail($id);

        // Materiales consolidados por producto
        $detallesRaw = EjecucionObraDetalle::with(['producto', 'bodegaLugar'])
                                           ->where('id_ejecucion_obra', $id)
                                           ->get();
        $detalles = $detallesRaw->groupBy('id_producto')->map(function($items, $id_producto) {
            $first = $items->first();
            return (object) [
                'id_producto' => $id_producto,
                'producto' => $first->producto,
                'bodegaLugar' => $first->bodegaLugar,
                'cantidad' => $items->sum('cantidad'),
                'Contabilizado' => $items->every('Contabilizado', 1),
                'ids' => $items->pluck('id_ejecucion_obra_detalle_materiales_utilizar')->toArray(),
                'all_contabilizado' => $items->every('Contabilizado', 1),
                'any_contabilizado' => $items->contains('Contabilizado', 1),
            ];
        })->values();

        $horasDiarias = HorasTrabajoDiarioDetalle::with('empleado')
                                                ->where('id_ejecucion_obra', $id)
                                                ->orderBy('hora_entrada', 'asc')
                                                ->get();

        $informesDiarios = \App\Models\InformeDiarioEjecucion::with(['empleados.empleado', 'articulos.producto', 'imagenes', 'detalles'])
                                                             ->where('id_ejecucion_obra', $id)
                                                             ->orderBy('fecha', 'desc')
                                                             ->get();

        $articulos = Articulo::where('estado', 1)->get();
        $empleados = Empleado::where('estado', 1)->get();
        $estados = EstadoEjecucionObra::where('activo', 1)->get();
        $bodegaActiva = Bodega::find(session('bodega_seleccionada'));

        return view('ejecuciones.detalle', compact('ejecucion', 'detalles', 'horasDiarias', 'informesDiarios', 'articulos', 'empleados', 'estados', 'bodegaActiva'));
    }

    public function storeDetail(Request $request, $id)
    {
        $ejec = EjecucionObra::findOrFail($id);
        if ($ejec->id_estado_ejecucion_obra == 2) {
            return response()->json(['success' => false, 'mensaje' => 'No se pueden agregar materiales a una ejecución TERMINADA.']);
        }

        $request->validate([
            'id_producto' => 'required|exists:articulos,id_producto',
            'cantidad' => 'required|numeric|min:0.01'
        ]);

        $idBodega = session('bodega_seleccionada');
        if (!$idBodega) {
            return response()->json(['success' => false, 'mensaje' => 'No hay bodega activa seleccionada en el sistema.']);
        }

        // Validar Stock
        $stockDisponible = StockProductoBodega::where('id_producto', $request->id_producto)
                                               ->where('id_bodega_principal', $idBodega)
                                               ->value('cantidad') ?? 0;
        if ($request->cantidad > $stockDisponible) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Stock insuficiente en la bodega seleccionada. Disponible: ' . number_format($stockDisponible, 2)
            ]);
        }

        try {
            EjecucionObraDetalle::create([
                'id_ejecucion_obra' => $id,
                'id_producto' => $request->id_producto,
                'cantidad' => $request->cantidad,
                'id_bodega_lugar' => $idBodega,
                'Contabilizado' => 0,
                'Estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Material añadido correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al añadir material: ' . $e->getMessage()]);
        }
    }

    public function deleteDetail($id_ejec, $id_detail)
    {
        try {
            $det = EjecucionObraDetalle::where('id_ejecucion_obra_detalle_materiales_utilizar', $id_detail)
                                       ->where('id_ejecucion_obra', $id_ejec)
                                       ->firstOrFail();
            if ($det->Contabilizado == 1) {
                return response()->json(['success' => false, 'mensaje' => 'No se pueden eliminar detalles que ya fueron contabilizados en stock.']);
            }
            $det->delete();
            return response()->json(['success' => true, 'mensaje' => 'Material removido correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al remover material: ' . $e->getMessage()]);
        }
    }

    public function updateDetailCantidad(Request $request, $id_ejec, $id_producto)
    {
        $request->validate([
            'cantidad' => 'required|numeric|min:0.01'
        ]);

        try {
            $detalles = EjecucionObraDetalle::where('id_ejecucion_obra', $id_ejec)
                                            ->where('id_producto', $id_producto)
                                            ->where('Contabilizado', 0)
                                            ->get();

            if ($detalles->isEmpty()) {
                return response()->json(['success' => false, 'mensaje' => 'No se encontraron detalles pendientes para este producto.']);
            }

            // Validar stock para la nueva cantidad total
            $nuevaCantidad = $request->cantidad;
            $bodega = $detalles->first()->id_bodega_lugar;
            $stockDisponible = StockProductoBodega::where('id_producto', $id_producto)
                                                   ->where('id_bodega_principal', $bodega)
                                                   ->value('cantidad') ?? 0;

            if ($nuevaCantidad > $stockDisponible) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Stock insuficiente. Disponible: ' . number_format($stockDisponible, 2)
                ]);
            }

            // Eliminar registros pendientes y crear uno nuevo con la cantidad consolidada
            $detalles->each->delete();

            EjecucionObraDetalle::create([
                'id_ejecucion_obra' => $id_ejec,
                'id_producto' => $id_producto,
                'cantidad' => $nuevaCantidad,
                'id_bodega_lugar' => $bodega,
                'Contabilizado' => 0,
                'Estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Cantidad actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar cantidad: ' . $e->getMessage()]);
        }
    }

    public function deleteDetailByProducto($id_ejec, $id_producto)
    {
        try {
            EjecucionObraDetalle::where('id_ejecucion_obra', $id_ejec)
                                ->where('id_producto', $id_producto)
                                ->where('Contabilizado', 0)
                                ->delete();
            return response()->json(['success' => true, 'mensaje' => 'Material removido correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al remover material: ' . $e->getMessage()]);
        }
    }

    public function storeDiario(Request $request, $id)
    {
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id_empleados',
            'hora_entrada' => 'required',
            'hora_salida' => 'required',
        ]);

        $ejec = EjecucionObra::findOrFail($id);
        $res = $this->calcularHorasTrabajo($request->hora_entrada, $request->hora_salida, $ejec->feriado, $ejec->fecha_solicitud);

        try {
            HorasTrabajoDiarioDetalle::create([
                'id_ejecucion_obra' => $id,
                'id_empleado' => $request->id_empleado,
                'hora_entrada' => $request->hora_entrada,
                'hora_salida' => $request->hora_salida,
                'cantidad_horas_normal' => $res['horas_normales'],
                'cantidad_horas_extra' => $res['horas_extras'],
                'cantidad_horas_extraordinaria' => $res['horas_extraordinarias'],
                'fecha_registro' => now()
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Horas de trabajo registradas correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al registrar horas: ' . $e->getMessage()]);
        }
    }

    public function deleteDiario($id_ejec, $id_diario)
    {
        try {
            $ht = HorasTrabajoDiarioDetalle::where('id_horas_trabajo_diario_detalle', $id_diario)
                                           ->where('id_ejecucion_obra', $id_ejec)
                                           ->firstOrFail();
            $ht->delete();
            return response()->json(['success' => true, 'mensaje' => 'Registro de horas eliminado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al eliminar registro: ' . $e->getMessage()]);
        }
    }

    public function updateDiario(Request $request, $id_ejec, $id_diario)
    {
        $request->validate([
            'hora_entrada' => 'required',
            'hora_salida' => 'required',
        ]);

        try {
            $ht = HorasTrabajoDiarioDetalle::where('id_horas_trabajo_diario_detalle', $id_diario)
                                           ->where('id_ejecucion_obra', $id_ejec)
                                           ->firstOrFail();

            $ejec = EjecucionObra::findOrFail($id_ejec);
            $res = $this->calcularHorasTrabajo($request->hora_entrada, $request->hora_salida, $ejec->feriado, $ejec->fecha_solicitud);

            $ht->update([
                'hora_entrada' => $request->hora_entrada,
                'hora_salida' => $request->hora_salida,
                'cantidad_horas_normal' => $res['horas_normales'],
                'cantidad_horas_extra' => $res['horas_extras'],
                'cantidad_horas_extraordinaria' => $res['horas_extraordinarias'],
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Horas de trabajo actualizadas correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar horas: ' . $e->getMessage()]);
        }
    }

    public function contabilizar($id)
    {
        $ejec = EjecucionObra::findOrFail($id);
        $detalles = EjecucionObraDetalle::with('producto')
                                         ->where('id_ejecucion_obra', $id)
                                         ->where('Contabilizado', 0)
                                         ->where('Estado', 1)
                                         ->get();

        if ($detalles->isEmpty()) {
            return response()->json(['success' => false, 'mensaje' => 'No hay materiales pendientes de contabilizar.']);
        }

        // Validar Stock de todos los items
        foreach ($detalles as $det) {
            $stock = StockProductoBodega::where('id_producto', $det->id_producto)
                                         ->where('id_bodega_principal', $det->id_bodega_lugar)
                                         ->value('cantidad') ?? 0;
            if ($stock < $det->cantidad) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Stock insuficiente para: ' . $det->producto->nombre . ' (Disponible: ' . number_format($stock, 2) . ')'
                ]);
            }
        }

        try {
            DB::transaction(function() use ($ejec, $detalles) {
                $id_usuario = Auth::id();
                $fecha = now()->toDateString();

                // Agrupar detalles por bodega y generar un movimiento por cada bodega
                $detallesPorBodega = $detalles->groupBy('id_bodega_lugar');

                foreach ($detallesPorBodega as $id_bodega_lugar => $items) {
                    // Crear Movimiento de Salida (Descargo)
                    $mov = Movimiento::create([
                        'id_tipo_movimiento' => 2, // SALIDA
                        'id_sub_tipo_movimiento' => 3, // DESCARGO
                        'id_bodega' => $id_bodega_lugar,
                        'no_documento' => 'EJEC-' . $ejec->id_ejecucion_obra . '-BOD-' . $id_bodega_lugar,
                        'fechaDocumento' => $fecha,
                        'id_forma_pago' => 1,
                        'cant_dia_pago' => 0,
                        'fechaPago' => $fecha,
                        'fechaCaptura' => $fecha,
                        'observacion' => 'MOVIMIENTO AUTOMATICO DE EJECUCION DE OBRA #' . $ejec->id_ejecucion_obra . ' BODEGA ' . $id_bodega_lugar,
                        'estado' => 1,
                        'guardarDefinitivo' => 1,
                        'iva_mto' => 0,
                        'desc_mto' => 0,
                        'subtotal_mto' => 0,
                        'total_mto' => 0,
                        'iva_porc' => 0,
                        'desc_porc' => 0,
                        'id_usuario' => $id_usuario
                    ]);

                    $subtotal_mto = 0;

                    foreach ($items as $det) {
                        $precio = $det->producto->precio;
                        $subtotal = $det->cantidad * $precio;
                        $subtotal_mto += $subtotal;

                        $movDet = MovimientoDetalle::create([
                            'id_movimiento' => $mov->id_movimiento,
                            'id_articulo' => $det->id_producto,
                            'id_bodega_lugar' => $id_bodega_lugar,
                            'precio' => $precio,
                            'cantidad' => $det->cantidad,
                            'lote' => null,
                            'bonificacion' => null,
                            'subtotal' => $subtotal,
                            'decuento' => 0,
                            'iva' => 0,
                            'total' => $subtotal,
                            'iva_porc' => 0,
                            'desc_porc' => 0,
                            'estado' => 1
                        ]);

                        // Registrar en stock_productos
                        StockProducto::create([
                            'id_movimiento' => $mov->id_movimiento,
                            'id_tipo_movimiento' => 2,
                            'id_sub_tipo_movimiento' => 3,
                            'no_documento' => $mov->no_documento,
                            'id_bodega_principal' => $id_bodega_lugar,
                            'id_bodega_secundaria' => $id_bodega_lugar,
                            'tipo_movimiento' => 'SALIDA',
                            'sub_tipo_movimiento' => 'DESCARGO',
                            'id_movimiento_detalle' => $movDet->id_movimiento_detalle,
                            'id_producto' => $det->id_producto,
                            'producto' => $det->producto->nombre,
                            'lote' => null,
                            'precio' => $precio,
                            'cantidad' => $det->cantidad,
                            'subtotal' => $subtotal,
                            'decuento' => 0,
                            'iva' => 0,
                            'total' => $subtotal,
                            'stock' => $det->cantidad,
                            'id_stock_credito' => 0,
                            'estado' => 1,
                            'id_usuario' => $id_usuario,
                            'fecha_captura' => $fecha
                        ]);
                    }

                    // Actualizar totales de movimiento
                    $mov->subtotal_mto = $subtotal_mto;
                    $mov->total_mto = $subtotal_mto;
                    $mov->save();

                    // Simular el procedimiento de actualización de articulos
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
                }

                // Marcar detalles de ejecución como contabilizados
                EjecucionObraDetalle::where('id_ejecucion_obra', $ejec->id_ejecucion_obra)
                                     ->where('Contabilizado', 0)
                                     ->update(['Contabilizado' => 1]);
            });

            return response()->json(['success' => true, 'mensaje' => 'Stock descargado de bodega correctamente. Solo se descontaron los items nuevos.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al contabilizar: ' . $e->getMessage()]);
        }
    }

    public function storeInformeDiario(Request $request, $id)
    {
        $request->validate([
            'fecha' => 'required|date',
            'descripcion' => 'nullable|max:1000',
            'observacion' => 'nullable|max:1000'
        ]);

        try {
            $bodega = Bodega::find(session('bodega_seleccionada'));

            $informe = \App\Models\InformeDiarioEjecucion::create([
                'id_ejecucion_obra' => $id,
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

    public function updateInformeDiario(Request $request, $id, $id_informe)
    {
        $request->validate([
            'fecha' => 'required|date',
            'descripcion' => 'nullable|max:1000',
            'observacion' => 'nullable|max:1000',
            'lugar' => 'nullable|max:255'
        ]);

        try {
            $informe = \App\Models\InformeDiarioEjecucion::where('id_informe_diario_ejecucion', $id_informe)
                                                         ->where('id_ejecucion_obra', $id)
                                                         ->firstOrFail();
            
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

    public function deleteInformeDiario($id, $id_informe)
    {
        try {
            $informe = \App\Models\InformeDiarioEjecucion::where('id_informe_diario_ejecucion', $id_informe)
                                                         ->where('id_ejecucion_obra', $id)
                                                         ->firstOrFail();
            $informe->delete();
            return response()->json(['success' => true, 'mensaje' => 'Informe eliminado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al eliminar informe: ' . $e->getMessage()]);
        }
    }

    // Métodos para empleados del informe
    public function storeInformeEmpleado(Request $request, $id, $id_informe)
    {
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id_empleados'
        ]);

        try {
            \App\Models\InformeDiarioEmpleado::create([
                'id_informe_diario_ejecucion' => $id_informe,
                'id_empleado' => $request->id_empleado,
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Empleado agregado al informe.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function deleteInformeEmpleado($id, $id_informe, $id_empleado)
    {
        try {
            \App\Models\InformeDiarioEmpleado::where('id_informe_diario_empleado', $id_empleado)
                                             ->where('id_informe_diario_ejecucion', $id_informe)
                                             ->delete();
            return response()->json(['success' => true, 'mensaje' => 'Empleado removido.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    // Métodos para artículos del informe
    public function storeInformeArticulo(Request $request, $id, $id_informe)
    {
        $request->validate([
            'id_producto' => 'required|exists:articulos,id_producto',
            'cantidad' => 'required|numeric|min:0.01',
            'lote' => 'nullable|max:100'
        ]);

        try {
            \App\Models\InformeDiarioArticulo::create([
                'id_informe_diario_ejecucion' => $id_informe,
                'id_producto' => $request->id_producto,
                'cantidad' => $request->cantidad,
                'lote' => $request->lote,
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Artículo agregado al informe.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function deleteInformeArticulo($id, $id_informe, $id_articulo)
    {
        try {
            \App\Models\InformeDiarioArticulo::where('id_informe_diario_articulo', $id_articulo)
                                             ->where('id_informe_diario_ejecucion', $id_informe)
                                             ->delete();
            return response()->json(['success' => true, 'mensaje' => 'Artículo removido.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function updateInformeArticulo(Request $request, $id, $id_informe, $id_articulo)
    {
        $request->validate([
            'cantidad' => 'required|numeric|min:0.01'
        ]);

        try {
            $articulo = \App\Models\InformeDiarioArticulo::where('id_informe_diario_articulo', $id_articulo)
                                                         ->where('id_informe_diario_ejecucion', $id_informe)
                                                         ->firstOrFail();
            $articulo->cantidad = $request->cantidad;
            $articulo->save();

            return response()->json(['success' => true, 'mensaje' => 'Cantidad actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar cantidad: ' . $e->getMessage()]);
        }
    }

    // Método para descripción del informe
    public function storeInformeDescripcion(Request $request, $id, $id_informe)
    {
        $request->validate([
            'descripcion' => 'required|max:2000'
        ]);

        try {
            \App\Models\InformeDiarioDetalle::create([
                'id_informe_diario_ejecucion' => $id_informe,
                'descripcion' => $request->descripcion,
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Descripción agregada.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function deleteInformeDescripcion($id, $id_informe, $id_descripcion)
    {
        try {
            \App\Models\InformeDiarioDetalle::where('id_informe_diario_detalle', $id_descripcion)
                                            ->where('id_informe_diario_ejecucion', $id_informe)
                                            ->delete();
            return response()->json(['success' => true, 'mensaje' => 'Descripción eliminada.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function storeInformeImagen(Request $request, $id, $id_informe)
    {
        $request->validate([
            'imagen' => 'required|image|max:5120',
            'descripcion' => 'nullable|max:500'
        ]);

        try {
            $archivo = $request->file('imagen');
            $nombre = 'informe_' . $id_informe . '_' . time() . '.' . $archivo->getClientOriginalExtension();
            $imageData = base64_encode(file_get_contents($archivo->getRealPath()));
            $base64 = 'data:' . $archivo->getMimeType() . ';base64,' . $imageData;

            \App\Models\InformeDiarioImagen::create([
                'id_informe_diario_ejecucion' => $id_informe,
                'ruta_imagen' => $base64,
                'descripcion' => $request->descripcion,
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Imagen subida correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al subir imagen: ' . $e->getMessage()]);
        }
    }

    public function deleteInformeImagen($id, $id_informe, $id_imagen)
    {
        try {
            $img = \App\Models\InformeDiarioImagen::where('id_informe_diario_imagen', $id_imagen)
                                                   ->where('id_informe_diario_ejecucion', $id_informe)
                                                   ->firstOrFail();
            $img->delete();
            return response()->json(['success' => true, 'mensaje' => 'Imagen eliminada.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function calcularHorasTrabajo($hora_entrada, $hora_salida, $feriado, $fecha_solicitud)
    {
        $entrada = strtotime($hora_entrada);
        $salida = strtotime($hora_salida);
        $total_horas = round((($salida - $entrada) / 3600), 2);
        if ($total_horas < 0) {
            $total_horas += 24;
        }

        $dia_semana = date('N', strtotime($fecha_solicitud)); // 1=lunes, 7=domingo
        $horas_normales = 0;
        $horas_extras = 0;
        $horas_extraordinarias = 0;

        if ($feriado == 1 || $dia_semana == 7) {
            $horas_extraordinarias = $total_horas;
        } elseif ($dia_semana == 6) {
            $horas_extras = $total_horas;
        } else {
            if ($total_horas <= 9) {
                $horas_normales = $total_horas;
            } else {
                $horas_normales = 9;
                $horas_extras = $total_horas - 9;
            }
        }

        return [
            'horas_normales' => $horas_normales,
            'horas_extras' => $horas_extras,
            'horas_extraordinarias' => $horas_extraordinarias
        ];
    }
}
