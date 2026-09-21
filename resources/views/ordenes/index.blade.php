@extends('layouts.app')

@section('title', 'Órdenes de Trabajo - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Órdenes de Trabajo</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Órdenes de Trabajo</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nueva Orden
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('ordenes.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por identificador, proyecto, obra u observaciones..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Órdenes de Trabajo -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4" style="width: 5%;">ID</th>
                    <th style="width: 12%;">IDENTIFICADOR</th>
                    <th style="width: 15%;">PROYECTO</th>
                    <th style="width: 25%;">NOMBRE DE LA OBRA</th>
                    <th style="width: 10%;">FECHA</th>
                    <th style="width: 13%;">LUGAR</th>
                    <th class="text-center pe-4" style="width: 10%;">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes as $o)
                    <tr class="@if($o->estado == 0) table-light text-muted @endif">
                        <td class="ps-4 fw-bold text-primary">{{ $o->id_orden }}</td>
                        <td class="fw-bold font-monospace">{{ $o->identificador }}</td>
                        <td class="fw-semibold">{{ $o->proyecto?->nombre }}</td>
                        <td class="fw-semibold text-dark">{{ $o->obra?->nombre }}</td>
                        <td>{{ \Carbon\Carbon::parse($o->fecha_inicial)->format('Y-m-d') }}</td>
                        <td>{{ $o->bodegaPrincipal?->nombreBodega }}</td>
                        <td class="text-center pe-4">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-warning dropdown-toggle fw-semibold" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-tasks me-1"></i> ACCIONES
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('pedidos.index') }}?orden={{ $o->id_orden }}">
                                            <i class="fa-solid fa-box-open me-2 text-info"></i> Pedido de Materiales
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('ejecuciones.index') }}?orden={{ $o->id_orden }}">
                                            <i class="fa-solid fa-hard-hat me-2 text-primary"></i> Ejecución de Obra
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    @if($o->estado == 1)
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="editOrden({{ $o->id_orden }})">
                                                <i class="fa-solid fa-pen me-2 text-success"></i> Editar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="deleteOrden({{ $o->id_orden }})">
                                                <i class="fa-solid fa-ban me-2 text-danger"></i> Desactivar
                                            </a>
                                        </li>
                                    @else
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="restoreOrden({{ $o->id_orden }})">
                                                <i class="fa-solid fa-rotate-left me-2 text-success"></i> Restaurar
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-closed fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron órdenes de trabajo registradas.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($ordenes->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $ordenes->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0 bg-info text-white">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Agregar Nueva Orden</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">PROYECTO: *</label>
                            <select class="form-select" name="id_proyecto" id="add_id_proyecto" required onchange="cargarObrasAdd(this.value)">
                                <option value="" selected disabled>Selecciona Proyecto</option>
                                @foreach($proyectos as $p)
                                    <option value="{{ $p->id }}">{{ $p->id }} - {{ $p->nombre }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">El código identificador se generará automáticamente</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">NOMBRE DE OBRA: *</label>
                            <select class="form-select" name="id_obra" id="add_id_obra" required>
                                <option value="" selected disabled>Selecciona una obra</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">FECHA: *</label>
                            <input type="date" class="form-control" name="fecha_inicial" required value="{{ date('Y-m-d') }}">
                            <input type="hidden" name="fecha_final" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">LUGAR: *</label>
                            <select class="form-select" name="id_bodega_principal" id="add_id_bodega_principal" required>
                                <option value="" disabled>Selecciona Lugar</option>
                                @foreach($bodegasPrincipales as $bp)
                                    <option value="{{ $bp->id_bodega }}" @if(session('bodega_seleccionada') == $bp->id_bodega) selected @endif>{{ $bp->nombreBodega }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="id_bodega_secundaria" id="add_id_bodega_secundaria" value="">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">OBSERVACIÓN:</label>
                            <textarea class="form-control" name="observacion" rows="3" placeholder="Detalles de la orden de trabajo..."></textarea>
                        </div>
                        <input type="hidden" name="costo" value="0">
                        <input type="hidden" name="id_estado_orden" value="1">
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

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0 bg-info text-white">
                <h4 class="fw-bold mb-0" id="modalEditLabel">Editar Orden</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">PROYECTO: *</label>
                            <select class="form-select" name="id_proyecto" id="edit_id_proyecto" required onchange="cargarObrasEdit(this.value)">
                                <option value="" disabled>Selecciona Proyecto</option>
                                @foreach($proyectos as $p)
                                    <option value="{{ $p->id }}">{{ $p->id }} - {{ $p->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">NOMBRE DE OBRA: *</label>
                            <select class="form-select" name="id_obra" id="edit_id_obra" required>
                                <option value="" disabled>Selecciona Nombre de Obra</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">IDENTIFICADOR:</label>
                            <input type="text" class="form-control bg-light" name="identificador" id="edit_identificador" readonly>
                            <small class="text-muted">El identificador no se puede modificar</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">LUGAR: *</label>
                            <select class="form-select" name="id_bodega_principal" id="edit_id_bodega_principal" required>
                                <option value="" disabled>Selecciona Lugar</option>
                                @foreach($bodegasPrincipales as $bp)
                                    <option value="{{ $bp->id_bodega }}">{{ $bp->nombreBodega }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="id_bodega_secundaria" id="edit_id_bodega_secundaria" value="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">FECHA: *</label>
                            <input type="date" class="form-control" name="fecha_inicial" id="edit_fecha_inicial" required>
                            <input type="hidden" name="fecha_final" id="edit_fecha_final">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ESTADO: *</label>
                            <select class="form-select" name="id_estado_orden" id="edit_id_estado_orden" required>
                                @foreach($estados as $est)
                                    <option value="{{ $est->id_estado_orden }}">{{ $est->estado }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">OBSERVACIÓN:</label>
                            <textarea class="form-control" name="observacion" id="edit_observacion" rows="3" placeholder="Detalles de la orden de trabajo..."></textarea>
                        </div>
                        <input type="hidden" name="costo" id="edit_costo" value="0">
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

@section('scripts')
<script>
    // Cargar obras por proyecto (Modal Agregar)
    function cargarObrasAdd(idProyecto) {
        const obraSelect = document.getElementById('add_id_obra');
        obraSelect.innerHTML = '<option value="" selected disabled>Cargando obras...</option>';
        obraSelect.disabled = true;

        if (!idProyecto) return;

        fetch(`/ordenes/obras/${idProyecto}`)
            .then(resp => resp.json())
            .then(r => {
                if (r.success) {
                    obraSelect.innerHTML = '<option value="" selected disabled>Selecciona una obra</option>';
                    r.obras.forEach(o => {
                        obraSelect.innerHTML += `<option value="${o.id}">${o.id} - ${o.nombre}</option>`;
                    });
                    obraSelect.disabled = false;
                } else {
                    obraSelect.innerHTML = '<option value="" selected disabled>Error al cargar obras</option>';
                }
            })
            .catch(() => {
                obraSelect.innerHTML = '<option value="" selected disabled>Error de red</option>';
            });
    }

    // Cargar obras por proyecto (Modal Editar)
    function cargarObrasEdit(idProyecto) {
        const obraSelect = document.getElementById('edit_id_obra');
        obraSelect.innerHTML = '<option value="" selected disabled>Cargando obras...</option>';
        obraSelect.disabled = true;

        if (!idProyecto) return;

        fetch(`/ordenes/obras/${idProyecto}`)
            .then(resp => resp.json())
            .then(r => {
                if (r.success) {
                    obraSelect.innerHTML = '<option value="" disabled>Selecciona Nombre de Obra</option>';
                    r.obras.forEach(o => {
                        obraSelect.innerHTML += `<option value="${o.id}">${o.id} - ${o.nombre}</option>`;
                    });
                    obraSelect.disabled = false;
                } else {
                    obraSelect.innerHTML = '<option value="" selected disabled>Error al cargar obras</option>';
                }
            })
            .catch(() => {
                obraSelect.innerHTML = '<option value="" selected disabled>Error de red</option>';
            });
    }

    // Guardar
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("ordenes.store") }}', {
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
    function editOrden(id) {
        fetch(`/ordenes/${id}`)
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                document.getElementById('edit_id').value = r.orden.id_orden;
                document.getElementById('edit_identificador').value = r.orden.identificador;
                document.getElementById('edit_costo').value = r.orden.costo;
                document.getElementById('edit_id_proyecto').value = r.orden.id_proyecto;
                document.getElementById('edit_fecha_inicial').value = r.orden.fecha_inicial;
                document.getElementById('edit_fecha_final').value = r.orden.fecha_final;
                document.getElementById('edit_id_bodega_principal').value = r.orden.id_bodega_principal;
                document.getElementById('edit_id_bodega_secundaria').value = r.orden.id_bodega_secundaria || '';
                document.getElementById('edit_id_estado_orden').value = r.orden.id_estado_orden;
                document.getElementById('edit_observacion').value = r.orden.observacion || '';

                // Cargar obras del proyecto y seleccionar la obra correcta
                cargarObrasEdit(r.orden.id_proyecto).then(() => {
                    document.getElementById('edit_id_obra').value = r.orden.id_obra;
                });

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

        fetch(`/ordenes/${id}/update`, {
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

    // Desactivar
    function deleteOrden(id) {
        Swal.fire({
            title: '¿Desactivar Orden de Trabajo?',
            text: 'Esta acción marcará la orden de trabajo como inactiva.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, desactivar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/ordenes/${id}/delete`, {
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
    function restoreOrden(id) {
        fetch(`/ordenes/${id}/restore`, {
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
