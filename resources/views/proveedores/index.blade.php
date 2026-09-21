@extends('layouts.app')

@section('title', 'Proveedores - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Gestión de Proveedores</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Proveedores</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nuevo Proveedor
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('proveedores.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por razón social, nombre de contacto o RFC..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Proveedores -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Identificación</th>
                    <th>Razón Social</th>
                    <th>Nombre Comercial</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Tipo Contribuyente</th>
                    <th>Agente Retención</th>
                    <th>Estado</th>
                    <th class="text-center pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proveedores as $prov)
                    <tr class="@if($prov->estado == 0) table-light text-muted @endif">
                        <td class="ps-4 fw-bold">{{ $prov->id_proveedor }}</td>
                        <td>
                            <span class="badge bg-secondary font-monospace">{{ $prov->tipo_identificacion_nombre ?: 'N/A' }}</span>
                            <span class="d-block fw-semibold text-muted mt-1">{{ $prov->numero_identificacion }}</span>
                        </td>
                        <td class="fw-semibold text-dark">{{ $prov->razon_social }}</td>
                        <td>{{ $prov->nombre_comercial ?: 'N/A' }}</td>
                        <td>{{ $prov->telefono ?: 'N/A' }}</td>
                        <td>{{ $prov->email ?: 'N/A' }}</td>
                        <td><span class="badge bg-light text-dark">{{ $prov->tipo_contribuyente_nombre ?: 'N/A' }}</span></td>
                        <td>{{ $prov->agente_retencion ?: 'N/A' }}</td>
                        <td>
                            @if($prov->estado == 1)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="editarProveedor({{ $prov->id_proveedor }})" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @if($prov->estado == 1)
                                    <button type="button" class="btn btn-outline-danger" onclick="cambiarEstado({{ $prov->id_proveedor }}, 'delete')" title="Desactivar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-outline-success" onclick="cambiarEstado({{ $prov->id_proveedor }}, 'restore')" title="Restaurar">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-triangle-exclamation fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron proveedores registrados.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($proveedores->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $proveedores->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Nuevo Proveedor</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <!-- Columna Izquierda -->
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Tipo Identificacion *</label>
                                <select class="form-select" name="tipo_identificacion" required>
                                    <option value="" selected disabled>Seleccione Tipo Identificacion</option>
                                    @foreach($tiposIdentificacion as $ti)
                                        <option value="{{ $ti->id_tipo_identificacion }}">{{ $ti->tipo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Nro.Identificacion *</label>
                                <input type="text" class="form-control" name="numero_identificacion" required placeholder="Ej. 9999999999">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Razón Social *</label>
                                <input type="text" class="form-control" name="razon_social" required placeholder="Ej. ABB">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Dirección</label>
                                <input type="text" class="form-control" name="direccion" placeholder="Ej. S/N">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" placeholder="Ej. 9999999999">
                            </div>
                        </div>
                        
                        <!-- Columna Derecha -->
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Correo</label>
                                <input type="text" class="form-control" name="email" placeholder="Ej. S/N">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Nombre Comercial</label>
                                <input type="text" class="form-control" name="nombre_comercial" placeholder="Ej. ABB">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Tipo Contribuyente *</label>
                                <select class="form-select" name="tipo_contribuyente" required>
                                    <option value="" selected disabled>Seleccione Tipo Contribuyente</option>
                                    @foreach($tiposContribuyente as $tc)
                                        <option value="{{ $tc->id_tipo_contribuyente }}">{{ $tc->tipo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Agente Retención</label>
                                <input type="text" class="form-control" name="agente_retencion" placeholder="Ej. S/N">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalEditLabel">Editar Proveedor</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" id="idproveedor" name="idproveedor">
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <!-- Columna Izquierda -->
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Tipo Identificacion *</label>
                                <select class="form-select" id="tipo_identificacionU" name="tipo_identificacionU" required>
                                    <option value="" disabled>Seleccione Tipo Identificacion</option>
                                    @foreach($tiposIdentificacion as $ti)
                                        <option value="{{ $ti->id_tipo_identificacion }}">{{ $ti->tipo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Nro.Identificacion *</label>
                                <input type="text" class="form-control" id="numero_identificacionU" name="numero_identificacionU" required placeholder="Ej. 9999999999">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Razón Social *</label>
                                <input type="text" class="form-control" id="razon_socialU" name="razon_socialU" required placeholder="Ej. ABB">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Dirección</label>
                                <input type="text" class="form-control" id="direccionU" name="direccionU" placeholder="Ej. S/N">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Teléfono</label>
                                <input type="text" class="form-control" id="telefonoU" name="telefonoU" placeholder="Ej. 9999999999">
                            </div>
                        </div>
                        
                        <!-- Columna Derecha -->
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Correo</label>
                                <input type="text" class="form-control" id="emailU" name="emailU" placeholder="Ej. S/N">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Nombre Comercial</label>
                                <input type="text" class="form-control" id="nombre_comercialU" name="nombre_comercialU" placeholder="Ej. ABB">
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Tipo Contribuyente *</label>
                                <select class="form-select" id="tipo_contribuyenteU" name="tipo_contribuyenteU" required>
                                    <option value="" disabled>Seleccione Tipo Contribuyente</option>
                                    @foreach($tiposContribuyente as $tc)
                                        <option value="{{ $tc->id_tipo_contribuyente }}">{{ $tc->tipo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Agente Retención</label>
                                <input type="text" class="form-control" id="agente_retencionU" name="agente_retencionU" placeholder="Ej. S/N">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Actualizar Proveedor</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Guardar nuevo proveedor
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('{{ route("proveedores.store") }}', {
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
    function editarProveedor(id) {
        fetch(`/proveedores/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                const prov = r.proveedor;
                document.getElementById('idproveedor').value = prov.id_proveedor;
                document.getElementById('tipo_identificacionU').value = prov.tipo_identificacion || '';
                document.getElementById('numero_identificacionU').value = prov.numero_identificacion || '';
                document.getElementById('razon_socialU').value = prov.razon_social || '';
                document.getElementById('direccionU').value = prov.direccion || '';
                document.getElementById('telefonoU').value = prov.telefono || '';
                document.getElementById('emailU').value = prov.email || '';
                document.getElementById('nombre_comercialU').value = prov.nombre_comercial || '';
                document.getElementById('tipo_contribuyenteU').value = prov.tipo_contribuyente || '';
                document.getElementById('agente_retencionU').value = prov.agente_retencion || '';

                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
                modal.show();
            } else {
                Swal.fire('Error', 'No se pudieron obtener los detalles del proveedor.', 'error');
            }
        });
    }

    // Guardar edición de proveedor
    document.getElementById('formEdit').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('idproveedor').value;
        const formData = new FormData(this);
        
        fetch(`/proveedores/${id}/update`, {
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

    // Desactivar / Restaurar proveedor
    function cambiarEstado(id, accion) {
        const url = accion === 'delete' ? `/proveedores/${id}/delete` : `/proveedores/${id}/restore`;
        const titulo = accion === 'delete' ? '¿Desactivar proveedor?' : '¿Restaurar proveedor?';
        const texto = accion === 'delete' ? 'El proveedor se marcará como Inactivo.' : 'El proveedor se marcará como Activo.';
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
