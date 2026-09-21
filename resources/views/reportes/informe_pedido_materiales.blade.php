@extends('layouts.app')

@section('title', 'Informe Pedido de Materiales - Intenergy')

@section('styles')
<style>
    .search-modal .list-group-item { cursor: pointer; padding: 8px 12px; font-size: 13px; }
    .search-modal .list-group-item:hover { background-color: #f0f7ff; }
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
        <!-- Id Orden con búsqueda popup -->
        <div class="col-md-4">
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

<!-- MODAL BUSCAR ORDEN DE TRABAJO -->
<div class="modal fade search-modal" id="modalOT" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
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
    const CSRF_TOKEN = '{{ csrf_token() }}';

    // === MODAL ORDEN DE TRABAJO ===
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
            if (data.length === 0) {
                lista.innerHTML = '';
                sinRes.style.display = '';
                return;
            }
            sinRes.style.display = 'none';
            lista.innerHTML = data.map(ot =>
                `<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                    onclick="seleccionarOT(${ot.id_orden}, '${ot.identificador}', '${(ot.proyecto || '').replace(/'/g, "\\'")}', '${(ot.obra || '').replace(/'/g, "\\'")}')">
                    <div>
                        <span class="fw-bold text-primary">${ot.identificador}</span>
                        <small class="text-muted ms-2">${ot.proyecto || ''} - ${ot.obra || ''}</small>
                    </div>
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
