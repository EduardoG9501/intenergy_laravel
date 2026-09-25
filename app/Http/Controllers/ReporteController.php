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
            $query = $this->getKardexQuery($request);
            $resultados = $this->applyKardexFilters($query, $request)->paginate(50);

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
                DB::raw("COALESCE((SELECT SUM(sb.cantidad) FROM stock_productos_bodega sb WHERE sb.id_producto = sp.id_producto AND sb.id_bodega_principal = sp.id_bodega_principal AND sb.estado = 1), 0) AS stock_actual"),
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
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Kardex</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    h2 { text-align: center; font-size: 16px; margin: 8px 0; }
    .info { text-align: center; color: #666; font-size: 10px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #0d6efd; color: white; padding: 7px 5px; text-align: center; font-size: 10px; font-weight: bold; border: 1px solid #0d6efd; }
    td { padding: 6px 5px; border-bottom: 1px solid #dee2e6; font-size: 10px; text-align: center; }
    tr:nth-child(even) td { background: #f8fafc; }
    .badge-ent { background: #22c55e; color: white; padding: 2px 8px; font-size: 9px; font-weight: bold; }
    .badge-sal { background: #ef4444; color: white; padding: 2px 8px; font-size: 9px; font-weight: bold; }
    .stock-pos { color: #16a34a; font-weight: bold; }
    .stock-neg { color: #dc2626; font-weight: bold; }
</style></head><body>
<h2>Entrada / Salida (Kardex)</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . ' | Total: ' . $resultados->count() . ' registro(s)</div>
<table>
<thead><tr><th>ID ORDEN</th><th>PROYECTO</th><th>NOMBRE DE LA OBRA</th><th>LUGAR</th><th>FECHA</th><th>TIPO MOVIMIENTO</th><th>SUB TIPO MOVIMIENTO</th><th>ARTICULO</th><th>CANTIDAD</th><th>STOCK ACTUAL</th><th>Nº DOCUMENTO</th></tr></thead>
<tbody>';

        foreach ($resultados as $r) {
            $badgeClass = $r->tipo_movimiento === 'ENTRADA' ? 'badge-ent' : 'badge-sal';
            $stockActual = $r->stock_actual ?? 0;
            $html .= '<tr>
                <td>' . $r->id_orden . '</td>
                <td>' . ($r->proyecto ?: '-') . '</td>
                <td>' . ($r->nombre_obra ?: '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                <td><span class="' . $badgeClass . '">' . $r->tipo_movimiento . '</span></td>
                <td>' . ($r->sub_tipo_movimiento ?: '-') . '</td>
                <td>' . $r->articulo . '</td>
                <td>' . number_format($r->cantidad, 2) . '</td>
                <td class="' . ($stockActual > 0 ? 'stock-pos' : 'stock-neg') . '">' . number_format($stockActual, 2) . '</td>
                <td>' . $r->documento . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="kardex_' . date('Y-m-d') . '.xls"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response($html, 200, $headers);
    }

    // Exportar Kardex a PDF (HTML imprimible)
    public function exportarKardexPdf(Request $request)
    {
        $query = $this->getKardexQuery($request);
        $resultados = $this->applyKardexFilters($query, $request)->get();

        $logoSvg = $this->getLogoSvg();

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Entrada / Salida (Kardex)</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #1e293b; padding: 25px 30px; background: #f1f5f9; }
    .no-print { position: fixed; top: 18px; right: 18px; z-index: 100; display: flex; gap: 8px; align-items: center; }
    .btn-print, .btn-zoom { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #0d6efd; color: #fff; border: none; padding: 10px 16px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; box-shadow: 0 3px 10px rgba(13,110,253,0.35); }
    .btn-print:hover, .btn-zoom:hover { background: #0b5ed7; }
    .btn-zoom { width: 42px; height: 42px; font-size: 20px; padding: 0; }
    .zoom-label { background: #0f172a; color: #fff; font-size: 12px; font-weight: 700; padding: 8px 10px; border-radius: 6px; min-width: 52px; text-align: center; }
    .btn-print svg { width: 18px; height: 18px; fill: #fff; }
    #reportRoot { transform-origin: top left; transition: transform 0.15s ease; background: #fff; padding: 25px 30px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
    .header { display: flex; align-items: center; gap: 30px; margin-bottom: 25px; }
    .header-logo { flex-shrink: 0; }
    .header-text { flex-grow: 1; text-align: center; }
    .header-text h1 { font-size: 30px; font-weight: bold; color: #0f172a; letter-spacing: 0.3px; text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { background: #0d6efd; color: white; padding: 11px 8px; text-align: center; font-size: 12px; font-weight: bold; border: 1px solid #0d6efd; }
    td { padding: 9px 8px; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-align: center; color: #334155; }
    tr:nth-child(even) td { background: #f8fafc; }
    .badge-ent { background: #22c55e; color: white; padding: 3px 10px; font-size: 11px; font-weight: bold; display: inline-block; }
    .badge-sal { background: #ef4444; color: white; padding: 3px 10px; font-size: 11px; font-weight: bold; display: inline-block; }
    .stock-pos { color: #16a34a; font-weight: 700; }
    .stock-neg { color: #dc2626; font-weight: 700; }
    .footer { margin-top: 25px; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #e0e0e0; padding-top: 12px; }
    @media print {
        body { background: #fff; padding: 12px; }
        .no-print { display: none !important; }
        #reportRoot { transform: none !important; box-shadow: none; padding: 0; background: #fff; }
    }
</style></head><body>
<div class="no-print">
    <button type="button" class="btn-zoom" onclick="changeZoom(-0.1)" title="Reducir">−</button>
    <span class="zoom-label" id="zoomLabel">100%</span>
    <button type="button" class="btn-zoom" onclick="changeZoom(0.1)" title="Agrandar">+</button>
    <button type="button" class="btn-print" onclick="window.print()">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
        Imprimir
    </button>
</div>
<div id="reportRoot">
<div class="header">
    <div class="header-logo">' . $logoSvg . '</div>
    <div class="header-text">
        <h1>Entrada / Salida (Kardex)</h1>
    </div>
</div>
<table>
<thead><tr><th>ID ORDEN</th><th>PROYECTO</th><th>NOMBRE DE LA OBRA</th><th>LUGAR</th><th>FECHA</th><th>TIPO MOVIMIENTO</th><th>SUB TIPO</th><th>ARTICULO</th><th>CANTIDAD</th><th>STOCK ACTUAL</th><th>Nº DOCUMENTO</th></tr></thead>
<tbody>';

        foreach ($resultados as $r) {
            $badgeClass = $r->tipo_movimiento === 'ENTRADA' ? 'badge-ent' : 'badge-sal';
            $stockActual = $r->stock_actual ?? 0;
            $html .= '<tr>
                <td>' . $r->id_orden . '</td>
                <td>' . ($r->proyecto ?: '-') . '</td>
                <td>' . ($r->nombre_obra ?: '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                <td><span class="' . $badgeClass . '">' . $r->tipo_movimiento . '</span></td>
                <td>' . ($r->sub_tipo_movimiento ?: '-') . '</td>
                <td>' . $r->articulo . '</td>
                <td><strong>' . number_format($r->cantidad, 2) . '</strong></td>
                <td class="' . ($stockActual > 0 ? 'stock-pos' : 'stock-neg') . '">' . number_format($stockActual, 2) . '</td>
                <td>' . $r->documento . '</td>
            </tr>';
        }

        $html .= '</tbody></table>
<div class="footer">Generado: ' . date('d/m/Y H:i') . ' | Total: ' . $resultados->count() . ' registro(s) | INTENERGY</div>
</div>
<script>
var currentZoom = 1;
function changeZoom(delta) {
    currentZoom = Math.min(3, Math.max(0.5, Math.round((currentZoom + delta) * 10) / 10));
    document.getElementById("reportRoot").style.transform = "scale(" + currentZoom + ")";
    document.getElementById("zoomLabel").textContent = Math.round(currentZoom * 100) + "%";
}
</script>
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

    private function getLogoSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="140" height="80" viewBox="0 0 140 80">
            <rect width="140" height="80" rx="2" fill="#111827"/>
            <path d="M84 5 L120 5 C128 5, 132 13, 129 24 L113 68 C110 75, 103 78, 97 75 L84 5 Z" fill="#15803d"/>
            <path d="M92 9 L114 9 C120 9, 123 15, 121 23 L108 60 C106 66, 101 68, 96 66 L92 9 Z" fill="#22c55e"/>
            <path d="M99 13 L110 13 C114 13, 116 17, 115 22 L106 52 C105 56, 101 58, 98 56 L99 13 Z" fill="#4ade80"/>
            <text x="10" y="38" font-family="Arial, Helvetica, sans-serif" font-size="16" font-weight="bold" fill="#ffffff">INTENERGY</text>
            <text x="10" y="55" font-family="Arial, Helvetica, sans-serif" font-size="8" fill="#94a3b8">Tecnología en energía</text>
        </svg>';
    }

    // Exportar a PDF (vista HTML imprimible)
    public function exportarOrdenesTrabajoPdf(Request $request)
    {
        $resultados = $this->getOrdenesTrabajoQuery($request);

        $proyectoNombre = 'Todos';
        if ($request->filled('proyecto')) {
            $p = DB::table('proyecto')->where('id', $request->proyecto)->first();
            $proyectoNombre = $p ? $p->nombre : 'Todos';
        }
        $obraNombre = 'Todas';
        if ($request->filled('obra')) {
            $o = DB::table('obra')->where('id', $request->obra)->first();
            $obraNombre = $o ? $o->nombre : 'Todas';
        }
        $estadoNombre = 'Todos';
        if ($request->filled('estado')) {
            $e = DB::table('estado_ordenes')->where('id_estado_orden', $request->estado)->first();
            $estadoNombre = $e ? $e->estado : 'Todos';
        }
        $fechaDesde = $request->filled('fecha_desde') ? date('Y-m-d', strtotime($request->fecha_desde)) : date('Y-m-01');
        $fechaHasta = $request->filled('fecha_hasta') ? date('Y-m-d', strtotime($request->fecha_hasta)) : date('Y-m-d');

        $logoSvg = $this->getLogoSvg();

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Reporte de Ordenes de Trabajo</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #1e293b; padding: 25px 30px; }
    .no-print { position: fixed; top: 18px; right: 18px; z-index: 100; }
    .btn-print { display: inline-flex; align-items: center; gap: 8px; background: #0d6efd; color: #fff; border: none; padding: 10px 22px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; box-shadow: 0 3px 10px rgba(13,110,253,0.35); }
    .btn-print:hover { background: #0b5ed7; }
    .btn-print svg { width: 18px; height: 18px; fill: #fff; }
    .header { display: flex; align-items: flex-start; gap: 30px; margin-bottom: 18px; }
    .header-logo { flex-shrink: 0; }
    .header-text { flex-grow: 1; padding-top: 4px; text-align: center; }
    .header-text h1 { font-size: 32px; font-weight: bold; color: #0f172a; margin-bottom: 8px; letter-spacing: 0.3px; text-align: center; }
    .header-text .dates { font-size: 18px; color: #334155; font-weight: 400; text-align: center; }
    .filters { margin-bottom: 20px; line-height: 1.7; }
    .filters .filters-title { font-size: 14px; font-weight: bold; color: #0f172a; text-decoration: underline; margin-bottom: 4px; display: inline-block; }
    .filters p { font-size: 13.5px; color: #1e293b; margin: 2px 0; }
    .filters strong { font-weight: 700; color: #0f172a; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th { background: #1976d2; color: white; padding: 11px 10px; text-align: left; font-size: 13px; font-weight: bold; }
    td { padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 13px; text-align: left; color: #334155; }
    tr:nth-child(even) td { background: #f8fafc; }
    .badge-estado { display: inline-block; padding: 4px 14px; border-radius: 3px; font-size: 12px; font-weight: bold; color: #fff; }
    .badge-aprobado { background: #22c55e; }
    .badge-grabado { background: #22c55e; }
    .badge-pendiente { background: #eab308; color: #1e293b; }
    .badge-otro { background: #94a3b8; }
    @media print {
        body { padding: 12px; }
        .no-print { display: none !important; }
    }
</style></head><body>
<div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
        Imprimir
    </button>
</div>
<div class="header">
    <div class="header-logo">' . $logoSvg . '</div>
    <div class="header-text">
        <h1>Reporte de Ordenes de Trabajo</h1>
        <div class="dates">DESDE: ' . $fechaDesde . '&nbsp;&nbsp;HASTA: ' . $fechaHasta . '</div>
    </div>
</div>
<div class="filters">
    <div class="filters-title">Filtros aplicados:</div>
    <p><strong>Lugar:</strong> Todos</p>
    <p><strong>Estado:</strong> ' . e($estadoNombre) . '</p>
    <p><strong>Proyecto:</strong> ' . e($proyectoNombre) . '</p>
    <p><strong>Nombre de la Obra:</strong> ' . e($obraNombre) . '</p>
</div>
<table>
<thead><tr><th>ID ORDEN</th><th>PROYECTO</th><th>NOMBRE DE LA OBRA</th><th>FECHA</th><th>LUGAR</th><th>OBSERVACION</th><th>ESTADO</th></tr></thead>
<tbody>';

        foreach ($resultados as $row) {
            $et = strtoupper($row->estado_orden ?? '');
            if (str_contains($et, 'APROB') || str_contains($et, 'GRAB')) {
                $estadoClass = 'badge-aprobado';
            } elseif (str_contains($et, 'PEND')) {
                $estadoClass = 'badge-pendiente';
            } else {
                $estadoClass = 'badge-otro';
            }

            $html .= '<tr>
                <td>' . $row->id_orden . '</td>
                <td>' . e($row->proyecto) . '</td>
                <td>' . e($row->obra) . '</td>
                <td>' . ($row->fecha ? date('Y-m-d', strtotime($row->fecha)) : '-') . '</td>
                <td>' . e($row->lugar) . '</td>
                <td>' . e($row->observacion ?: '-') . '</td>
                <td><span class="badge-estado ' . $estadoClass . '">' . e($row->estado_orden) . '</span></td>
            </tr>';
        }

        $html .= '</tbody></table>
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

        $resultados = $query->orderBy('a.nombre', 'asc')
                            ->orderBy('pm.fecha_solicitud', 'desc')
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

        $filename = 'pedido_materiales_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
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

        // Obtener info de filtros
        $identificador = '';
        $proyecto = '';
        $obra = '';
        if ($request->filled('id_orden')) {
            $ot = DB::table('ordenes_trabajo as ot')
                ->leftJoin('proyecto as p', 'ot.id_proyecto', '=', 'p.id')
                ->leftJoin('obra as o', 'ot.id_obra', '=', 'o.id')
                ->select('ot.identificador', 'p.nombre as proyecto', 'o.nombre as obra')
                ->where('ot.id_orden', $request->id_orden)
                ->first();
            if ($ot) {
                $identificador = $ot->identificador;
                $proyecto = $ot->proyecto ?? '';
                $obra = $ot->obra ?? '';
            }
        } elseif ($resultados->isNotEmpty()) {
            $identificador = $resultados->first()->identificador;
            $proyecto = $resultados->first()->proyecto;
            $obra = $resultados->first()->obra;
        }

        // Agrupar artículos
        $agrupados = [];
        $totales = [];
        $granTotal = 0;
        foreach ($resultados as $row) {
            $articulo = $row->articulo ?: 'Sin artículo';
            if (!isset($agrupados[$articulo])) {
                $agrupados[$articulo] = [];
                $totales[$articulo] = 0;
            }
            $agrupados[$articulo][] = $row;
            $totales[$articulo] += $row->cantidad;
            $granTotal += $row->cantidad;
        }

        $logoSvg = $this->getLogoSvg();

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>ReportePedidoMateriales</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #0f172a; padding: 25px 30px; }
    .no-print { position: fixed; top: 18px; right: 18px; z-index: 100; }
    .btn-print { display: inline-flex; align-items: center; gap: 8px; background: #0d6efd; color: #fff; border: none; padding: 10px 22px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; box-shadow: 0 3px 10px rgba(13,110,253,0.35); }
    .btn-print:hover { background: #0b5ed7; }
    .btn-print svg { width: 18px; height: 18px; fill: #fff; }
    .header { display: flex; align-items: center; gap: 30px; margin-bottom: 25px; }
    .header-logo { flex-shrink: 0; }
    .header-text { flex-grow: 1; text-align: center; }
    .header-text h1 { font-size: 30px; font-weight: bold; color: #0f172a; letter-spacing: 0.3px; text-align: center; }
    .filters { display: grid; grid-template-columns: 130px 1fr; row-gap: 6px; column-gap: 15px; margin-bottom: 25px; max-width: 750px; }
    .filters .label { font-weight: bold; color: #0f172a; font-size: 14px; }
    .filters .value { color: #1e293b; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th { background: #0d6efd; color: white; padding: 12px 12px; font-size: 14px; font-weight: bold; text-align: center; }
    th.col-cantidad { text-align: right; }
    td { padding: 11px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13.5px; color: #334155; }
    td.fecha, td.lugar { text-align: center; }
    td.articulo { text-align: left; }
    td.cantidad { text-align: right; font-weight: bold; color: #0f172a; }
    tr:nth-child(even) td { background: #f8fafc; }
    .row-total td { background: #f8fafc !important; border-bottom: 1px solid #e2e8f0; }
    .row-total td.total-label { text-align: right; font-weight: 700; color: #0f172a; font-size: 13.5px; }
    .row-total td.cantidad { font-weight: 700; }
    .row-grand-total td { background: #0d6efd !important; color: #ffffff; font-weight: 700; border: none; }
    .row-grand-total td.total-label { text-align: right; font-size: 14px; }
    .row-grand-total td.cantidad { text-align: right; color: #ffffff; font-size: 14px; }
    @media print {
        body { padding: 12px; }
        .no-print { display: none !important; }
    }
</style></head><body>
<div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
        Imprimir
    </button>
</div>
<div class="header">
    <div class="header-logo">' . $logoSvg . '</div>
    <div class="header-text">
        <h1>REPORTE DE PEDIDOS DE MATERIALES</h1>
    </div>
</div>
<div class="filters">
    <div class="label">PROYECTO:</div><div class="value">' . e($proyecto ?: 'Todos') . '</div>
    <div class="label">OBRA:</div><div class="value">' . e($obra ?: 'Todas') . '</div>
    <div class="label">ID ORDEN:</div><div class="value">' . e($identificador ?: '-') . '</div>
</div>
<table>
<thead><tr><th>FECHA</th><th>LUGAR</th><th>ARTICULO</th><th class="col-cantidad">CANTIDAD</th></tr></thead>
<tbody>';

        foreach ($agrupados as $articulo => $items) {
            foreach ($items as $row) {
                $html .= '<tr>
                    <td class="fecha">' . ($row->fecha ? date('Y-m-d', strtotime($row->fecha)) : '-') . '</td>
                    <td class="lugar">' . e($row->lugar ?: '-') . '</td>
                    <td class="articulo">' . e($row->articulo) . '</td>
                    <td class="cantidad">' . number_format($row->cantidad, 2) . '</td>
                </tr>';
            }
            $html .= '<tr class="row-total">
                <td class="fecha"></td>
                <td class="lugar"></td>
                <td class="total-label">TOTALES ' . strtoupper($articulo) . ':</td>
                <td class="cantidad">' . number_format($totales[$articulo], 2) . '</td>
            </tr>';
        }

        $html .= '<tr class="row-grand-total">
            <td class="fecha"></td>
            <td class="lugar"></td>
            <td class="total-label">TOTALES GENERALES:</td>
            <td class="cantidad">' . number_format($granTotal, 2) . '</td>
        </tr>';

        $html .= '</tbody></table>
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

    // Detalle de informes (artículos, empleados, descripciones) agrupado por informe
    private function getEjecucionInformesDetalles($informes)
    {
        $ids = $informes->pluck('id_informe_diario_ejecucion')->all();
        if (empty($ids)) {
            return collect();
        }

        return \App\Models\InformeDiarioEjecucion::with(['empleados.empleado', 'articulos.producto', 'detalles'])
            ->whereIn('id_informe_diario_ejecucion', $ids)
            ->get()
            ->keyBy('id_informe_diario_ejecucion');
    }

    // Fila de detalle de un informe: Artículos | Empleados | Imágenes | Descripción
    private function buildInformeDetalleHtml($idInforme, $detalles, $imagenes, $isPdf = false)
    {
        $inf = $detalles->get($idInforme);
        $imgs = $imagenes->get($idInforme, collect());

        $arts = [];
        $emps = [];
        $descs = [];
        if ($inf) {
            foreach ($inf->articulos as $a) {
                $nombre = $a->producto ? $a->producto->nombre : 'N/A';
                $arts[] = e($nombre) . ' (' . $a->cantidad . ') [Lote :' . e($a->lote ?: '-') . ']';
            }
            foreach ($inf->empleados as $emp) {
                $emps[] = e($emp->empleado ? $emp->empleado->nombres_apellidos : 'N/A');
            }
            if ($inf->descripcion) {
                $descs[] = $inf->descripcion;
            }
            foreach ($inf->detalles as $d) {
                $descs[] = $d->descripcion;
            }
        }

        $imgsHtml = '';
        foreach ($imgs as $img) {
            $size = $isPdf ? 80 : 70;
            $imgsHtml .= '<img src="' . $img->ruta_imagen . '" style="width:' . $size . 'px;height:' . $size . 'px;object-fit:cover;border:1px solid #ccc;margin:2px;">';
        }

        $font = $isPdf ? 'font-size:12px;' : 'font-size:10px;';
        $cell = 'vertical-align:top;text-align:left;padding:8px 10px;border-bottom:1px solid #e2e8f0;line-height:1.6;' . $font;
        $label = 'font-weight:bold;color:#0f172a;';

        $artHtml = implode('<br>', $arts) ?: '-';
        $empHtml = implode('<br>', $emps) ?: '-';
        $imgHtml = $imgsHtml ?: '-';
        $descHtml = implode('<br>', array_map('e', $descs)) ?: '-';

        return '<tr>
            <td colspan="6" style="padding:0;background:#f1f5f9;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="width:30%;' . $cell . '"><span style="' . $label . '">Artículos:</span><br>' . $artHtml . '</td>
                        <td style="width:22%;' . $cell . '"><span style="' . $label . '">Empleados:</span><br>' . $empHtml . '</td>
                        <td style="width:23%;' . $cell . '"><span style="' . $label . '">Imágenes:</span><br>' . $imgHtml . '</td>
                        <td style="width:25%;' . $cell . '"><span style="' . $label . '">Descripción:</span><br>' . $descHtml . '</td>
                    </tr>
                </table>
            </td>
        </tr>';
    }

    // Exportar Ejecucion Obra a Excel
    public function exportarEjecucionObra(Request $request)
    {
        $materiales = $this->getEjecucionMateriales($request)->sortBy('articulo')->values();
        $horas = $this->getEjecucionHoras($request);
        $informes = $this->getEjecucionInformes($request);

        // Agrupar materiales por artículo
        $agrupados = [];
        $totalesMat = [];
        $granTotalMat = 0;
        foreach ($materiales as $row) {
            $articulo = $row->articulo ?: 'Sin artículo';
            if (!isset($agrupados[$articulo])) {
                $agrupados[$articulo] = [];
                $totalesMat[$articulo] = 0;
            }
            $agrupados[$articulo][] = $row;
            $totalesMat[$articulo] += $row->cantidad;
            $granTotalMat += $row->cantidad;
        }

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8">
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Ejecucion</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    h2 { text-align: center; font-size: 16px; margin: 8px 0; }
    h3 { font-size: 13px; color: #0f172a; border-bottom: 2px solid #0d6efd; padding-bottom: 3px; margin-top: 22px; text-transform: uppercase; }
    .info { text-align: center; color: #666; font-size: 10px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    th { background: #0d6efd; color: white; padding: 7px 5px; text-align: center; font-size: 10px; font-weight: bold; border: 1px solid #0d6efd; }
    td { padding: 6px 5px; border-bottom: 1px solid #dee2e6; font-size: 10px; text-align: center; }
    tr:nth-child(even) td { background: #f8fafc; }
    .row-subtotal td { background: #d6eaff !important; font-weight: bold; border-top: 2px solid #0d6efd; border-left: 3px solid #0d6efd; }
    .row-grand-total td { background: #0d6efd !important; color: white; font-weight: bold; }
    .badge-si { background: #22c55e; color: white; padding: 2px 8px; }
    .badge-no { background: #eab308; color: #1e293b; padding: 2px 8px; }
</style></head><body>
<h2>Reporte de Ejecucion de Obra</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . '</div>';

        // Materiales agrupados
        $html .= '<h3>Materiales a Utilizar (' . $materiales->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>ID ORDEN</th><th>PROYECTO</th><th>NOMBRE DE LA OBRA</th><th>FECHA</th><th>LUGAR</th><th>ARTICULO</th><th>CANTIDAD</th><th>CONTABILIZADO</th></tr></thead><tbody>';
        foreach ($agrupados as $articulo => $items) {
            foreach ($items as $r) {
                $html .= '<tr>
                    <td>' . $r->identificador . '</td>
                    <td>' . $r->proyecto . '</td>
                    <td>' . $r->obra . '</td>
                    <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                    <td>' . ($r->lugar ?: '-') . '</td>
                    <td>' . ($r->articulo ?: '-') . '</td>
                    <td>' . number_format($r->cantidad, 2) . '</td>
                    <td><span class="' . ($r->Contabilizado ? 'badge-si' : 'badge-no') . '">' . ($r->Contabilizado ? 'SI' : 'NO') . '</span></td>
                </tr>';
            }
            $html .= '<tr class="row-subtotal">
                <td colspan="5"></td>
                <td style="text-align:right;">Subtotal ' . strtoupper($articulo) . ':</td>
                <td>' . number_format($totalesMat[$articulo], 2) . '</td>
                <td></td>
            </tr>';
        }
        $html .= '<tr class="row-grand-total">
            <td colspan="6" style="text-align:right;">TOTALES GENERALES:</td>
            <td>' . number_format($granTotalMat, 2) . '</td>
            <td></td>
        </tr>';
        $html .= '</tbody></table>';

        // Horas
        $html .= '<h3>Horas de Trabajo (' . $horas->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>ID ORDEN</th><th>PROYECTO</th><th>NOMBRE DE LA OBRA</th><th>FECHA</th><th>LUGAR</th><th>EMPLEADO</th><th>H.ENTRADA</th><th>H.SALIDA</th><th>H.NORMAL</th><th>H.EXTRAS</th><th>H.EXTRAORD.</th></tr></thead><tbody>';
        foreach ($horas as $r) {
            $html .= '<tr>
                <td>' . $r->identificador . '</td>
                <td>' . $r->proyecto . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
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

        // Informes Diarios (con detalle: artículos, empleados, imágenes y descripción)
        $informeDetalles = $this->getEjecucionInformesDetalles($informes);
        $imagenesInf = $this->getEjecucionInformesImagenes($request);

        $html .= '<h3>Informes Diarios (' . $informes->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>NOMBRE DE LA OBRA</th><th>ORDEN TRABAJO</th><th>FECHA</th><th>LUGAR</th><th>UBICACIÓN</th><th>OBSERVACIÓN</th></tr></thead><tbody>';
        foreach ($informes as $r) {
            $html .= '<tr>
                <td>' . $r->obra . '</td>
                <td>' . $r->identificador . '</td>
                <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->ubicacion ?: '-') . '</td>
                <td>' . ($r->observacion ?: '-') . '</td>
            </tr>';
            $html .= $this->buildInformeDetalleHtml($r->id_informe_diario_ejecucion, $informeDetalles, $imagenesInf, false);
        }
        $html .= '</tbody></table>';

        $html .= '</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ejecucion_obra_' . date('Y-m-d') . '.xls"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response($html, 200, $headers);
    }

    // Exportar Ejecucion Obra a PDF
    public function exportarEjecucionObraPdf(Request $request)
    {
        $materiales = $this->getEjecucionMateriales($request)->sortBy('articulo')->values();
        $horas = $this->getEjecucionHoras($request);
        $informes = $this->getEjecucionInformes($request);

        // Agrupar materiales por artículo
        $agrupados = [];
        $totalesMat = [];
        $granTotalMat = 0;
        foreach ($materiales as $row) {
            $articulo = $row->articulo ?: 'Sin artículo';
            if (!isset($agrupados[$articulo])) {
                $agrupados[$articulo] = [];
                $totalesMat[$articulo] = 0;
            }
            $agrupados[$articulo][] = $row;
            $totalesMat[$articulo] += $row->cantidad;
            $granTotalMat += $row->cantidad;
        }

        $logoSvg = $this->getLogoSvg();

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Reporte de Ejecución de Obra</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #1e293b; padding: 25px 30px; }
    .no-print { position: fixed; top: 18px; right: 18px; z-index: 100; }
    .btn-print { display: inline-flex; align-items: center; gap: 8px; background: #0d6efd; color: #fff; border: none; padding: 10px 22px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; box-shadow: 0 3px 10px rgba(13,110,253,0.35); }
    .btn-print:hover { background: #0b5ed7; }
    .btn-print svg { width: 18px; height: 18px; fill: #fff; }
    .header { display: flex; align-items: center; gap: 30px; margin-bottom: 25px; }
    .header-logo { flex-shrink: 0; }
    .header-text { flex-grow: 1; text-align: center; }
    .header-text h1 { font-size: 30px; font-weight: bold; color: #0f172a; letter-spacing: 0.3px; text-align: center; }
    h3.section-title { margin: 22px 0 8px; font-size: 16px; color: #0f172a; text-transform: uppercase; font-weight: bold; display: inline-block; border-bottom: 3px solid #0d6efd; padding-bottom: 4px; letter-spacing: 0.5px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 6px; }
    th { background: #0d6efd; color: white; padding: 11px 10px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #0d6efd; }
    td { padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 13px; text-align: center; color: #334155; }
    tr:nth-child(even) td { background: #f8fafc; }
    .row-subtotal td { background: #d6eaff !important; font-weight: 700; color: #0f172a; border-left: 3px solid #0d6efd; border-top: 1px solid #b6d4fe; }
    .row-subtotal td.sub-label { text-align: right; }
    .row-grand-total td { background: #0d6efd !important; color: #ffffff !important; font-weight: 700; border: none; }
    .row-grand-total td.sub-label { text-align: right; }
    .badge { padding: 3px 10px; border-radius: 3px; font-size: 11px; font-weight: bold; display: inline-block; }
    .badge-si { background: #22c55e; color: #fff; }
    .badge-no { background: #eab308; color: #1e293b; }
    .footer { margin-top: 25px; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #e0e0e0; padding-top: 12px; }
    @media print {
        body { padding: 12px; }
        .no-print { display: none !important; }
    }
</style></head><body>
<div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
        Imprimir
    </button>
</div>
<div class="header">
    <div class="header-logo">' . $logoSvg . '</div>
    <div class="header-text">
        <h1>Reporte de Ejecución de Obra</h1>
    </div>
</div>';

        // Materiales agrupados
        $html .= '<h3 class="section-title">Materiales a Utilizar (' . $materiales->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>FECHA</th><th>LUGAR</th><th>ARTICULO</th><th>CANTIDAD</th><th>CONTABILIZADO</th></tr></thead><tbody>';
        foreach ($agrupados as $articulo => $items) {
            foreach ($items as $r) {
                $html .= '<tr>
                    <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                    <td>' . e($r->lugar ?: '-') . '</td>
                    <td>' . e($r->articulo ?: '-') . '</td>
                    <td><strong>' . number_format($r->cantidad, 2) . '</strong></td>
                    <td><span class="badge ' . ($r->Contabilizado ? 'badge-si' : 'badge-no') . '">' . ($r->Contabilizado ? 'SI' : 'NO') . '</span></td>
                </tr>';
            }
            $html .= '<tr class="row-subtotal">
                <td></td>
                <td></td>
                <td class="sub-label">Subtotal ' . e($articulo) . '</td>
                <td>' . number_format($totalesMat[$articulo], 2) . '</td>
                <td></td>
            </tr>';
        }
        $html .= '<tr class="row-grand-total">
            <td colspan="3" class="sub-label">TOTALES GENERALES:</td>
            <td>' . number_format($granTotalMat, 2) . '</td>
            <td></td>
        </tr>';
        $html .= '</tbody></table>';

        // Horas
        $html .= '<h3 class="section-title">Horas de Trabajo (' . $horas->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>FECHA</th><th>LUGAR</th><th>EMPLEADO</th><th>H.ENTRADA</th><th>H.SALIDA</th><th>H.NORMAL</th><th>H.EXTRAS</th><th>H.EXTRAORD.</th></tr></thead><tbody>';
        foreach ($horas as $r) {
            $html .= '<tr>
                <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                <td>' . e($r->lugar ?: '-') . '</td>
                <td>' . e($r->empleado) . '</td>
                <td>' . $r->hora_entrada . '</td>
                <td>' . $r->hora_salida . '</td>
                <td>' . $r->cantidad_horas_normal . '</td>
                <td>' . $r->cantidad_horas_extra . '</td>
                <td>' . $r->cantidad_horas_extraordinaria . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        // Informes (con detalle: artículos, empleados, imágenes y descripción)
        $informeDetalles = $this->getEjecucionInformesDetalles($informes);
        $imagenesInf = $this->getEjecucionInformesImagenes($request);

        $html .= '<h3 class="section-title">Informes Diarios (' . $informes->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>NOMBRE DE LA OBRA</th><th>ORDEN TRABAJO</th><th>FECHA</th><th>LUGAR</th><th>UBICACIÓN</th><th>OBSERVACIÓN</th></tr></thead><tbody>';
        foreach ($informes as $r) {
            $html .= '<tr>
                <td>' . e($r->obra) . '</td>
                <td>' . e($r->identificador) . '</td>
                <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                <td>' . e($r->lugar ?: '-') . '</td>
                <td>' . e($r->ubicacion ?: '-') . '</td>
                <td>' . e($r->observacion ?: '-') . '</td>
            </tr>';
            $html .= $this->buildInformeDetalleHtml($r->id_informe_diario_ejecucion, $informeDetalles, $imagenesInf, true);
        }
        $html .= '</tbody></table>';

        $html .= '<div class="footer">Generado: ' . date('d/m/Y H:i') . ' | INTENERGY</div>
</body></html>';

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
        $materiales = $this->getLiqMateriales($request)->sortBy('articulo')->values();
        $horas = $this->getLiqHoras($request);
        $informes = $this->getLiqInformes($request);
        $liquidacion = $this->getOrCreateLiquidacion($request);

        // Agrupar materiales por artículo
        $agrupados = [];
        $totalesMat = [];
        $granTotalMat = 0;
        foreach ($materiales as $row) {
            $articulo = $row->articulo ?: 'Sin artículo';
            if (!isset($agrupados[$articulo])) {
                $agrupados[$articulo] = [];
                $totalesMat[$articulo] = 0;
            }
            $agrupados[$articulo][] = $row;
            $totalesMat[$articulo] += $row->cantidad;
            $granTotalMat += $row->cantidad;
        }

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8">
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Liquidacion</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    h2 { text-align: center; font-size: 16px; margin: 8px 0; }
    h3 { font-size: 13px; color: #0f172a; border-bottom: 2px solid #0d6efd; padding-bottom: 3px; margin-top: 22px; text-transform: uppercase; }
    .info { text-align: center; color: #666; font-size: 10px; margin-bottom: 10px; }
    .text-section { background: #f8f9fa; padding: 8px; border: 1px solid #ddd; white-space: pre-wrap; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    th { background: #0d6efd; color: white; padding: 7px 5px; text-align: center; font-size: 10px; font-weight: bold; border: 1px solid #0d6efd; }
    td { padding: 6px 5px; border-bottom: 1px solid #dee2e6; font-size: 10px; text-align: center; }
    tr:nth-child(even) td { background: #f8fafc; }
    .row-subtotal td { background: #d6eaff !important; font-weight: bold; border-top: 2px solid #0d6efd; border-left: 3px solid #0d6efd; }
    .row-grand-total td { background: #0d6efd !important; color: white; font-weight: bold; }
    .badge-si { background: #22c55e; color: white; padding: 2px 8px; }
    .badge-no { background: #eab308; color: #1e293b; padding: 2px 8px; }
</style></head><body>
<h2>Liquidacion de Trabajo</h2>
<div class="info">Generado: ' . date('d/m/Y H:i') . '</div>';

        // Mano de Obra
        $html .= '<h3>Mano de Obra</h3>';
        $html .= '<div class="text-section">' . ($liquidacion && $liquidacion->mano_obra ? strip_tags($liquidacion->mano_obra) : '-') . '</div>';

        // Importante
        $html .= '<h3>Importante</h3>';
        $html .= '<div class="text-section">' . ($liquidacion && $liquidacion->importante ? strip_tags($liquidacion->importante) : '-') . '</div>';

        // Materiales agrupados
        $html .= '<h3>Materiales a Utilizar (' . $materiales->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>ID ORDEN</th><th>PROYECTO</th><th>NOMBRE DE LA OBRA</th><th>FECHA</th><th>LUGAR</th><th>ARTICULO</th><th>CANTIDAD</th><th>CONTABILIZADO</th></tr></thead><tbody>';
        foreach ($agrupados as $articulo => $items) {
            foreach ($items as $r) {
                $html .= '<tr>
                    <td>' . $r->identificador . '</td>
                    <td>' . $r->proyecto . '</td>
                    <td>' . $r->obra . '</td>
                    <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                    <td>' . ($r->lugar ?: '-') . '</td>
                    <td>' . ($r->articulo ?: '-') . '</td>
                    <td>' . number_format($r->cantidad, 2) . '</td>
                    <td><span class="' . ($r->Contabilizado ? 'badge-si' : 'badge-no') . '">' . ($r->Contabilizado ? 'SI' : 'NO') . '</span></td>
                </tr>';
            }
            $html .= '<tr class="row-subtotal">
                <td colspan="5"></td>
                <td style="text-align:right;">Subtotal ' . strtoupper($articulo) . ':</td>
                <td>' . number_format($totalesMat[$articulo], 2) . '</td>
                <td></td>
            </tr>';
        }
        $html .= '<tr class="row-grand-total">
            <td colspan="6" style="text-align:right;">TOTALES GENERALES:</td>
            <td>' . number_format($granTotalMat, 2) . '</td>
            <td></td>
        </tr>';
        $html .= '</tbody></table>';

        // Horas
        $html .= '<h3>Horas de Trabajo (' . $horas->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>ID ORDEN</th><th>PROYECTO</th><th>NOMBRE DE LA OBRA</th><th>FECHA</th><th>LUGAR</th><th>EMPLEADO</th><th>H.ENTRADA</th><th>H.SALIDA</th><th>H.NORMAL</th><th>H.EXTRAS</th><th>H.EXTRAORD.</th></tr></thead><tbody>';
        foreach ($horas as $r) {
            $html .= '<tr>
                <td>' . $r->identificador . '</td>
                <td>' . $r->proyecto . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
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
        $html .= '<table><thead><tr><th>ID INFORME</th><th>NOMBRE DE LA OBRA</th><th>FECHA</th><th>LUGAR</th><th>UBICACION</th><th>OBSERVACION</th></tr></thead><tbody>';
        foreach ($informes as $r) {
            $html .= '<tr>
                <td>' . $r->id_informe_diario_ejecucion . '</td>
                <td>' . $r->obra . '</td>
                <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                <td>' . ($r->lugar ?: '-') . '</td>
                <td>' . ($r->ubicacion ?: '-') . '</td>
                <td>' . ($r->observacion ?: '-') . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        $html .= '</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="liquidacion_trabajo_' . date('Y-m-d') . '.xls"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response($html, 200, $headers);
    }

    // Exportar Liquidación a PDF (vista HTML imprimible)
    public function exportarLiquidacionTrabajoPdf(Request $request)
    {
        $materiales = $this->getLiqMateriales($request)->sortBy('articulo')->values();
        $horas = $this->getLiqHoras($request);
        $informes = $this->getLiqInformes($request);
        $liquidacion = $this->getOrCreateLiquidacion($request);
        $liquidacionImagenes = $liquidacion ? $liquidacion->imagenes()->where('estado', 1)->get() : collect();

        $agrupados = [];
        $totalesMat = [];
        $granTotalMat = 0;
        foreach ($materiales as $row) {
            $articulo = $row->articulo ?: 'Sin artículo';
            if (!isset($agrupados[$articulo])) {
                $agrupados[$articulo] = [];
                $totalesMat[$articulo] = 0;
            }
            $agrupados[$articulo][] = $row;
            $totalesMat[$articulo] += $row->cantidad;
            $granTotalMat += $row->cantidad;
        }

        $logoSvg = $this->getLogoSvg();

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Liquidación de Trabajo</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #1e293b; padding: 25px 30px; }
    .no-print { position: fixed; top: 18px; right: 18px; z-index: 100; }
    .btn-print { display: inline-flex; align-items: center; gap: 8px; background: #0d6efd; color: #fff; border: none; padding: 10px 22px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; box-shadow: 0 3px 10px rgba(13,110,253,0.35); }
    .btn-print:hover { background: #0b5ed7; }
    .btn-print svg { width: 18px; height: 18px; fill: #fff; }
    .header { display: flex; align-items: center; gap: 30px; margin-bottom: 25px; }
    .header-logo { flex-shrink: 0; }
    .header-text { flex-grow: 1; text-align: center; }
    .header-text h1 { font-size: 30px; font-weight: bold; color: #0f172a; letter-spacing: 0.3px; text-align: center; }
    h3.section-title { margin: 22px 0 8px; font-size: 16px; color: #0f172a; text-transform: uppercase; font-weight: bold; display: inline-block; border-bottom: 3px solid #0d6efd; padding-bottom: 4px; letter-spacing: 0.5px; }
    .text-section { background: #f8f9fa; border: 1px solid #dee2e6; padding: 12px 15px; margin-bottom: 15px; white-space: pre-wrap; font-size: 13px; line-height: 1.5; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 6px; }
    th { background: #0d6efd; color: white; padding: 11px 10px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #0d6efd; }
    td { padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 13px; text-align: center; color: #334155; }
    tr:nth-child(even) td { background: #f8fafc; }
    .row-subtotal td { background: #d6eaff !important; font-weight: 700; color: #0f172a; border-left: 3px solid #0d6efd; border-top: 1px solid #b6d4fe; }
    .row-subtotal td.sub-label { text-align: right; }
    .row-grand-total td { background: #0d6efd !important; color: #ffffff !important; font-weight: 700; border: none; }
    .row-grand-total td.sub-label { text-align: right; }
    .badge { padding: 3px 10px; border-radius: 3px; font-size: 11px; font-weight: bold; display: inline-block; }
    .badge-si { background: #22c55e; color: #fff; }
    .badge-no { background: #eab308; color: #1e293b; }
    .imgs-grid { display: flex; flex-wrap: wrap; gap: 5px; }
    .imgs-grid img { width: 45px; height: 45px; object-fit: cover; border-radius: 3px; border: 1px solid #ccc; }
    .footer { margin-top: 25px; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #e0e0e0; padding-top: 12px; }
    @media print { body { padding: 12px; } .no-print { display: none !important; } }
</style></head><body>
<div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
        Imprimir
    </button>
</div>
<div class="header">
    <div class="header-logo">' . $logoSvg . '</div>
    <div class="header-text">
        <h1>Liquidación de Trabajo</h1>
    </div>
</div>';

        // Mano de Obra
        $html .= '<h3 class="section-title">Mano de Obra</h3>';
        $html .= '<div class="text-section">' . ($liquidacion && $liquidacion->mano_obra ? strip_tags($liquidacion->mano_obra) : '-') . '</div>';

        // Importante
        $html .= '<h3 class="section-title">Importante</h3>';
        $html .= '<div class="text-section">' . ($liquidacion && $liquidacion->importante ? strip_tags($liquidacion->importante) : '-') . '</div>';

        // Imágenes Adjuntas de Liquidación
        if ($liquidacionImagenes->count() > 0) {
            $html .= '<h3 class="section-title">Imágenes Adjuntas (' . $liquidacionImagenes->count() . ')</h3><div class="imgs-grid">';
            foreach ($liquidacionImagenes as $img) {
                $html .= '<img src="' . $img->ruta . '">';
            }
            $html .= '</div>';
        }

        // Materiales agrupados
        $html .= '<h3 class="section-title">Materiales a Utilizar (' . $materiales->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>FECHA</th><th>LUGAR</th><th>ARTICULO</th><th>CANTIDAD</th><th>CONTABILIZADO</th></tr></thead><tbody>';
        foreach ($agrupados as $articulo => $items) {
            foreach ($items as $r) {
                $html .= '<tr>
                    <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                    <td>' . e($r->lugar ?: '-') . '</td>
                    <td>' . e($r->articulo ?: '-') . '</td>
                    <td><strong>' . number_format($r->cantidad, 2) . '</strong></td>
                    <td><span class="badge ' . ($r->Contabilizado ? 'badge-si' : 'badge-no') . '">' . ($r->Contabilizado ? 'SI' : 'NO') . '</span></td>
                </tr>';
            }
            $html .= '<tr class="row-subtotal">
                <td></td>
                <td></td>
                <td class="sub-label">Subtotal ' . e($articulo) . '</td>
                <td>' . number_format($totalesMat[$articulo], 2) . '</td>
                <td></td>
            </tr>';
        }
        $html .= '<tr class="row-grand-total">
            <td colspan="3" class="sub-label">TOTALES GENERALES:</td>
            <td>' . number_format($granTotalMat, 2) . '</td>
            <td></td>
        </tr>';
        $html .= '</tbody></table>';

        // Horas
        $html .= '<h3 class="section-title">Horas de Trabajo (' . $horas->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>FECHA</th><th>LUGAR</th><th>EMPLEADO</th><th>H.ENTRADA</th><th>H.SALIDA</th><th>H.NORMAL</th><th>H.EXTRAS</th><th>H.EXTRAORD.</th></tr></thead><tbody>';
        foreach ($horas as $r) {
            $html .= '<tr>
                <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                <td>' . e($r->lugar ?: '-') . '</td>
                <td>' . e($r->empleado) . '</td>
                <td>' . $r->hora_entrada . '</td>
                <td>' . $r->hora_salida . '</td>
                <td>' . $r->cantidad_horas_normal . '</td>
                <td>' . $r->cantidad_horas_extra . '</td>
                <td>' . $r->cantidad_horas_extraordinaria . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        // Informes Diarios
        $html .= '<h3 class="section-title">Informes Diarios (' . $informes->count() . ' registros)</h3>';
        $html .= '<table><thead><tr><th>ID INFORME</th><th>NOMBRE DE LA OBRA</th><th>FECHA</th><th>LUGAR</th><th>UBICACION</th><th>OBSERVACION</th></tr></thead><tbody>';
        foreach ($informes as $r) {
            $html .= '<tr>
                <td>INF-' . str_pad($r->id_informe_diario_ejecucion, 5, '0', STR_PAD_LEFT) . '</td>
                <td>' . e($r->obra) . '</td>
                <td>' . ($r->fecha ? date('Y-m-d', strtotime($r->fecha)) : '-') . '</td>
                <td>' . e($r->lugar ?: '-') . '</td>
                <td>' . e($r->ubicacion ?: '-') . '</td>
                <td>' . e($r->observacion ?: '-') . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<div class="footer">Generado: ' . date('d/m/Y H:i') . ' | INTENERGY</div>
</body></html>';

        return response($html)->header('Content-Type', 'text/html');
    }
}
