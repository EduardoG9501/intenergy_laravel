@extends('layouts.app')

@section('title', 'Pedidos de Materiales - Intenergy')

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
        <h2 class="fw-bold mb-0">Pedidos de Materiales</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pedidos de Materiales</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nuevo Pedido
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('pedidos.index') }}" method="GET" class="row g-3 align-items-center">
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

<!-- Listado de Pedidos -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4" style="width: 15%;">ID Pedido</th>
                    <th style="width: 20%;">Orden de Trabajo</th>
                    <th style="width: 20%;">Fecha Solicitud</th>
                    <th style="width: 25%;">Observaciones</th>
                    <th style="width: 10%;">Estado</th>
                    <th class="text-center pe-4" style="width: 10%;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pedidos as $p)
                    <tr>
                        <td class="ps-4 fw-bold font-monospace text-primary">PED-{{ str_pad($p->id_pedido_material, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="fw-semibold">{{ $p->orden?->identificador }}</td>
                        <td>{{ $p->fecha_solicitud }}</td>
                        <td class="text-truncate" style="max-width: 250px;">{{ $p->observacion ?: 'Sin observaciones.' }}</td>
                        <td>
                            <span class="badge @if($p->id_estado_pedido_material == 1) bg-warning text-dark @elseif($p->id_estado_pedido_material == 2) bg-success @else bg-danger @endif">
                                {{ $p->estadoPedido?->estado }}
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <a href="{{ route('pedidos.show', $p->id_pedido_material) }}" class="btn btn-sm btn-outline-primary me-1" title="Ver Detalles">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-success" onclick="editPedido({{ $p->id_pedido_material }})" title="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-cart-flatbed fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron pedidos de materiales registrados.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($pedidos->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $pedidos->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Crear Pedido de Materiales</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Orden de Trabajo *</label>
                        <select class="form-select select2-orden" name="id_orden" id="id_orden" required>
                            <option value="" selected disabled>Selecciona Orden de Trabajo</option>
                            @foreach($ordenes as $o)
                                <option value="{{ $o->id_orden }}">{{ $o->identificador }} - {{ $o->obra?->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fecha de Solicitud *</label>
                        <input type="date" class="form-control" name="fecha_solicitud" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observación / Descripción</label>
                        <textarea class="form-control" name="observacion" rows="3" placeholder="Detalles o justificación del pedido..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Pedido</button>
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
                <h4 class="fw-bold mb-0" id="modalEditLabel">Editar Pedido de Material</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ORDEN DE TRABAJO: *</label>
                            <select class="form-select select2-orden-edit" name="id_orden" id="edit_id_orden" required>
                                <option value="" disabled>Selecciona Orden de Trabajo</option>
                                @foreach($ordenes as $o)
                                    <option value="{{ $o->id_orden }}">{{ $o->identificador }} - {{ $o->obra?->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">FECHA SOLICITUD: *</label>
                            <input type="date" class="form-control" name="fecha_solicitud" id="edit_fecha_solicitud" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">OBSERVACIÓN:</label>
                            <textarea class="form-control" name="observacion" id="edit_observacion" rows="3" placeholder="Detalles o justificación del pedido..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ESTADO DEL PEDIDO: *</label>
                            <select class="form-select" name="id_estado_pedido_material" id="edit_id_estado_pedido_material" required>
                                @foreach($estados as $est)
                                    <option value="{{ $est->id_estado_pedido_material }}">{{ $est->estado }}</option>
                                @endforeach
                            </select>
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

@section('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar Select2 en el selector de orden de trabajo (modal agregar)
        $(document).ready(function() {
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

            // Inicializar Select2 en el selector de orden de trabajo (modal editar)
            $('.select2-orden-edit').select2({
                dropdownParent: $('#modalEdit'),
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

            // Reiniciar Select2 cuando se cierra el modal de agregar
            $('#modalAdd').on('hidden.bs.modal', function () {
                $('.select2-orden').val(null).trigger('change');
            });

            // Reiniciar Select2 cuando se cierra el modal de editar
            $('#modalEdit').on('hidden.bs.modal', function () {
                $('.select2-orden-edit').val(null).trigger('change');
            });
        });

        // Guardar
        const formAdd = document.getElementById('formAdd');
        if (formAdd) {
            formAdd.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('{{ route("pedidos.store") }}', {
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
                            text: 'Redirigiendo al detalle del pedido...',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = `/pedidos/${r.id}`;
                        });
                    } else {
                        Swal.fire('Error', r.mensaje, 'error');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire('Error', 'Error al guardar el pedido', 'error');
                });
            });
        }

        // Actualizar
        const formEdit = document.getElementById('formEdit');
        if (formEdit) {
            formEdit.addEventListener('submit', function(e) {
                e.preventDefault();
                const id = document.getElementById('edit_id').value;
                const formData = new FormData(this);

                fetch(`/pedidos/${id}/update`, {
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
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire('Error', 'Error al actualizar el pedido', 'error');
                });
            });
        }
    });

    // Cargar datos para editar
    function editPedido(id) {
        console.log('Editando pedido:', id);
        
        fetch(`/pedidos/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => {
            if (!resp.ok) {
                throw new Error(`HTTP error! status: ${resp.status}`);
            }
            return resp.json();
        })
        .then(r => {
            console.log('Respuesta recibida:', r);
            
            if (r.success) {
                document.getElementById('edit_id').value = r.pedido.id_pedido_material;
                document.getElementById('edit_fecha_solicitud').value = r.pedido.fecha_solicitud;
                document.getElementById('edit_observacion').value = r.pedido.observacion || '';
                document.getElementById('edit_id_estado_pedido_material').value = r.pedido.id_estado_pedido_material;
                
                // Establecer valor de Select2
                $('.select2-orden-edit').val(r.pedido.id_orden).trigger('change');

                const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
                modal.show();
            } else {
                Swal.fire('Error', 'No se pudo cargar el pedido', 'error');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            Swal.fire('Error', 'Error al cargar el pedido: ' + err.message, 'error');
        });
    }
</script>
@endsection
