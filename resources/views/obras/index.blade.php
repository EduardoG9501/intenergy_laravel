@extends('layouts.app')

@section('title', 'Nombre de Obra - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Nombre de Obra</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nombre de Obra</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nuevo Nombre de Obra
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('obras.index') }}" method="GET" class="row g-3 align-items-center">
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

<!-- Listado de Obras -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4" style="width: 10%;">ID</th>
                    <th style="width: 50%;">Nombre de la Obra</th>
                    <th style="width: 20%;">Fecha Registro</th>
                    <th style="width: 10%;">Estado</th>
                    <th class="text-center pe-4" style="width: 10%;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($obras as $o)
                    <tr class="@if($o->estado == 0) table-light text-muted @endif">
                        <td class="ps-4 fw-bold font-monospace">{{ $o->id }}</td>
                        <td class="fw-semibold">{{ $o->nombre }}</td>
                        <td>{{ $o->fecha_registro }}</td>
                        <td>
                            @if($o->estado == 1)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-secondary">Inactiva</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            @if($o->estado == 1)
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editObra({{ $o->id }})" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger me-1" onclick="eliminarObra({{ $o->id }})" title="Eliminar">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="deleteObra({{ $o->id }})" title="Desactivar">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                            @else
                                <button class="btn btn-sm btn-outline-danger me-1" onclick="eliminarObra({{ $o->id }})" title="Eliminar">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="restoreObra({{ $o->id }})" title="Restaurar">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-helmet-safety fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron obras registradas.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($obras->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $obras->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Crear Nombre de Obra</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la Obra *</label>
                        <input type="text" class="form-control" name="nombre" required placeholder="Ej. Subestación Norte 60KV">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Obra</button>
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
                <h4 class="fw-bold mb-0" id="modalEditLabel">Editar Obra</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la Obra *</label>
                        <input type="text" class="form-control" name="nombre" id="edit_nombre" required placeholder="Ej. Subestación Norte 60KV">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Actualizar Obra</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Guardar
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("obras.store") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalAdd'));
                if (modal) modal.hide();
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
                    title: 'Error de Validación',
                    text: r.mensaje || 'El nombre de la obra ya se encuentra registrado.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Hubo un error al procesar la solicitud.', 'error');
        });
    });

    // Cargar datos
    function editObra(id) {
        fetch(`/obras/${id}`)
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                document.getElementById('edit_id').value = r.obra.id;
                document.getElementById('edit_nombre').value = r.obra.nombre;
                const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
                modal.show();
            }
        });
    }

    // Actualizar
    document.getElementById('formEdit').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('edit_id').value;
        const formData = new FormData(this);

        fetch(`/obras/${id}/update`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEdit'));
                if (modal) modal.hide();
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
                    text: r.mensaje || 'El nombre de la obra ya se encuentra registrado.',
                    icon: 'warning'
                });
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Hubo un error al procesar la solicitud.', 'error');
        });
    });

    // Eliminar Obra (si no tiene movimientos)
    function eliminarObra(id) {
        Swal.fire({
            title: '¿Eliminar Obra?',
            text: 'Esta acción eliminará la obra solo si no posee movimientos registrados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/obras/${id}/eliminar`, {
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
    function deleteObra(id) {
        Swal.fire({
            title: '¿Desactivar Obra?',
            text: 'Esta acción marcará la obra como inactiva.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, desactivar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/obras/${id}/delete`, {
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
    function restoreObra(id) {
        fetch(`/obras/${id}/restore`, {
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
