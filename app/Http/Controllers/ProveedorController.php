<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::query()
            ->leftJoin('tipoidentificacion', 'proveedor.tipo_identificacion', '=', 'tipoidentificacion.id_tipo_identificacion')
            ->leftJoin('tipocontribuyente', 'proveedor.tipo_contribuyente', '=', 'tipocontribuyente.id_tipo_contribuyente')
            ->select('proveedor.*', 'tipoidentificacion.tipo as tipo_identificacion_nombre', 'tipocontribuyente.tipo as tipo_contribuyente_nombre');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('proveedor.razon_social', 'like', "%{$buscar}%")
                  ->orWhere('proveedor.nombre_comercial', 'like', "%{$buscar}%")
                  ->orWhere('proveedor.numero_identificacion', 'like', "%{$buscar}%")
                  ->orWhere('proveedor.email', 'like', "%{$buscar}%");
            });
        }

        $proveedores = $query->orderBy('proveedor.id_proveedor', 'desc')->paginate(15);
        $tiposIdentificacion = \Illuminate\Support\Facades\DB::table('tipoidentificacion')->where('estado', 1)->get();
        $tiposContribuyente = \Illuminate\Support\Facades\DB::table('tipocontribuyente')->where('estado', 1)->get();

        return view('proveedores.index', compact('proveedores', 'tiposIdentificacion', 'tiposContribuyente'));
    }

    public function show($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        return response()->json([
            'success' => true,
            'proveedor' => $proveedor
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_identificacion' => 'required',
            'numero_identificacion' => 'required|max:100',
            'razon_social' => 'required|max:200',
            'direccion' => 'nullable|max:200',
            'telefono' => 'nullable|max:200',
            'email' => 'nullable|max:200',
            'nombre_comercial' => 'nullable|max:200',
            'tipo_contribuyente' => 'required',
            'agente_retencion' => 'nullable|max:200',
        ]);

        // Validar razon_social única globalmente
        $socialExiste = Proveedor::where('razon_social', trim($request->razon_social))->exists();
        if ($socialExiste) {
            return response()->json(['success' => false, 'mensaje' => 'La Razón Social ya está registrada en el sistema.']);
        }

        // Validar número de identificación único globalmente
        $identificacionExiste = Proveedor::where('numero_identificacion', trim($request->numero_identificacion))->exists();
        if ($identificacionExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El Número de Identificación ya está registrado en el sistema.']);
        }

        try {
            Proveedor::create([
                'id_usuario' => Auth::id(),
                'tipo_identificacion' => $request->tipo_identificacion,
                'numero_identificacion' => trim($request->numero_identificacion),
                'razon_social' => trim($request->razon_social),
                'direccion' => trim($request->direccion),
                'telefono' => trim($request->telefono),
                'email' => trim($request->email),
                'nombre_comercial' => trim($request->nombre_comercial),
                'tipo_contribuyente' => $request->tipo_contribuyente,
                'agente_retencion' => trim($request->agente_retencion),
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Proveedor guardado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar el proveedor: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $request->validate([
            'tipo_identificacionU' => 'required',
            'numero_identificacionU' => 'required|max:100',
            'razon_socialU' => 'required|max:200',
            'direccionU' => 'nullable|max:200',
            'telefonoU' => 'nullable|max:200',
            'emailU' => 'nullable|max:200',
            'nombre_comercialU' => 'nullable|max:200',
            'tipo_contribuyenteU' => 'required',
            'agente_retencionU' => 'nullable|max:200',
        ]);

        // Validar razon_social única excluyendo actual
        $socialExiste = Proveedor::where('razon_social', trim($request->razon_socialU))
                                 ->where('id_proveedor', '!=', $id)
                                 ->exists();
        if ($socialExiste) {
            return response()->json(['success' => false, 'mensaje' => 'La Razón Social ya está registrada por otro proveedor.']);
        }

        // Validar número de identificación único excluyendo actual
        $identificacionExiste = Proveedor::where('numero_identificacion', trim($request->numero_identificacionU))
                                         ->where('id_proveedor', '!=', $id)
                                         ->exists();
        if ($identificacionExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El Número de Identificación ya está registrado por otro proveedor.']);
        }

        try {
            $proveedor->update([
                'tipo_identificacion' => $request->tipo_identificacionU,
                'numero_identificacion' => trim($request->numero_identificacionU),
                'razon_social' => trim($request->razon_socialU),
                'direccion' => trim($request->direccionU),
                'telefono' => trim($request->telefonoU),
                'email' => trim($request->emailU),
                'nombre_comercial' => trim($request->nombre_comercialU),
                'tipo_contribuyente' => $request->tipo_contribuyenteU,
                'agente_retencion' => trim($request->agente_retencionU),
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Proveedor actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar el proveedor: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $proveedor = Proveedor::findOrFail($id);
            $proveedor->estado = 0; // Desactivar
            $proveedor->save();

            return response()->json(['success' => true, 'mensaje' => 'Proveedor desactivado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar el proveedor: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $proveedor = Proveedor::findOrFail($id);
            $proveedor->estado = 1; // Activar
            $proveedor->save();

            return response()->json(['success' => true, 'mensaje' => 'Proveedor restaurado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al restaurar el proveedor: ' . $e->getMessage()]);
        }
    }
}
