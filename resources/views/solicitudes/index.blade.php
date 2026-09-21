@extends('layouts.app')

@section('title', 'Solicitudes de Materiales - Intenergy')

@section('styles')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .select2-dropdown {
        border: 1px solid #dee2e6;
        border-radius: 6px;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Solicitudes de Materiales</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Solicitudes</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nueva Solicitud
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('solicitudes.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por código de OT u observaciones..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Solicitudes -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">ID Solicitud</th>
                    <th>Orden de Trabajo</th>
                    <th>Bodega Origen</th>
                    <th>Bodega Destino</th>
                    <th>Responsable</th>
                    <th>Fecha Solicitud</th>
                    <th>Estado</th>
                    <th class="text-center pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($solicitudes as $s)
                    <tr>
                        <td class="ps-4 fw-bold font-monospace text-primary">SOL-{{ str_pad($s->id_solicitud_material, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="fw-semibold">{{ $s->orden?->identificador }}</td>
                        <td>{{ $s->bodegaPrincipal?->nombreBodega }}</td>
                        <td>{{ $s->bodegaSecundaria?->nombreBodega }}</td>
                        <td class="fw-semibold text-dark">{{ $s->responsable?->nombres_apellidos }}</td>
                        <td>{{ $s->fecha_solicitud }}</td>
                        <td>
                            <span class="badge @if($s->id_estado_solicitud == 1) bg-warning text-dark @elseif($s->id_estado_solicitud == 2) bg-success @else bg-danger @endif">
                                {{ $s->estadoSolicitud?->estado }}
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <a href="{{ route('solicitudes.show', $s->id_solicitud_material) }}" class="btn btn-sm btn-outline-primary" title="Gestionar Detalles">
                                <i class="fa-solid fa-eye me-1"></i> Detalles
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-dolly fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron solicitudes de materiales registradas.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($solicitudes->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $solicitudes->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Crear Solicitud de Materiales</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Orden de Trabajo *</label>
                            <select class="form-select select2-orden" name="id_orden" id="id_orden_solicitud" required>
                                <option value="" selected disabled>Selecciona Orden de Trabajo</option>
                                @foreach($ordenes as $o)
                                    <option value="{{ $o->id_orden }}">{{ $o->identificador }} - {{ $o->obra?->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha de Solicitud *</label>
                            <input type="date" class="form-control" name="fecha_solicitud" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bodega Origen *</label>
                            <select class="form-select" name="id_bodega_principal" required>
                                <option value="" selected disabled>Selecciona Bodega Principal</option>
                                @foreach($bodegasPrincipales as $bp)
                                    <option value="{{ $bp->id_bodega }}">{{ $bp->nombreBodega }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bodega Destino (Secundaria) *</label>
                            <select class="form-select" name="id_bodega_secundaria" required>
                                <option value="" selected disabled>Selecciona Bodega Secundaria</option>
                                @foreach($bodegasSecundarias as $bs)
                                    <option value="{{ $bs->id_bodega }}">{{ $bs->nombreBodega }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Responsable de la Obra *</label>
                            <select class="form-select" name="id_responsable_obra" required>
                                <option value="" selected disabled>Selecciona Responsable</option>
                                @foreach($empleados as $emp)
                                    <option value="{{ $emp->id_empleados }}">{{ $emp->nombres_apellidos }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Entrega Materiales *</label>
                            <select class="form-select" name="id_entrega_articulos" required>
                                <option value="" selected disabled>Selecciona Quien Entrega</option>
                                @foreach($empleados as $emp)
                                    <option value="{{ $emp->id_empleados }}">{{ $emp->nombres_apellidos }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Recibe Materiales *</label>
                            <select class="form-select" name="id_recibe_articulos" required>
                                <option value="" selected disabled>Selecciona Quien Recibe</option>
                                @foreach($empleados as $emp)
                                    <option value="{{ $emp->id_empleados }}">{{ $emp->nombres_apellidos }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Observación / Descripción</label>
                            <textarea class="form-control" name="observacion" rows="3" placeholder="Detalles de la entrega, motivos del traslado, etc..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Inicializar Select2 en el selector de orden de trabajo
        $('.select2-orden').select2({
            dropdownParent: $('#modalAdd'),
            width: '100%',
            placeholder: 'Buscar orden de trabajo...',
            allowClear: true,
            language: {
                noResults: function() {
                    return "No se encontraron resultados";
                },
                searching: function() {
                    return "Buscando...";
                }
            }
        });

        // Reiniciar Select2 cuando se cierra el modal
        $('#modalAdd').on('hidden.bs.modal', function () {
            $('.select2-orden').val(null).trigger('change');
        });
    });

    // Guardar
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("solicitudes.store") }}', {
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
                    title: '¡Creada!',
                    text: 'Redirigiendo al detalle de la solicitud...',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = `/solicitudes/${r.id}`;
                });
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });
</script>
@endsection
