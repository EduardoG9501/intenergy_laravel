<?php

namespace App\Http\Controllers;

use App\Models\StockProductoBodega;
use App\Models\Bodega;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = StockProductoBodega::with(['articulo', 'bodegaPrincipal', 'bodegaSecundaria']);

        if ($request->filled('id_bodega')) {
            $query->where('id_bodega_principal', $request->id_bodega);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('articulo', function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigobarra', 'like', "%{$buscar}%");
            });
        }

        $stock = $query->orderBy('cantidad', 'desc')->paginate(15);

        $bodegas = Bodega::where('es_bodega_secundaria', 0)->where('estado', 1)->get();

        return view('stock.index', compact('stock', 'bodegas'));
    }
}
