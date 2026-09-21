@extends('layouts.app')

@section('title', 'Bodegas - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Gestión de Bodegas</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Bodegas</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nueva Bodega
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('bodegas.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por nombre de bodega o identificador..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Bodegas -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Identificador</th>
                    <th>Nombre de Bodega</th>
                    <th>Tipo</th>
                    <th>Fecha Captura</th>
                    <th>Estado</th>
                    <th class="text-center pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bodegas as $bod)
                    <tr class="@if($bod->estado == 0) table-light text-muted @endif">
                        <td class="ps-4 fw-bold">{{ $bod->id_bodega }}</td>
                        <td><span class="badge bg-secondary font-monospace">{{ $bod->identificador }}</span></td>
                        <td class="fw-semibold text-dark">{{ $bod->nombreBodega }}</td>
                        <td>
                            @if($bod->es_bodega_secundaria == 1)
                                <span class="badge bg-warning-subtle text-warning">Secundaria</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary">Principal</span>
                            @endif
                        </td>
                        <td>{{ $bod->fechaCaptura }}</td>
                        <td>
                            @if($bod->estado == 1)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="editarBodega({{ $bod->id_bodega }})" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @if($bod->estado == 1)
                                    <button type="button" class="btn btn-outline-danger" onclick="cambiarEstado({{ $bod->id_bodega }}, 'delete')" title="Desactivar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-outline-success" onclick="cambiarEstado({{ $bod->id_bodega }}, 'restore')" title="Restaurar">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-triangle-exclamation fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron bodegas registradas.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($bodegas->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $bodegas->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Nueva Bodega</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nombre de Bodega *</label>
                            <input type="text" class="form-control" name="nombreBodega" required placeholder="Ej. Bodega General Norte">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Identificador (Máx 10 letras) *</label>
                            <input type="text" class="form-control" name="identificador" required maxlength="10" placeholder="Ej. BGN">
                            <small class="text-muted">Se utilizará como prefijo para los identificadores de Órdenes de Trabajo.</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">¿Es bodega secundaria? *</label>
                            <select class="form-select" name="es_bodega_secundaria" id="es_bodega_secundaria" required>
                                <option value="0" selected>No (Es Bodega Principal)</option>
                                <option value="1">Sí (Es Bodega Secundaria)</option>
                            </select>
                        </div>
                        <div class="col-md-12 d-none" id="grupoBodegaPrincipal">
                            <label class="form-label fw-semibold">Vincular a Bodega Principal *</label>
                            <select class="form-select" name="id_bodega_principal" id="id_bodega_principal">
                                <option value="" selected disabled>Selecciona Bodega Principal</option>
                                @foreach($principales as $principal)
                                    <option value="{{ $principal->id_bodega }}">{{ $principal->nombreBodega }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Bodega</button>
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
                <h4 class="fw-bold mb-0" id="modalEditLabel">Editar Bodega</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" id="idbodega" name="idbodega">
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nombre de Bodega *</label>
                            <input type="text" class="form-control" id="nombreBodegaU" name="nombreBodegaU" required placeholder="Ej. Bodega General Norte">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Identificador (Máx 10 letras) *</label>
                            <input type="text" class="form-control" id="identificadorU" name="identificadorU" required maxlength="10" placeholder="Ej. BGN">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">¿Es bodega secundaria? *</label>
                            <select class="form-select" name="es_bodega_secundariaU" id="es_bodega_secundariaU" required>
                                <option value="0">No (Es Bodega Principal)</option>
                                <option value="1">Sí (Es Bodega Secundaria)</option>
                            </select>
                        </div>
                        <div class="col-md-12 d-none" id="grupoBodegaPrincipalU">
                            <label class="form-label fw-semibold">Vincular a Bodega Principal *</label>
                            <select class="form-select" name="id_bodega_principalU" id="id_bodega_principalU">
                                <option value="" disabled>Selecciona Bodega Principal</option>
                                @foreach($principales as $principal)
                                    <option value="{{ $principal->id_bodega }}">{{ $principal->nombreBodega }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Actualizar Bodega</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Mostrar/Ocultar combo de Bodega Principal en AGREGAR
    document.getElementById('es_bodega_secundaria').addEventListener('change', function() {
        const grupo = document.getElementById('grupoBodegaPrincipal');
        const select = document.getElementById('id_bodega_principal');
        if (this.value == '1') {
            grupo.classList.remove('d-none');
            select.setAttribute('required', 'required');
        } else {
            grupo.classList.add('d-none');
            select.removeAttribute('required');
            select.value = '';
        }
    });

    // Mostrar/Ocultar combo de Bodega Principal en EDITAR
    document.getElementById('es_bodega_secundariaU').addEventListener('change', function() {
        const grupo = document.getElementById('grupoBodegaPrincipalU');
        const select = document.getElementById('id_bodega_principalU');
        if (this.value == '1') {
            grupo.classList.remove('d-none');
            select.setAttribute('required', 'required');
        } else {
            grupo.classList.add('d-none');
            select.removeAttribute('required');
            select.value = '';
        }
    });

    // Guardar nueva bodega
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('{{ route("bodegas.store") }}', {
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
                document.getElementById('grupoBodegaPrincipal').classList.add('d-none');

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
    function editarBodega(id) {
        fetch(`/bodegas/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                const bod = r.bodega;
                document.getElementById('idbodega').value = bod.id_bodega;
                document.getElementById('nombreBodegaU').value = bod.nombreBodega;
                document.getElementById('identificadorU').value = bod.identificador;
                
                const esSecundaria = bod.es_bodega_secundaria;
                document.getElementById('es_bodega_secundariaU').value = esSecundaria;

                const grupoU = document.getElementById('grupoBodegaPrincipalU');
                const selectU = document.getElementById('id_bodega_principalU');

                if (esSecundaria == 1) {
                    grupoU.classList.remove('d-none');
                    selectU.setAttribute('required', 'required');
                    selectU.value = bod.id_bodega_principal;
                } else {
                    grupoU.classList.add('d-none');
                    selectU.removeAttribute('required');
                    selectU.value = '';
                }

                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
                modal.show();
            } else {
                Swal.fire('Error', 'No se pudieron obtener los detalles de la bodega.', 'error');
            }
        });
    }

    // Guardar edición de bodega
    document.getElementById('formEdit').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('idbodega').value;
        const formData = new FormData(this);
        
        fetch(`/bodegas/${id}/update`, {
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

    // Desactivar / Restaurar bodega
    function cambiarEstado(id, accion) {
        const url = accion === 'delete' ? `/bodegas/${id}/delete` : `/bodegas/${id}/restore`;
        const titulo = accion === 'delete' ? '¿Desactivar bodega?' : '¿Restaurar bodega?';
        const texto = accion === 'delete' ? 'La bodega se marcará como Inactiva.' : 'La bodega se marcará como Activa.';
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
