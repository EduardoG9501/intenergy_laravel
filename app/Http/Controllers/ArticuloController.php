<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Categoria;
use App\Models\Imagen;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ArticuloController extends Controller
{
    public function index(Request $request)
    {
        $activeBodegaId = session('bodega_seleccionada');
        $activeBodegaName = 'N/A';
        if ($activeBodegaId) {
            $activeBodegaName = \App\Models\Bodega::where('id_bodega', $activeBodegaId)->value('nombreBodega') ?? 'N/A';
        }

        $query = Articulo::with(['categoria', 'subcategoria', 'proveedorRel'])
            ->select('articulos.*');

        if ($activeBodegaId) {
            $query->selectSub(function($q) use ($activeBodegaId) {
                $q->select('cantidad')
                  ->from('stock_productos_bodega')
                  ->whereColumn('id_producto', 'articulos.id_producto')
                  ->where('id_bodega_principal', $activeBodegaId)
                  ->where('estado', 1)
                  ->limit(1);
            }, 'stock_bodega_activa');
        } else {
            $query->selectRaw('0 as stock_bodega_activa');
        }

        // Búsqueda por término
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('descripcion', 'like', "%{$buscar}%")
                  ->orWhere('codigobarra', 'like', "%{$buscar}%")
                  ->orWhere('referencia', 'like', "%{$buscar}%");
            });
        }

        // Ordenar por ID descendente por defecto
        $articulos = $query->orderBy('id_producto', 'desc')->paginate(15);

        // Listados para los combos del formulario modal
        $categorias = Categoria::where('subcategoria', 0)->where('estado', 1)->get();
        $subcategorias = Categoria::where('subcategoria', 1)->where('estado', 1)->get();
        $proveedores = Proveedor::where('estado', 1)->get();

        return view('articulos.index', compact('articulos', 'categorias', 'subcategorias', 'proveedores', 'activeBodegaName'));
    }

    public function show($id)
    {
        $articulo = Articulo::findOrFail($id);
        
        // Obtener la ruta de la imagen relacionada
        $imagen = Imagen::find($articulo->id_imagen);
        $rutaImagen = $imagen ? $imagen->ruta : '';
        
        if (str_starts_with($rutaImagen, 'data:')) {
            $urlImagen = $rutaImagen;
        } else {
            // Limpiar ../../ de la ruta de la imagen para que se sirva desde /public/
            if (str_starts_with($rutaImagen, '../../')) {
                $rutaImagen = str_replace('../../', '', $rutaImagen);
            }
            $urlImagen = $rutaImagen ? asset($rutaImagen) : '';
        }

        return response()->json([
            'success' => true,
            'articulo' => $articulo,
            'ruta_imagen' => $urlImagen
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|max:50',
            'categoriaSelect' => 'required',
            'subcategoriaSelect' => 'nullable',
            'proveedorSelect' => 'nullable',
            'precio' => 'nullable|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Validar nombre único
        $nombreExiste = Articulo::where('nombre', trim($request->nombre))->exists();
        if ($nombreExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El nombre del artículo ya existe.']);
        }

        // Validar código de barra único si se proporciona
        if ($request->filled('codigobarra')) {
            $codigoExiste = Articulo::where('codigobarra', trim($request->codigobarra))->exists();
            if ($codigoExiste) {
                return response()->json(['success' => false, 'mensaje' => 'El código de barra ya está registrado.']);
            }
        }

        try {
            DB::transaction(function() use ($request) {
                $idImagen = null;

                // Si subió una imagen
                if ($request->hasFile('imagen')) {
                    $file = $request->file('imagen');
                    $imageData = base64_encode(file_get_contents($file->getRealPath()));
                    $base64 = 'data:' . $file->getMimeType() . ';base64,' . $imageData;

                    // Insertar en la tabla 'imagenes'
                    $imagen = Imagen::create([
                        'id_categoria' => $request->categoriaSelect,
                        'nombre' => $file->getClientOriginalName(),
                        'ruta' => $base64,
                        'fechaSubida' => now()->toDateString(),
                        'estado' => 1
                    ]);
                    $idImagen = $imagen->id_imagen;
                }

                // Insertar el artículo
                Articulo::create([
                    'id_categoria' => $request->categoriaSelect,
                    'id_subcategoria' => $request->subcategoriaSelect,
                    'id_imagen' => $idImagen,
                    'id_usuario' => Auth::id(),
                    'nombre' => trim($request->nombre),
                    'descripcion' => $request->descripcion,
                    'cantidad' => 0, // Stock inicial siempre en 0, se carga con movimientos
                    'precio' => $request->precio ?? 0,
                    'fechaCaptura' => now()->toDateString(),
                    'estado' => 1,
                    'codigobarra' => $request->filled('codigobarra') ? trim($request->codigobarra) : null,
                    'proveedor' => null, // Desactualizado (se usa la relación id_proveedor)
                    'referencia' => $request->referencia,
                    'id_proveedor' => $request->proveedorSelect,
                ]);
            });

            return response()->json(['success' => true, 'mensaje' => 'Artículo guardado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al guardar el artículo: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $articulo = Articulo::findOrFail($id);

        $request->validate([
            'nombreU' => 'required|max:50',
            'categoriaSelectU' => 'required',
            'subcategoriaSelectU' => 'nullable',
            'proveedorSelectU' => 'nullable',
            'precioU' => 'nullable|numeric|min:0',
            'imagenU' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Validar nombre único excluyendo actual
        $nombreExiste = Articulo::where('nombre', trim($request->nombreU))
                                ->where('id_producto', '!=', $id)
                                ->exists();
        if ($nombreExiste) {
            return response()->json(['success' => false, 'mensaje' => 'El nombre del artículo ya existe.']);
        }

        // Validar código de barra único si se proporciona
        if ($request->filled('codigobarraU')) {
            $codigoExiste = Articulo::where('codigobarra', trim($request->codigobarraU))
                                    ->where('id_producto', '!=', $id)
                                    ->exists();
            if ($codigoExiste) {
                return response()->json(['success' => false, 'mensaje' => 'El código de barra ya está registrado.']);
            }
        }

        try {
            DB::transaction(function() use ($request, $articulo) {
                $idImagen = $articulo->id_imagen;

                // Si subió una nueva imagen
                if ($request->hasFile('imagenU')) {
                    $file = $request->file('imagenU');
                    $imageData = base64_encode(file_get_contents($file->getRealPath()));
                    $base64 = 'data:' . $file->getMimeType() . ';base64,' . $imageData;

                    // Crear nueva imagen en Base64 en la base de datos
                    $imagen = Imagen::create([
                        'id_categoria' => $request->categoriaSelectU,
                        'nombre' => $file->getClientOriginalName(),
                        'ruta' => $base64,
                        'fechaSubida' => now()->toDateString(),
                        'estado' => 1
                    ]);
                    $idImagen = $imagen->id_imagen;
                } elseif ($idImagen) {
                    // Si no subió una nueva imagen pero tiene una relacionada, actualizar la categoría de la imagen existente
                    $existingImg = Imagen::find($idImagen);
                    if ($existingImg) {
                        $existingImg->update([
                            'id_categoria' => $request->categoriaSelectU
                        ]);
                    }
                }

                // Actualizar el artículo
                $articulo->update([
                    'id_categoria' => $request->categoriaSelectU,
                    'id_subcategoria' => $request->subcategoriaSelectU,
                    'id_imagen' => $idImagen,
                    'nombre' => trim($request->nombreU),
                    'descripcion' => $request->descripcionU,
                    'precio' => $request->precioU ?? 0,
                    'codigobarra' => $request->filled('codigobarraU') ? trim($request->codigobarraU) : null,
                    'referencia' => $request->referenciaU,
                    'id_proveedor' => $request->proveedorSelectU,
                ]);
            });

            return response()->json(['success' => true, 'mensaje' => 'Artículo actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al actualizar el artículo: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $articulo = Articulo::findOrFail($id);

            // Verificar si el artículo tiene movimientos en movimientos_detalle
            $tieneMovimientos = DB::table('movimientos_detalle')
                                  ->where('id_articulo', $id)
                                  ->exists();

            if ($tieneMovimientos) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No se puede desactivar o borrar este artículo porque ya cuenta con movimientos registrados en el sistema.'
                ]);
            }

            $articulo->estado = 0; // Desactivar artículo
            $articulo->save();

            return response()->json(['success' => true, 'mensaje' => 'Artículo desactivado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al desactivar el artículo: ' . $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        try {
            $articulo = Articulo::findOrFail($id);
            $articulo->estado = 1; // Activar artículo
            $articulo->save();

            return response()->json(['success' => true, 'mensaje' => 'Artículo restaurado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error al restaurar el artículo: ' . $e->getMessage()]);
        }
    }
}
