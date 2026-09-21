@extends('layouts.app')

@section('title', 'Movimientos de Inventario - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Movimientos de Inventario</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Movimientos</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="fa-solid fa-plus me-2"></i> Nuevo Movimiento
    </button>
</div>

<!-- Filtro de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('movimientos.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Buscar por número de documento u observaciones..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-dark py-2">Buscar</button>
        </div>
    </form>
</div>

<!-- Listado de Movimientos -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">No. Doc</th>
                    <th>Lugar</th>
                    <th>Tipo</th>
                    <th>Subtipo</th>
                    <th>Fecha Doc</th>
                    <th>Total</th>
                    <th>F. Pago</th>
                    <th>Estado</th>
                    <th class="text-center pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientos as $mov)
                    <tr class="@if($mov->estado == 0) table-light text-muted @endif">
                        <td class="ps-4 fw-bold font-monospace text-primary">{{ $mov->no_documento }}</td>
                        <td>{{ $mov->bodega?->nombreBodega }}</td>
                        <td>
                            @if($mov->id_tipo_movimiento == 1)
                                <span class="badge bg-success-subtle text-success">ENTRADA</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">SALIDA</span>
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $mov->subTipoMovimiento?->sub_tipo }}</td>
                        <td>{{ $mov->fechaDocumento }}</td>
                        <td class="fw-bold text-success">${{ number_format($mov->total_mto, 2) }}</td>
                        <td>
                            @if($mov->id_forma_pago == 1)
                                <span class="badge bg-info-subtle text-info">Contado</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Crédito</span>
                            @endif
                        </td>
                        <td>
                            @if($mov->estado == 0)
                                <span class="badge bg-secondary">Baja</span>
                            @elseif($mov->guardarDefinitivo == 1)
                                <span class="badge bg-success">Definitivo</span>
                            @else
                                <span class="badge bg-warning text-dark">Borrador</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            @if($mov->guardarDefinitivo == 0 && $mov->estado == 1)
                                <a href="{{ route('movimientos.show', $mov->id_movimiento) }}" class="btn btn-sm btn-outline-warning" title="Gestionar detalle (Borrador)">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Detalle
                                </a>
                            @else
                                <a href="{{ route('movimientos.show', $mov->id_movimiento) }}" class="btn btn-sm btn-outline-primary" title="Ver resumen">
                                    <i class="fa-solid fa-eye me-1"></i> Ver
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-triangle-exclamation fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron movimientos registrados.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($movimientos->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $movimientos->links() }}
        </div>
    @endif
</div>

<!-- MODAL AGREGAR CABECERA -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalAddLabel">Crear Cabecera de Movimiento</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lugar (Bodega Principal) *</label>
                            <select class="form-select" id="id_bodega_disabled" disabled>
                                @foreach($bodegasPrincipales as $bdg)
                                    <option value="{{ $bdg->id_bodega }}" @if($bdg->id_bodega == session('bodega_seleccionada')) selected @endif>{{ $bdg->nombreBodega }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="id_bodega" value="{{ session('bodega_seleccionada') }}">
                        </div>
                        <div class="col-md-6" id="proveedor_container">
                            <label class="form-label fw-semibold">Proveedor (Opcional)</label>
                            <select class="form-select" id="id_proveedor" name="id_proveedor">
                                <option value="" selected>Sin Proveedor</option>
                                @foreach($proveedores as $prov)
                                    <option value="{{ $prov->id_proveedor }}">{{ $prov->razon_social }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Movimiento *</label>
                            <select class="form-select" id="id_tipo_movimiento" name="id_tipo_movimiento" required onchange="cargarSubtipos(this.value)">
                                <option value="" selected disabled>Selecciona Tipo</option>
                                @foreach($tiposMovimiento as $tm)
                                    <option value="{{ $tm->id_tipo_movimiento }}">{{ $tm->tipo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subtipo de Movimiento *</label>
                            <select class="form-select" id="id_sub_tipo_movimiento" name="id_sub_tipo_movimiento" required disabled>
                                <option value="" selected disabled>Selecciona Subtipo</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="documento_container">
                            <label class="form-label fw-semibold">Número de Documento *</label>
                            <input type="text" class="form-control" name="no_documento" id="no_documento" required placeholder="001-001-000000001" maxlength="21" inputmode="numeric" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha de Documento *</label>
                            <input type="date" class="form-control" name="fechaDocumento" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6" id="forma_pago_container">
                            <label class="form-label fw-semibold">Forma de Pago *</label>
                            <select class="form-select" id="id_forma_pago" name="id_forma_pago" required onchange="toggleDiasPago(this.value)">
                                <option value="1" selected>Contado / Efectivo</option>
                                <option value="2">Crédito</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="dias_pago_container" style="display: none;">
                            <label class="form-label fw-semibold">Días de Crédito *</label>
                            <input type="number" class="form-control" id="cant_dia_pago" name="cant_dia_pago" value="0" min="0">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Observación / Descripción</label>
                            <textarea class="form-control" name="observacion" rows="2" placeholder="Detalles de la compra, venta o traspaso..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar y Añadir Detalle</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-formateo del número de documento con patrón XXX-XXX-XXXXXXXXX
    document.getElementById('no_documento').addEventListener('input', function(e) {
        let valor = this.value.replace(/[^0-9]/g, ''); // Solo dígitos
        let formateado = '';
        for (let i = 0; i < valor.length && i < 15; i++) {
            if (i === 3 || i === 6) formateado += '-';
            formateado += valor[i];
        }
        this.value = formateado;
    });

    function cargarSubtipos(idTipo) {
        const subSelect = document.getElementById('id_sub_tipo_movimiento');
        subSelect.innerHTML = '<option value="" selected disabled>Cargando subtipos...</option>';
        subSelect.disabled = true;

        if (!idTipo) return;

        fetch(`/movimientos/subtipos/${idTipo}`)
            .then(resp => resp.json())
            .then(r => {
                if (r.success) {
                    subSelect.innerHTML = '<option value="" selected disabled>Selecciona Subtipo</option>';
                    r.subtipos.forEach(s => {
                        subSelect.innerHTML += `<option value="${s.id_sub_tipo_movimiento}">${s.sub_tipo}</option>`;
                    });
                    subSelect.disabled = false;
                } else {
                    subSelect.innerHTML = '<option value="" selected disabled>Error al cargar subtipos</option>';
                }
            })
            .catch(err => {
                subSelect.innerHTML = '<option value="" selected disabled>Error de red</option>';
            });
    }

    function toggleDiasPago(val) {
        const container = document.getElementById('dias_pago_container');
        const input = document.getElementById('cant_dia_pago');
        if (val == 2) {
            container.style.display = 'block';
            input.required = true;
            input.value = 30;
        } else {
            container.style.display = 'none';
            input.required = false;
            input.value = 0;
        }
    }

    document.getElementById('id_sub_tipo_movimiento').addEventListener('change', function() {
        const subTypeId = this.value;
        const docContainer = document.getElementById('documento_container');
        const docInput = document.getElementById('no_documento');
        const pagoContainer = document.getElementById('forma_pago_container');
        const pagoSelect = document.getElementById('id_forma_pago');
        const diasContainer = document.getElementById('dias_pago_container');
        const diasInput = document.getElementById('cant_dia_pago');
        const provContainer = document.getElementById('proveedor_container');
        const provSelect = document.getElementById('id_proveedor');

        // Proveedor: mostrar solo si es Compras (ID = 1)
        if (subTypeId == 1) {
            provContainer.style.display = 'block';
        } else {
            provContainer.style.display = 'none';
            provSelect.value = '';
        }

        if (subTypeId == 2 || subTypeId == 3) { // 2 = CARGOS, 3 = DESCARGO
            docContainer.style.display = 'none';
            docInput.required = false;
            docInput.value = '';

            pagoContainer.style.display = 'none';
            pagoSelect.required = false;
            pagoSelect.value = '1';

            diasContainer.style.display = 'none';
            diasInput.required = false;
            diasInput.value = '0';
        } else {
            docContainer.style.display = 'block';
            docInput.required = true;

            pagoContainer.style.display = 'block';
            pagoSelect.required = true;
        }
    });

    // Registrar nuevo movimiento
    document.getElementById('formAdd').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("movimientos.store") }}', {
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

                Swal.fire({
                    title: '¡Cabecera Creada!',
                    text: 'Redirigiendo para cargar los artículos del movimiento...',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = `/movimientos/${r.id_movimiento}`;
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
</script>
@endsection
