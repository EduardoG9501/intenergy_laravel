@extends('layouts.app')

@section('title', 'Asignación de Horas a Empleados - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Control de Horas: <span class="text-primary font-monospace">HRS-{{ str_pad($hora->id_horas_trabajo, 5, '0', STR_PAD_LEFT) }}</span></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('horas.index') }}">Control de Horas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Empleados</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('horas.index') }}" class="btn btn-outline-dark px-4 py-2">
        <i class="fa-solid fa-arrow-left me-2"></i> Volver a Lista
    </a>
</div>

<div class="row g-4">
    <!-- Información General -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 h-100">
            <h4 class="fw-bold mb-4">Ficha del Control</h4>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Responsable Obra:</span>
                    <span class="fw-bold text-dark">{{ $hora->responsable?->nombres_apellidos }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Bodega Origen:</span>
                    <span>{{ $hora->bodegaPrincipal?->nombreBodega }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Bodega Destino:</span>
                    <span>{{ $hora->bodegaSecundaria?->nombreBodega }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Fecha de Registro:</span>
                    <span>{{ $hora->fecha }}</span>
                </li>
            </ul>

            <div class="bg-light p-3 rounded mb-4">
                <span class="fw-semibold text-muted d-block mb-1">Observación:</span>
                <p class="mb-0 text-dark small">{{ $hora->observacion ?: 'Sin observaciones.' }}</p>
            </div>
        </div>
    </div>

    <!-- Asignación de Horas -->
    <div class="col-lg-8">
        <div class="card card-custom p-4 mb-4">
            <h4 class="fw-bold mb-3">Registrar Horas de Empleado</h4>
            <form id="formAddDetail" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Empleado *</label>
                    <select class="form-select" name="id_empleado" required>
                        <option value="" selected disabled>Selecciona Empleado</option>
                        @foreach($empleados as $emp)
                            <option value="{{ $emp->id_empleados }}">{{ $emp->nombres_apellidos }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Número Horas *</label>
                    <input type="number" class="form-control" name="numero_horas" required placeholder="Horas" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Costo por Hora ($) *</label>
                    <input type="number" step="0.01" class="form-control" name="costo_por_hora" required placeholder="0.00">
                </div>
                <div class="col-md-12 d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">
                        <i class="fa-solid fa-plus me-1"></i> Asignar Horas
                    </button>
                </div>
            </form>
        </div>

        <!-- Listado de Empleados en este Registro -->
        <div class="card card-custom p-0 overflow-hidden">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="fw-bold mb-0">Detalle de Mano de Obra y Costos</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Empleado</th>
                            <th>Nº Horas</th>
                            <th>Costo por Hora</th>
                            <th>Total</th>
                            <th class="text-center pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($detalles as $det)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $det->empleado?->nombres_apellidos }}</td>
                                <td class="fw-semibold">{{ $det->numero_horas }} hrs</td>
                                <td>${{ number_format($det->costo_por_hora, 2) }}</td>
                                <td class="fw-bold text-success">${{ number_format($det->total, 2) }}</td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarDetalle({{ $det->id_horas_trabajo_empleado }})">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-user-slash fa-2x mb-3 text-warning"></i>
                                    <p class="mb-0">No se han asignado horas de trabajo a empleados en este registro.</p>
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
    // Agregar
    document.getElementById('formAddDetail').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("horas.storeDetail", $hora->id_horas_trabajo) }}', {
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
            title: '¿Remover asignación de horas?',
            text: 'Esta acción eliminará el registro de horas del empleado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, remover'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/horas/{{ $hora->id_horas_trabajo }}/detalle/${idDetail}/delete`, {
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
</script>
@endsection
