@extends('layouts.app')

@section('title', 'Detalle de Solicitud de Materiales - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Detalle de Solicitud: <span class="text-primary font-monospace">SOL-{{ str_pad($solicitud->id_solicitud_material, 5, '0', STR_PAD_LEFT) }}</span></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('solicitudes.index') }}">Solicitudes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalle</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('solicitudes.index') }}" class="btn btn-outline-dark px-4 py-2">
        <i class="fa-solid fa-arrow-left me-2"></i> Volver a Lista
    </a>
</div>

<div class="row g-4">
    <!-- Información de Solicitud -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 h-100">
            <h4 class="fw-bold mb-4">Ficha de Solicitud</h4>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Orden de Trabajo:</span>
                    <span class="fw-bold text-dark font-monospace">{{ $solicitud->orden?->identificador }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Bodega Origen:</span>
                    <span>{{ $solicitud->bodegaPrincipal?->nombreBodega }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Bodega Destino:</span>
                    <span>{{ $solicitud->bodegaSecundaria?->nombreBodega }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Responsable:</span>
                    <span class="fw-semibold">{{ $solicitud->responsable?->nombres_apellidos }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Entrega / Recibe:</span>
                    <span class="small text-end">{{ $solicitud->entrega?->nombres_apellidos }} ➡️ {{ $solicitud->recibe?->nombres_apellidos }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Fecha Solicitud:</span>
                    <span>{{ $solicitud->fecha_solicitud }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Estado Actual:</span>
                    <span class="badge @if($solicitud->id_estado_solicitud == 1) bg-warning text-dark @elseif($solicitud->id_estado_solicitud == 2) bg-success @else bg-danger @endif px-3 py-2">
                        {{ $solicitud->estadoSolicitud?->estado }}
                    </span>
                </li>
            </ul>

            <div class="bg-light p-3 rounded mb-4">
                <span class="fw-semibold text-muted d-block mb-1">Observación:</span>
                <p class="mb-0 text-dark small">{{ $solicitud->observacion ?: 'Sin observaciones.' }}</p>
            </div>

            <!-- Cambiar Estado -->
            <div class="border-top pt-4">
                <label class="form-label fw-semibold">Cambiar Estado</label>
                <form id="formUpdateStatus" class="row g-2">
                    @csrf
                    <div class="col-8">
                        <select class="form-select" name="id_estado_solicitud" required>
                            @foreach($estados as $est)
                                <option value="{{ $est->id_estado_solicitud }}" @if($solicitud->id_estado_solicitud == $est->id_estado_solicitud) selected @endif>{{ $est->estado }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 d-grid">
                        <button type="submit" class="btn btn-dark">Aplicar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Artículos de Solicitud -->
    <div class="col-lg-8">
        @if($solicitud->id_estado_solicitud == 1)
            <div class="card card-custom p-4 mb-4">
                <h4 class="fw-bold mb-3">Agregar Artículo a la Solicitud</h4>
                <form id="formAddDetail" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Artículo / Material *</label>
                        <select class="form-select" name="id_producto" required>
                            <option value="" selected disabled>Selecciona Artículo</option>
                            @foreach($articulos as $art)
                                <option value="{{ $art->id_producto }}">{{ $art->nombre }} - {{ $art->referencia ?: 'Sin Ref' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Cantidad *</label>
                        <input type="number" step="0.01" class="form-control" name="cantidad" required placeholder="0.00">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary-custom py-2">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Listado -->
        <div class="card card-custom p-0 overflow-hidden">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="fw-bold mb-0">Artículos Solicitados para Traslado</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 15%;">Código Barra</th>
                            <th style="width: 55%;">Artículo</th>
                            <th style="width: 15%;">Cantidad</th>
                            @if($solicitud->id_estado_solicitud == 1)
                                <th class="text-center pe-4" style="width: 15%;">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($detalles as $det)
                            <tr>
                                <td class="ps-4 font-monospace fw-bold">{{ $det->producto?->codigobarra ?: 'N/A' }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $det->producto?->nombre }}</div>
                                    <small class="text-muted">{{ $det->producto?->referencia }}</small>
                                </td>
                                <td class="fw-bold text-primary">{{ number_format($det->cantidad, 2) }}</td>
                                @if($solicitud->id_estado_solicitud == 1)
                                    <td class="text-center pe-4">
                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarDetalle({{ $det->id_solicitud_material_detalle }})">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-box-open fa-2x mb-3 text-warning"></i>
                                    <p class="mb-0">No se han añadido materiales a esta solicitud.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    @if($solicitud->id_estado_solicitud == 1)
    // Agregar
    document.getElementById('formAddDetail').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("solicitudes.storeDetail", $solicitud->id_solicitud_material) }}', {
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

    // Eliminar
    function eliminarDetalle(idDetail) {
        Swal.fire({
            title: '¿Remover material?',
            text: 'Esta acción eliminará el material de la solicitud.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, remover'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/solicitudes/{{ $solicitud->id_solicitud_material }}/detalle/${idDetail}/delete`, {
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
    @endif

    // Cambiar estado
    document.getElementById('formUpdateStatus').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("solicitudes.updateStatus", $solicitud->id_solicitud_material) }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                Swal.fire({
                    title: '¡Actualizado!',
                    text: r.mensaje,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });
</script>
@endsection
