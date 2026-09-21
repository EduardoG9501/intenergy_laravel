<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::query();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('apellido', 'like', "%{$buscar}%")
                  ->orWhere('rfc', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
        }

        $clientes = $query->orderBy('id_cliente', 'desc')->paginate(15);

        return view('clientes.index', compact('clientes'));
    }

    public function show($id)
    {
        $cliente = Cliente::findOrFail($id);
        return response()->json([
            'success' => true,
            'cliente' => $cliente
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|max:200',
            'apellido' => 'nullable|max:200',
            'direccion' => 'nullable|max:200',
            'email' => 'nullable|email|max:200',
            'telefono' => 'nullable|max:200',
            'rfc' => 'nullable|max:200',
        ]);

        if ($request->filled('rfc')) {
            $rfcExiste = Cliente::where('rfc', strtoupper(trim($request->rfc)))
                                ->where('estado', 1)
                                ->exists();
            if ($rfcExiste) {
                return response()->json(['success' => false, 'mensaje' => 'El RFC ya está registrado en otro cliente activo.']);
            }
        }

        try {
            Cliente::create([
                'id_usuario' => Auth::id(),
                'nombre' => trim($request->nombre),
                'apellido' => trim($request->apellido),
                'direccion' => trim($request->direccion),
                'email' => trim($request->email),
                'telefono' => trim($request->telefono),
                'rfc' => strtoupper(trim($request->rfc)),
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Cliente guardado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar el cliente: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $request->validate([
            'nombreU' => 'required|max:200',
            'apellidoU' => 'nullable|max:200',
            'direccionU' => 'nullable|max:200',
            'emailU' => 'nullable|email|max:200',
            'telefonoU' => 'nullable|max:200',
            'rfcU' => 'nullable|max:200',
        ]);

        if ($request->filled('rfcU')) {
            $rfcExiste = Cliente::where('rfc', strtoupper(trim($request->rfcU)))
                                ->where('id_cliente', '!=', $id)
                                ->where('estado', 1)
                                ->exists();
            if ($rfcExiste) {
                return response()->json(['success' => false, 'mensaje' => 'El RFC ya está registrado en otro cliente activo.']);
            }
        }

        try {
            $cliente->update([
                'nombre' => trim($request->nombreU),
                'apellido' => trim($request->apellidoU),
                'direccion' => trim($request->direccionU),
                'email' => trim($request->emailU),
                'telefono' => trim($request->telefonoU),
                'rfc' => strtoupper(trim($request->rfcU)),
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Cliente actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar el cliente: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->estado = 0;
            $cliente->save();

            return response()->json(['success' => true, 'mensaje' => 'Cliente desactivado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar el cliente: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->estado = 1;
            $cliente->save();

            return response()->json(['success' => true, 'mensaje' => 'Cliente restaurado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al restaurar el cliente: ' . $e->getMessage()]);
        }
    }
}
