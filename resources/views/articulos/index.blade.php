@extends('layouts.app')

@section('title', 'Artículos - Intenergy')

@section('styles')
<style>
    .image-hover-container {
        position: relative;
        display: inline-block;
    }
    .image-hover-preview {
        display: none;
        position: absolute;
        left: 70px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 9999;
        border: 4px solid #fff;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        border-radius: 12px;
        width: 250px;
        height: 250px;
        object-fit: cover;
        pointer-events: none;
        background-color: #fff;
    }
    .image-hover-container:hover .image-hover-preview {
        display: block;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Catálogo de Artículos</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Artículos</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nuevo Artículo
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('articulos.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por nombre, código de barra, descripción o referencia..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Artículos -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">Id</th>
                    <th>Proveedor</th>
                    <th>Nombre</th>
                    <th>Cod. Barra</th>
                    <th>Categoría</th>
                    <th>Stock ({{ $activeBodegaName }})</th>
                    <th>Precio</th>
                    <th>Imagen</th>
                    <th>Estado</th>
                    <th class="text-center pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articulos as $art)
                    @php
                        $rutaImg = $art->id_imagen ? App\Models\Imagen::find($art->id_imagen)?->ruta : '';
                        if ($rutaImg && !str_starts_with($rutaImg, 'data:') && str_starts_with($rutaImg, '../../')) {
                            $rutaImg = str_replace('../../', '', $rutaImg);
                        }
                    @endphp
                    <tr class="@if($art->estado == 0) table-light text-muted @endif">
                        <td class="ps-4 fw-bold text-secondary">{{ $art->id_producto }}</td>
                        <td>{{ $art->proveedorRel?->razon_social ?: 'N/A' }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $art->nombre }}</div>
                            <small class="text-muted d-block text-truncate" style="max-width: 200px;">{{ $art->descripcion }}</small>
                        </td>
                        <td class="fw-bold">{{ $art->codigobarra ?: 'N/A' }}</td>
                        <td>
                            <span class="badge bg-secondary mb-1 d-inline-block">{{ $art->categoria?->nombreCategoria }}</span>
                            @if($art->subcategoria)
                                <span class="badge bg-light text-dark d-block" style="font-size: 11px; width: fit-content;">{{ $art->subcategoria?->nombreCategoria }}</span>
                            @endif
                        </td>
                        <td class="fw-bold text-success">{{ number_format($art->stock_bodega_activa ?? 0, 0) }}</td>
                        <td class="fw-bold">${{ number_format($art->precio, 2) }}</td>
                        <td>
                            <div class="image-hover-container">
                                <img src="{{ str_starts_with($rutaImg, 'data:') ? $rutaImg : ($rutaImg ? asset($rutaImg) : asset('img/no-image.png')) }}" class="rounded shadow-sm" alt="{{ $art->nombre }}" width="50" height="50" style="object-fit: cover; background-color: #eee;">
                                <img src="{{ str_starts_with($rutaImg, 'data:') ? $rutaImg : ($rutaImg ? asset($rutaImg) : asset('img/no-image.png')) }}" class="image-hover-preview shadow-lg" alt="{{ $art->nombre }}">
                            </div>
                        </td>
                        <td>
                            @if($art->estado == 1)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="editarArticulo({{ $art->id_producto }})" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @if($art->estado == 1)
                                    <button type="button" class="btn btn-outline-danger" onclick="cambiarEstado({{ $art->id_producto }}, 'delete')" title="Desactivar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-outline-success" onclick="cambiarEstado({{ $art->id_producto }}, 'restore')" title="Restaurar">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-triangle-exclamation fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron artículos en el catálogo.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($articulos->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $articulos->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Nuevo Artículo</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd" enctype="multipart/form-data">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Código de Barra</label>
                            <input type="text" class="form-control" name="codigobarra" placeholder="Ej. 7501000000000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre del Artículo *</label>
                            <input type="text" class="form-control" name="nombre" required placeholder="Nombre descriptivo">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Proveedor</label>
                            <select class="form-select" name="proveedorSelect">
                                <option value="" selected>Selecciona Proveedor</option>
                                @foreach($proveedores as $prov)
                                    <option value="{{ $prov->id_proveedor }}">{{ $prov->razon_social }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Referencia</label>
                            <input type="text" class="form-control" name="referencia" placeholder="Referencia interna">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="2" placeholder="Detalles técnicos o notas sobre el producto"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Categoría *</label>
                            <select class="form-select" name="categoriaSelect" required>
                                <option value="" selected disabled>Selecciona Categoría</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id_categoria }}">{{ $cat->nombreCategoria }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sub Categoría</label>
                            <select class="form-select" name="subcategoriaSelect">
                                <option value="" selected>Selecciona la Sub Categoría</option>
                                @foreach($subcategorias as $sub)
                                    <option value="{{ $sub->id_categoria }}">{{ $sub->nombreCategoria }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Precio de Venta</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" name="precio" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Imagen del Producto</label>
                            <input class="form-control" type="file" name="imagen" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Artículo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalEditLabel">Editar Artículo</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="idarticulo" name="idarticulo">
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Código de Barra</label>
                            <input type="text" class="form-control" id="codigobarraU" name="codigobarraU" placeholder="Ej. 7501000000000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre del Artículo *</label>
                            <input type="text" class="form-control" id="nombreU" name="nombreU" required placeholder="Nombre descriptivo">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Proveedor</label>
                            <select class="form-select" id="proveedorSelectU" name="proveedorSelectU">
                                <option value="">Selecciona Proveedor</option>
                                @foreach($proveedores as $prov)
                                    <option value="{{ $prov->id_proveedor }}">{{ $prov->razon_social }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Referencia</label>
                            <input type="text" class="form-control" id="referenciaU" name="referenciaU" placeholder="Referencia interna">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control" id="descripcionU" name="descripcionU" rows="2" placeholder="Detalles técnicos o notas sobre el producto"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Categoría *</label>
                            <select class="form-select" id="categoriaSelectU" name="categoriaSelectU" required>
                                <option value="" disabled>Selecciona Categoría</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id_categoria }}">{{ $cat->nombreCategoria }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sub Categoría</label>
                            <select class="form-select" id="subcategoriaSelectU" name="subcategoriaSelectU">
                                <option value="">Selecciona la Sub Categoría</option>
                                @foreach($subcategorias as $sub)
                                    <option value="{{ $sub->id_categoria }}">{{ $sub->nombreCategoria }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Precio de Venta</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" id="precioU" name="precioU" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-block">Imagen actual</label>
                            <img width="80" id="imagenShow" class="rounded border shadow-sm mb-2" src="" height="80" style="object-fit: cover; background-color: #eee;">
                            <input class="form-control" type="file" id="imagenU" name="imagenU" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Actualizar Artículo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Enviar formulario de agregar
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('{{ route("articulos.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalAdd'));
                modal.hide();
                document.getElementById('formAdd').reset();

                Swal.fire({
                    title: '¡Éxito!',
                    text: r.mensaje,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Advertencia',
                    text: r.mensaje,
                    icon: 'warning'
                });
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Hubo un error al procesar la solicitud.', 'error');
        });
    });

    // Cargar datos para editar
    function editarArticulo(id) {
        fetch(`/articulos/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                const art = r.articulo;
                document.getElementById('idarticulo').value = art.id_producto;
                document.getElementById('codigobarraU').value = art.codigobarra || '';
                document.getElementById('nombreU').value = art.nombre;
                document.getElementById('descripcionU').value = art.descripcion || '';
                document.getElementById('referenciaU').value = art.referencia || '';
                document.getElementById('precioU').value = art.precio;
                
                document.getElementById('proveedorSelectU').value = art.id_proveedor;
                document.getElementById('categoriaSelectU').value = art.id_categoria;
                document.getElementById('subcategoriaSelectU').value = art.id_subcategoria;

                document.getElementById('imagenShow').src = r.ruta_imagen || '{{ asset("img/no-image.png") }}';

                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
                modal.show();
            } else {
                Swal.fire('Error', 'No se pudieron obtener los detalles del artículo.', 'error');
            }
        });
    }

    // Enviar formulario de editar
    document.getElementById('formEdit').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('idarticulo').value;
        const formData = new FormData(this);
        
        fetch(`/articulos/${id}/update`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEdit'));
                modal.hide();

                Swal.fire({
                    title: '¡Éxito!',
                    text: r.mensaje,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Advertencia',
                    text: r.mensaje,
                    icon: 'warning'
                });
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Hubo un error al procesar la solicitud.', 'error');
        });
    });

    // Desactivar / Restaurar artículo
    function cambiarEstado(id, accion) {
        const url = accion === 'delete' ? `/articulos/${id}/delete` : `/articulos/${id}/restore`;
        const titulo = accion === 'delete' ? '¿Desactivar artículo?' : '¿Restaurar artículo?';
        const texto = accion === 'delete' ? 'El artículo se marcará como Inactivo.' : 'El artículo se marcará como Activo.';
        const confirmText = accion === 'delete' ? 'Sí, desactivar' : 'Sí, restaurar';

        Swal.fire({
            title: titulo,
            text: texto,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: accion === 'delete' ? '#d33' : '#3085d6',
            cancelButtonColor: '#aaa',
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(resp => resp.json())
                .then(r => {
                    if (r.success) {
                        Swal.fire({
                            title: '¡Hecho!',
                            text: r.mensaje,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', r.mensaje, 'error');
                    }
                });
            }
        });
    }
</script>
@endsection
