<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Bodega;
use App\Models\Proyecto;
use App\Models\Obra;
use App\Models\TipoMovimiento;
use App\Models\SubTipoMovimiento;
use App\Models\LiquidacionTrabajo;
use App\Models\LiquidacionTrabajoImagen;
use App\Models\StockProducto;
use App\Models\StockProductoBodega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    // --- REPORT KARDEX ---
    public function kardex(Request $request)
    {
        if ($request->ajax() || $request->has('buscar')) {
            $query = DB::table('stock_productos as sp')
                ->join('bodegas as bop', 'sp.id_bodega_principal', '=', 'bop.id_bodega')
                ->leftJoin('solicitud_materiales as sm', 'sp.no_documento', '=', DB::raw("CONCAT('SOL-', sm.id_solicitud_material)"))
                ->leftJoin('ejecucion_obra as eo', function($join) {
                    $join->on('eo.id_ejecucion_obra', '=', DB::raw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(sp.no_documento, '-BOD-', 1), 'EJEC-', -1) AS UNSIGNED)"))
                         ->where('sp.no_documento', 'LIKE', 'EJEC-%-BOD-%');
                })
                ->leftJoin('ordenes_trabajo as ot', 'ot.id_orden', '=', DB::raw("COALESCE(sm.id_orden, eo.id_orden)"))
                ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
                ->leftJoin('obra as ob', 'ot.id_obra', '=', 'ob.id')
                ->leftJoin('articulos as a', 'sp.id_producto', '=', 'a.id_producto')
                ->where('sp.estado', 1)
                ->select(
                    DB::raw("COALESCE(ot.identificador, sp.no_documento) AS id_orden"),
                    'p.nombre as proyecto',
                    'ob.nombre as nombre_obra',
                    'bop.nombreBodega as lugar',
                    'sp.fecha_captura as fecha',
                    'sp.tipo_movimiento',
                    'sp.sub_tipo_movimiento',
                    'sp.producto as articulo',
                    'sp.cantidad',
                    'sp.no_documento as documento'
                );

            if ($request->filled('id_bodega')) {
                $query->where('sp.id_bodega_principal', $request->id_bodega);
            }
            if ($request->filled('id_producto')) {
                $query->where('sp.id_producto', $request->id_producto);
            }
            if ($request->filled('id_orden')) {
                $query->where('ot.id_orden', $request->id_orden);
            }

            $resultados = $query->orderBy('sp.fecha_captura', 'desc')->paginate(50);

            if ($request->ajax()) {
                return response()->json([
                    'html_tabla' => view('reportes.partials.tabla_kardex', compact('resultados'))->render(),
                    'total' => $resultados->total()
                ]);
            }
        }

        return view('reportes.kardex');
    }

    private function getKardexQuery(Request $request)
    {
        return DB::table('stock_productos as sp')
            ->join('bodegas as bop', 'sp.id_bodega_principal', '=', 'bop.id_bodega')
            ->leftJoin('solicitud_materiales as sm', 'sp.no_documento', '=', DB::raw("CONCAT('SOL-', sm.id_solicitud_material)"))
            ->leftJoin('ejecucion_obra as eo', function($join) {
                $join->on('eo.id_ejecucion_obra', '=', DB::raw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(sp.no_documento, '-BOD-', 1), 'EJEC-', -1) AS UNSIGNED)"))
                     ->where('sp.no_documento', 'LIKE', 'EJEC-%-BOD-%');
            })
            ->leftJoin('ordenes_trabajo as ot', 'ot.id_orden', '=', DB::raw("COALESCE(sm.id_orden, eo.id_orden)"))
            ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
            ->leftJoin('obra as ob', 'ot.id_obra', '=', 'ob.id')
            ->where('sp.estado', 1)
            ->select(
                DB::raw("COALESCE(ot.identificador, sp.no_documento) AS id_orden"),
                'p.nombre as proyecto',
                'ob.nombre as nombre_obra',
                'bop.nombreBodega as lugar',
                'sp.fecha_captura as fecha',
                'sp.tipo_movimiento',
                'sp.sub_tipo_movimiento',
                'sp.producto as articulo',
                'sp.cantidad',
                'sp.no_documento as documento'
            );
    }

    private function applyKardexFilters($query, Request $request)
    {
        if ($request->filled('id_bodega')) {
            $query->where('sp.id_bodega_principal', $request->id_bodega);
        }
        if ($request->filled('id_producto')) {
            $query->where('sp.id_producto', $request->id_producto);
        }
        if ($request->filled('id_orden')) {
            $query->where('ot.id_orden', $request->id_orden);
        }
        return $query->orderBy('sp.fecha_captura', 'desc');
    }

    // Exportar Kardex a Excel (HTML con imágenes)
    public function exportarKardex(Request $request)
    {
        $query = $this->getKardexQuery($request);
        $resultados = $this->applyKardexFilters($query, $request)->get();

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8">
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Kardex</x:Name></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    h2 { text-align: center; font-size: 16px; }
    .info { text-align: center; color: #666; font-size: 10px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #2c3e50; color: white; padding: 6px 4px; text-align: left; font-size: 10px; }
    td { padding: 5px 4px; border-bottom: 1px solid #ddd; font-size: 10px; }
    tr:nth-child(even) { background: #f5f5f5; }
    .badge-ent { background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
    .badge-sal { background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
</style></head><body>
<h2>Entrada / Salida (Kardex)</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . ' | Total: ' . $resultados->count() . ' registro(s)</div>
<table>
<thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Lugar</th><th>Fecha</th><th>Tipo Movimiento</th><th>Sub Tipo Movimiento</th><th>Articulo</th><th>Cantidad</th><th>Nº Documento</th></tr></thead>
<tbody>';

        foreach ($resultados as $r) {
            $badgeClass = $r->tipo_movimiento === 'ENTRADA' ? 'badge-ent' : 'badge-sal';
            $html .= '<tr>
                <td>' . $r->id_orden . '</td>
                <td>' . ($r->proyecto ?: '-') . '</td>
                <td>' . ($r->nombre_obra ?: '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td><span class="' . $badgeClass . '">' . $r->tipo_movimiento . '</span></td>
                <td>' . ($r->sub_tipo_movimiento ?: '-') . '</td>
                <td>' . $r->articulo . '</td>
                <td>' . number_format($r->cantidad, 2) . '</td>
                <td>' . $r->documento . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="kardex_' . date('Y-m-d') . '.xls"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        return response($html, 200, $headers);
    }

    // Exportar Kardex a PDF (HTML imprimible)
    public function exportarKardexPdf(Request $request)
    {
        $query = $this->getKardexQuery($request);
        $resultados = $this->applyKardexFilters($query, $request)->get();

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Entrada / Salida (Kardex)</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; }
    h2 { text-align: center; margin-bottom: 4px; font-size: 16px; }
    .info { text-align: center; color: #666; margin-bottom: 15px; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #2c3e50; color: white; padding: 7px 5px; text-align: left; font-size: 10px; }
    td { padding: 6px 5px; border-bottom: 1px solid #ddd; font-size: 10px; }
    tr:nth-child(even) { background: #f5f5f5; }
    .badge-ent { background: #28a745; color: white; padding: 2px 8px; border-radius: 3px; font-size: 9px; }
    .badge-sal { background: #dc3545; color: white; padding: 2px 8px; border-radius: 3px; font-size: 9px; }
    @media print { body { padding: 10px; } }
</style></head><body>
<h2>Entrada / Salida (Kardex)</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . ' | Total: ' . $resultados->count() . ' registro(s)</div>
<table>
<thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Lugar</th><th>Fecha</th><th>Tipo Movimiento</th><th>Sub Tipo Movimiento</th><th>Articulo</th><th>Cantidad</th><th>Nº Documento</th></tr></thead>
<tbody>';

        foreach ($resultados as $r) {
            $badgeClass = $r->tipo_movimiento === 'ENTRADA' ? 'badge-ent' : 'badge-sal';
            $html .= '<tr>
                <td>' . $r->id_orden . '</td>
                <td>' . ($r->proyecto ?: '-') . '</td>
                <td>' . ($r->nombre_obra ?: '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td><span class="' . $badgeClass . '">' . $r->tipo_movimiento . '</span></td>
                <td>' . ($r->sub_tipo_movimiento ?: '-') . '</td>
                <td>' . $r->articulo . '</td>
                <td><strong>' . number_format($r->cantidad, 2) . '</strong></td>
                <td>' . $r->documento . '</td>
            </tr>';
        }

        $html .= '</tbody></table>
<script>window.onload = function() { window.print(); }</script>
</body></html>';

        return response($html)->header('Content-Type', 'text/html');
    }

    // --- REPORT STOCK ---
    public function stock(Request $request)
    {
        $articulos = Articulo::where('estado', 1)->orderBy('nombre')->get();
        $bodegas = Bodega::where('estado', 1)->orderBy('nombreBodega')->get();

        if ($request->ajax() || $request->has('buscar')) {
            $query = StockProducto::query()
                ->select([
                    'stock_productos.tipo_movimiento',
                    'stock_productos.sub_tipo_movimiento',
                    'bop.nombreBodega AS BodegaPrincipal',
                    'bos.nombreBodega AS BodegaSecundaria',
                    'stock_productos.no_documento',
                    'stock_productos.producto',
                    'stock_productos.precio',
                    'stock_productos.cantidad',
                    'stock_productos.subtotal',
                    'stock_productos.decuento',
                    'stock_productos.iva',
                    'stock_productos.total',
                    'stock_productos.fecha_captura'
                ])
                ->from('stock_productos')
                ->join('bodegas AS bop', 'stock_productos.id_bodega_principal', '=', 'bop.id_bodega')
                ->join('bodegas AS bos', 'stock_productos.id_bodega_secundaria', '=', 'bos.id_bodega');

            if ($request->filled('articuloSelect') && $request->articuloSelect != '0') {
                $query->where('stock_productos.id_producto', $request->articuloSelect);
            }
            if ($request->filled('almacenSelect') && $request->almacenSelect != '0') {
                $query->where('stock_productos.id_bodega_principal', $request->almacenSelect);
            }
            if ($request->filled('almacenSelectSecundaria') && $request->almacenSelectSecundaria != '0') {
                $query->where('stock_productos.id_bodega_secundaria', $request->almacenSelectSecundaria);
            }
            if ($request->filled('finicio') && $request->filled('ffin')) {
                $query->whereBetween(DB::raw('DATE(stock_productos.fecha_captura)'), [$request->finicio, $request->ffin]);
            }

            $resultados = $query->orderBy('stock_productos.producto', 'asc')
                                ->orderBy('stock_productos.fecha_captura', 'desc')
                                ->get();

            if ($request->ajax()) {
                return view('reportes.partials.tabla_stock', compact('resultados'));
            }
        } else {
            $resultados = collect();
        }

        return view('reportes.stock', compact('articulos', 'bodegas', 'resultados'));
    }

    // --- REPORT DISPONIBILIDAD ---
    public function disponibilidad(Request $request)
    {
        $bodegas = Bodega::where('estado', 1)->orderBy('nombreBodega')->get();
        $articulos = Articulo::where('estado', 1)->orderBy('nombre')->get();

        if ($request->ajax() || $request->has('buscar')) {
            $query = StockProductoBodega::query()
                ->select([
                    'stock_productos_bodega.id_producto',
                    'a.nombre AS nombre_articulo',
                    'stock_productos_bodega.id_bodega_principal AS id_bodega',
                    'b.nombreBodega',
                    'stock_productos_bodega.cantidad'
                ])
                ->from('stock_productos_bodega')
                ->join('articulos AS a', 'a.id_producto', '=', 'stock_productos_bodega.id_producto')
                ->join('bodegas AS b', 'b.id_bodega', '=', 'stock_productos_bodega.id_bodega_principal')
                ->where('stock_productos_bodega.estado', 1)
                ->where('stock_productos_bodega.cantidad', '>', 0);

            if ($request->filled('id_bodega')) {
                $query->where('stock_productos_bodega.id_bodega_principal', $request->id_bodega);
            }
            if ($request->filled('id_producto')) {
                $query->where('stock_productos_bodega.id_producto', $request->id_producto);
            }

            $resultados = $query->orderBy('b.nombreBodega', 'asc')
                                ->orderBy('a.nombre', 'asc')
                                ->get();

            if ($request->ajax()) {
                return response()->json($resultados);
            }
        } else {
            $resultados = collect();
        }

        return view('reportes.disponibilidad', compact('bodegas', 'articulos', 'resultados'));
    }

    // Obras por proyecto (Auxiliar AJAX)
    public function getObrasProyecto(Request $request)
    {
        $obras = DB::table('ordenes_trabajo as ot')
            ->join('obra as o', 'ot.id_obra', '=', 'o.id')
            ->where('ot.id_proyecto', $request->id_proyecto)
            ->where('ot.estado', 1)
            ->select('o.id', 'o.nombre')
            ->distinct()
            ->get();

        $html = '<option value="">Todos</option>';
        foreach ($obras as $o) {
            $html .= '<option value="' . $o->id . '">' . $o->nombre . '</option>';
        }
        return response($html);
    }

    // --- REPORTE: INFORME ORDENES DE TRABAJO ---
    public function informeOrdenesTrabajo(Request $request)
    {
        $proyectos = Proyecto::where('estado', 1)->orderBy('nombre')->get();
        $obras = Obra::where('estado', 1)->orderBy('nombre')->get();
        $estados = DB::table('estado_ordenes')->where('activo', 1)->get();
        $resultados = collect();

        if ($request->ajax() && $request->has('buscar')) {
            $resultados = $this->getOrdenesTrabajoQuery($request);

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('reportes.partials.tabla_ordenes_trabajo', compact('resultados'))->render(),
                    'count' => $resultados->count()
                ]);
            }
        } elseif ($request->has('buscar')) {
            $resultados = $this->getOrdenesTrabajoQuery($request);
        }

        return view('reportes.informe_ordenes_trabajo', compact('proyectos', 'obras', 'estados', 'resultados'));
    }

    private function getOrdenesTrabajoQuery(Request $request)
    {
        $query = DB::table('ordenes_trabajo as ot')
            ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
            ->leftJoin('obra as o', 'ot.id_obra', '=', 'o.id')
            ->leftJoin('estado_ordenes as eo', 'ot.id_estado_orden', '=', 'eo.id_estado_orden')
            ->leftJoin('bodegas as b', 'ot.id_bodega_principal', '=', 'b.id_bodega')
            ->select(
                'ot.id_orden',
                'ot.identificador',
                'p.nombre as proyecto',
                'o.nombre as obra',
                'ot.fecha_graba as fecha',
                'b.nombreBodega as lugar',
                'ot.observacion',
                'eo.estado as estado_orden',
                'ot.id_estado_orden'
            )
            ->where('ot.estado', 1);

        if ($request->filled('proyecto')) {
            $query->where('ot.id_proyecto', $request->proyecto);
        }
        if ($request->filled('obra')) {
            $query->where('ot.id_obra', $request->obra);
        }
        if ($request->filled('estado')) {
            $query->where('ot.id_estado_orden', $request->estado);
        }
        if ($request->filled('fecha_desde')) {
            $query->where('ot.fecha_graba', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->where('ot.fecha_graba', '<=', $request->fecha_hasta . ' 23:59:59');
        }

        return $query->orderBy('ot.fecha_graba', 'desc')->get();
    }

    // AJAX: Buscar proyectos para modal
    public function buscarProyectos(Request $request)
    {
        $busqueda = $request->get('q', '');
        $proyectos = Proyecto::where('estado', 1)
            ->where('nombre', 'LIKE', "%{$busqueda}%")
            ->orderBy('nombre')
            ->limit(50)
            ->get();

        return response()->json($proyectos);
    }

    // AJAX: Buscar obras por proyecto
    public function buscarObras(Request $request)
    {
        $busqueda = $request->get('q', '');
        $idProyecto = $request->get('id_proyecto');

        $query = Obra::where('estado', 1)
            ->where('nombre', 'LIKE', "%{$busqueda}%");

        if ($idProyecto) {
            $query->whereIn('id', function($sub) use ($idProyecto) {
                $sub->select('id_obra')->from('ordenes_trabajo')
                    ->where('id_proyecto', $idProyecto)
                    ->where('estado', 1);
            });
        }

        $obras = $query->orderBy('nombre')->limit(50)->get();
        return response()->json($obras);
    }

    // Exportar a Excel (CSV)
    public function exportarOrdenesTrabajo(Request $request)
    {
        $resultados = $this->getOrdenesTrabajoQuery($request);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ordenes_trabajo_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($resultados) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['ID Orden', 'Identificador', 'Proyecto', 'Nombre de Obra', 'Fecha', 'Lugar', 'Observación', 'Estado'], ';');

            foreach ($resultados as $row) {
                fputcsv($file, [
                    $row->id_orden,
                    $row->identificador,
                    $row->proyecto,
                    $row->obra,
                    $row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '',
                    $row->lugar,
                    $row->observacion,
                    $row->estado_orden
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Exportar a PDF (vista HTML imprimible)
    public function exportarOrdenesTrabajoPdf(Request $request)
    {
        $resultados = $this->getOrdenesTrabajoQuery($request);

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Informe de Ordenes de Trabajo</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; }
    h2 { text-align: center; margin-bottom: 4px; font-size: 16px; }
    .info { text-align: center; color: #666; margin-bottom: 15px; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #2c3e50; color: white; padding: 7px 5px; text-align: left; font-size: 10px; }
    td { padding: 6px 5px; border-bottom: 1px solid #ddd; font-size: 10px; }
    tr:nth-child(even) { background: #f5f5f5; }
    .badge-success { background: #28a745; color: white; padding: 2px 8px; border-radius: 3px; font-size: 9px; }
    .badge-warning { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 3px; font-size: 9px; }
    .badge-secondary { background: #6c757d; color: white; padding: 2px 8px; border-radius: 3px; font-size: 9px; }
    @media print { body { padding: 10px; } }
</style></head><body>
<h2>Informe de Ordenes de Trabajo</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . ' | Total: ' . $resultados->count() . ' registro(s)</div>
<table>
<thead><tr><th>ID</th><th>Identificador</th><th>Proyecto</th><th>Obra</th><th>Fecha</th><th>Lugar</th><th>Observación</th><th>Estado</th></tr></thead>
<tbody>';

        foreach ($resultados as $row) {
            $badgeClass = match($row->id_estado_orden) {
                1 => 'badge-success',
                2 => 'badge-warning',
                default => 'badge-secondary'
            };
            $html .= '<tr>
                <td>' . $row->id_orden . '</td>
                <td><strong>' . $row->identificador . '</strong></td>
                <td>' . $row->proyecto . '</td>
                <td>' . $row->obra . '</td>
                <td>' . ($row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '-') . '</td>
                <td>' . $row->lugar . '</td>
                <td>' . ($row->observacion ?: '-') . '</td>
                <td><span class="' . $badgeClass . '">' . $row->estado_orden . '</span></td>
            </tr>';
        }

        $html .= '</tbody></table>
<script>window.onload = function() { window.print(); }</script>
</body></html>';

        return response($html)->header('Content-Type', 'text/html');
    }

    // --- REPORTE: INFORME PEDIDO DE MATERIALES ---
    public function informePedidoMateriales(Request $request)
    {
        $resultados = collect();

        if ($request->ajax() && $request->has('buscar')) {
            $resultados = $this->getPedidoMaterialesQuery($request);

            return response()->json([
                'html' => view('reportes.partials.tabla_pedido_materiales', compact('resultados'))->render(),
                'count' => $resultados->count()
            ]);
        } elseif ($request->has('buscar')) {
            $resultados = $this->getPedidoMaterialesQuery($request);
        }

        return view('reportes.informe_pedido_materiales', compact('resultados'));
    }

    private function getPedidoMaterialesQuery(Request $request)
    {
        $query = DB::table('pedido_materiales as pm')
            ->join('pedido_materiales_detalle as pmd', 'pm.id_pedido_material', '=', 'pmd.id_pedido_material')
            ->join('ordenes_trabajo as ot', 'pm.id_orden', '=', 'ot.id_orden')
            ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
            ->leftJoin('obra as o', 'ot.id_obra', '=', 'o.id')
            ->leftJoin('bodegas as b', 'ot.id_bodega_principal', '=', 'b.id_bodega')
            ->leftJoin('articulos as a', 'pmd.id_producto', '=', 'a.id_producto')
            ->select(
                'ot.id_orden',
                'ot.identificador',
                'p.nombre as proyecto',
                'o.nombre as obra',
                'pm.fecha_solicitud as fecha',
                'b.nombreBodega as lugar',
                'a.nombre as articulo',
                'pmd.cantidad'
            )
            ->where('ot.estado', 1)
            ->where('pmd.Estado', 1);

        if ($request->filled('id_orden')) {
            $query->where('pm.id_orden', $request->id_orden);
        }

        $resultados = $query->orderBy('pm.fecha_solicitud', 'desc')
                            ->orderBy('ot.identificador', 'asc')
                            ->get();

        return $resultados;
    }

    // AJAX: Buscar ordenes de trabajo para modal
    public function buscarOrdenesTrabajo(Request $request)
    {
        $busqueda = $request->get('q', '');
        $ordenes = DB::table('ordenes_trabajo as ot')
            ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
            ->leftJoin('obra as o', 'ot.id_obra', '=', 'o.id')
            ->select('ot.id_orden', 'ot.identificador', 'p.nombre as proyecto', 'o.nombre as obra')
            ->where('ot.estado', 1)
            ->where(function($q) use ($busqueda) {
                $q->where('ot.identificador', 'LIKE', "%{$busqueda}%")
                  ->orWhere('p.nombre', 'LIKE', "%{$busqueda}%")
                  ->orWhere('o.nombre', 'LIKE', "%{$busqueda}%")
                  ->orWhere('ot.id_orden', 'LIKE', "%{$busqueda}%");
            })
            ->orderBy('ot.identificador')
            ->limit(50)
            ->get();

        return response()->json($ordenes);
    }

    public function buscarBodegas(Request $request)
    {
        $busqueda = $request->get('q', '');
        $bodegas = DB::table('bodegas')
            ->select('id_bodega', 'nombreBodega')
            ->where('estado', 1)
            ->where('nombreBodega', 'LIKE', "%{$busqueda}%")
            ->orderBy('nombreBodega')
            ->limit(50)
            ->get();

        return response()->json($bodegas);
    }

    public function buscarArticulos(Request $request)
    {
        $busqueda = $request->get('q', '');
        $articulos = Articulo::select('id_producto', 'nombre')
            ->where('estado', 1)
            ->where(function($q) use ($busqueda) {
                $q->where('nombre', 'LIKE', "%{$busqueda}%")
                  ->orWhere('codigobarra', 'LIKE', "%{$busqueda}%")
                  ->orWhere('descripcion', 'LIKE', "%{$busqueda}%");
            })
            ->orderBy('nombre')
            ->limit(50)
            ->get();

        return response()->json($articulos);
    }

    // Exportar a Excel (CSV)
    public function exportarPedidoMateriales(Request $request)
    {
        $resultados = $this->getPedidoMaterialesQuery($request);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="pedido_materiales_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($resultados) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Id Orden', 'Identificador', 'Proyecto', 'Nombre De La Obra', 'Fecha', 'Lugar', 'Artículo', 'Cantidad'], ';');

            foreach ($resultados as $row) {
                fputcsv($file, [
                    $row->id_orden,
                    $row->identificador,
                    $row->proyecto,
                    $row->obra,
                    $row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '',
                    $row->lugar,
                    $row->articulo,
                    $row->cantidad
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Exportar a PDF (vista HTML imprimible)
    public function exportarPedidoMaterialesPdf(Request $request)
    {
        $resultados = $this->getPedidoMaterialesQuery($request);

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Informe de Pedido de Materiales</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; }
    h2 { text-align: center; margin-bottom: 4px; font-size: 16px; }
    .info { text-align: center; color: #666; margin-bottom: 15px; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #2c3e50; color: white; padding: 7px 5px; text-align: left; font-size: 10px; }
    td { padding: 6px 5px; border-bottom: 1px solid #ddd; font-size: 10px; }
    tr:nth-child(even) { background: #f5f5f5; }
    @media print { body { padding: 10px; } }
</style></head><body>
<h2>Informe de Pedido de Materiales</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . ' | Total: ' . $resultados->count() . ' registro(s)</div>
<table>
<thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Artículo</th><th>Cantidad</th></tr></thead>
<tbody>';

        foreach ($resultados as $row) {
            $html .= '<tr>
                <td>' . $row->id_orden . ' <small>(' . $row->identificador . ')</small></td>
                <td>' . $row->proyecto . '</td>
                <td>' . $row->obra . '</td>
                <td>' . ($row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '-') . '</td>
                <td>' . ($row->lugar ?: '-') . '</td>
                <td>' . ($row->articulo ?: '-') . '</td>
                <td><strong>' . number_format($row->cantidad, 2) . '</strong></td>
            </tr>';
        }

        $html .= '</tbody></table>
<script>window.onload = function() { window.print(); }</script>
</body></html>';

        return response($html)->header('Content-Type', 'text/html');
    }

    // --- REPORTE: REPORTE DE EJECUCION DE OBRA ---
    public function reporteEjecucionObra(Request $request)
    {
        $materiales = collect();
        $horas = collect();
        $informes = collect();
        $imagenes = collect();

        if ($request->ajax() && $request->has('buscar')) {
            $materiales = $this->getEjecucionMateriales($request);
            $horas = $this->getEjecucionHoras($request);
            $informes = $this->getEjecucionInformes($request);
            $imagenes = $this->getEjecucionInformesImagenes($request);

            return response()->json([
                'html_materiales' => view('reportes.partials.tabla_ejecucion_materiales', compact('materiales'))->render(),
                'html_horas' => view('reportes.partials.tabla_ejecucion_horas', compact('horas'))->render(),
                'html_informes' => view('reportes.partials.tabla_ejecucion_informes', compact('informes', 'imagenes'))->render(),
                'count_materiales' => $materiales->count(),
                'count_horas' => $horas->count(),
                'count_informes' => $informes->count()
            ]);
        } elseif ($request->has('buscar')) {
            $materiales = $this->getEjecucionMateriales($request);
            $horas = $this->getEjecucionHoras($request);
            $informes = $this->getEjecucionInformes($request);
            $imagenes = $this->getEjecucionInformesImagenes($request);
        }

        return view('reportes.reporte_ejecucion_obra', compact('materiales', 'horas', 'informes', 'imagenes'));
    }

    private function getEjecucionMateriales(Request $request)
    {
        $query = DB::table('ejecucion_obra_detalle_materiales_utilizar as det')
            ->join('ejecucion_obra as eo', 'det.id_ejecucion_obra', '=', 'eo.id_ejecucion_obra')
            ->join('ordenes_trabajo as ot', 'eo.id_orden', '=', 'ot.id_orden')
            ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
            ->leftJoin('obra as o', 'ot.id_obra', '=', 'o.id')
            ->leftJoin('bodegas as b', 'det.id_bodega_lugar', '=', 'b.id_bodega')
            ->leftJoin('articulos as a', 'det.id_producto', '=', 'a.id_producto')
            ->select(
                'ot.id_orden',
                'ot.identificador',
                'p.nombre as proyecto',
                'o.nombre as obra',
                'eo.fecha_solicitud as fecha',
                'b.nombreBodega as lugar',
                'a.nombre as articulo',
                'det.cantidad',
                'det.Contabilizado'
            )
            ->where('ot.estado', 1)
            ->where('det.Estado', 1);

        if ($request->filled('id_orden')) {
            $query->where('eo.id_orden', $request->id_orden);
        }

        return $query->orderBy('eo.fecha_solicitud', 'desc')->get();
    }

    private function getEjecucionHoras(Request $request)
    {
        $query = DB::table('horas_trabajo_diario_detalle as ht')
            ->join('ejecucion_obra as eo', 'ht.id_ejecucion_obra', '=', 'eo.id_ejecucion_obra')
            ->join('ordenes_trabajo as ot', 'eo.id_orden', '=', 'ot.id_orden')
            ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
            ->leftJoin('obra as o', 'ot.id_obra', '=', 'o.id')
            ->leftJoin('bodegas as b', 'ot.id_bodega_principal', '=', 'b.id_bodega')
            ->leftJoin('empleados as e', 'ht.id_empleado', '=', 'e.id_empleados')
            ->select(
                'ot.id_orden',
                'ot.identificador',
                'p.nombre as proyecto',
                'o.nombre as obra',
                'eo.fecha_solicitud as fecha',
                'b.nombreBodega as lugar',
                'e.nombres_apellidos as empleado',
                DB::raw('TIME_FORMAT(ht.hora_entrada, "%H:%i") as hora_entrada'),
                DB::raw('TIME_FORMAT(ht.hora_salida, "%H:%i") as hora_salida'),
                'ht.cantidad_horas_normal',
                'ht.cantidad_horas_extra',
                'ht.cantidad_horas_extraordinaria'
            )
            ->where('ot.estado', 1);

        if ($request->filled('id_orden')) {
            $query->where('eo.id_orden', $request->id_orden);
        }

        return $query->orderBy('eo.fecha_solicitud', 'desc')->orderBy('e.nombres_apellidos', 'asc')->get();
    }

    private function getEjecucionInformes(Request $request)
    {
        $query = DB::table('informe_diario_ejecucions as inf')
            ->join('ejecucion_obra as eo', 'inf.id_ejecucion_obra', '=', 'eo.id_ejecucion_obra')
            ->join('ordenes_trabajo as ot', 'eo.id_orden', '=', 'ot.id_orden')
            ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
            ->leftJoin('obra as o', 'ot.id_obra', '=', 'o.id')
            ->leftJoin('bodegas as b', 'ot.id_bodega_principal', '=', 'b.id_bodega')
            ->select(
                'inf.id_informe_diario_ejecucion',
                'ot.id_orden',
                'ot.identificador',
                'o.nombre as obra',
                'inf.fecha',
                'b.nombreBodega as lugar',
                'inf.lugar as ubicacion',
                'inf.observacion'
            )
            ->where('ot.estado', 1)
            ->where('inf.estado', 1);

        if ($request->filled('id_orden')) {
            $query->where('eo.id_orden', $request->id_orden);
        }

        return $query->orderBy('inf.fecha', 'desc')->get();
    }

    private function getEjecucionInformesImagenes(Request $request)
    {
        $query = DB::table('informe_diario_imagens as img')
            ->join('informe_diario_ejecucions as inf', 'img.id_informe_diario_ejecucion', '=', 'inf.id_informe_diario_ejecucion')
            ->join('ejecucion_obra as eo', 'inf.id_ejecucion_obra', '=', 'eo.id_ejecucion_obra')
            ->select(
                'img.id_informe_diario_imagen',
                'img.id_informe_diario_ejecucion',
                'img.ruta_imagen',
                'img.descripcion'
            )
            ->where('img.estado', 1);

        if ($request->filled('id_orden')) {
            $query->where('eo.id_orden', $request->id_orden);
        }

        return $query->get()->groupBy('id_informe_diario_ejecucion');
    }

    // Exportar Ejecucion Obra a Excel
    public function exportarEjecucionObra(Request $request)
    {
        $materiales = $this->getEjecucionMateriales($request);
        $horas = $this->getEjecucionHoras($request);
        $informes = $this->getEjecucionInformes($request);
        $imagenes = $this->getEjecucionInformesImagenes($request);

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8">
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Ejecución</x:Name></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    h2 { text-align: center; font-size: 16px; }
    h3 { font-size: 13px; color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 3px; margin-top: 20px; }
    .info { text-align: center; color: #666; font-size: 10px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    th { background: #2c3e50; color: white; padding: 6px 4px; text-align: left; font-size: 10px; }
    td { padding: 5px 4px; border-bottom: 1px solid #ddd; font-size: 10px; }
    tr:nth-child(even) { background: #f5f5f5; }
    .badge-si { background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
    .badge-no { background: #ffc107; color: #333; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
    .imgs-grid { display: flex; flex-wrap: wrap; gap: 4px; }
    .imgs-grid img { width: 40px; height: 40px; object-fit: cover; border: 1px solid #ccc; margin: 1px; }
</style></head><body>
<h2>Reporte de Ejecución de Obra</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . '</div>';

        // Materiales
        $html .= '<h3>Materiales a Utilizar (' . $materiales->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Articulo</th><th>Cantidad</th><th>Contabilizado</th></tr></thead><tbody>';
        foreach ($materiales as $r) {
            $html .= '<tr>
                <td>' . $r->identificador . '</td>
                <td>' . $r->proyecto . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->articulo ?: '-') . '</td>
                <td>' . number_format($r->cantidad, 2) . '</td>
                <td><span class="' . ($r->Contabilizado ? 'badge-si' : 'badge-no') . '">' . ($r->Contabilizado ? 'Sí' : 'No') . '</span></td>
            </tr>';
        }
        $html .= '</tbody></table>';

        // Horas
        $html .= '<h3>Horas de Trabajo (' . $horas->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Empleado</th><th>H.Entrada</th><th>H.Salida</th><th>H.Normal</th><th>H.Extras</th><th>H.Extraordinarias</th></tr></thead><tbody>';
        foreach ($horas as $r) {
            $html .= '<tr>
                <td>' . $r->identificador . '</td>
                <td>' . $r->proyecto . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . $r->empleado . '</td>
                <td>' . $r->hora_entrada . '</td>
                <td>' . $r->hora_salida . '</td>
                <td>' . $r->cantidad_horas_normal . '</td>
                <td>' . $r->cantidad_horas_extra . '</td>
                <td>' . $r->cantidad_horas_extraordinaria . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        // Informes Diarios
        $html .= '<h3>Informes Diarios (' . $informes->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Informe</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Ubicación</th><th>Observación</th><th>Imágenes</th></tr></thead><tbody>';
        foreach ($informes as $r) {
            $imgs = $imagenes->get($r->id_informe_diario_ejecucion, collect());
            $imgsHtml = '';
            foreach ($imgs as $img) {
                $imgsHtml .= '<img src="' . $img->ruta_imagen . '" style="width:40px;height:40px;object-fit:cover;border:1px solid #ccc;margin:1px;">';
            }
            $html .= '<tr>
                <td>' . $r->id_informe_diario_ejecucion . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->ubicacion ?: '-') . '</td>
                <td>' . ($r->observacion ?: '-') . '</td>
                <td class="imgs-grid">' . ($imgsHtml ?: '-') . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        $html .= '</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ejecucion_obra_' . date('Y-m-d') . '.xls"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        return response($html, 200, $headers);
    }

    // Exportar Ejecucion Obra a PDF
    public function exportarEjecucionObraPdf(Request $request)
    {
        $materiales = $this->getEjecucionMateriales($request);
        $horas = $this->getEjecucionHoras($request);
        $informes = $this->getEjecucionInformes($request);
        $imagenes = $this->getEjecucionInformesImagenes($request);

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Reporte de Ejecución de Obra</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 10px; color: #333; padding: 15px; }
    h2 { text-align: center; margin-bottom: 4px; font-size: 15px; }
    h3 { margin: 15px 0 5px; font-size: 12px; color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 3px; }
    .info { text-align: center; color: #666; margin-bottom: 10px; font-size: 9px; }
    table { width: 100%; border-collapse: collapse; margin-top: 5px; margin-bottom: 15px; }
    th { background: #2c3e50; color: white; padding: 5px 4px; text-align: left; font-size: 9px; }
    td { padding: 4px; border-bottom: 1px solid #ddd; font-size: 9px; }
    tr:nth-child(even) { background: #f5f5f5; }
    .badge { padding: 1px 6px; border-radius: 3px; font-size: 8px; }
    .badge-si { background: #28a745; color: white; }
    .badge-no { background: #ffc107; color: #333; }
    @media print { body { padding: 10px; } }
</style></head><body>
<h2>Reporte de Ejecución de Obra</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . '</div>';

        // Materiales
        $html .= '<h3>Materiales a Utilizar (' . $materiales->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Articulo</th><th>Cantidad</th><th>Contabilizado</th></tr></thead><tbody>';
        foreach ($materiales as $r) {
            $html .= '<tr><td>' . $r->identificador . '</td><td>' . $r->proyecto . '</td><td>' . $r->obra . '</td><td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '') . '</td><td>' . $r->lugar . '</td><td>' . $r->articulo . '</td><td>' . number_format($r->cantidad, 2) . '</td><td><span class="badge ' . ($r->Contabilizado ? 'badge-si' : 'badge-no') . '">' . ($r->Contabilizado ? 'Sí' : 'No') . '</span></td></tr>';
        }
        $html .= '</tbody></table>';

        // Horas
        $html .= '<h3>Horas de Trabajo (' . $horas->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Empleado</th><th>H.Entrada</th><th>H.Salida</th><th>H.Normal</th><th>H.Extras</th><th>H.Extraordinarias</th></tr></thead><tbody>';
        foreach ($horas as $r) {
            $html .= '<tr><td>' . $r->identificador . '</td><td>' . $r->proyecto . '</td><td>' . $r->obra . '</td><td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '') . '</td><td>' . $r->lugar . '</td><td>' . $r->empleado . '</td><td>' . $r->hora_entrada . '</td><td>' . $r->hora_salida . '</td><td>' . $r->cantidad_horas_normal . '</td><td>' . $r->cantidad_horas_extra . '</td><td>' . $r->cantidad_horas_extraordinaria . '</td></tr>';
        }
        $html .= '</tbody></table>';

        // Informes
        $html .= '<h3>Informes Diarios (' . $informes->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Informe</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Ubicación</th><th>Observación</th><th>Imágenes</th></tr></thead><tbody>';
        foreach ($informes as $r) {
            $imgs = $imagenes->get($r->id_informe_diario_ejecucion, collect());
            $imgsHtml = '';
            foreach ($imgs as $img) {
                $imgsHtml .= '<img src="' . $img->ruta_imagen . '" style="width:35px;height:35px;object-fit:cover;border-radius:3px;border:1px solid #ccc;margin-right:2px;">';
            }
            $html .= '<tr><td>' . $r->id_informe_diario_ejecucion . '</td><td>' . $r->obra . '</td><td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '') . '</td><td>' . $r->lugar . '</td><td>' . $r->ubicacion . '</td><td>' . ($r->observacion ?: '-') . '</td><td>' . ($imgsHtml ?: '-') . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<script>window.onload = function() { window.print(); }</script></body></html>';

        return response($html)->header('Content-Type', 'text/html');
    }

    // --- REPORTE: LIQUIDACION DE TRABAJO ---
    public function liquidacionTrabajo(Request $request)
    {
        $materiales = collect();
        $horas = collect();
        $informes = collect();
        $imagenes = collect();
        $liquidacion = null;
        $liquidacionImagenes = collect();

        if ($request->ajax() && $request->has('buscar')) {
            $materiales = $this->getLiqMateriales($request);
            $horas = $this->getLiqHoras($request);
            $informes = $this->getLiqInformes($request);
            $imagenes = $this->getEjecucionInformesImagenes($request);
            $liquidacion = $this->getOrCreateLiquidacion($request);
            $liquidacionImagenes = $liquidacion ? $liquidacion->imagenes()->where('estado', 1)->get() : collect();

            return response()->json([
                'html_materiales' => view('reportes.partials.tabla_ejecucion_materiales', compact('materiales'))->render(),
                'html_horas' => view('reportes.partials.tabla_ejecucion_horas', compact('horas'))->render(),
                'html_informes' => view('reportes.partials.tabla_ejecucion_informes', compact('informes', 'imagenes'))->render(),
                'mano_obra' => $liquidacion->mano_obra ?? '',
                'importante' => $liquidacion->importante ?? '',
                'html_imagenes_liq' => view('reportes.partials.tabla_liquidacion_imagenes', compact('liquidacionImagenes'))->render(),
                'liquidacion_id' => $liquidacion->id_liquidacion_trabajo ?? null,
                'count_materiales' => $materiales->count(),
                'count_horas' => $horas->count(),
                'count_informes' => $informes->count(),
                'count_imagenes_liq' => $liquidacionImagenes->count()
            ]);
        } elseif ($request->has('buscar')) {
            $materiales = $this->getLiqMateriales($request);
            $horas = $this->getLiqHoras($request);
            $informes = $this->getLiqInformes($request);
            $imagenes = $this->getEjecucionInformesImagenes($request);
            $liquidacion = $this->getOrCreateLiquidacion($request);
            $liquidacionImagenes = $liquidacion ? $liquidacion->imagenes()->where('estado', 1)->get() : collect();
        }

        return view('reportes.liquidacion_trabajo', compact('materiales', 'horas', 'informes', 'imagenes', 'liquidacion', 'liquidacionImagenes'));
    }

    private function getLiqMateriales(Request $request)
    {
        $query = DB::table('ejecucion_obra_detalle_materiales_utilizar as det')
            ->join('ejecucion_obra as eo', 'det.id_ejecucion_obra', '=', 'eo.id_ejecucion_obra')
            ->join('ordenes_trabajo as ot', 'eo.id_orden', '=', 'ot.id_orden')
            ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
            ->leftJoin('obra as o', 'ot.id_obra', '=', 'o.id')
            ->leftJoin('bodegas as b', 'det.id_bodega_lugar', '=', 'b.id_bodega')
            ->leftJoin('articulos as a', 'det.id_producto', '=', 'a.id_producto')
            ->select('ot.id_orden', 'ot.identificador', 'p.nombre as proyecto', 'o.nombre as obra', 'eo.fecha_solicitud as fecha', 'b.nombreBodega as lugar', 'a.nombre as articulo', 'det.cantidad', 'det.Contabilizado')
            ->where('ot.estado', 1)->where('det.Estado', 1);

        if ($request->filled('id_orden')) {
            $query->where('eo.id_orden', $request->id_orden);
        }
        return $query->orderBy('eo.fecha_solicitud', 'desc')->get();
    }

    private function getLiqHoras(Request $request)
    {
        $query = DB::table('horas_trabajo_diario_detalle as ht')
            ->join('ejecucion_obra as eo', 'ht.id_ejecucion_obra', '=', 'eo.id_ejecucion_obra')
            ->join('ordenes_trabajo as ot', 'eo.id_orden', '=', 'ot.id_orden')
            ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
            ->leftJoin('obra as o', 'ot.id_obra', '=', 'o.id')
            ->leftJoin('bodegas as b', 'ot.id_bodega_principal', '=', 'b.id_bodega')
            ->leftJoin('empleados as e', 'ht.id_empleado', '=', 'e.id_empleados')
            ->select('ot.id_orden', 'ot.identificador', 'p.nombre as proyecto', 'o.nombre as obra', 'eo.fecha_solicitud as fecha', 'b.nombreBodega as lugar', 'e.nombres_apellidos as empleado', DB::raw('TIME_FORMAT(ht.hora_entrada, "%H:%i") as hora_entrada'), DB::raw('TIME_FORMAT(ht.hora_salida, "%H:%i") as hora_salida'), 'ht.cantidad_horas_normal', 'ht.cantidad_horas_extra', 'ht.cantidad_horas_extraordinaria')
            ->where('ot.estado', 1);

        if ($request->filled('id_orden')) {
            $query->where('eo.id_orden', $request->id_orden);
        }
        return $query->orderBy('eo.fecha_solicitud', 'desc')->orderBy('e.nombres_apellidos', 'asc')->get();
    }

    private function getLiqInformes(Request $request)
    {
        $query = DB::table('informe_diario_ejecucions as inf')
            ->join('ejecucion_obra as eo', 'inf.id_ejecucion_obra', '=', 'eo.id_ejecucion_obra')
            ->join('ordenes_trabajo as ot', 'eo.id_orden', '=', 'ot.id_orden')
            ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
            ->leftJoin('obra as o', 'ot.id_obra', '=', 'o.id')
            ->leftJoin('bodegas as b', 'ot.id_bodega_principal', '=', 'b.id_bodega')
            ->select('inf.id_informe_diario_ejecucion', 'ot.id_orden', 'ot.identificador', 'o.nombre as obra', 'inf.fecha', 'b.nombreBodega as lugar', 'inf.lugar as ubicacion', 'inf.observacion')
            ->where('ot.estado', 1)->where('inf.estado', 1);

        if ($request->filled('id_orden')) {
            $query->where('eo.id_orden', $request->id_orden);
        }
        return $query->orderBy('inf.fecha', 'desc')->get();
    }

    private function getOrCreateLiquidacion(Request $request)
    {
        if (!$request->filled('id_orden')) return null;

        $orden = DB::table('ordenes_trabajo')->where('id_orden', $request->id_orden)->first();
        if (!$orden) return null;

        $liq = LiquidacionTrabajo::where('id_orden', $request->id_orden)->where('estado', 1)->first();

        if (!$liq) {
            $liq = LiquidacionTrabajo::create([
                'id_usuario' => Auth::id(),
                'id_orden' => $request->id_orden,
                'id_proyecto' => $orden->id_proyecto,
                'id_obra' => $orden->id_obra,
                'id_bodega' => $orden->id_bodega_principal,
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
                'estado' => 1
            ]);
        }

        return $liq;
    }

    // Guardar Mano de Obra e Importante
    public function guardarLiquidacion(Request $request)
    {
        $request->validate([
            'id_liquidacion' => 'required|exists:liquidacion_trabajo,id_liquidacion_trabajo',
            'mano_obra' => 'nullable|string',
            'importante' => 'nullable|string'
        ]);

        try {
            $liq = LiquidacionTrabajo::findOrFail($request->id_liquidacion);
            $liq->update([
                'mano_obra' => $request->mano_obra,
                'importante' => $request->importante,
                'fecha_actualizacion' => now()
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Liquidación guardada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    // Subir imagen a liquidación
    public function subirImagenLiquidacion(Request $request)
    {
        $request->validate([
            'id_liquidacion' => 'required|exists:liquidacion_trabajo,id_liquidacion_trabajo',
            'imagenes' => 'required|array',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240'
        ]);

        try {
            $liq = LiquidacionTrabajo::findOrFail($request->id_liquidacion);

            foreach ($request->file('imagenes') as $file) {
                $nombre = 'liq_' . time() . '_' . mt_rand(100000, 999999) . '.' . $file->getClientOriginalExtension();
                $imageData = base64_encode(file_get_contents($file->getRealPath()));
                $base64 = 'data:' . $file->getMimeType() . ';base64,' . $imageData;

                LiquidacionTrabajoImagen::create([
                    'id_liquidacion_trabajo' => $liq->id_liquidacion_trabajo,
                    'nombre' => $nombre,
                    'ruta' => $base64,
                    'fecha_subida' => now(),
                    'estado' => 1
                ]);
            }

            return response()->json(['success' => true, 'mensaje' => 'Imágenes subidas correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al subir: ' . $e->getMessage()]);
        }
    }

    // Eliminar imagen de liquidación
    public function eliminarImagenLiquidacion($id)
    {
        try {
            $img = LiquidacionTrabajoImagen::findOrFail($id);
            $img->update(['estado' => 0]);

            return response()->json(['success' => true, 'mensaje' => 'Imagen eliminada.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    }

    // Exportar Liquidación a Excel (HTML con imágenes embebidas)
    public function exportarLiquidacionTrabajo(Request $request)
    {
        $materiales = $this->getLiqMateriales($request);
        $horas = $this->getLiqHoras($request);
        $informes = $this->getLiqInformes($request);
        $imagenes = $this->getEjecucionInformesImagenes($request);
        $liquidacion = $this->getOrCreateLiquidacion($request);
        $liquidacionImagenes = $liquidacion ? $liquidacion->imagenes()->where('estado', 1)->get() : collect();

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8">
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Liquidación</x:Name></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    h2 { text-align: center; font-size: 16px; }
    h3 { font-size: 13px; color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 3px; margin-top: 20px; }
    .info { text-align: center; color: #666; font-size: 10px; margin-bottom: 10px; }
    .text-section { background: #f8f9fa; padding: 8px; border: 1px solid #ddd; white-space: pre-wrap; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    th { background: #2c3e50; color: white; padding: 6px 4px; text-align: left; font-size: 10px; }
    td { padding: 5px 4px; border-bottom: 1px solid #ddd; font-size: 10px; }
    tr:nth-child(even) { background: #f5f5f5; }
    .badge-si { background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
    .badge-no { background: #ffc107; color: #333; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
    .imgs-grid { display: flex; flex-wrap: wrap; gap: 4px; }
    .imgs-grid img { width: 50px; height: 50px; object-fit: cover; border: 1px solid #ccc; }
    .liq-imgs img { width: 60px; height: 60px; object-fit: cover; border: 1px solid #ccc; margin: 2px; }
</style></head><body>
<h2>Liquidación de Trabajo</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . '</div>';

        // Mano de Obra
        $html .= '<h3>Mano de Obra</h3>';
        $html .= '<div class="text-section">' . ($liquidacion->mano_obra ? strip_tags($liquidacion->mano_obra) : '-') . '</div>';

        // Importante
        $html .= '<h3>Importante</h3>';
        $html .= '<div class="text-section">' . ($liquidacion->importante ? strip_tags($liquidacion->importante) : '-') . '</div>';

        // Imágenes Adjuntas de Liquidación
        if ($liquidacionImagenes->count() > 0) {
            $html .= '<h3>Imágenes Adjuntas (' . $liquidacionImagenes->count() . ')</h3><div class="liq-imgs">';
            foreach ($liquidacionImagenes as $img) {
                // ruta ya contiene el base64 data URI completo
                $html .= '<img src="' . $img->ruta . '" alt="' . htmlspecialchars($img->nombre) . '">';
            }
            $html .= '</div>';
        }

        // Materiales
        $html .= '<h3>Materiales a Utilizar (' . $materiales->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Articulo</th><th>Cantidad</th><th>Contabilizado</th></tr></thead><tbody>';
        foreach ($materiales as $r) {
            $html .= '<tr>
                <td>' . $r->identificador . '</td>
                <td>' . $r->proyecto . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->articulo ?: '-') . '</td>
                <td>' . number_format($r->cantidad, 2) . '</td>
                <td><span class="' . ($r->Contabilizado ? 'badge-si' : 'badge-no') . '">' . ($r->Contabilizado ? 'Sí' : 'No') . '</span></td>
            </tr>';
        }
        $html .= '</tbody></table>';

        // Horas
        $html .= '<h3>Horas de Trabajo (' . $horas->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Empleado</th><th>H.Entrada</th><th>H.Salida</th><th>H.Normal</th><th>H.Extras</th><th>H.Extraordinarias</th></tr></thead><tbody>';
        foreach ($horas as $r) {
            $html .= '<tr>
                <td>' . $r->identificador . '</td>
                <td>' . $r->proyecto . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . $r->empleado . '</td>
                <td>' . $r->hora_entrada . '</td>
                <td>' . $r->hora_salida . '</td>
                <td>' . $r->cantidad_horas_normal . '</td>
                <td>' . $r->cantidad_horas_extra . '</td>
                <td>' . $r->cantidad_horas_extraordinaria . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        // Informes Diarios
        $html .= '<h3>Informes Diarios (' . $informes->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Informe</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Ubicación</th><th>Observación</th><th>Imágenes</th></tr></thead><tbody>';
        foreach ($informes as $r) {
            $imgs = $imagenes->get($r->id_informe_diario_ejecucion, collect());
            $imgsHtml = '';
            foreach ($imgs as $img) {
                $imgsHtml .= '<img src="' . $img->ruta_imagen . '" style="width:40px;height:40px;object-fit:cover;border:1px solid #ccc;margin:1px;">';
            }
            $html .= '<tr>
                <td>' . $r->id_informe_diario_ejecucion . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->ubicacion ?: '-') . '</td>
                <td>' . ($r->observacion ?: '-') . '</td>
                <td class="imgs-grid">' . ($imgsHtml ?: '-') . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        $html .= '</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="liquidacion_trabajo_' . date('Y-m-d') . '.xls"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        return response($html, 200, $headers);
    }

    // Exportar Liquidación a PDF (vista HTML imprimible)
    public function exportarLiquidacionTrabajoPdf(Request $request)
    {
        $materiales = $this->getLiqMateriales($request);
        $horas = $this->getLiqHoras($request);
        $informes = $this->getLiqInformes($request);
        $imagenes = $this->getEjecucionInformesImagenes($request);
        $liquidacion = $this->getOrCreateLiquidacion($request);
        $liquidacionImagenes = $liquidacion ? $liquidacion->imagenes()->where('estado', 1)->get() : collect();

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Liquidación de Trabajo</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 10px; color: #333; padding: 15px; }
    h2 { text-align: center; margin-bottom: 4px; font-size: 15px; }
    h3 { margin: 15px 0 5px; font-size: 12px; color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 3px; }
    .info { text-align: center; color: #666; margin-bottom: 10px; font-size: 9px; }
    table { width: 100%; border-collapse: collapse; margin-top: 5px; margin-bottom: 15px; }
    th { background: #2c3e50; color: white; padding: 5px 4px; text-align: left; font-size: 9px; }
    td { padding: 4px; border-bottom: 1px solid #ddd; font-size: 9px; }
    tr:nth-child(even) { background: #f5f5f5; }
    .text-section { background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 15px; white-space: pre-wrap; font-size: 10px; }
    .badge { padding: 1px 6px; border-radius: 3px; font-size: 8px; }
    .badge-si { background: #28a745; color: white; }
    .badge-no { background: #ffc107; color: #333; }
    .imgs-grid { display: flex; flex-wrap: wrap; gap: 5px; }
    .imgs-grid img { width: 40px; height: 40px; object-fit: cover; border-radius: 3px; border: 1px solid #ddd; }
    @media print { body { padding: 10px; } }
</style></head><body>
<h2>Liquidación de Trabajo</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . '</div>';

        // Mano de Obra
        $html .= '<h3>Mano de Obra</h3>';
        $html .= '<div class="text-section">' . ($liquidacion->mano_obra ? strip_tags($liquidacion->mano_obra) : '-') . '</div>';

        // Importante
        $html .= '<h3>Importante</h3>';
        $html .= '<div class="text-section">' . ($liquidacion->importante ? strip_tags($liquidacion->importante) : '-') . '</div>';

        // Imágenes Adjuntas de Liquidación
        if ($liquidacionImagenes->count() > 0) {
            $html .= '<h3>Imágenes Adjuntas (' . $liquidacionImagenes->count() . ')</h3><div class="imgs-grid">';
            foreach ($liquidacionImagenes as $img) {
                $html .= '<img src="' . $img->ruta . '">';
            }
            $html .= '</div>';
        }

        // Materiales
        $html .= '<h3>Materiales a Utilizar (' . $materiales->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Articulo</th><th>Cantidad</th><th>Contabilizado</th></tr></thead><tbody>';
        foreach ($materiales as $r) {
            $html .= '<tr>
                <td>' . $r->identificador . '</td>
                <td>' . $r->proyecto . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->articulo ?: '-') . '</td>
                <td>' . number_format($r->cantidad, 2) . '</td>
                <td><span class="badge ' . ($r->Contabilizado ? 'badge-si' : 'badge-no') . '">' . ($r->Contabilizado ? 'Sí' : 'No') . '</span></td>
            </tr>';
        }
        $html .= '</tbody></table>';

        // Horas
        $html .= '<h3>Horas de Trabajo (' . $horas->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Empleado</th><th>H.Entrada</th><th>H.Salida</th><th>H.Normal</th><th>H.Extras</th><th>H.Extraordinarias</th></tr></thead><tbody>';
        foreach ($horas as $r) {
            $html .= '<tr>
                <td>' . $r->identificador . '</td>
                <td>' . $r->proyecto . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . $r->empleado . '</td>
                <td>' . $r->hora_entrada . '</td>
                <td>' . $r->hora_salida . '</td>
                <td>' . $r->cantidad_horas_normal . '</td>
                <td>' . $r->cantidad_horas_extra . '</td>
                <td>' . $r->cantidad_horas_extraordinaria . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        // Informes Diarios
        $html .= '<h3>Informes Diarios (' . $informes->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>Id Informe</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Ubicación</th><th>Observación</th><th>Imágenes</th></tr></thead><tbody>';
        foreach ($informes as $r) {
            $imgs = $imagenes->get($r->id_informe_diario_ejecucion, collect());
            $imgsHtml = '';
            foreach ($imgs as $img) {
                $imgsHtml .= '<img src="' . $img->ruta_imagen . '" style="width:35px;height:35px;object-fit:cover;border-radius:3px;border:1px solid #ddd;margin-right:3px;">';
            }
            $html .= '<tr>
                <td>' . $r->id_informe_diario_ejecucion . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('d/m/Y', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->ubicacion ?: '-') . '</td>
                <td>' . ($r->observacion ?: '-') . '</td>
                <td>' . ($imgsHtml ?: '-') . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<script>window.onload = function() { window.print(); }</script>
</body></html>';

        return response($html)->header('Content-Type', 'text/html');
    }
}
