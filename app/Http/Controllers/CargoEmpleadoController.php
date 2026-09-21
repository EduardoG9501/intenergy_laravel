<?php

namespace App\Http\Controllers;

use App\Models\CargoEmpleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CargoEmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $query = CargoEmpleado::query();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('cargo', 'like', "%{$buscar}%");
        }

        $cargos = $query->orderBy('id_cargo', 'desc')->paginate(15);

        return view('cargos.index', compact('cargos'));
    }

    public function show($id)
    {
        $cargo = CargoEmpleado::findOrFail($id);
        return response()->json([
            'success' => true,
            'cargo' => $cargo
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cargo' => 'required|max:200',
        ]);

        $cargoExiste = CargoEmpleado::where('cargo', trim($request->cargo))
                                    ->where('estado', 1)
                                    ->exists();
        if ($cargoExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El cargo ya existe y está activo.']);
        }

        try {
            CargoEmpleado::create([
                'id_usuario' => Auth::id(),
                'cargo' => trim($request->cargo),
                'fechaCaptura' => now()->toDateString(),
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Cargo guardado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar el cargo: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $cargoModel = CargoEmpleado::findOrFail($id);

        $request->validate([
            'cargoU' => 'required|max:200',
        ]);

        $cargoExiste = CargoEmpleado::where('cargo', trim($request->cargoU))
                                    ->where('id_cargo', '!=', $id)
                                    ->where('estado', 1)
                                    ->exists();
        if ($cargoExiste) {
            return response()->json(['success' => false, 'mensaje' => 'Ya existe otro cargo activo con este nombre.']);
        }

        try {
            $cargoModel->update([
                'cargo' => trim($request->cargoU),
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Cargo actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar el cargo: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $cargoModel = CargoEmpleado::findOrFail($id);
            $cargoModel->estado = 0;
            $cargoModel->save();

            return response()->json(['success' => true, 'mensaje' => 'Cargo desactivado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar el cargo: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $cargoModel = CargoEmpleado::findOrFail($id);
            $cargoModel->estado = 1;
            $cargoModel->save();

            return response()->json(['success' => true, 'mensaje' => 'Cargo restaurado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al restaurar el cargo: ' . $e->getMessage()]);
        }
    }
}
