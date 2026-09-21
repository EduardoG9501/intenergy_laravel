<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\CargoEmpleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $query = Empleado::with('cargo');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('nombres_apellidos', 'like', "%{$buscar}%")
                  ->orWhere('cedula', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%")
                  ->orWhere('telefono', 'like', "%{$buscar}%");
        }

        $empleados = $query->orderBy('id_empleados', 'desc')->paginate(15);

        $cargos = CargoEmpleado::where('estado', 1)->get();

        return view('empleados.index', compact('empleados', 'cargos'));
    }

    public function show($id)
    {
        $empleado = Empleado::findOrFail($id);
        return response()->json([
            'success' => true,
            'empleado' => $empleado
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombres_apellidos' => 'required|max:200',
            'cedula' => 'required|max:100',
            'direccion' => 'nullable|max:200',
            'email' => 'nullable|email|max:200',
            'telefono' => 'nullable|max:200',
            'id_cargo' => 'required|exists:cargo_empleado,id_cargo',
        ]);

        // Validar nombres y apellidos únicos globalmente
        $nombreExiste = Empleado::where('nombres_apellidos', trim($request->nombres_apellidos))->exists();
        if ($nombreExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El Nombre del Empleado ya está registrado en el sistema.']);
        }

        // Validar número de cédula único globalmente
        $cedulaExiste = Empleado::where('cedula', trim($request->cedula))->exists();
        if ($cedulaExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El Número de Cédula ya está registrado en el sistema.']);
        }

        try {
            Empleado::create([
                'id_usuario' => Auth::id(),
                'nombres_apellidos' => trim($request->nombres_apellidos),
                'cedula' => trim($request->cedula),
                'id_genero' => 0,
                'direccion' => trim($request->direccion),
                'email' => trim($request->email),
                'telefono' => trim($request->telefono),
                'fecha_nacimiento' => null,
                'id_cargo' => $request->id_cargo,
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Empleado guardado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar el empleado: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $empleado = Empleado::findOrFail($id);

        $request->validate([
            'nombres_apellidosU' => 'required|max:200',
            'cedulaU' => 'required|max:100',
            'direccionU' => 'nullable|max:200',
            'emailU' => 'nullable|email|max:200',
            'telefonoU' => 'nullable|max:200',
            'id_cargoU' => 'required|exists:cargo_empleado,id_cargo',
        ]);

        // Validar nombres y apellidos únicos excluyendo actual
        $nombreExiste = Empleado::where('nombres_apellidos', trim($request->nombres_apellidosU))
                                ->where('id_empleados', '!=', $id)
                                ->exists();
        if ($nombreExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El Nombre del Empleado ya está registrado por otro empleado.']);
        }

        // Validar número de cédula único excluyendo actual
        $cedulaExiste = Empleado::where('cedula', trim($request->cedulaU))
                                ->where('id_empleados', '!=', $id)
                                ->exists();
        if ($cedulaExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El Número de Cédula ya está registrado por otro empleado.']);
        }

        try {
            $empleado->update([
                'nombres_apellidos' => trim($request->nombres_apellidosU),
                'cedula' => trim($request->cedulaU),
                'direccion' => trim($request->direccionU),
                'email' => trim($request->emailU),
                'telefono' => trim($request->telefonoU),
                'id_cargo' => $request->id_cargoU,
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Empleado actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar el empleado: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $empleado = Empleado::findOrFail($id);
            $empleado->estado = 0;
            $empleado->save();

            return response()->json(['success' => true, 'mensaje' => 'Empleado desactivado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar el empleado: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $empleado = Empleado::findOrFail($id);
            $empleado->estado = 1;
            $empleado->save();

            return response()->json(['success' => true, 'mensaje' => 'Empleado restaurado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al restaurar el empleado: ' . $e->getMessage()]);
        }
    }
}
