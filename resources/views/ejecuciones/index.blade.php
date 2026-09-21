@extends('layouts.app')

@section('title', 'Ejecución de Obra - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Ejecución Diaria de Obra</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ejecución de Obra</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nueva Ejecución Diaria
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('ejecuciones.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por código de OT..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Ejecuciones -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">ID Registro</th>
                    <th>Orden de Trabajo</th>
                    <th>Proyecto</th>
                    <th>Nombre de Obra</th>
                    <th>Fecha Solicitud</th>
                    <th>Feriado</th>
                    <th>Estado</th>
                    <th class="text-center pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ejecuciones as $ej)
                    <tr>
                        <td class="ps-4 fw-bold font-monospace text-primary">EJEC-{{ str_pad($ej->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="fw-semibold">{{ $ej->orden?->identificador }}</td>
                        <td>{{ $ej->orden?->proyecto?->nombre }}</td>
                        <td>{{ $ej->orden?->obra?->nombre }}</td>
                        <td>{{ $ej->fecha_solicitud }}</td>
                        <td>
                            @if($ej->feriado)
                                <span class="badge bg-danger">Sí</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge @if($ej->id_estado_ejecucion_obra == 1) bg-warning text-dark @else bg-success @endif">
                                {{ $ej->estadoEjecucion?->estado }}
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            @if($ej->id_estado_ejecucion_obra != 2)
                                <button class="btn btn-sm btn-outline-info me-1" title="Editar" onclick="editarEjecucion({{ $ej->id_ejecucion_obra }}, '{{ $ej->fecha_solicitud }}', {{ $ej->feriado }}, '{{ addslashes($ej->observacion) }}', {{ $ej->id_estado_ejecucion_obra }})">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            @endif
                            <a href="{{ route('ejecuciones.show', $ej->id_ejecucion_obra) }}" class="btn btn-sm btn-outline-primary" title="Gestionar Detalles">
                                <i class="fa-solid fa-eye me-1"></i> Detalles
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-person-digging fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron registros de ejecución diaria.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($ejecuciones->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $ejecuciones->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Crear Ejecución Diaria</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Orden de Trabajo *</label>
                        <select class="form-select" name="id_orden" required>
                            <option value="" selected disabled>Selecciona Orden de Trabajo</option>
                            @foreach($ordenes as $o)
                                <option value="{{ $o->id_orden }}">{{ $o->identificador }} - {{ $o->obra?->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fecha Ejecución *</label>
                        <input type="date" class="form-control" name="fecha_solicitud" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">¿Es Feriado / Festivo? *</label>
                        <select class="form-select" name="feriado" required>
                            <option value="0" selected>No (Día Normal)</option>
                            <option value="1">Sí (Día Feriado/Domingo)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observación / Descripción</label>
                        <textarea class="form-control" name="observacion" rows="3" placeholder="Comentarios sobre el avance de la obra hoy..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Ejecución</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0 bg-info text-white">
                <h4 class="fw-bold mb-0">Editar Ejecución</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" name="id_ejecucion" id="edit_id_ejecucion">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fecha de Solicitud *</label>
                        <input type="date" class="form-control" name="fecha_solicitud" id="edit_fecha_solicitud" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">¿Es Feriado / Festivo? *</label>
                        <select class="form-select" name="feriado" id="edit_feriado" required>
                            <option value="0">No (Día Normal)</option>
                            <option value="1">Sí (Día Feriado/Domingo)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observación / Descripción</label>
                        <textarea class="form-control" name="observacion" id="edit_observacion" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado de la Ejecución *</label>
                        <select class="form-select" name="id_estado_ejecucion_obra" id="edit_estado" required>
                            <option value="1">PENDIENTE</option>
                            <option value="2">TERMINADO</option>
                        </select>
                    </div>
                    <div class="alert alert-warning small mb-0" id="edit_estado_warning" style="display:none;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        <strong>Atención:</strong> Al marcar como TERMINADO ya no podrá editar esta ejecución.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4 py-2">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function editarEjecucion(id, fecha, feriado, observacion, estado) {
        document.getElementById('edit_id_ejecucion').value = id;
        document.getElementById('edit_fecha_solicitud').value = fecha;
        document.getElementById('edit_feriado').value = feriado;
        document.getElementById('edit_observacion').value = observacion || '';
        document.getElementById('edit_estado').value = estado;
        toggleEstadoWarning();
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    function toggleEstadoWarning() {
        var val = document.getElementById('edit_estado').value;
        document.getElementById('edit_estado_warning').style.display = val == '2' ? '' : 'none';
    }

    document.getElementById('edit_estado').addEventListener('change', toggleEstadoWarning);

    document.getElementById('formEdit').addEventListener('submit', function(e) {
        e.preventDefault();
        var id = document.getElementById('edit_id_ejecucion').value;
        var formData = new FormData(this);

        fetch(`/ejecuciones/${id}/update`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalEdit')).hide();
                Swal.fire({ title: '¡Actualizado!', text: r.mensaje, icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });

    // Guardar
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("ejecuciones.store") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalAdd'));
                modal.hide();

                Swal.fire({
                    title: '¡Creado!',
                    text: 'Redirigiendo a la carga de materiales y horas...',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = `/ejecuciones/${r.id}`;
                });
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });
</script>
@endsection
