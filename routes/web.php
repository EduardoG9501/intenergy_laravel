<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Redirigir la raíz al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de Autenticación
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rutas protegidas por autenticación
Route::middleware(['auth'])->group(function () {
    Route::get('/inicio', function () {
        return view('inicio');
    })->name('inicio');

    // Rutas de Artículos
    Route::get('/articulos', [App\Http\Controllers\ArticuloController::class, 'index'])->name('articulos.index');
    Route::get('/articulos/{id}', [App\Http\Controllers\ArticuloController::class, 'show'])->name('articulos.show');
    Route::post('/articulos', [App\Http\Controllers\ArticuloController::class, 'store'])->name('articulos.store');
    Route::post('/articulos/{id}/update', [App\Http\Controllers\ArticuloController::class, 'update'])->name('articulos.update');
    Route::post('/articulos/{id}/delete', [App\Http\Controllers\ArticuloController::class, 'destroy'])->name('articulos.delete');
    Route::post('/articulos/{id}/restore', [App\Http\Controllers\ArticuloController::class, 'restore'])->name('articulos.restore');

    // Rutas de Bodegas
    Route::get('/bodegas', [App\Http\Controllers\BodegaController::class, 'index'])->name('bodegas.index');
    Route::get('/bodegas/{id}', [App\Http\Controllers\BodegaController::class, 'show'])->name('bodegas.show');
    Route::post('/bodegas', [App\Http\Controllers\BodegaController::class, 'store'])->name('bodegas.store');
    Route::post('/bodegas/{id}/update', [App\Http\Controllers\BodegaController::class, 'update'])->name('bodegas.update');
    Route::post('/bodegas/{id}/delete', [App\Http\Controllers\BodegaController::class, 'destroy'])->name('bodegas.delete');
    Route::post('/bodegas/{id}/restore', [App\Http\Controllers\BodegaController::class, 'restore'])->name('bodegas.restore');

    // Rutas de Categorías
    Route::get('/categorias', [App\Http\Controllers\CategoriaController::class, 'index'])->name('categorias.index');
    Route::get('/categorias/{id}', [App\Http\Controllers\CategoriaController::class, 'show'])->name('categorias.show');
    Route::post('/categorias', [App\Http\Controllers\CategoriaController::class, 'store'])->name('categorias.store');
    Route::post('/categorias/{id}/update', [App\Http\Controllers\CategoriaController::class, 'update'])->name('categorias.update');
    Route::post('/categorias/{id}/delete', [App\Http\Controllers\CategoriaController::class, 'destroy'])->name('categorias.delete');
    Route::post('/categorias/{id}/restore', [App\Http\Controllers\CategoriaController::class, 'restore'])->name('categorias.restore');

    // Rutas de Proveedores
    Route::get('/proveedores', [App\Http\Controllers\ProveedorController::class, 'index'])->name('proveedores.index');
    Route::get('/proveedores/{id}', [App\Http\Controllers\ProveedorController::class, 'show'])->name('proveedores.show');
    Route::post('/proveedores', [App\Http\Controllers\ProveedorController::class, 'store'])->name('proveedores.store');
    Route::post('/proveedores/{id}/update', [App\Http\Controllers\ProveedorController::class, 'update'])->name('proveedores.update');
    Route::post('/proveedores/{id}/delete', [App\Http\Controllers\ProveedorController::class, 'destroy'])->name('proveedores.delete');
    Route::post('/proveedores/{id}/restore', [App\Http\Controllers\ProveedorController::class, 'restore'])->name('proveedores.restore');

    // Rutas de Clientes
    Route::get('/clientes', [App\Http\Controllers\ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/{id}', [App\Http\Controllers\ClienteController::class, 'show'])->name('clientes.show');
    Route::post('/clientes', [App\Http\Controllers\ClienteController::class, 'store'])->name('clientes.store');
    Route::post('/clientes/{id}/update', [App\Http\Controllers\ClienteController::class, 'update'])->name('clientes.update');
    Route::post('/clientes/{id}/delete', [App\Http\Controllers\ClienteController::class, 'destroy'])->name('clientes.delete');
    Route::post('/clientes/{id}/restore', [App\Http\Controllers\ClienteController::class, 'restore'])->name('clientes.restore');

    // Rutas de Cargos
    Route::get('/cargos', [App\Http\Controllers\CargoEmpleadoController::class, 'index'])->name('cargos.index');
    Route::get('/cargos/{id}', [App\Http\Controllers\CargoEmpleadoController::class, 'show'])->name('cargos.show');
    Route::post('/cargos', [App\Http\Controllers\CargoEmpleadoController::class, 'store'])->name('cargos.store');
    Route::post('/cargos/{id}/update', [App\Http\Controllers\CargoEmpleadoController::class, 'update'])->name('cargos.update');
    Route::post('/cargos/{id}/delete', [App\Http\Controllers\CargoEmpleadoController::class, 'destroy'])->name('cargos.delete');
    Route::post('/cargos/{id}/restore', [App\Http\Controllers\CargoEmpleadoController::class, 'restore'])->name('cargos.restore');

    // Rutas de Empleados
    Route::get('/empleados', [App\Http\Controllers\EmpleadoController::class, 'index'])->name('empleados.index');
    Route::get('/empleados/{id}', [App\Http\Controllers\EmpleadoController::class, 'show'])->name('empleados.show');
    Route::post('/empleados', [App\Http\Controllers\EmpleadoController::class, 'store'])->name('empleados.store');
    Route::post('/empleados/{id}/update', [App\Http\Controllers\EmpleadoController::class, 'update'])->name('empleados.update');
    Route::post('/empleados/{id}/delete', [App\Http\Controllers\EmpleadoController::class, 'destroy'])->name('empleados.delete');
    Route::post('/empleados/{id}/restore', [App\Http\Controllers\EmpleadoController::class, 'restore'])->name('empleados.restore');

    // Rutas de Movimientos
    Route::get('/movimientos', [App\Http\Controllers\MovimientoController::class, 'index'])->name('movimientos.index');
    Route::post('/movimientos', [App\Http\Controllers\MovimientoController::class, 'store'])->name('movimientos.store');
    Route::get('/movimientos/subtipos/{id_tipo}', [App\Http\Controllers\MovimientoController::class, 'getSubtypes'])->name('movimientos.subtypes');
    Route::get('/movimientos/{id}', [App\Http\Controllers\MovimientoController::class, 'show'])->name('movimientos.show');
    Route::post('/movimientos/{id}/detalle', [App\Http\Controllers\MovimientoController::class, 'storeDetail'])->name('movimientos.storeDetail');
    Route::post('/movimientos/{id_mov}/detalle/{id_detail}/delete', [App\Http\Controllers\MovimientoController::class, 'deleteDetail'])->name('movimientos.deleteDetail');
    Route::post('/movimientos/{id_mov}/detalle/{id_detail}/update-cantidad', [App\Http\Controllers\MovimientoController::class, 'updateDetailCantidad'])->name('movimientos.updateDetailCantidad');
    Route::post('/movimientos/{id}/confirmar', [App\Http\Controllers\MovimientoController::class, 'guardarDefinitivo'])->name('movimientos.guardarDefinitivo');
    Route::post('/movimientos/{id}/delete', [App\Http\Controllers\MovimientoController::class, 'destroy'])->name('movimientos.delete');
    Route::post('/movimientos/{id}/update-iva-descuento', [App\Http\Controllers\MovimientoController::class, 'updateIvaDescuento'])->name('movimientos.updateIvaDescuento');
    Route::post('/select-bodega', [App\Http\Controllers\MovimientoController::class, 'selectBodegaActiva'])->name('select_bodega_activa');

    // Rutas de Stock / Existencias
    Route::get('/stock', [App\Http\Controllers\StockController::class, 'index'])->name('stock.index');

    // --- FASE 5: OPERACIONES Y CONTROL DE OBRAS ---

    // Rutas de Proyectos
    Route::get('/proyectos', [App\Http\Controllers\ProyectoController::class, 'index'])->name('proyectos.index');
    Route::post('/proyectos', [App\Http\Controllers\ProyectoController::class, 'store'])->name('proyectos.store');
    Route::get('/proyectos/{id}', [App\Http\Controllers\ProyectoController::class, 'show'])->name('proyectos.show');
    Route::post('/proyectos/{id}/update', [App\Http\Controllers\ProyectoController::class, 'update'])->name('proyectos.update');
    Route::post('/proyectos/{id}/delete', [App\Http\Controllers\ProyectoController::class, 'destroy'])->name('proyectos.delete');
    Route::post('/proyectos/{id}/eliminar', [App\Http\Controllers\ProyectoController::class, 'eliminar'])->name('proyectos.eliminar');
    Route::post('/proyectos/{id}/restore', [App\Http\Controllers\ProyectoController::class, 'restore'])->name('proyectos.restore');

    // Rutas de Obras
    Route::get('/obras', [App\Http\Controllers\ObraController::class, 'index'])->name('obras.index');
    Route::post('/obras', [App\Http\Controllers\ObraController::class, 'store'])->name('obras.store');
    Route::get('/obras/{id}', [App\Http\Controllers\ObraController::class, 'show'])->name('obras.show');
    Route::post('/obras/{id}/update', [App\Http\Controllers\ObraController::class, 'update'])->name('obras.update');
    Route::post('/obras/{id}/delete', [App\Http\Controllers\ObraController::class, 'destroy'])->name('obras.delete');
    Route::post('/obras/{id}/eliminar', [App\Http\Controllers\ObraController::class, 'eliminar'])->name('obras.eliminar');
    Route::post('/obras/{id}/restore', [App\Http\Controllers\ObraController::class, 'restore'])->name('obras.restore');

    // Rutas de Ordenes de Trabajo
    Route::get('/ordenes', [App\Http\Controllers\OrdenTrabajoController::class, 'index'])->name('ordenes.index');
    Route::post('/ordenes', [App\Http\Controllers\OrdenTrabajoController::class, 'store'])->name('ordenes.store');
    Route::get('/ordenes/{id}', [App\Http\Controllers\OrdenTrabajoController::class, 'show'])->name('ordenes.show');
    Route::post('/ordenes/{id}/update', [App\Http\Controllers\OrdenTrabajoController::class, 'update'])->name('ordenes.update');
    Route::post('/ordenes/{id}/delete', [App\Http\Controllers\OrdenTrabajoController::class, 'destroy'])->name('ordenes.delete');
    Route::post('/ordenes/{id}/restore', [App\Http\Controllers\OrdenTrabajoController::class, 'restore'])->name('ordenes.restore');

    // Rutas de Pedidos de Materiales
    Route::get('/pedidos', [App\Http\Controllers\PedidoMaterialController::class, 'index'])->name('pedidos.index');
    Route::post('/pedidos', [App\Http\Controllers\PedidoMaterialController::class, 'store'])->name('pedidos.store');
    Route::get('/pedidos/{id}', [App\Http\Controllers\PedidoMaterialController::class, 'show'])->name('pedidos.show');
    Route::post('/pedidos/{id}/update', [App\Http\Controllers\PedidoMaterialController::class, 'update'])->name('pedidos.update');
    Route::post('/pedidos/{id}/detalle', [App\Http\Controllers\PedidoMaterialController::class, 'storeDetail'])->name('pedidos.storeDetail');
    Route::post('/pedidos/{id}/detalle/{id_detail}/delete', [App\Http\Controllers\PedidoMaterialController::class, 'deleteDetail'])->name('pedidos.deleteDetail');
    Route::post('/pedidos/{id}/status', [App\Http\Controllers\PedidoMaterialController::class, 'updateStatus'])->name('pedidos.updateStatus');

    // Rutas de Solicitudes de Materiales
    Route::get('/solicitudes', [App\Http\Controllers\SolicitudMaterialController::class, 'index'])->name('solicitudes.index');
    Route::post('/solicitudes', [App\Http\Controllers\SolicitudMaterialController::class, 'store'])->name('solicitudes.store');
    Route::get('/solicitudes/{id}', [App\Http\Controllers\SolicitudMaterialController::class, 'show'])->name('solicitudes.show');
    Route::post('/solicitudes/{id}/detalle', [App\Http\Controllers\SolicitudMaterialController::class, 'storeDetail'])->name('solicitudes.storeDetail');
    Route::post('/solicitudes/{id}/detalle/{id_detail}/delete', [App\Http\Controllers\SolicitudMaterialController::class, 'deleteDetail'])->name('solicitudes.deleteDetail');
    Route::post('/solicitudes/{id}/status', [App\Http\Controllers\SolicitudMaterialController::class, 'updateStatus'])->name('solicitudes.updateStatus');

    // Rutas de Ejecuciones de Obra
    Route::get('/ejecuciones', [App\Http\Controllers\EjecucionObraController::class, 'index'])->name('ejecuciones.index');
    Route::post('/ejecuciones', [App\Http\Controllers\EjecucionObraController::class, 'store'])->name('ejecuciones.store');
    Route::post('/ejecuciones/{id}/update', [App\Http\Controllers\EjecucionObraController::class, 'update'])->name('ejecuciones.update');
    Route::get('/ejecuciones/{id}', [App\Http\Controllers\EjecucionObraController::class, 'show'])->name('ejecuciones.show');
    Route::post('/ejecuciones/{id}/detalle', [App\Http\Controllers\EjecucionObraController::class, 'storeDetail'])->name('ejecuciones.storeDetail');
    Route::post('/ejecuciones/{id}/detalle/{id_detail}/delete', [App\Http\Controllers\EjecucionObraController::class, 'deleteDetail'])->name('ejecuciones.deleteDetail');
    Route::post('/ejecuciones/{id}/diario', [App\Http\Controllers\EjecucionObraController::class, 'storeDiario'])->name('ejecuciones.storeDiario');
    Route::post('/ejecuciones/{id}/diario/{id_diario}/delete', [App\Http\Controllers\EjecucionObraController::class, 'deleteDiario'])->name('ejecuciones.deleteDiario');
    Route::post('/ejecuciones/{id}/contabilizar', [App\Http\Controllers\EjecucionObraController::class, 'contabilizar'])->name('ejecuciones.contabilizar');
    
    // Rutas de Informe Diario en Ejecuciones
    Route::post('/ejecuciones/{id}/informe-diario', [App\Http\Controllers\EjecucionObraController::class, 'storeInformeDiario'])->name('ejecuciones.storeInformeDiario');
    Route::post('/ejecuciones/{id}/informe-diario/{id_informe}/update', [App\Http\Controllers\EjecucionObraController::class, 'updateInformeDiario'])->name('ejecuciones.updateInformeDiario');
    Route::post('/ejecuciones/{id}/informe-diario/{id_informe}/delete', [App\Http\Controllers\EjecucionObraController::class, 'deleteInformeDiario'])->name('ejecuciones.deleteInformeDiario');
    
    // Rutas de detalles del informe (empleados, artículos, descripción)
    Route::post('/ejecuciones/{id}/informe-diario/{id_informe}/empleado', [App\Http\Controllers\EjecucionObraController::class, 'storeInformeEmpleado'])->name('ejecuciones.storeInformeEmpleado');
    Route::post('/ejecuciones/{id}/informe-diario/{id_informe}/empleado/{id_empleado}/delete', [App\Http\Controllers\EjecucionObraController::class, 'deleteInformeEmpleado'])->name('ejecuciones.deleteInformeEmpleado');
    Route::post('/ejecuciones/{id}/informe-diario/{id_informe}/articulo', [App\Http\Controllers\EjecucionObraController::class, 'storeInformeArticulo'])->name('ejecuciones.storeInformeArticulo');
    Route::post('/ejecuciones/{id}/informe-diario/{id_informe}/articulo/{id_articulo}/delete', [App\Http\Controllers\EjecucionObraController::class, 'deleteInformeArticulo'])->name('ejecuciones.deleteInformeArticulo');
    Route::post('/ejecuciones/{id}/informe-diario/{id_informe}/descripcion', [App\Http\Controllers\EjecucionObraController::class, 'storeInformeDescripcion'])->name('ejecuciones.storeInformeDescripcion');
    Route::post('/ejecuciones/{id}/informe-diario/{id_informe}/descripcion/{id_descripcion}/delete', [App\Http\Controllers\EjecucionObraController::class, 'deleteInformeDescripcion'])->name('ejecuciones.deleteInformeDescripcion');
    Route::post('/ejecuciones/{id}/informe-diario/{id_informe}/imagen', [App\Http\Controllers\EjecucionObraController::class, 'storeInformeImagen'])->name('ejecuciones.storeInformeImagen');
    Route::post('/ejecuciones/{id}/informe-diario/{id_informe}/imagen/{id_imagen}/delete', [App\Http\Controllers\EjecucionObraController::class, 'deleteInformeImagen'])->name('ejecuciones.deleteInformeImagen');

    // Rutas de Control de Horas de Trabajo
    Route::get('/horas', [App\Http\Controllers\HorasTrabajoController::class, 'index'])->name('horas.index');
    Route::post('/horas', [App\Http\Controllers\HorasTrabajoController::class, 'store'])->name('horas.store');
    Route::get('/horas/{id}', [App\Http\Controllers\HorasTrabajoController::class, 'show'])->name('horas.show');
    Route::post('/horas/{id}/detalle', [App\Http\Controllers\HorasTrabajoController::class, 'storeDetail'])->name('horas.storeDetail');
    Route::post('/horas/{id}/detalle/{id_detail}/delete', [App\Http\Controllers\HorasTrabajoController::class, 'deleteDetail'])->name('horas.deleteDetail');

    // Rutas de Informes Diarios
    Route::get('/informes-diarios', [App\Http\Controllers\InformeDiarioController::class, 'index'])->name('informes.index');
    Route::post('/informes-diarios', [App\Http\Controllers\InformeDiarioController::class, 'store'])->name('informes.store');
    Route::get('/informes-diarios/{id}', [App\Http\Controllers\InformeDiarioController::class, 'show'])->name('informes.show');
    Route::post('/informes-diarios/{id}/update', [App\Http\Controllers\InformeDiarioController::class, 'update'])->name('informes.update');
    Route::post('/informes-diarios/{id}/delete', [App\Http\Controllers\InformeDiarioController::class, 'destroy'])->name('informes.delete');
    Route::post('/informes-diarios/{id}/empleado', [App\Http\Controllers\InformeDiarioController::class, 'storeEmpleado'])->name('informes.storeEmpleado');
    Route::post('/informes-diarios/{id}/empleado/{id_empleado}/delete', [App\Http\Controllers\InformeDiarioController::class, 'deleteEmpleado'])->name('informes.deleteEmpleado');
    Route::post('/informes-diarios/{id}/articulo', [App\Http\Controllers\InformeDiarioController::class, 'storeArticulo'])->name('informes.storeArticulo');
    Route::post('/informes-diarios/{id}/articulo/{id_articulo}/delete', [App\Http\Controllers\InformeDiarioController::class, 'deleteArticulo'])->name('informes.deleteArticulo');
    Route::post('/informes-diarios/{id}/descripcion', [App\Http\Controllers\InformeDiarioController::class, 'storeDescripcion'])->name('informes.storeDescripcion');
    Route::post('/informes-diarios/{id}/descripcion/{id_descripcion}/delete', [App\Http\Controllers\InformeDiarioController::class, 'deleteDescripcion'])->name('informes.deleteDescripcion');
    Route::post('/informes-diarios/{id}/imagen', [App\Http\Controllers\InformeDiarioController::class, 'storeImagen'])->name('informes.storeImagen');
    Route::post('/informes-diarios/{id}/imagen/{id_imagen}/delete', [App\Http\Controllers\InformeDiarioController::class, 'deleteImagen'])->name('informes.deleteImagen');

    // --- FASE 6: REPORTES Y ADMINISTRACION ---

    // Rutas de Reportes
    Route::get('/reportes/kardex', [App\Http\Controllers\ReporteController::class, 'kardex'])->name('reportes.kardex');
    Route::get('/reportes/exportar-kardex', [App\Http\Controllers\ReporteController::class, 'exportarKardex'])->name('reportes.exportar_kardex');
    Route::get('/reportes/exportar-kardex-pdf', [App\Http\Controllers\ReporteController::class, 'exportarKardexPdf'])->name('reportes.exportar_kardex_pdf');
    Route::get('/reporte-stock', [App\Http\Controllers\ReporteController::class, 'stock'])->name('reportes.stock');
    Route::get('/reporte-disponibilidad', [App\Http\Controllers\ReporteController::class, 'disponibilidad'])->name('reportes.disponibilidad');
    Route::get('/reportes/obras-proyecto', [App\Http\Controllers\ReporteController::class, 'getObrasProyecto'])->name('reportes.obras_proyecto');

    Route::get('/reportes/informe-ordenes-trabajo', [App\Http\Controllers\ReporteController::class, 'informeOrdenesTrabajo'])->name('reportes.informe_ordenes_trabajo');
    Route::get('/reportes/informe-pedido-materiales', [App\Http\Controllers\ReporteController::class, 'informePedidoMateriales'])->name('reportes.informe_pedido_materiales');
    Route::get('/reportes/reporte-ejecucion-obra', [App\Http\Controllers\ReporteController::class, 'reporteEjecucionObra'])->name('reportes.reporte_ejecucion_obra');
    Route::get('/reportes/liquidacion-trabajo', [App\Http\Controllers\ReporteController::class, 'liquidacionTrabajo'])->name('reportes.liquidacion_trabajo');

    // AJAX y Export Reportes OT
    Route::get('/reportes/buscar-proyectos', [App\Http\Controllers\ReporteController::class, 'buscarProyectos'])->name('reportes.buscar_proyectos');
    Route::get('/reportes/buscar-obras', [App\Http\Controllers\ReporteController::class, 'buscarObras'])->name('reportes.buscar_obras');
    Route::get('/reportes/exportar-ordenes-trabajo', [App\Http\Controllers\ReporteController::class, 'exportarOrdenesTrabajo'])->name('reportes.exportar_ordenes_trabajo');
    Route::get('/reportes/exportar-ordenes-trabajo-pdf', [App\Http\Controllers\ReporteController::class, 'exportarOrdenesTrabajoPdf'])->name('reportes.exportar_ordenes_trabajo_pdf');

    // AJAX y Export Reportes Pedido Materiales
    Route::get('/reportes/buscar-ordenes-trabajo', [App\Http\Controllers\ReporteController::class, 'buscarOrdenesTrabajo'])->name('reportes.buscar_ordenes_trabajo');
    Route::get('/reportes/buscar-bodegas', [App\Http\Controllers\ReporteController::class, 'buscarBodegas'])->name('reportes.buscar_bodegas');
    Route::get('/reportes/buscar-articulos', [App\Http\Controllers\ReporteController::class, 'buscarArticulos'])->name('reportes.buscar_articulos');
    Route::get('/reportes/exportar-pedido-materiales', [App\Http\Controllers\ReporteController::class, 'exportarPedidoMateriales'])->name('reportes.exportar_pedido_materiales');
    Route::get('/reportes/exportar-pedido-materiales-pdf', [App\Http\Controllers\ReporteController::class, 'exportarPedidoMaterialesPdf'])->name('reportes.exportar_pedido_materiales_pdf');

    // AJAX y Export Reportes Ejecucion Obra
    Route::get('/reportes/exportar-ejecucion-obra', [App\Http\Controllers\ReporteController::class, 'exportarEjecucionObra'])->name('reportes.exportar_ejecucion_obra');
    Route::get('/reportes/exportar-ejecucion-obra-pdf', [App\Http\Controllers\ReporteController::class, 'exportarEjecucionObraPdf'])->name('reportes.exportar_ejecucion_obra_pdf');

    // Liquidacion Trabajo - Save
    Route::post('/reportes/guardar-liquidacion', [App\Http\Controllers\ReporteController::class, 'guardarLiquidacion'])->name('reportes.guardar_liquidacion');
    Route::post('/reportes/subir-imagen-liquidacion', [App\Http\Controllers\ReporteController::class, 'subirImagenLiquidacion'])->name('reportes.subir_imagen_liquidacion');
    Route::post('/reportes/eliminar-imagen-liquidacion/{id}', [App\Http\Controllers\ReporteController::class, 'eliminarImagenLiquidacion'])->name('reportes.eliminar_imagen_liquidacion');

    // Liquidacion Trabajo - Export
    Route::get('/reportes/exportar-liquidacion-trabajo', [App\Http\Controllers\ReporteController::class, 'exportarLiquidacionTrabajo'])->name('reportes.exportar_liquidacion_trabajo');
    Route::get('/reportes/exportar-liquidacion-trabajo-pdf', [App\Http\Controllers\ReporteController::class, 'exportarLiquidacionTrabajoPdf'])->name('reportes.exportar_liquidacion_trabajo_pdf');

    // Rutas de Usuarios y Permisos
    Route::get('/usuarios', [App\Http\Controllers\UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [App\Http\Controllers\UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}', [App\Http\Controllers\UsuarioController::class, 'show'])->name('usuarios.show');
    Route::post('/usuarios/{id}/update', [App\Http\Controllers\UsuarioController::class, 'update'])->name('usuarios.update');
    Route::post('/usuarios/{id}/delete', [App\Http\Controllers\UsuarioController::class, 'destroy'])->name('usuarios.delete');
    Route::post('/usuarios/{id}/restore', [App\Http\Controllers\UsuarioController::class, 'restore'])->name('usuarios.restore');
    Route::get('/usuarios/{id}/permisos', [App\Http\Controllers\UsuarioController::class, 'getPermisos'])->name('usuarios.permisos.get');
    Route::post('/usuarios/{id}/permisos', [App\Http\Controllers\UsuarioController::class, 'guardarPermisos'])->name('usuarios.permisos.post');

    // Rutas de Backup
    Route::get('/backup', [App\Http\Controllers\BackupController::class, 'index'])->name('backup.index');
    Route::post('/backup', [App\Http\Controllers\BackupController::class, 'store'])->name('backup.store');
    Route::get('/backup/{id}/download', [App\Http\Controllers\BackupController::class, 'download'])->name('backup.download');

    // Configuración IVA
    Route::get('/configuracion/iva', [App\Http\Controllers\IvaController::class, 'index'])->name('configuracion.iva');
    Route::post('/configuracion/iva/{id}/update', [App\Http\Controllers\IvaController::class, 'update'])->name('configuracion.iva.update');
});
