@extends('layouts.app')

@section('title', 'Reporte de Ejecución de Obra - Intenergy')

@section('styles')
<style>
    .accordion-custom .accordion-button { font-weight: 700; font-size: 14px; }
    .accordion-custom .accordion-button:not(.collapsed) { background-color: #f0f7ff; color: #0d6efd; }
    .section-count { font-size: 12px; padding: 2px 10px; border-radius: 20px; }

    .img-preview-wrapper {
        position: relative;
        display: inline-block;
    }
    .img-preview-wrapper .img-thumb {
        width: 70px;
        height: 70px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.15s ease;
    }
    .img-preview-wrapper .img-thumb:hover {
        transform: scale(1.15);
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    .img-preview-wrapper .img-preview-hover {
        display: none;
        position: fixed;
        z-index: 9999;
        pointer-events: none;
        padding: 6px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.35);
    }
    .img-preview-wrapper .img-preview-hover img {
        width: 260px;
        height: 220px;
        object-fit: contain;
        border-radius: 4px;
    }
    .img-preview-wrapper:hover .img-preview-hover {
        display: block;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Reporte de Ejecución de Obra</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="#">Reportes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ejecución de Obra</li>
            </ol>
        </nav>
    </div>
</div>

<!-- FILTROS -->
<div class="card card-custom p-4 mb-4">
    <form id="formFiltros" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-5">
            <label class="form-label fw-semibold">Orden de Trabajo:</label>
            <input type="hidden" name="id_orden" id="filtro_orden_id">
            <div class="input-group">
                <input type="text" class="form-control bg-white" id="filtro_orden_nombre" readonly placeholder="Todas las órdenes de trabajo" style="font-size:13px;">
                <button type="button" class="btn btn-outline-primary" onclick="abrirModalOT()" title="Buscar orden de trabajo">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <button type="button" class="btn btn-outline-danger" id="filtro_orden_clear" style="display:none;" onclick="limpiarOT()" title="Limpiar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <div class="col-md-7 d-flex gap-2 justify-content-end">
            <button type="submit" class="btn btn-dark px-4 py-2" id="btnBuscar">
                <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
            </button>
            <a href="#" class="btn btn-success px-4 py-2" id="btnExportExcel" style="display:none;" onclick="exportarExcel()">
                <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
            </a>
            <a href="#" class="btn btn-danger px-4 py-2" id="btnExportPdf" style="display:none;" onclick="exportarPdf()">
                <i class="fa-solid fa-file-pdf me-1"></i> Exportar a PDF
            </a>
        </div>
    </form>
</div>

<!-- RESULTADOS - ACCORDION -->
<div class="accordion accordion-custom" id="accordionEjecucion">

    <!-- MATERIALES -->
    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMateriales" aria-expanded="true">
                <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i> Materiales a Utilizar
                <span class="badge bg-primary section-count ms-2" id="countMateriales">{{ $materiales->count() }}</span>
            </button>
        </h2>
        <div id="collapseMateriales" class="accordion-collapse collapse show" data-bs-parent="#accordionEjecucion">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Id Orden</th>
                                <th>Proyecto</th>
                                <th>Nombre De La Obra</th>
                                <th>Fecha</th>
                                <th>Lugar</th>
                                <th>Articulo</th>
                                <th>Cantidad</th>
                                <th>Contabilizado</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyMateriales">
                            @forelse($materiales as $row)
                                <tr>
                                    <td class="ps-3 fw-bold text-primary">{{ $row->identificador }}</td>
                                    <td>{{ $row->proyecto }}</td>
                                    <td>{{ $row->obra }}</td>
                                    <td>{{ $row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '-' }}</td>
                                    <td>{{ $row->lugar ?: '-' }}</td>
                                    <td class="fw-semibold">{{ $row->articulo ?: '-' }}</td>
                                    <td class="fw-bold text-primary">{{ number_format($row->cantidad, 2) }}</td>
                                    <td>
                                        @if($row->Contabilizado)
                                            <span class="badge bg-success">Sí</span>
                                        @else
                                            <span class="badge bg-warning text-dark">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center py-4 text-muted">Use los filtros para buscar resultados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- HORAS DE TRABAJO -->
    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHoras">
                <i class="fa-solid fa-clock me-2 text-warning"></i> Horas de Trabajo
                <span class="badge bg-warning text-dark section-count ms-2" id="countHoras">{{ $horas->count() }}</span>
            </button>
        </h2>
        <div id="collapseHoras" class="accordion-collapse collapse" data-bs-parent="#accordionEjecucion">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Id Orden</th>
                                <th>Proyecto</th>
                                <th>Nombre De La Obra</th>
                                <th>Fecha</th>
                                <th>Lugar</th>
                                <th>Empleado</th>
                                <th>Hora Entrada</th>
                                <th>Hora Salida</th>
                                <th class="text-center">H.Normal</th>
                                <th class="text-center">H.Extras</th>
                                <th class="text-center">H.Extraordinarias</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyHoras">
                            @forelse($horas as $row)
                                <tr>
                                    <td class="ps-3 fw-bold text-primary">{{ $row->identificador }}</td>
                                    <td>{{ $row->proyecto }}</td>
                                    <td>{{ $row->obra }}</td>
                                    <td>{{ $row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '-' }}</td>
                                    <td>{{ $row->lugar ?: '-' }}</td>
                                    <td class="fw-semibold">{{ $row->empleado }}</td>
                                    <td class="font-monospace">{{ $row->hora_entrada }}</td>
                                    <td class="font-monospace">{{ $row->hora_salida }}</td>
                                    <td class="text-center fw-bold">{{ number_format($row->cantidad_horas_normal, 2) }}</td>
                                    <td class="text-center fw-bold text-warning">{{ number_format($row->cantidad_horas_extra, 2) }}</td>
                                    <td class="text-center fw-bold text-danger">{{ number_format($row->cantidad_horas_extraordinaria, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="text-center py-4 text-muted">Use los filtros para buscar resultados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- INFORMES DIARIOS -->
    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInformes">
                <i class="fa-solid fa-clipboard me-2 text-success"></i> Informes Diarios
                <span class="badge bg-success section-count ms-2" id="countInformes">{{ $informes->count() }}</span>
            </button>
        </h2>
        <div id="collapseInformes" class="accordion-collapse collapse" data-bs-parent="#accordionEjecucion">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Id Informe</th>
                                <th>Nombre De La Obra</th>
                                <th>Fecha</th>
                                <th>Lugar</th>
                                <th>Ubicación</th>
                                <th>Observación</th>
                                <th>Imágenes</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyInformes">
                            @forelse($informes as $row)
                                @php
                                    $imgs = $imagenes->get($row->id_informe_diario_ejecucion, collect());
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-bold text-primary">INF-{{ str_pad($row->id_informe_diario_ejecucion, 5, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $row->obra }}</td>
                                    <td>{{ $row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '-' }}</td>
                                    <td>{{ $row->lugar ?: '-' }}</td>
                                    <td>{{ $row->ubicacion ?: '-' }}</td>
                                    <td>{{ $row->observacion ?: '-' }}</td>
                                    <td>
                                        @if($imgs->count() > 0)
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($imgs as $img)
                                                    <div class="img-preview-wrapper">
                                                        <a href="{{ $img->ruta_imagen }}" target="_blank">
                                                            <img src="{{ $img->ruta_imagen }}" alt="img" class="rounded border img-thumb">
                                                        </a>
                                                        <div class="img-preview-hover">
                                                            <img src="{{ $img->ruta_imagen }}" alt="preview" class="rounded shadow">
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4 text-muted">Use los filtros para buscar resultados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL BUSCAR ORDEN DE TRABAJO -->
<div class="modal fade" id="modalOT" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1060;">
        <div class="modal-content border-0 shadow-lg" style="z-index: 1060;">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-clipboard-list me-2"></i> Seleccionar Orden de Trabajo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" class="form-control" id="buscadorOT"
                           placeholder="Escribe para buscar por ID, identificador, proyecto u obra..."
                           oninput="buscarOT()" style="font-size: 13px;">
                </div>
                <div class="list-group" id="listaOT" style="max-height: 350px; overflow-y: auto;"></div>
                <div id="sinResultadosOT" class="text-center text-muted py-4" style="display:none;">
                    <i class="fa-solid fa-search fa-2x mb-2 text-warning"></i>
                    <p class="mb-0">No se encontraron órdenes de trabajo</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function abrirModalOT() {
        document.getElementById('buscadorOT').value = '';
        buscarOT();
        new bootstrap.Modal(document.getElementById('modalOT')).show();
        setTimeout(() => document.getElementById('buscadorOT').focus(), 400);
    }

    function buscarOT() {
        const q = document.getElementById('buscadorOT').value;
        fetch('{{ route("reportes.buscar_ordenes_trabajo") }}?' + new URLSearchParams({ q }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const lista = document.getElementById('listaOT');
            const sinRes = document.getElementById('sinResultadosOT');
            if (data.length === 0) { lista.innerHTML = ''; sinRes.style.display = ''; return; }
            sinRes.style.display = 'none';
            lista.innerHTML = data.map(ot =>
                `<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                    onclick="seleccionarOT(${ot.id_orden}, '${ot.identificador}', '${(ot.proyecto || '').replace(/'/g, "\\'")}', '${(ot.obra || '').replace(/'/g, "\\'")}')">
                    <div><span class="fw-bold text-primary">${ot.identificador}</span> <small class="text-muted ms-2">${ot.proyecto || ''} - ${ot.obra || ''}</small></div>
                    <i class="fa-solid fa-chevron-right text-muted small"></i>
                </button>`
            ).join('');
        });
    }

    function seleccionarOT(id, identificador, proyecto, obra) {
        document.getElementById('filtro_orden_id').value = id;
        document.getElementById('filtro_orden_nombre').value = `${identificador} — ${proyecto} / ${obra}`;
        document.getElementById('filtro_orden_clear').style.display = '';
        bootstrap.Modal.getInstance(document.getElementById('modalOT')).hide();
    }

    function limpiarOT() {
        document.getElementById('filtro_orden_id').value = '';
        document.getElementById('filtro_orden_nombre').value = '';
        document.getElementById('filtro_orden_clear').style.display = 'none';
    }

    document.getElementById('formFiltros').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const params = new URLSearchParams(formData).toString();

        document.getElementById('btnBuscar').innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Buscando...';

        fetch('{{ route("reportes.reporte_ejecucion_obra") }}?' + params + '&buscar=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('tbodyMateriales').innerHTML = data.html_materiales;
            document.getElementById('tbodyHoras').innerHTML = data.html_horas;
            document.getElementById('tbodyInformes').innerHTML = data.html_informes;

            document.getElementById('countMateriales').textContent = data.count_materiales;
            document.getElementById('countHoras').textContent = data.count_horas;
            document.getElementById('countInformes').textContent = data.count_informes;

            const hasData = data.count_materiales > 0 || data.count_horas > 0 || data.count_informes > 0;
            document.getElementById('btnExportExcel').style.display = hasData ? '' : 'none';
            document.getElementById('btnExportPdf').style.display = hasData ? '' : 'none';

            document.getElementById('btnBuscar').innerHTML = '<i class="fa-solid fa-magnifying-glass me-1"></i> Buscar';
        });
    });

    function exportarExcel() {
        const params = new URLSearchParams(new FormData(document.getElementById('formFiltros'))).toString();
        window.location.href = '{{ route("reportes.exportar_ejecucion_obra") }}?' + params;
    }

    function exportarPdf() {
        const params = new URLSearchParams(new FormData(document.getElementById('formFiltros'))).toString();
        window.open('{{ route("reportes.exportar_ejecucion_obra_pdf") }}?' + params, '_blank');
    }

    // Hover preview de imágenes - posición fija跟随 cursor
    document.addEventListener('mousemove', function(e) {
        const preview = document.querySelector('.img-preview-wrapper:hover .img-preview-hover');
        if (preview) {
            let x = e.clientX + 15;
            let y = e.clientY - 120;
            if (y < 10) y = e.clientY + 15;
            if (x + 280 > window.innerWidth) x = e.clientX - 290;
            preview.style.left = x + 'px';
            preview.style.top = y + 'px';
        }
    });
</script>
@endsection
