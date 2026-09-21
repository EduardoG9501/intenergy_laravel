<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Menu;
use App\Models\PermisoUsuarioMenu;
use App\Models\PermisoBodega;
use App\Models\Bodega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('apellido', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $usuarios = $query->orderBy('id_usuario', 'desc')->paginate(15);
        return view('usuarios.index', compact('usuarios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|max:100',
            'apellido' => 'required|max:100',
            'email' => 'required|email|max:150|unique:usuarios,email',
            'password' => 'required|min:4'
        ]);

        try {
            User::create([
                'nombre' => trim($request->nombre),
                'apellido' => trim($request->apellido),
                'email' => trim($request->email),
                'password' => Hash::make($request->password),
                'fechaCaptura' => now()->toDateString(),
                'estado' => 1
            ]);

            return response()->json(['success' => true, 'mensaje' => 'Usuario registrado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $usuario = User::findOrFail($id);
        return response()->json(['success' => true, 'usuario' => $usuario]);
    }

    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $request->validate([
            'nombre' => 'required|max:100',
            'apellido' => 'required|max:100',
            'email' => 'required|email|max:150|unique:usuarios,email,' . $id . ',id_usuario',
            'password' => 'nullable|min:4'
        ]);

        try {
            $data = [
                'nombre' => trim($request->nombre),
                'apellido' => trim($request->apellido),
                'email' => trim($request->email)
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $usuario->update($data);

            return response()->json(['success' => true, 'mensaje' => 'Usuario actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $usuario = User::findOrFail($id);
            if ($usuario->email === 'admin') {
                return response()->json(['success' => false, 'mensaje' => 'No se puede desactivar el usuario administrador principal.']);
            }
            $usuario->estado = 0;
            $usuario->save();
            return response()->json(['success' => true, 'mensaje' => 'Usuario desactivado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $usuario = User::findOrFail($id);
            $usuario->estado = 1;
            $usuario->save();
            return response()->json(['success' => true, 'mensaje' => 'Usuario activado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al activar: ' . $e->getMessage()]);
        }
    }

    // Obtener menús y bodegas para gestión de permisos
    public function getPermisos($id)
    {
        try {
            // Todos los menus activos excepto configuraciones criticas
            $menus = Menu::where('estado', 1)
                         ->whereNotIn('menu_hijo', ['BACKUP', 'IVA'])
                         ->orderBy('menu_padre')
                         ->orderBy('menu_hijo')
                         ->get();

            // Si es el admin principal, tiene todos los permisos asignados por defecto
            $usuario = User::findOrFail($id);
            if ($usuario->email === 'admin') {
                $menuPermitidos = $menus->pluck('id_menu')->toArray();
                $bodegasPermitidas = Bodega::where('estado', 1)->pluck('id_bodega')->toArray();
            } else {
                $menuPermitidos = PermisoUsuarioMenu::where('id_usuario', $id)->pluck('id_menu')->toArray();
                $bodegasPermitidas = PermisoBodega::where('id_usuario', $id)->where('estado', 1)->pluck('id_bodega')->toArray();
            }

            $bodegas = Bodega::where('estado', 1)->orderBy('nombreBodega')->get();

            return response()->json([
                'success' => true,
                'menus' => $menus,
                'permisos_menu' => $menuPermitidos,
                'bodegas' => $bodegas,
                'permisos_bodega' => $bodegasPermitidas
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al cargar permisos: ' . $e->getMessage()]);
        }
    }

    // Guardar permisos
    public function guardarPermisos(Request $request, $id)
    {
        $usuario = User::findOrFail($id);
        if ($usuario->email === 'admin') {
            return response()->json(['success' => false, 'mensaje' => 'El administrador principal siempre cuenta con todos los permisos del sistema.']);
        }

        try {
            DB::transaction(function() use ($id, $request) {
                // 1. Guardar menus
                PermisoUsuarioMenu::where('id_usuario', $id)->delete();
                if ($request->filled('menus') && is_array($request->menus)) {
                    foreach ($request->menus as $id_menu) {
                        PermisoUsuarioMenu::create([
                            'id_usuario' => $id,
                            'id_menu' => $id_menu,
                            'fecha_creacion' => now()
                        ]);
                    }
                }

                // 2. Guardar bodegas
                PermisoBodega::where('id_usuario', $id)->delete();
                if ($request->filled('bodegas') && is_array($request->bodegas)) {
                    foreach ($request->bodegas as $id_bodega) {
                        PermisoBodega::create([
                            'id_usuario' => $id,
                            'id_bodega' => $id_bodega,
                            'fecha_creacion' => now(),
                            'estado' => 1
                        ]);
                    }
                }
            });

            return response()->json(['success' => true, 'mensaje' => 'Permisos guardados con éxito.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar permisos: ' . $e->getMessage()]);
        }
    }
}
