@extends('layouts.app')

@section('title', 'Proyectos - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Proyectos</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Proyectos</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nuevo Proyecto
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('proyectos.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por nombre..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Proyectos -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4" style="width: 10%;">ID</th>
                    <th style="width: 50%;">Nombre del Proyecto</th>
                    <th style="width: 20%;">Fecha Registro</th>
                    <th style="width: 10%;">Estado</th>
                    <th class="text-center pe-4" style="width: 10%;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proyectos as $p)
                    <tr class="@if($p->estado == 0) table-light text-muted @endif">
                        <td class="ps-4 fw-bold font-monospace">{{ $p->id }}</td>
                        <td class="fw-semibold">{{ $p->nombre }}</td>
                        <td>{{ $p->fecha_registro }}</td>
                        <td>
                            @if($p->estado == 1)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            @if($p->estado == 1)
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editProyecto({{ $p->id }})" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger me-1" onclick="eliminarProyecto({{ $p->id }})" title="Eliminar">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="deleteProyecto({{ $p->id }})" title="Desactivar">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                            @else
                                <button class="btn btn-sm btn-outline-danger me-1" onclick="eliminarProyecto({{ $p->id }})" title="Eliminar">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="restoreProyecto({{ $p->id }})" title="Restaurar">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron proyectos registrados.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($proyectos->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $proyectos->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Crear Proyecto</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del Proyecto *</label>
                        <input type="text" class="form-control" name="nombre" required placeholder="Ej. Línea de Transmisión Sur">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Proyecto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalEditLabel">Editar Proyecto</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del Proyecto *</label>
                        <input type="text" class="form-control" name="nombre" id="edit_nombre" required placeholder="Ej. Línea de Transmisión Sur">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Actualizar Proyecto</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Guardar Proyecto
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("proyectos.store") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                location.reload();
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });

    // Cargar datos para editar
    function editProyecto(id) {
        fetch(`/proyectos/${id}`)
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                document.getElementById('edit_id').value = r.proyecto.id;
                document.getElementById('edit_nombre').value = r.proyecto.nombre;
                const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
                modal.show();
            }
        });
    }

    // Actualizar Proyecto
    document.getElementById('formEdit').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('edit_id').value;
        const formData = new FormData(this);

        fetch(`/proyectos/${id}/update`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                location.reload();
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });

    // Eliminar Proyecto (si no tiene movimientos)
    function eliminarProyecto(id) {
        Swal.fire({
            title: '¿Eliminar Proyecto?',
            text: 'Esta acción eliminará el proyecto solo si no posee movimientos registrados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/proyectos/${id}/eliminar`, {
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
                            title: '¡Eliminado!',
                            text: r.mensaje,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'No se puede eliminar',
                            text: r.mensaje,
                            icon: 'warning'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Ocurrió un error al procesar la eliminación.', 'error');
                });
            }
        });
    }

    // Desactivar
    function deleteProyecto(id) {
        Swal.fire({
            title: '¿Desactivar Proyecto?',
            text: 'Esta acción marcará el proyecto como inactivo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, desactivar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/proyectos/${id}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(resp => resp.json())
                .then(r => {
                    if (r.success) {
                        location.reload();
                    } else {
                        Swal.fire('Error', r.mensaje, 'error');
                    }
                });
            }
        });
    }

    // Restaurar
    function restoreProyecto(id) {
        fetch(`/proyectos/${id}/restore`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                location.reload();
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    }
</script>
@endsection
