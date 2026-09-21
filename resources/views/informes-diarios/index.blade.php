@extends('layouts.app')

@section('title', 'Informes Diarios - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Informes Diarios</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Informes Diarios</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nuevo Informe Diario
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('informes.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Buscar</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por descripción, observación o lugar..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Fecha Desde</label>
            <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Fecha Hasta</label>
            <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Informes Diarios -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">ID Informe</th>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Lugar</th>
                    <th>Ejecución</th>
                    <th class="text-center pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($informes as $inf)
                    <tr>
                        <td class="ps-4 fw-bold font-monospace text-primary">INF-{{ str_pad($inf->id_informe_diario_ejecucion, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ \Carbon\Carbon::parse($inf->fecha)->format('d/m/Y') }}</td>
                        <td class="text-truncate" style="max-width: 250px;">{{ $inf->descripcion ?: 'Sin descripción.' }}</td>
                        <td>{{ $inf->lugar ?: '-' }}</td>
                        <td>
                            @if($inf->ejecucion)
                                <a href="{{ route('ejecuciones.show', $inf->ejecucion->id_ejecucion_obra) }}" class="text-primary fw-semibold text-decoration-none">
                                    EJEC-{{ str_pad($inf->ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}
                                </a>
                            @else
                                <span class="text-muted">Sin vincular</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            <a href="{{ route('informes.show', $inf->id_informe_diario_ejecucion) }}" class="btn btn-sm btn-outline-dark me-1" title="Ver Detalle">
                                <i class="fa-solid fa-eye me-1"></i> Ver
                            </a>
                            <button class="btn btn-sm btn-outline-info me-1" title="Editar" onclick="editarInforme({{ $inf->id_informe_diario_ejecucion }}, '{{ $inf->fecha }}', '{{ addslashes($inf->descripcion) }}', '{{ addslashes($inf->observacion) }}', '{{ addslashes($inf->lugar) }}', {{ $inf->id_ejecucion_obra ?? 'null' }})">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="eliminarInforme({{ $inf->id_informe_diario_ejecucion }})">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-file-circle-exclamation fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron informes diarios.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($informes->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $informes->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Nuevo Informe Diario</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha del Informe *</label>
                            <input type="date" class="form-control" name="fecha" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Descripción</label>
                            <input type="text" class="form-control" name="descripcion" placeholder="Descripción general del informe...">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Observaciones</label>
                            <textarea class="form-control" name="observacion" rows="3" placeholder="Observaciones adicionales..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Ejecución de Obra (opcional)</label>
                            <input type="hidden" name="id_ejecucion_obra" id="add_id_ejecucion_obra">
                            <div class="input-group">
                                <input type="text" class="form-control bg-white" id="add_nombre_ejecucion" placeholder="Sin vincular" readonly>
                                <button type="button" class="btn btn-outline-primary" onclick="abrirModalEjecuciones('add')">
                                    <i class="fa-solid fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" id="clearEjecucionAdd" onclick="limpiarEjecucion('add')" style="display:none;">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Informe</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0 bg-info text-white">
                <h4 class="fw-bold mb-0" id="modalEditLabel">Editar Informe Diario</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" name="id_informe" id="edit_id_informe">
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha del Informe *</label>
                            <input type="date" class="form-control" name="fecha" id="edit_fecha" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Descripción</label>
                            <input type="text" class="form-control" name="descripcion" id="edit_descripcion">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Observaciones</label>
                            <textarea class="form-control" name="observacion" id="edit_observacion" rows="3"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Ejecución de Obra (opcional)</label>
                            <input type="hidden" name="id_ejecucion_obra" id="edit_id_ejecucion_obra">
                            <div class="input-group">
                                <input type="text" class="form-control bg-white" id="edit_nombre_ejecucion" placeholder="Sin vincular" readonly>
                                <button type="button" class="btn btn-outline-primary" onclick="abrirModalEjecuciones('edit')">
                                    <i class="fa-solid fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" id="clearEjecucionEdit" onclick="limpiarEjecucion('edit')" style="display:none;">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-danger px-4 py-2 me-2" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-success px-4 py-2">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<!-- MODAL SELECCIONAR EJECUCION -->
<div class="modal fade" id="modalSeleccionarEjecucion" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1060;">
        <div class="modal-content border-0 shadow-lg" style="z-index: 1060;">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-hard-hat me-2"></i> Seleccionar Ejecución de Obra</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" class="form-control" id="buscadorEjecucionModal" placeholder="Buscar por obra, proyecto o ID..." oninput="filtrarEjecucionesModal()">
                </div>
                <div class="list-group" id="listaEjecucionesModal" style="max-height: 350px; overflow-y: auto;"></div>
                <div id="sinResultadosEjecucion" class="text-center text-muted py-4" style="display:none;">
                    <i class="fa-solid fa-search fa-2x mb-2 text-warning"></i>
                    <p class="mb-0">No se encontraron ejecuciones</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    var arrEjecuciones = {!! json_encode($ejecuciones->map(fn($e) => ['id' => $e->id_ejecucion_obra, 'label' => 'EJEC-' . str_pad($e->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) . ' — ' . ($e->orden?->obra?->nombre ?? 'Sin obra')])) !!};
    var ejecucionTarget = null;

    function abrirModalEjecuciones(target) {
        ejecucionTarget = target;
        document.getElementById('buscadorEjecucionModal').value = '';
        renderizarListaEjecuciones('');
        new bootstrap.Modal(document.getElementById('modalSeleccionarEjecucion')).show();
        setTimeout(function() { document.getElementById('buscadorEjecucionModal').focus(); }, 400);
    }

    function renderizarListaEjecuciones(busqueda) {
        var lista = document.getElementById('listaEjecucionesModal');
        var sinResultados = document.getElementById('sinResultadosEjecucion');
        var busq = busqueda.toLowerCase().trim();
        var html = '';
        var visibles = 0;

        for (var i = 0; i < arrEjecuciones.length; i++) {
            var ej = arrEjecuciones[i];
            var labelLower = ej.label.toLowerCase();
            if (busq === '' || labelLower.indexOf(busq) !== -1) {
                html += '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" onclick="seleccionarEjecucionModal(' + ej.id + ', this.getAttribute(\x27data-label\x27))" data-label="' + ej.label.replace(/"/g, '&quot;') + '">' +
                    '<span>' + ej.label + '</span>' +
                    '<i class="fa-solid fa-chevron-right text-muted small"></i>' +
                    '</button>';
                visibles++;
            }
        }

        lista.innerHTML = html;
        sinResultados.style.display = visibles === 0 ? '' : 'none';
    }

    function filtrarEjecucionesModal() {
        renderizarListaEjecuciones(document.getElementById('buscadorEjecucionModal').value);
    }

    function seleccionarEjecucionModal(id, label) {
        if (!ejecucionTarget) return;
        document.getElementById(ejecucionTarget + '_id_ejecucion_obra').value = id;
        document.getElementById(ejecucionTarget + '_nombre_ejecucion').value = label;
        document.getElementById('clearEjecucion' + ejecucionTarget.charAt(0).toUpperCase() + ejecucionTarget.slice(1)).style.display = '';
        bootstrap.Modal.getInstance(document.getElementById('modalSeleccionarEjecucion')).hide();
    }

    function limpiarEjecucion(target) {
        document.getElementById(target + '_id_ejecucion_obra').value = '';
        document.getElementById(target + '_nombre_ejecucion').value = '';
        document.getElementById('clearEjecucion' + target.charAt(0).toUpperCase() + target.slice(1)).style.display = 'none';
    }

    // Guardar nuevo informe
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("informes.store") }}', {
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
                    text: 'Redirigiendo al detalle del informe...',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = `/informes-diarios/${r.id}`;
                });
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });

    // Abrir modal editar
    function editarInforme(id, fecha, descripcion, observacion, lugar, idEjecucion) {
        document.getElementById('edit_id_informe').value = id;
        document.getElementById('edit_fecha').value = fecha;
        document.getElementById('edit_descripcion').value = descripcion || '';
        document.getElementById('edit_observacion').value = observacion || '';
        document.getElementById('edit_id_ejecucion_obra').value = idEjecucion || '';
        if (idEjecucion) {
            var ej = arrEjecuciones.find(function(e) { return e.id == idEjecucion; });
            document.getElementById('edit_nombre_ejecucion').value = ej ? ej.label : '';
            document.getElementById('clearEjecucionEdit').style.display = '';
        } else {
            document.getElementById('edit_nombre_ejecucion').value = '';
            document.getElementById('clearEjecucionEdit').style.display = 'none';
        }
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    // Guardar edición
    document.getElementById('formEdit').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('edit_id_informe').value;
        const formData = new FormData(this);

        fetch(`/informes-diarios/${id}/update`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEdit'));
                modal.hide();
                Swal.fire({ title: '¡Actualizado!', text: 'Informe actualizado correctamente.', icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });

    // Eliminar informe
    function eliminarInforme(id) {
        Swal.fire({
            title: '¿Eliminar informe?',
            text: 'Esta acción eliminará el informe y todos sus datos asociados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/informes-diarios/${id}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(resp => resp.json())
                .then(r => {
                    if (r.success) {
                        Swal.fire({ title: '¡Eliminado!', text: 'Informe eliminado correctamente.', icon: 'success', timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                    } else {
                        Swal.fire('Error', r.mensaje, 'error');
                    }
                });
            }
        });
    }
</script>
@endsection
