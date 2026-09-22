@extends('layouts.app')

@section('title', 'Informe Pedido de Materiales - Intenergy')

@section('styles')
<style>
    .ot-search-wrapper { position: relative; }
    .ot-dropdown { position: absolute; top: 100%; left: 0; right: 0; z-index: 1050; background: white; border: 1px solid #dee2e6; border-radius: 0 0 6px 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-height: 300px; overflow-y: auto; display: none; }
    .ot-dropdown .ot-item { padding: 8px 12px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f0f0f0; }
    .ot-dropdown .ot-item:hover { background-color: #f0f7ff; }
    .ot-dropdown .ot-item .ot-id { font-weight: bold; color: #0d6efd; }
    .ot-dropdown .ot-item .ot-desc { color: #666; font-size: 12px; }
    .ot-dropdown .ot-empty { padding: 15px; text-align: center; color: #999; font-size: 13px; }

    #tablaResultados { border-collapse: collapse; width: 100%; }
    #tablaResultados thead th {
        background: #ffffff !important;
        color: #1e293b !important;
        border: 1px solid #e2e8f0 !important;
        font-size: 12px;
        font-weight: 700;
        padding: 10px 8px;
        text-align: left;
        white-space: nowrap;
    }
    #tablaResultados thead th .sort-icon { font-size: 10px; opacity: 0.45; margin-left: 4px; }
    #tablaResultados tbody td {
        border: 1px solid #e2e8f0;
        font-size: 12.5px;
        padding: 9px 8px;
        color: #334155;
        vertical-align: middle;
        background: #ffffff;
    }
    #tablaResultados tbody tr:hover td { background: #f0f7ff; }
    #tablaResultados tbody tr.row-subtotal td {
        background: #cfe2ff !important;
        border: 1px solid #b6d4fe;
        font-size: 12.5px;
        padding: 9px 8px;
    }
    #tablaResultados tbody tr.row-subtotal td:nth-child(7),
    #tablaResultados tbody tr.row-subtotal td:nth-child(8) {
        background: #d6eaff !important;
        color: #0f172a;
        font-weight: 700;
    }
    #tablaResultados tbody tr.row-grand-total td {
        background: #0d6efd !important;
        color: #ffffff !important;
        border: 1px solid #0d6efd;
        font-weight: 700;
        font-size: 13px;
        padding: 10px 8px;
    }
    #tablaResultados .col-check { width: 34px; text-align: center; }
    #tablaResultados .col-check input { width: 15px; height: 15px; cursor: pointer; }
    #tablaResultados td.text-end { text-align: right; }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Informe de Pedido de Materiales</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="#">Reportes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pedido de Materiales</li>
            </ol>
        </nav>
    </div>
</div>

<!-- FILTROS -->
<div class="card card-custom p-4 mb-4">
    <form id="formFiltros" class="row g-3 align-items-end">
        @csrf
        <!-- Id Orden con búsqueda directa -->
        <div class="col-md-4">
            <label class="form-label fw-semibold">Orden de Trabajo:</label>
            <input type="hidden" name="id_orden" id="filtro_orden_id">
            <div class="ot-search-wrapper">
                <input type="text" class="form-control" id="filtro_orden_nombre"
                       placeholder="Escriba ID o nombre para buscar..."
                       autocomplete="off" style="font-size:13px;">
                <div class="ot-dropdown" id="otDropdown"></div>
            </div>
        </div>

        <!-- Botones -->
        <div class="col-md-8 d-flex gap-2 justify-content-end">
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

<!-- RESULTADOS -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaResultados" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th class="col-check text-center"><input type="checkbox" id="checkAll" title="Seleccionar todo"></th>
                    <th>ID ORDEN <span class="sort-icon">⇅</span></th>
                    <th>PROYECTO <span class="sort-icon">⇅</span></th>
                    <th>NOMBRE DE LA OBRA <span class="sort-icon">⇅</span></th>
                    <th>FECHA <span class="sort-icon">⇅</span></th>
                    <th>LUGAR <span class="sort-icon">⇅</span></th>
                    <th>ARTICULO <span class="sort-icon">⇅</span></th>
                    <th class="text-end">CANTIDAD <span class="sort-icon">⇅</span></th>
                </tr>
            </thead>
            <tbody id="tbodyResultados">
                @include('reportes.partials.tabla_pedido_materiales', ['resultados' => $resultados])
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-2 text-muted small" id="resultInfo" @if($resultados->isEmpty()) style="display:none;" @endif>
        Mostrando <strong id="resultCount">{{ $resultados->count() }}</strong> registro(s)
    </div>
</div>
@endsection

@section('scripts')
<script>
    const CSRF_TOKEN = '{{ csrf_token() }}';

    // === BÚSQUEDA DIRECTA ORDEN DE TRABAJO ===
    const inputOT = document.getElementById('filtro_orden_nombre');
    const dropdownOT = document.getElementById('otDropdown');
    const hiddenOT = document.getElementById('filtro_orden_id');
    let debounceTimer = null;

    inputOT.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < 1) { dropdownOT.style.display = 'none'; hiddenOT.value = ''; return; }
        debounceTimer = setTimeout(() => fetchOT(q), 300);
    });

    inputOT.addEventListener('focus', function() {
        if (this.value.trim().length >= 1) fetchOT(this.value.trim());
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.ot-search-wrapper')) dropdownOT.style.display = 'none';
    });

    function fetchOT(q) {
        fetch('{{ route("reportes.buscar_ordenes_trabajo") }}?' + new URLSearchParams({ q }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) {
                dropdownOT.innerHTML = '<div class="ot-empty"><i class="fa-solid fa-search me-1"></i> Sin resultados</div>';
            } else {
                dropdownOT.innerHTML = data.map(ot =>
                    `<div class="ot-item" onclick="selectOT(${ot.id_orden}, '${ot.identificador}', '${(ot.proyecto||'').replace(/'/g,"\\'")}', '${(ot.obra||'').replace(/'/g,"\\'")}')">
                        <span class="ot-id">${ot.identificador}</span>
                        <span class="ot-desc ms-2">${ot.proyecto || ''} / ${ot.obra || ''}</span>
                    </div>`
                ).join('');
            }
            dropdownOT.style.display = 'block';
        });
    }

    function selectOT(id, identificador, proyecto, obra) {
        hiddenOT.value = id;
        inputOT.value = identificador + ' — ' + proyecto + ' / ' + obra;
        dropdownOT.style.display = 'none';
    }

    // === BÚSQUEDA AJAX ===
    document.getElementById('formFiltros').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const params = new URLSearchParams(formData).toString();

        document.getElementById('btnBuscar').innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Buscando...';

        fetch('{{ route("reportes.informe_pedido_materiales") }}?' + params + '&buscar=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('tbodyResultados').innerHTML = data.html;
            document.getElementById('resultCount').textContent = data.count;
            document.getElementById('resultInfo').style.display = data.count > 0 ? '' : 'none';
            document.getElementById('btnExportExcel').style.display = data.count > 0 ? '' : 'none';
            document.getElementById('btnExportPdf').style.display = data.count > 0 ? '' : 'none';
            document.getElementById('btnBuscar').innerHTML = '<i class="fa-solid fa-magnifying-glass me-1"></i> Buscar';
        });
    });

    // === EXPORTAR ===
    function exportarExcel() {
        const formData = new FormData(document.getElementById('formFiltros'));
        const params = new URLSearchParams(formData).toString();
        window.location.href = '{{ route("reportes.exportar_pedido_materiales") }}?' + params;
    }

    function exportarPdf() {
        const formData = new FormData(document.getElementById('formFiltros'));
        const params = new URLSearchParams(formData).toString();
        window.open('{{ route("reportes.exportar_pedido_materiales_pdf") }}?' + params, '_blank');
    }

    // === CHECK ALL ===
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'checkAll') {
            document.querySelectorAll('.row-check').forEach(cb => cb.checked = e.target.checked);
        }
    });
</script>
@endsection
