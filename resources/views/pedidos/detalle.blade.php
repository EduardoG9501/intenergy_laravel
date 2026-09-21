@extends('layouts.app')

@section('title', 'Detalle de Pedido de Materiales - Intenergy')

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
        <h2 class="fw-bold mb-0">Detalle de Pedido: <span class="text-primary font-monospace">PED-{{ str_pad($pedido->id_pedido_material, 5, '0', STR_PAD_LEFT) }}</span></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pedidos.index') }}">Pedidos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalle</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('pedidos.index') }}" class="btn btn-outline-dark px-4 py-2">
        <i class="fa-solid fa-arrow-left me-2"></i> Volver a Lista
    </a>
</div>

<div class="row g-4">
    <!-- Ficha Informativa -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 h-100">
            <h4 class="fw-bold mb-4">Información del Pedido</h4>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Orden de Trabajo:</span>
                    <span class="fw-bold text-dark font-monospace">{{ $pedido->orden?->identificador }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Nombre de Obra:</span>
                    <span class="fw-semibold text-truncate" style="max-width: 180px;">{{ $pedido->orden?->obra?->nombre }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Fecha Solicitud:</span>
                    <span>{{ $pedido->fecha_solicitud }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Estado Actual:</span>
                    <span class="badge @if($pedido->id_estado_pedido_material == 1) bg-warning text-dark @elseif($pedido->id_estado_pedido_material == 2) bg-success @else bg-danger @endif px-3 py-2">
                        {{ $pedido->estadoPedido?->estado }}
                    </span>
                </li>
            </ul>

            <div class="bg-light p-3 rounded mb-4">
                <span class="fw-semibold text-muted d-block mb-1">Observación:</span>
                <p class="mb-0 text-dark small">{{ $pedido->observacion ?: 'Sin observaciones.' }}</p>
            </div>

            <!-- Cambio de Estado -->
            <div class="border-top pt-4">
                <label class="form-label fw-semibold">Cambiar Estado del Pedido</label>
                <form id="formUpdateStatus" class="row g-2">
                    @csrf
                    <div class="col-8">
                        <select class="form-select" name="id_estado_pedido_material" required>
                            @foreach($estados as $est)
                                <option value="{{ $est->id_estado_pedido_material }}" @if($pedido->id_estado_pedido_material == $est->id_estado_pedido_material) selected @endif>{{ $est->estado }}</option>
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

    <!-- Cargar Artículos -->
    <div class="col-lg-8">
        @if($pedido->id_estado_pedido_material == 1)
            <!-- Solo se añaden artículos si está pendiente/borrador -->
            <div class="card card-custom p-4 mb-4">
                <h4 class="fw-bold mb-3">Agregar Artículo al Pedido</h4>
                <form id="formAddDetail" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Artículo / Material *</label>
                        <select class="form-select select2-articulos" name="id_producto" id="id_producto" required>
                            <option value="" disabled>Selecciona Artículo</option>
                            @foreach($articulos as $art)
                                <option value="{{ $art->id_producto }}" data-codigo="{{ $art->codigobarra }}" data-referencia="{{ $art->referencia }}">{{ $art->nombre }} - {{ $art->referencia ?: 'Sin Ref' }}</option>
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

        <!-- Tabla de Detalles -->
        <div class="card card-custom p-0 overflow-hidden">
            <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Listado de Materiales Solicitados</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-success fw-semibold" id="exportExcel">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-danger fw-semibold" id="exportPDF">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaPedidoDetalle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 15%;">Código Barra</th>
                            <th style="width: 45%;">Artículo</th>
                            <th style="width: 15%;">Cantidad</th>
                            @if($pedido->id_estado_pedido_material == 1)
                                <th class="text-center pe-4" style="width: 25%;">Acciones</th>
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
                                @if($pedido->id_estado_pedido_material == 1)
                                    <td class="text-center pe-4">
                                        <button class="btn btn-sm btn-outline-warning me-1" onclick="modificarCantidad({{ $det->id_pedido_material_detalle }}, {{ $det->cantidad }})" title="Editar cantidad">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarDetalle({{ $det->id_pedido_material_detalle }})">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-box-open fa-2x mb-3 text-warning"></i>
                                    <p class="mb-0">No se han añadido materiales a este pedido.</p>
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
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar Select2 para artículos
        $(document).ready(function() {
            $('.select2-articulos').select2({
                width: '100%',
                placeholder: 'Buscar artículo...',
                allowClear: true,
                language: {
                    noResults: function() {
                        return "No se encontraron artículos";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        });

        @if($pedido->id_estado_pedido_material == 1)
        // Agregar artículo
        const formAddDetail = document.getElementById('formAddDetail');
        if (formAddDetail) {
            formAddDetail.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('{{ route("pedidos.storeDetail", $pedido->id_pedido_material) }}', {
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
                    Swal.fire('Error', 'Error al agregar artículo', 'error');
                });
            });
        }
        @endif

        // Cambiar estado
        const formUpdateStatus = document.getElementById('formUpdateStatus');
        if (formUpdateStatus) {
            formUpdateStatus.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('{{ route("pedidos.updateStatus", $pedido->id_pedido_material) }}', {
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
        }
    });

    // Eliminar artículo
    function eliminarDetalle(idDetail) {
        Swal.fire({
            title: '¿Remover material?',
            text: 'Esta acción eliminará el material del pedido.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, remover'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/pedidos/{{ $pedido->id_pedido_material }}/detalle/${idDetail}/delete`, {
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
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire('Error', 'Error al eliminar artículo', 'error');
                });
            }
        });
    }

    // Modificar cantidad
    function modificarCantidad(idDetail, cantidadActual) {
        Swal.fire({
            title: 'Modificar Cantidad',
            text: 'Ingrese la nueva cantidad para el material:',
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
                fetch(`/pedidos/{{ $pedido->id_pedido_material }}/detalle/${idDetail}/update-cantidad`, {
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
</script>

<!-- ExcelJS y FileSaver -->
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
<!-- jsPDF y AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.7.0/jspdf.plugin.autotable.min.js"></script>

<script>
    // Export Excel
    document.getElementById('exportExcel')?.addEventListener('click', function() {
        var table = document.getElementById('tablaPedidoDetalle');
        var headers = Array.from(table.querySelectorAll('thead tr th')).slice(0, -1).map(cell => cell.innerText);

        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            var cols = Array.from(row.cells).slice(0, -1).map(cell => cell.innerText.trim());
            if (cols.length > 1) bodyRows.push(cols);
        });

        if (bodyRows.length === 0) {
            Swal.fire('Atención', 'No hay materiales para exportar.', 'warning');
            return;
        }

        var workbook = new ExcelJS.Workbook();
        var worksheet = workbook.addWorksheet('Pedido Materiales');

        worksheet.columns = [
            { header: 'Código Barra', key: 'codigo', width: 20 },
            { header: 'Artículo', key: 'articulo', width: 50 },
            { header: 'Cantidad', key: 'cantidad', width: 15 }
        ];

        let headerRow = worksheet.getRow(1);
        headerRow.eachCell(cell => {
            cell.font = { bold: true, color: { argb: "FFFFFFFF" } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
        });

        bodyRows.forEach(row => {
            worksheet.addRow({
                codigo: row[0],
                articulo: row[1],
                cantidad: row[2]
            });
        });

        workbook.xlsx.writeBuffer().then(function(buffer) {
            saveAs(new Blob([buffer], { type: "application/octet-stream" }), "Pedido_Materiales_PED-{{ str_pad($pedido->id_pedido_material, 5, '0', STR_PAD_LEFT) }}.xlsx");
        });
    });

    // Export PDF
    document.getElementById('exportPDF')?.addEventListener('click', function() {
        var table = document.getElementById('tablaPedidoDetalle');
        var headers = Array.from(table.querySelectorAll('thead tr th')).slice(0, -1).map(cell => cell.innerText);

        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            var cols = Array.from(row.cells).slice(0, -1).map(cell => cell.innerText.trim());
            if (cols.length > 1) bodyRows.push(cols);
        });

        if (bodyRows.length === 0) {
            Swal.fire('Atención', 'No hay materiales para exportar.', 'warning');
            return;
        }

        const doc = new window.jspdf.jsPDF();
        doc.setFontSize(16);
        doc.setTextColor(11, 26, 48);
        doc.text("Pedido de Materiales: PED-{{ str_pad($pedido->id_pedido_material, 5, '0', STR_PAD_LEFT) }}", 14, 20);

        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text("OT: {{ $pedido->orden?->identificador }} | Obra: {{ $pedido->orden?->obra?->nombre }} | Fecha: {{ $pedido->fecha_solicitud }}", 14, 30);

        doc.autoTable({
            head: [headers],
            body: bodyRows,
            startY: 38,
            headStyles: { fillColor: [11, 26, 48] },
            alternateRowStyles: { fillColor: [245, 247, 251] }
        });

        doc.save("Pedido_Materiales_PED-{{ str_pad($pedido->id_pedido_material, 5, '0', STR_PAD_LEFT) }}.pdf");
    });
</script>
@endsection
