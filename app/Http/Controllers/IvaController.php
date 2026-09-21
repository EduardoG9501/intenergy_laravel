<?php

namespace App\Http\Controllers;

use App\Models\Iva;
use Illuminate\Http\Request;

class IvaController extends Controller
{
    public function index()
    {
        $ivas = Iva::orderBy('id_iva', 'desc')->get();
        return view('configuracion.iva', compact('ivas'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0|max:100',
            'CODIGO_SRI' => 'required|string|max:20',
            'Codigo_Porc_Iva' => 'required|string|max:20',
        ]);

        try {
            $iva = Iva::findOrFail($id);
            $iva->update([
                'monto' => $request->monto,
                'CODIGO_SRI' => $request->CODIGO_SRI,
                'Codigo_Porc_Iva' => $request->Codigo_Porc_Iva,
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Configuración IVA actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }
}
