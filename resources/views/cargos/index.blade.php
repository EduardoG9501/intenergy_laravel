@extends('layouts.app')

@section('title', 'Cargos - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Cargos de Empleados</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cargos</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nuevo Cargo
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('cargos.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por nombre de cargo..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Cargos -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Nombre de Cargo</th>
                    <th>Fecha Captura</th>
                    <th>Estado</th>
                    <th class="text-center pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cargos as $crg)
                    <tr class="@if($crg->estado == 0) table-light text-muted @endif">
                        <td class="ps-4 fw-bold">{{ $crg->id_cargo }}</td>
                        <td class="fw-semibold text-dark">{{ $crg->cargo }}</td>
                        <td>{{ $crg->fechaCaptura }}</td>
                        <td>
                            @if($crg->estado == 1)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="editarCargo({{ $crg->id_cargo }})" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @if($crg->estado == 1)
                                    <button type="button" class="btn btn-outline-danger" onclick="cambiarEstado({{ $crg->id_cargo }}, 'delete')" title="Desactivar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-outline-success" onclick="cambiarEstado({{ $crg->id_cargo }}, 'restore')" title="Restaurar">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-triangle-exclamation fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron cargos registrados.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($cargos->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $cargos->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Nuevo Cargo</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nombre de Cargo *</label>
                            <input type="text" class="form-control" name="cargo" required placeholder="Ej. Supervisor de Obra">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Cargo</button>
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
                <h4 class="fw-bold mb-0" id="modalEditLabel">Editar Cargo</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" id="idcargo" name="idcargo">
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nombre de Cargo *</label>
                            <input type="text" class="form-control" id="cargoU" name="cargoU" required placeholder="Ej. Supervisor de Obra">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Actualizar Cargo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Guardar nuevo cargo
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('{{ route("cargos.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
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
    function editarCargo(id) {
        fetch(`/cargos/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                const crg = r.cargo;
                document.getElementById('idcargo').value = crg.id_cargo;
                document.getElementById('cargoU').value = crg.cargo;

                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
                modal.show();
            } else {
                Swal.fire('Error', 'No se pudieron obtener los detalles del cargo.', 'error');
            }
        });
    }

    // Guardar edición de cargo
    document.getElementById('formEdit').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('idcargo').value;
        const formData = new FormData(this);
        
        fetch(`/cargos/${id}/update`, {
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

    // Desactivar / Restaurar cargo
    function cambiarEstado(id, accion) {
        const url = accion === 'delete' ? `/cargos/${id}/delete` : `/cargos/${id}/restore`;
        const titulo = accion === 'delete' ? '¿Desactivar cargo?' : '¿Restaurar cargo?';
        const texto = accion === 'delete' ? 'El cargo se marcará como Inactivo.' : 'El cargo se marcará como Activo.';
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
