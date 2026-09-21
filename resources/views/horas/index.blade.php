@extends('layouts.app')

@section('title', 'Control de Horas de Trabajo - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Control de Horas Laborales</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Control de Horas</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nuevo Registro de Horas
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('horas.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por observaciones..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Registros de Horas -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">ID Control</th>
                    <th>Responsable</th>
                    <th>Bodega Origen</th>
                    <th>Bodega Destino</th>
                    <th>Fecha</th>
                    <th>Observación</th>
                    <th class="text-center pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($horas as $h)
                    <tr>
                        <td class="ps-4 fw-bold font-monospace text-primary">HRS-{{ str_pad($h->id_horas_trabajo, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="fw-semibold text-dark">{{ $h->responsable?->nombres_apellidos }}</td>
                        <td>{{ $h->bodegaPrincipal?->nombreBodega }}</td>
                        <td>{{ $h->bodegaSecundaria?->nombreBodega }}</td>
                        <td>{{ $h->fecha }}</td>
                        <td class="text-truncate" style="max-width: 250px;">{{ $h->observacion ?: 'Sin observaciones.' }}</td>
                        <td class="text-center pe-4">
                            <a href="{{ route('horas.show', $h->id_horas_trabajo) }}" class="btn btn-sm btn-outline-primary" title="Gestionar Horas por Empleado">
                                <i class="fa-solid fa-user-clock me-1"></i> Empleados
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-clock-rotate-left fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron registros de control de horas.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($horas->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $horas->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Nuevo Control de Horas</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha de Registro *</label>
                            <input type="date" class="form-control" name="fecha" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Responsable de Obra *</label>
                            <select class="form-select" name="id_responsable_obra" required>
                                <option value="" selected disabled>Selecciona Responsable</option>
                                @foreach($empleados as $emp)
                                    <option value="{{ $emp->id_empleados }}">{{ $emp->nombres_apellidos }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bodega Principal *</label>
                            <select class="form-select" name="id_bodega_principal" required>
                                <option value="" selected disabled>Selecciona Bodega Principal</option>
                                @foreach($bodegasPrincipales as $bp)
                                    <option value="{{ $bp->id_bodega }}">{{ $bp->nombreBodega }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bodega Secundaria (Destino) *</label>
                            <select class="form-select" name="id_bodega_secundaria" required>
                                <option value="" selected disabled>Selecciona Bodega Secundaria</option>
                                @foreach($bodegasSecundarias as $bs)
                                    <option value="{{ $bs->id_bodega }}">{{ $bs->nombreBodega }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Observación / Descripción</label>
                            <textarea class="form-control" name="observacion" rows="3" placeholder="Detalles sobre las jornadas registradas, frentes cubiertos, etc..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Registro</button>
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

        fetch('{{ route("horas.store") }}', {
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
                    text: 'Redirigiendo a la asignación de horas de empleados...',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = `/horas/${r.id}`;
                });
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });
</script>
@endsection
