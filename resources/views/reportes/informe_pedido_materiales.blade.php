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
        <table class="table table-hover align-middle mb-0" id="tablaResultados">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">Id Orden</th>
                    <th>Proyecto</th>
                    <th>Nombre De La Obra</th>
                    <th>Fecha</th>
                    <th>Lugar</th>
                    <th>Artículo</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody id="tbodyResultados">
                @forelse($resultados as $row)
                    <tr>
                        <td class="ps-4 fw-bold text-primary">{{ $row->identificador }}</td>
                        <td>{{ $row->proyecto }}</td>
                        <td>{{ $row->obra }}</td>
                        <td>{{ $row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '-' }}</td>
                        <td>{{ $row->lugar ?: '-' }}</td>
                        <td class="fw-semibold">{{ $row->articulo ?: '-' }}</td>
                        <td class="fw-bold text-primary">{{ number_format($row->cantidad, 2) }}</td>
                    </tr>
                @empty
                    <tr id="rowEmpty">
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">Use los filtros para buscar resultados.</p>
                        </td>
                    </tr>
                @endforelse
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
</script>
@endsection
