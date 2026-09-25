@extends('layouts.app')

@section('title', 'Detalle de Movimiento - Intenergy')

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
    .btn-xs {
        padding: 0.15rem 0.3rem;
        font-size: 0.75rem;
        border-radius: 0.2rem;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Detalle de Movimiento: <span class="text-primary font-monospace">{{ $movimiento->no_documento }}</span></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('movimientos.index') }}">Movimientos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalle</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('movimientos.index') }}" class="btn btn-outline-dark px-4 py-2">
        <i class="fa-solid fa-arrow-left me-2"></i> Volver a Lista
    </a>
</div>

<div class="row g-4">
    <!-- Información General -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 h-100">
            <h4 class="fw-bold mb-4">Información General</h4>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Tipo Movimiento:</span>
                    @if($movimiento->id_tipo_movimiento == 1)
                        <span class="badge bg-success-subtle text-success px-3 py-2">ENTRADA</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger px-3 py-2">SALIDA</span>
                    @endif
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Subtipo:</span>
                    <span class="fw-bold text-dark">{{ $movimiento->subTipoMovimiento?->sub_tipo }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Lugar (Bodega Principal):</span>
                    <span class="fw-semibold">{{ $movimiento->bodega?->nombreBodega }}</span>
                </li>
                @if($movimiento->proveedor)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <span class="text-muted fw-semibold">Proveedor:</span>
                        <span class="fw-semibold text-truncate" style="max-width: 180px;">{{ $movimiento->proveedor->razon_social }}</span>
                    </li>
                @endif
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Fecha Documento:</span>
                    <span>{{ $movimiento->fechaDocumento }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Estado Proceso:</span>
                    @if($movimiento->guardarDefinitivo == 1)
                        <span class="badge bg-success px-3 py-2"><i class="fa-solid fa-lock me-1"></i> Definitivo</span>
                    @else
                        <span class="badge bg-warning text-dark px-3 py-2"><i class="fa-solid fa-pen-clip me-1"></i> Borrador</span>
                    @endif
                </li>
            </ul>

            <div class="bg-light p-3 rounded mb-4">
                <span class="fw-semibold text-muted d-block mb-1">Observación:</span>
                <p class="mb-0 text-dark small">{{ $movimiento->observacion ?: 'Sin observaciones.' }}</p>
            </div>

            @if($movimiento->id_sub_tipo_movimiento != 2 && $movimiento->id_sub_tipo_movimiento != 3)
                <!-- Resumen de Totales -->
                <div class="border-top pt-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal:</span>
                        <span class="fw-semibold text-dark">${{ number_format($movimiento->subtotal_mto, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 align-items-center">
                        <span class="text-muted">Descuento ({{ number_format($movimiento->desc_porc, 0) }}%):</span>
                        <div class="d-flex align-items-center">
                            <span class="fw-semibold text-danger">-${{ number_format($movimiento->desc_mto, 2) }}</span>
                            @if($movimiento->guardarDefinitivo == 0 && $movimiento->estado == 1)
                                <button type="button" class="btn btn-xs btn-outline-warning ms-2" data-bs-toggle="modal" data-bs-target="#modalIvaDesc" title="Modificar IVA/Descuento">
                                    <i class="fa-solid fa-calculator"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">IVA ({{ number_format($movimiento->iva_porc, 0) }}%):</span>
                        <span class="fw-semibold text-dark">${{ number_format($movimiento->iva_mto, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-3">
                        <span class="h5 fw-bold mb-0">Total:</span>
                        <span class="h5 fw-bold text-success mb-0">${{ number_format($movimiento->total_mto, 2) }}</span>
                    </div>
                </div>
            @endif

            @if($movimiento->guardarDefinitivo == 0 && $movimiento->estado == 1)
                <button type="button" class="btn btn-success w-100 py-3 mt-4 fw-bold shadow" onclick="confirmarDefinitivo()">
                    <i class="fa-solid fa-square-check me-2"></i> Confirmar Definitivo
                </button>
            @endif
        </div>
    </div>

    <!-- Gestión de Artículos -->
    <div class="col-lg-8">
        @if($movimiento->guardarDefinitivo == 0 && $movimiento->estado == 1)
            <!-- Formulario de Agregar Artículo -->
            <div class="card card-custom p-4 mb-4">
                <h4 class="fw-bold mb-3">Agregar Artículo al Detalle</h4>
                <form id="formAddDetail" class="row g-3 align-items-end">
                    @csrf
                    @if($movimiento->id_sub_tipo_movimiento == 2 || $movimiento->id_sub_tipo_movimiento == 3)
                        <!-- Formulario Simplificado para CARGOS y DESCARGOS -->
                        <div class="col-md-7">
                            <label class="form-label fw-bold text-primary">ARTICULO:</label>
                            <select class="form-select" name="id_articulo" id="id_articulo" required>
                                <option value="" selected disabled>Selecciona articulo</option>
                                @foreach($articulos as $art)
                                    <option value="{{ $art->id_producto }}" data-precio="{{ $art->precio }}">{{ $art->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-primary">CANTIDAD:</label>
                            <input type="number" step="0.01" class="form-control" name="cantidad" required placeholder="0.00">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary-custom py-2">
                                <i class="fa-solid fa-plus me-1"></i> Añadir
                            </button>
                        </div>
                    @else
                        <!-- Formulario Completo para Compras y otros movimientos -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Artículo *</label>
                            <select class="form-select" name="id_articulo" id="id_articulo" required onchange="setPrecioOriginal(this)">
                                <option value="" selected disabled>Selecciona Artículo</option>
                                @foreach($articulos as $art)
                                    <option value="{{ $art->id_producto }}" data-precio="{{ $art->precio }}">{{ $art->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Bodega de Destino *</label>
                            <select class="form-select" name="id_bodega_lugar" required>
                                <option value="{{ $movimiento->id_bodega }}" selected>{{ $movimiento->bodega?->nombreBodega }} (Principal)</option>
                                @foreach($bodegasSecundarias as $sec)
                                    <option value="{{ $sec->id_bodega }}">{{ $sec->nombreBodega }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Lote</label>
                            <input type="text" class="form-control" name="lote" placeholder="Ej. L-2026">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Precio Unitario *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.0001" class="form-control" name="precio" id="precio" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Cantidad *</label>
                            <input type="number" step="0.01" class="form-control" name="cantidad" required placeholder="0.00">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">% IVA</label>
                            <input type="number" step="0.01" class="form-control" name="iva_porc" id="iva_porc" value="{{ $movimiento->iva_porc }}" min="0" max="100" placeholder="0.00">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">% Descuento</label>
                            <input type="number" step="0.01" class="form-control" name="desc_porc" id="desc_porc" value="{{ $movimiento->desc_porc }}" min="0" max="100" placeholder="0.00">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary-custom w-100 py-2">
                                <i class="fa-solid fa-plus me-1"></i> Añadir Artículo
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        @endif

        <!-- Tabla de Artículos del Movimiento -->
        <div class="card card-custom p-0 overflow-hidden">
            <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">LISTA DE ARTÍCULOS</h5>
                <div class="d-flex gap-2">
                    @if($movimiento->guardarDefinitivo == 0 && $movimiento->estado == 1 && $movimiento->id_sub_tipo_movimiento != 2 && $movimiento->id_sub_tipo_movimiento != 3)
                        <button type="button" class="btn btn-sm btn-outline-light fw-semibold" onclick="actualizarTodosIvaDesc()" title="Actualizar IVA y Descuento a todos los artículos">
                            <i class="fa-solid fa-calculator me-1"></i> Actualizar Todos
                        </button>
                    @endif
                    <button type="button" class="btn btn-sm btn-success fw-semibold" id="exportExcel">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-danger fw-semibold" id="exportPDF">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaDetalleArticulos">
                    <thead class="table-light">
                        @if($movimiento->id_sub_tipo_movimiento == 2 || $movimiento->id_sub_tipo_movimiento == 3)
                            <tr>
                                <th class="ps-4" style="width: 10%;">ID</th>
                                <th style="width: 45%;">ARTICULO</th>
                                <th style="width: 20%;">BODEGA</th>
                                <th style="width: 15%;">CANTIDAD</th>
                                <th class="text-center pe-4" style="width: 10%;">ACCIONES</th>
                            </tr>
                        @else
                            <tr>
                                <th class="ps-4">Artículo</th>
                                <th>Bodega Destino</th>
                                <th>Lote</th>
                                <th>Precio U.</th>
                                <th>Cantidad</th>
                                <th>IVA%</th>
                                <th>Desc%</th>
                                <th>Subtotal</th>
                                <th>Total</th>
                                @if($movimiento->guardarDefinitivo == 0 && $movimiento->estado == 1)
                                    <th class="text-center pe-4">Acciones</th>
                                @endif
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($detalles as $det)
                            @if($movimiento->id_sub_tipo_movimiento == 2 || $movimiento->id_sub_tipo_movimiento == 3)
                                <tr>
                                    <td class="ps-4 fw-bold font-monospace">{{ $det->id_movimientos_detalle_tmp ?? $det->id_movimiento_detalle ?? $loop->iteration }}</td>
                                    <td class="fw-bold text-dark">{{ $det->articulo?->nombre }}</td>
                                    <td>{{ $movimiento->bodega?->nombreBodega ?: $det->bodegaLugar?->nombreBodega }}</td>
                                    <td class="fw-bold text-primary">{{ number_format($det->cantidad, 2) }}</td>
                                    <td class="text-center pe-4">
                                        @if($movimiento->guardarDefinitivo == 0 && $movimiento->estado == 1)
                                            <button class="btn btn-sm btn-outline-warning me-1" onclick="modificarCantidad({{ $det->id_movimientos_detalle_tmp }}, {{ $det->cantidad }})" title="Editar cantidad">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarArticulo({{ $det->id_movimientos_detalle_tmp }})" title="Eliminar artículo">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        @else
                                            <span class="text-muted small">Cerrado</span>
                                        @endif
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $det->articulo?->nombre }}</div>
                                        <small class="text-muted font-monospace">{{ $det->articulo?->codigobarra ?: 'Sin código' }}</small>
                                    </td>
                                    <td>{{ $det->bodegaLugar?->nombreBodega }}</td>
                                    <td class="font-monospace">{{ $det->lote ?: 'N/A' }}</td>
                                    <td>${{ number_format($det->precio, 2) }}</td>
                                    <td class="fw-semibold">{{ number_format($det->cantidad, 2) }}</td>
                                    <td><span class="badge bg-info-subtle text-info">{{ number_format($det->iva_porc, 2) }}%</span></td>
                                    <td><span class="badge bg-warning-subtle text-warning">{{ number_format($det->desc_porc, 2) }}%</span></td>
                                    <td>${{ number_format($det->subtotal, 2) }}</td>
                                    <td class="fw-bold text-success">${{ number_format($det->total, 2) }}</td>
                                    @if($movimiento->guardarDefinitivo == 0 && $movimiento->estado == 1)
                                        <td class="text-center pe-4">
                                            <button class="btn btn-sm btn-outline-info me-1" onclick="modificarIvaDesc({{ $det->id_movimientos_detalle_tmp }}, {{ $det->iva_porc }}, {{ $det->desc_porc }})" title="Editar IVA / Descuento">
                                                <i class="fa-solid fa-calculator"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning me-1" onclick="modificarCantidad({{ $det->id_movimientos_detalle_tmp }}, {{ $det->cantidad }})" title="Editar cantidad">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarArticulo({{ $det->id_movimientos_detalle_tmp }})" title="Eliminar artículo">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ ($movimiento->id_sub_tipo_movimiento == 2 || $movimiento->id_sub_tipo_movimiento == 3) ? 5 : 10 }}" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-box-open fa-2x mb-3 text-warning"></i>
                                    <p class="mb-0">No se han añadido artículos a este movimiento.</p>
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
    function setPrecioOriginal(select) {
        const option = select.options[select.selectedIndex];
        const precio = option.getAttribute('data-precio') || 0;
        document.getElementById('precio').value = parseFloat(precio).toFixed(4);
    }

    @if($movimiento->guardarDefinitivo == 0 && $movimiento->estado == 1)
    // Agregar detalle
    document.getElementById('formAddDetail').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("movimientos.storeDetail", $movimiento->id_movimiento) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                location.reload();
            } else {
                Swal.fire('Advertencia', r.mensaje, 'warning');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Hubo un error al añadir el artículo.', 'error');
        });
    });

    // Enviar formulario con Enter en cualquier campo
    document.getElementById('formAddDetail').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
            this.dispatchEvent(new Event('submit'));
        }
    });

    // Eliminar artículo
    function eliminarArticulo(idDetail) {
        Swal.fire({
            title: '¿Remover artículo?',
            text: 'El artículo se eliminará del detalle de este movimiento.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, remover',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/movimientos/{{ $movimiento->id_movimiento }}/detalle/${idDetail}/delete`, {
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

    // Confirmar definitivo
    function confirmarDefinitivo() {
        Swal.fire({
            title: '¿Confirmar Movimiento Definitivo?',
            text: 'Esta acción aplicará el stock a las bodegas permanentemente y no se podrá revertir.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, confirmar definitivo',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ route("movimientos.guardarDefinitivo", $movimiento->id_movimiento) }}', {
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
                            title: '¡Procesado!',
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
            }
        });
    }
    @endif

    // Modificar cantidad
    function modificarCantidad(idDetail, cantidadActual) {
        Swal.fire({
            title: 'Modificar Cantidad',
            text: 'Ingrese la nueva cantidad para el artículo:',
            input: 'number',
            inputValue: cantidadActual,
            inputAttributes: {
                step: '0.01',
                min: '0.01'
            },
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                fetch(`/movimientos/{{ $movimiento->id_movimiento }}/detalle/${idDetail}/update-cantidad`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ cantidad: result.value })
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
                    Swal.fire('Error', 'Hubo un error al actualizar la cantidad.', 'error');
                });
            }
        });
    }

    // Modificar IVA y Descuento de un artículo
    function modificarIvaDesc(idDetail, ivaActual, descActual) {
        Swal.fire({
            title: 'Modificar IVA y Descuento',
            html:
                '<div class="text-start">' +
                    '<label class="form-label fw-semibold">% IVA</label>' +
                    '<input type="number" id="swalIva" class="swal2-input" step="0.01" min="0" max="100" value="' + ivaActual + '" style="margin-bottom: 10px;">' +
                    '<label class="form-label fw-semibold">% Descuento</label>' +
                    '<input type="number" id="swalDesc" class="swal2-input" step="0.01" min="0" max="100" value="' + descActual + '">' +
                '</div>',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0d6efd',
            preConfirm: () => {
                const iva = document.getElementById('swalIva').value;
                const desc = document.getElementById('swalDesc').value;
                if (iva === '' || desc === '') {
                    Swal.showValidationMessage('Debe completar ambos campos');
                    return false;
                }
                return { iva_porc: parseFloat(iva), desc_porc: parseFloat(desc) }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                fetch(`/movimientos/{{ $movimiento->id_movimiento }}/detalle/${idDetail}/update-iva-descuento`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(result.value)
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
                    Swal.fire('Error', 'Hubo un error al actualizar IVA/Descuento.', 'error');
                });
            }
        });
    }

    // Actualizar IVA y Descuento a TODOS los artículos
    function actualizarTodosIvaDesc() {
        Swal.fire({
            title: 'Actualizar IVA y Descuento a TODOS',
            text: 'Se aplicará el mismo porcentaje de IVA y Descuento a todos los artículos del movimiento.',
            html:
                '<div class="text-start">' +
                    '<label class="form-label fw-semibold">% IVA para todos</label>' +
                    '<input type="number" id="swalIvaAll" class="swal2-input" step="0.01" min="0" max="100" value="15.00" style="margin-bottom: 10px;">' +
                    '<label class="form-label fw-semibold">% Descuento para todos</label>' +
                    '<input type="number" id="swalDescAll" class="swal2-input" step="0.01" min="0" max="100" value="0.00">' +
                '</div>',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Aplicar a Todos',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#198754',
            preConfirm: () => {
                const iva = document.getElementById('swalIvaAll').value;
                const desc = document.getElementById('swalDescAll').value;
                if (iva === '' || desc === '') {
                    Swal.showValidationMessage('Debe completar ambos campos');
                    return false;
                }
                return { iva_porc: parseFloat(iva), desc_porc: parseFloat(desc) }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    title: '¿Está seguro?',
                    text: 'Se sobrescribirán los porcentajes de TODOS los artículos.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'Sí, aplicar a todos',
                    cancelButtonText: 'Cancelar'
                }).then((confirm) => {
                    if (confirm.isConfirmed) {
                        fetch('{{ route("movimientos.updateAllIvaDescuento", $movimiento->id_movimiento) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(result.value)
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
                            Swal.fire('Error', 'Hubo un error al actualizar todos los artículos.', 'error');
                        });
                    }
                });
            }
        });
    }

    // Export Excel
    document.getElementById('exportExcel')?.addEventListener('click', function() {
        var table = document.getElementById('tablaDetalleArticulos');
        var ths = Array.from(table.querySelectorAll('thead tr th')).map(cell => cell.innerText.trim());
        var dropAcciones = ths.length > 0 && ths[ths.length - 1].toUpperCase().includes('ACCION');
        var headers = dropAcciones ? ths.slice(0, -1) : ths;

        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            if (row.querySelector('td[colspan]')) return;
            var cells = Array.from(row.cells);
            if (dropAcciones) cells.pop();
            var cols = cells.map(cell => cell.innerText.trim());
            if (cols.length > 1) bodyRows.push(cols);
        });

        if (bodyRows.length === 0) {
            Swal.fire('Atención', 'No hay artículos cargados para exportar.', 'warning');
            return;
        }

        var parseNum = function(v) { var n = parseFloat(String(v).replace(/[^0-9.\-]/g, '')); return isNaN(n) ? 0 : n; };
        var subIdx = headers.findIndex(h => h.toLowerCase().includes('subtotal'));
        var totIdx = headers.findIndex(h => h.toLowerCase().trim() === 'total');
        var tieneTotales = subIdx >= 0 || totIdx >= 0;
        var sumSub = 0, sumTot = 0;
        if (tieneTotales) {
            bodyRows.forEach(function(r) {
                if (subIdx >= 0) sumSub += parseNum(r[subIdx]);
                if (totIdx >= 0) sumTot += parseNum(r[totIdx]);
            });
            var totalRow = headers.map(function() { return ''; });
            totalRow[0] = 'TOTAL';
            if (subIdx >= 0) totalRow[subIdx] = '$' + sumSub.toFixed(2);
            if (totIdx >= 0) totalRow[totIdx] = '$' + sumTot.toFixed(2);
            bodyRows.push(totalRow);
        }

        var workbook = new ExcelJS.Workbook();
        var worksheet = workbook.addWorksheet('Detalle');

        let headerRow = worksheet.addRow(headers);
        headerRow.eachCell(cell => {
            cell.font = { bold: true, color: { argb: "FFFFFFFF" } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
        });

        bodyRows.forEach(row => worksheet.addRow(row));

        if (tieneTotales && worksheet.lastRow) {
            worksheet.lastRow.eachCell(cell => {
                cell.font = { bold: true, color: { argb: "FF0B1A30" } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FFD6EAF8" } };
            });
        }

        worksheet.columns.forEach(column => {
            column.width = 22;
        });

        workbook.xlsx.writeBuffer().then(function(buffer) {
            saveAs(new Blob([buffer], { type: "application/octet-stream" }), "Detalle_Movimiento_{{ $movimiento->no_documento }}.xlsx");
        });
    });

    // Export PDF
    document.getElementById('exportPDF')?.addEventListener('click', function() {
        var table = document.getElementById('tablaDetalleArticulos');
        var ths = Array.from(table.querySelectorAll('thead tr th')).map(cell => cell.innerText.trim());
        var dropAcciones = ths.length > 0 && ths[ths.length - 1].toUpperCase().includes('ACCION');
        var headers = dropAcciones ? ths.slice(0, -1) : ths;

        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            if (row.querySelector('td[colspan]')) return;
            var cells = Array.from(row.cells);
            if (dropAcciones) cells.pop();
            var cols = cells.map(cell => cell.innerText.trim());
            if (cols.length > 1) bodyRows.push(cols);
        });

        if (bodyRows.length === 0) {
            Swal.fire('Atención', 'No hay artículos cargados para exportar.', 'warning');
            return;
        }

        var parseNum = function(v) { var n = parseFloat(String(v).replace(/[^0-9.\-]/g, '')); return isNaN(n) ? 0 : n; };
        var subIdx = headers.findIndex(h => h.toLowerCase().includes('subtotal'));
        var totIdx = headers.findIndex(h => h.toLowerCase().trim() === 'total');
        var footRow = null;
        if (subIdx >= 0 || totIdx >= 0) {
            var sumSub = 0, sumTot = 0;
            bodyRows.forEach(function(r) {
                if (subIdx >= 0) sumSub += parseNum(r[subIdx]);
                if (totIdx >= 0) sumTot += parseNum(r[totIdx]);
            });
            var totalRow = headers.map(function() { return ''; });
            totalRow[0] = 'TOTAL';
            if (subIdx >= 0) totalRow[subIdx] = '$' + sumSub.toFixed(2);
            if (totIdx >= 0) totalRow[totIdx] = '$' + sumTot.toFixed(2);
            footRow = [totalRow];
        }

        const doc = new window.jspdf.jsPDF();
        doc.setFontSize(16);
        doc.setTextColor(11, 26, 48);
        doc.text("Detalle de Movimiento: {{ $movimiento->no_documento }}", 14, 20);

        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text("Subtipo: {{ $movimiento->subTipoMovimiento?->sub_tipo }} | Fecha: {{ $movimiento->fechaDocumento }} | Bodega: {{ $movimiento->bodega?->nombreBodega }}", 14, 32);

        doc.autoTable({
            head: [headers],
            body: bodyRows,
            foot: footRow || undefined,
            startY: 42,
            headStyles: { fillColor: [11, 26, 48] },
            footStyles: { fillColor: [214, 234, 248], fontStyle: 'bold', textColor: [11, 26, 48] },
            alternateRowStyles: { fillColor: [245, 247, 251] }
        });

        doc.save("Detalle_Movimiento_{{ $movimiento->no_documento }}.pdf");
    });

    // Inicializar Select2 en selector de articulo
    $(document).ready(function() {
        if ($('#id_articulo').length) {
            $('#id_articulo').select2({
                placeholder: 'Selecciona Artículo',
                allowClear: true
            });
        }
    });

    @if($movimiento->guardarDefinitivo == 0 && $movimiento->estado == 1)
    // Formulario actualizar IVA / Descuento
    document.getElementById('formIvaDesc').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("movimientos.updateIvaDescuento", $movimiento->id_movimiento) }}', {
            method: 'POST',
            body: formData,
            headers: {
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
        })
        .catch(err => {
            Swal.fire('Error', 'Hubo un error al actualizar los porcentajes.', 'error');
        });
    });
    @endif
</script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- ExcelJS y FileSaver -->
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
<!-- jsPDF y AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.7.0/jspdf.plugin.autotable.min.js"></script>

<!-- Modal IVA / Descuento -->
<div class="modal fade" id="modalIvaDesc" tabindex="-1" aria-labelledby="modalIvaDescLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0">Modificar IVA y Descuento</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formIvaDesc">
                @csrf
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">% IVA *</label>
                        <input type="number" step="0.01" class="form-control" name="iva_porc" id="modal_iva_porc" required value="{{ $movimiento->iva_porc }}" min="0" max="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">% Descuento *</label>
                        <input type="number" step="0.01" class="form-control" name="desc_porc" id="modal_desc_porc" required value="{{ $movimiento->desc_porc }}" min="0" max="100">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
