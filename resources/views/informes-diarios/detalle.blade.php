@extends('layouts.app')

@section('title', 'Detalle Informe Diario - Intenergy')

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
        <h2 class="fw-bold mb-0">Informe Diario: <span class="text-primary font-monospace">INF-{{ str_pad($informe->id_informe_diario_ejecucion, 5, '0', STR_PAD_LEFT) }}</span></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('informes.index') }}">Informes Diarios</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalle</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('informes.index') }}" class="btn btn-outline-dark px-4 py-2">
        <i class="fa-solid fa-arrow-left me-2"></i> Volver a Lista
    </a>
</div>

<div class="row g-4">
    <!-- Ficha del Informe -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 h-100">
            <h4 class="fw-bold mb-4">Ficha del Informe</h4>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Fecha:</span>
                    <span class="fw-bold text-dark">{{ \Carbon\Carbon::parse($informe->fecha)->format('d/m/Y') }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Descripción:</span>
                    <span class="text-truncate" style="max-width: 180px;">{{ $informe->descripcion ?: 'Sin descripción.' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Observaciones:</span>
                    <span class="text-truncate" style="max-width: 180px;">{{ $informe->observacion ?: 'Sin observaciones.' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Lugar:</span>
                    <span>{{ $informe->lugar ?: 'Sin lugar.' }}</span>
                </li>
                @if($informe->ejecucion)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <span class="text-muted fw-semibold">Ejecución:</span>
                        <a href="{{ route('ejecuciones.show', $informe->ejecucion->id_ejecucion_obra) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                            EJEC-{{ str_pad($informe->ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}
                            <i class="fa-solid fa-arrow-up-right-from-square ms-1 small"></i>
                        </a>
                    </li>
                @else
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <span class="text-muted fw-semibold">Ejecución:</span>
                        <span class="badge bg-secondary">Sin ejecución asociada</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>

    <!-- Sub-tabs del Informe -->
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Contenido del Informe</h4>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-success fw-semibold" id="exportInformeCompleto">
                    <i class="fa-solid fa-file-excel me-1"></i> Exportar Todo (Excel)
                </button>
                <button type="button" class="btn btn-sm btn-danger fw-semibold" id="exportInformeCompletoPDF">
                    <i class="fa-solid fa-file-pdf me-1"></i> Exportar Todo (PDF)
                </button>
            </div>
        </div>

        <ul class="nav nav-tabs nav-fill mb-4" id="informeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="tab-empleados" data-bs-toggle="tab" data-bs-target="#pane-empleados" type="button" role="tab" aria-controls="pane-empleados" aria-selected="true">
                    <i class="fa-solid fa-users me-1"></i> Empleados <span class="badge bg-dark ms-1">{{ $informe->empleados->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-articulos" data-bs-toggle="tab" data-bs-target="#pane-articulos" type="button" role="tab" aria-controls="pane-articulos" aria-selected="false">
                    <i class="fa-solid fa-box me-1"></i> Artículos <span class="badge bg-dark ms-1">{{ $informe->articulos->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-imagenes" data-bs-toggle="tab" data-bs-target="#pane-imagenes" type="button" role="tab" aria-controls="pane-imagenes" aria-selected="false">
                    <i class="fa-solid fa-image me-1"></i> Imágenes <span class="badge bg-dark ms-1">{{ $informe->imagenes->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-descripcion" data-bs-toggle="tab" data-bs-target="#pane-descripcion" type="button" role="tab" aria-controls="pane-descripcion" aria-selected="false">
                    <i class="fa-solid fa-align-left me-1"></i> Descripción <span class="badge bg-dark ms-1">{{ $informe->detalles->count() }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="informeTabsContent">
            <!-- SUB-TAB: EMPLEADOS -->
            <div class="tab-pane fade show active" id="pane-empleados" role="tabpanel" aria-labelledby="tab-empleados">
                <div class="card card-custom p-4">
                    <form class="row g-2 mb-3" onsubmit="agregarEmpleado(event)">
                        @csrf
                        <div class="col-md-8">
                            <select class="form-select form-select-sm" name="id_empleado" required>
                                <option value="" selected disabled>Selecciona Empleado</option>
                                @foreach($empleados as $emp)
                                    <option value="{{ $emp->id_empleados }}">{{ $emp->nombres_apellidos }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary-custom"><i class="fa-solid fa-plus me-1"></i> Agregar</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Empleado</th>
                                    <th class="text-center pe-3" style="width:80px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($informe->empleados as $emp)
                                    <tr>
                                        <td class="ps-3 fw-semibold">{{ $emp->empleado?->nombres_apellidos }}</td>
                                        <td class="text-center pe-3">
                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarEmpleado({{ $emp->id_informe_diario_empleado }})">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted py-3">Sin empleados asignados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SUB-TAB: ARTICULOS -->
            <div class="tab-pane fade" id="pane-articulos" role="tabpanel" aria-labelledby="tab-articulos">
                <div class="card card-custom p-4">
                    <form class="row g-2 mb-3" onsubmit="agregarArticulo(event)">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">Artículo *</label>
                            <select class="form-select form-select-sm select2-articulos-inf" name="id_producto" id="selectArticulo" required>
                                <option value="" disabled selected>Selecciona artículo...</option>
                                @foreach($articulos as $art)
                                    <option value="{{ $art->id_producto }}">{{ $art->nombre }} - {{ $art->referencia ?: 'Sin Ref' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Cantidad *</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="cantidad" required placeholder="0.00" min="0.01">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Lote</label>
                            <input type="text" class="form-control form-control-sm" name="lote" placeholder="Opcional">
                        </div>
                        <div class="col-md-2 d-flex align-items-end justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary-custom"><i class="fa-solid fa-plus me-1"></i> Agregar</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" id="tablaArticulosInf">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Artículo</th>
                                    <th>Cantidad</th>
                                    <th>Lote</th>
                                    <th class="text-center pe-3" style="width:120px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($informe->articulos as $art)
                                    <tr>
                                        <td class="ps-3 fw-semibold">{{ $art->producto?->nombre }}</td>
                                        <td>{{ number_format($art->cantidad, 2) }}</td>
                                        <td>{{ $art->lote ?: '-' }}</td>
                                        <td class="text-center pe-3">
                                            <button class="btn btn-sm btn-outline-warning me-1" onclick="modificarCantidadArt({{ $art->id_informe_diario_articulo }}, {{ $art->cantidad }})" title="Editar cantidad">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarArticulo({{ $art->id_informe_diario_articulo }})">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">Sin artículos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($informe->articulos->count())
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-sm btn-success fw-semibold" onclick="exportArticulosExcel()">
                                <i class="fa-solid fa-file-excel me-1"></i> Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-danger fw-semibold" onclick="exportArticulosPDF()">
                                <i class="fa-solid fa-file-pdf me-1"></i> PDF
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- SUB-TAB: IMAGENES -->
            <div class="tab-pane fade" id="pane-imagenes" role="tabpanel" aria-labelledby="tab-imagenes">
                <div class="card card-custom p-4">
                    <form class="row g-2 mb-3" onsubmit="agregarImagen(event)">
                        @csrf
                        <div class="col-md-6">
                            <input type="file" class="form-control form-control-sm" name="imagen" required accept="image/*">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" name="descripcion" placeholder="Descripción (opc.)">
                        </div>
                        <div class="col-md-2 d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary-custom"><i class="fa-solid fa-upload me-1"></i> Subir</button>
                        </div>
                    </form>
                    <div class="row g-3">
                        @forelse($informe->imagenes as $img)
                            <div class="col-md-3">
                                <div class="card h-100 border-0 shadow-sm">
                                    <img src="{{ $img->ruta_imagen }}" class="card-img-top" style="height:150px; object-fit:cover;" alt="{{ $img->descripcion }}">
                                    <div class="card-body p-2">
                                        <p class="card-text small text-muted mb-1">{{ $img->descripcion ?: 'Sin descripción' }}</p>
                                        <button class="btn btn-sm btn-outline-danger w-100" onclick="eliminarImagen({{ $img->id_informe_diario_imagen }})">
                                            <i class="fa-solid fa-trash-can me-1"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted py-4">Sin imágenes adjuntas.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- SUB-TAB: DESCRIPCION -->
            <div class="tab-pane fade" id="pane-descripcion" role="tabpanel" aria-labelledby="tab-descripcion">
                <div class="card card-custom p-4">
                    <form class="row g-2 mb-3" onsubmit="agregarDescripcion(event)">
                        @csrf
                        <div class="col-md-10">
                            <input type="text" class="form-control form-control-sm" name="descripcion" required placeholder="Descripción detallada de la actividad...">
                        </div>
                        <div class="col-md-2 d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary-custom"><i class="fa-solid fa-plus me-1"></i> Agregar</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Descripción</th>
                                    <th class="text-center pe-3" style="width:80px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($informe->detalles as $det)
                                    <tr>
                                        <td class="ps-3">{{ $det->descripcion }}</td>
                                        <td class="text-center pe-3">
                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarDescripcion({{ $det->id_informe_diario_detalle }})">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted py-3">Sin descripciones registradas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- ExcelJS y FileSaver -->
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
<!-- jsPDF y AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.7.0/jspdf.plugin.autotable.min.js"></script>

<script>
    const INF_ID = {{ $informe->id_informe_diario_ejecucion }};
    const CSRF_TOKEN = '{{ csrf_token() }}';

    function postForm(url, formData) {
        return fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json());
    }

    function postAction(url) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json());
    }

    function postJSON(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data)
        }).then(r => r.json());
    }

    // === TAB PERSISTENCE ===
    function saveTabState(subTab) {
        if (subTab) sessionStorage.setItem('inf_subTab', subTab);
    }

    function restoreTabState() {
        const subTab = sessionStorage.getItem('inf_subTab');
        if (subTab) {
            const tabBtn = document.querySelector(`[data-bs-target="#${subTab}"]`);
            if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        }
        sessionStorage.removeItem('inf_subTab');
    }

    document.addEventListener('DOMContentLoaded', restoreTabState);

    // === SELECT2 INIT ===
    $(document).ready(function() {
        $('.select2-articulos-inf').select2({ width: '100%', placeholder: 'Buscar artículo...', allowClear: true, language: { noResults: () => "No se encontraron artículos", searching: () => "Buscando..." } });
    });

    function reload() { location.reload(); }

    function toast(msg, subTab) {
        if (subTab) saveTabState(subTab);
        Swal.fire({ title: msg, icon: 'success', timer: 1200, showConfirmButton: false });
        setTimeout(reload, 1200);
    }

    function confirmar(msg, fn) {
        Swal.fire({ title: msg, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#aaa', confirmButtonText: 'Sí, continuar' })
        .then(r => { if (r.isConfirmed) fn(); });
    }

    // === SUB-TAB: EMPLEADOS ===
    function agregarEmpleado(e) {
        e.preventDefault();
        saveTabState('pane-empleados');
        const fd = new FormData(e.target);
        postForm(`/informes-diarios/${INF_ID}/empleado`, fd)
        .then(r => r.success ? toast('Empleado agregado', 'pane-empleados') : Swal.fire('Error', r.mensaje, 'error'));
    }

    function eliminarEmpleado(idEmp) {
        confirmar('¿Remover empleado?', () => {
            saveTabState('pane-empleados');
            postAction(`/informes-diarios/${INF_ID}/empleado/${idEmp}/delete`)
            .then(r => r.success ? toast('Empleado removido', 'pane-empleados') : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === SUB-TAB: ARTICULOS ===
    function agregarArticulo(e) {
        e.preventDefault();
        saveTabState('pane-articulos');
        const fd = new FormData(e.target);
        postForm(`/informes-diarios/${INF_ID}/articulo`, fd)
        .then(r => r.success ? toast('Artículo agregado', 'pane-articulos') : Swal.fire('Error', r.mensaje, 'error'));
    }

    function modificarCantidadArt(idArt, cantidadActual) {
        Swal.fire({
            title: 'Modificar Cantidad',
            text: 'Ingrese la nueva cantidad:',
            input: 'number', inputValue: cantidadActual,
            inputAttributes: { step: '0.01', min: '0.01' },
            showCancelButton: true, confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar', confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                saveTabState('pane-articulos');
                postJSON(`/informes-diarios/${INF_ID}/articulo/${idArt}/update-cantidad`, { cantidad: result.value })
                .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
            }
        });
    }

    function eliminarArticulo(idArt) {
        confirmar('¿Remover artículo?', () => {
            saveTabState('pane-articulos');
            postAction(`/informes-diarios/${INF_ID}/articulo/${idArt}/delete`)
            .then(r => r.success ? toast('Artículo removido', 'pane-articulos') : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === EXPORT ARTICULOS ===
    function exportArticulosExcel() {
        var table = document.getElementById('tablaArticulosInf');
        var headers = ['Artículo', 'Cantidad', 'Lote'];
        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            if (row.querySelector('td[colspan]')) return;
            var cols = Array.from(row.cells).slice(0, 3).map(c => c.innerText.trim());
            if (cols.length > 0) bodyRows.push(cols);
        });
        if (bodyRows.length === 0) { Swal.fire('Atención', 'No hay datos para exportar.', 'warning'); return; }

        var workbook = new ExcelJS.Workbook();
        var ws = workbook.addWorksheet('Artículos');
        ws.columns = [{ header: 'Artículo', key: 'articulo', width: 40 }, { header: 'Cantidad', key: 'cantidad', width: 15 }, { header: 'Lote', key: 'lote', width: 20 }];
        let hRow = ws.getRow(1);
        hRow.eachCell(cell => { cell.font = { bold: true, color: { argb: "FFFFFFFF" } }; cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } }; cell.alignment = { horizontal: "center", vertical: "middle" }; });
        bodyRows.forEach(row => ws.addRow({ articulo: row[0], cantidad: row[1], lote: row[2] }));
        workbook.xlsx.writeBuffer().then(buffer => { saveAs(new Blob([buffer], { type: "application/octet-stream" }), `Articulos_INF-{{ str_pad($informe->id_informe_diario_ejecucion, 5, '0', STR_PAD_LEFT) }}.xlsx`); });
    }

    function exportArticulosPDF() {
        var table = document.getElementById('tablaArticulosInf');
        var headers = ['Artículo', 'Cantidad', 'Lote'];
        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            if (row.querySelector('td[colspan]')) return;
            var cols = Array.from(row.cells).slice(0, 3).map(c => c.innerText.trim());
            if (cols.length > 0) bodyRows.push(cols);
        });
        if (bodyRows.length === 0) { Swal.fire('Atención', 'No hay datos para exportar.', 'warning'); return; }

        const doc = new window.jspdf.jsPDF();
        doc.setFontSize(14); doc.setTextColor(11, 26, 48);
        doc.text("Artículos - Informe INF-{{ str_pad($informe->id_informe_diario_ejecucion, 5, '0', STR_PAD_LEFT) }}", 14, 20);
        doc.setFontSize(10); doc.setTextColor(100);
        doc.text("Fecha: {{ \Carbon\Carbon::parse($informe->fecha)->format('d/m/Y') }} | Lugar: {{ $informe->lugar ?: 'N/A' }}", 14, 28);
        doc.autoTable({ head: [headers], body: bodyRows, startY: 35, headStyles: { fillColor: [11, 26, 48] }, alternateRowStyles: { fillColor: [245, 247, 251] } });
        doc.save(`Articulos_INF-{{ str_pad($informe->id_informe_diario_ejecucion, 5, '0', STR_PAD_LEFT) }}.pdf`);
    }

    // === SUB-TAB: IMAGENES ===
    function agregarImagen(e) {
        e.preventDefault();
        saveTabState('pane-imagenes');
        const fd = new FormData(e.target);
        fetch(`/informes-diarios/${INF_ID}/imagen`, {
            method: 'POST', body: fd,
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json())
        .then(r => r.success ? toast('Imagen subida', 'pane-imagenes') : Swal.fire('Error', r.mensaje, 'error'));
    }

    function eliminarImagen(idImg) {
        confirmar('¿Eliminar imagen?', () => {
            saveTabState('pane-imagenes');
            postAction(`/informes-diarios/${INF_ID}/imagen/${idImg}/delete`)
            .then(r => r.success ? toast('Imagen eliminada', 'pane-imagenes') : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === SUB-TAB: DESCRIPCION ===
    function agregarDescripcion(e) {
        e.preventDefault();
        saveTabState('pane-descripcion');
        const fd = new FormData(e.target);
        postForm(`/informes-diarios/${INF_ID}/descripcion`, fd)
        .then(r => r.success ? toast('Descripción agregada', 'pane-descripcion') : Swal.fire('Error', r.mensaje, 'error'));
    }

    function eliminarDescripcion(idDesc) {
        confirmar('¿Eliminar descripción?', () => {
            saveTabState('pane-descripcion');
            postAction(`/informes-diarios/${INF_ID}/descripcion/${idDesc}/delete`)
            .then(r => r.success ? toast('Descripción eliminada', 'pane-descripcion') : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === EXPORT COMPLETO INFORME ===
    var informeData = {
        id: 'INF-{{ str_pad($informe->id_informe_diario_ejecucion, 5, '0', STR_PAD_LEFT) }}',
        fecha: '{{ \Carbon\Carbon::parse($informe->fecha)->format("d/m/Y") }}',
        descripcion: '{!! addslashes($informe->descripcion ?: "Sin descripción") !!}',
        observacion: '{!! addslashes($informe->observacion ?: "Sin observaciones") !!}',
        lugar: '{!! addslashes($informe->lugar ?: "N/A") !!}',
        ejecucion: '{{ $informe->ejecucion ? "EJEC-" . str_pad($informe->ejecucion->id_ejecucion_obra, 5, "0", STR_PAD_LEFT) : "Sin ejecución" }}',
        empleados: {!! json_encode($informe->empleados->map(fn($e) => $e->empleado?->nombres_apellidos ?? 'N/A')) !!},
        articulos: {!! json_encode($informe->articulos->map(fn($a) => ['nombre' => $a->producto?->nombre ?? 'N/A', 'cantidad' => $a->cantidad, 'lote' => $a->lote ?: ''])) !!},
        descripciones: {!! json_encode($informe->detalles->map(fn($d) => $d->descripcion)) !!}
    };

    document.getElementById('exportInformeCompleto')?.addEventListener('click', function() {
        var wb = new ExcelJS.Workbook();

        // Hoja: Resumen
        var wsResumen = wb.addWorksheet('Resumen');
        wsResumen.columns = [{ header: 'Campo', key: 'campo', width: 25 }, { header: 'Valor', key: 'valor', width: 50 }];
        wsResumen.getRow(1).eachCell(c => { c.font = { bold: true, color: { argb: "FFFFFFFF" } }; c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } }; });
        wsResumen.addRow({ campo: 'ID Informe', valor: informeData.id });
        wsResumen.addRow({ campo: 'Fecha', valor: informeData.fecha });
        wsResumen.addRow({ campo: 'Descripción', valor: informeData.descripcion });
        wsResumen.addRow({ campo: 'Observación', valor: informeData.observacion });
        wsResumen.addRow({ campo: 'Lugar', valor: informeData.lugar });
        wsResumen.addRow({ campo: 'Ejecución', valor: informeData.ejecucion });

        // Hoja: Empleados
        var wsEmp = wb.addWorksheet('Empleados');
        wsEmp.columns = [{ header: '#', key: 'num', width: 8 }, { header: 'Empleado', key: 'nombre', width: 50 }];
        wsEmp.getRow(1).eachCell(c => { c.font = { bold: true, color: { argb: "FFFFFFFF" } }; c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } }; });
        informeData.empleados.forEach((e, i) => wsEmp.addRow({ num: i + 1, nombre: e }));

        // Hoja: Artículos
        var wsArt = wb.addWorksheet('Artículos');
        wsArt.columns = [{ header: 'Artículo', key: 'articulo', width: 40 }, { header: 'Cantidad', key: 'cantidad', width: 15 }, { header: 'Lote', key: 'lote', width: 20 }];
        wsArt.getRow(1).eachCell(c => { c.font = { bold: true, color: { argb: "FFFFFFFF" } }; c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } }; });
        informeData.articulos.forEach(a => wsArt.addRow({ articulo: a.nombre, cantidad: a.cantidad, lote: a.lote || '-' }));

        // Hoja: Descripciones
        var wsDesc = wb.addWorksheet('Descripciones');
        wsDesc.columns = [{ header: '#', key: 'num', width: 8 }, { header: 'Descripción', key: 'desc', width: 80 }];
        wsDesc.getRow(1).eachCell(c => { c.font = { bold: true, color: { argb: "FFFFFFFF" } }; c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } }; });
        informeData.descripciones.forEach((d, i) => wsDesc.addRow({ num: i + 1, desc: d }));

        wb.xlsx.writeBuffer().then(buffer => { saveAs(new Blob([buffer], { type: "application/octet-stream" }), informeData.id + '.xlsx'); });
    });

    document.getElementById('exportInformeCompletoPDF')?.addEventListener('click', function() {
        const doc = new window.jspdf.jsPDF('p', 'mm', 'a4');
        var y = 15;

        doc.setFontSize(18); doc.setTextColor(11, 26, 48); doc.setFont(undefined, 'bold');
        doc.text('Informe Diario: ' + informeData.id, 14, y); y += 8;

        doc.setFontSize(10); doc.setFont(undefined, 'normal'); doc.setTextColor(80);
        doc.text('Fecha: ' + informeData.fecha + '  |  Lugar: ' + informeData.lugar + '  |  Ejecución: ' + informeData.ejecucion, 14, y); y += 8;
        doc.text('Descripción: ' + informeData.descripcion, 14, y); y += 5;
        doc.text('Observación: ' + informeData.observacion, 14, y); y += 10;

        // Empleados
        doc.setFontSize(13); doc.setTextColor(11, 26, 48); doc.setFont(undefined, 'bold');
        doc.text('Empleados (' + informeData.empleados.length + ')', 14, y); y += 7;
        doc.setFontSize(9); doc.setFont(undefined, 'normal'); doc.setTextColor(60);
        if (informeData.empleados.length > 0) {
            informeData.empleados.forEach((e, i) => { doc.text((i+1) + '. ' + e, 14, y); y += 5; });
        } else {
            doc.text('Sin empleados registrados.', 14, y); y += 5;
        }
        y += 5;

        // Artículos
        if (y > 250) { doc.addPage(); y = 15; }
        doc.setFontSize(13); doc.setTextColor(11, 26, 48); doc.setFont(undefined, 'bold');
        doc.text('Artículos (' + informeData.articulos.length + ')', 14, y); y += 7;

        if (informeData.articulos.length > 0) {
            var artRows = informeData.articulos.map(a => [a.nombre, String(a.cantidad), a.lote || '-']);
            doc.autoTable({
                head: [['Artículo', 'Cantidad', 'Lote']],
                body: artRows,
                startY: y,
                headStyles: { fillColor: [11, 26, 48] },
                alternateRowStyles: { fillColor: [245, 247, 251] },
                margin: { left: 14 }
            });
            y = doc.lastAutoTable.finalY + 8;
        } else {
            doc.setFontSize(9); doc.setFont(undefined, 'normal'); doc.setTextColor(60);
            doc.text('Sin artículos registrados.', 14, y); y += 8;
        }

        // Descripciones
        if (y > 250) { doc.addPage(); y = 15; }
        doc.setFontSize(13); doc.setTextColor(11, 26, 48); doc.setFont(undefined, 'bold');
        doc.text('Descripciones de Actividades (' + informeData.descripciones.length + ')', 14, y); y += 7;
        doc.setFontSize(9); doc.setFont(undefined, 'normal'); doc.setTextColor(60);
        if (informeData.descripciones.length > 0) {
            informeData.descripciones.forEach((d, i) => {
                if (y > 275) { doc.addPage(); y = 15; }
                doc.text((i+1) + '. ' + d, 14, y); y += 5;
            });
        } else {
            doc.text('Sin descripciones registradas.', 14, y);
        }

        doc.save(informeData.id + '.pdf');
    });
</script>
@endsection
