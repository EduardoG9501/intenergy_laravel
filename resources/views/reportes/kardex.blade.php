@extends('layouts.app')

@section('title', 'Kardex (Entrada / Salida) - Intenergy')

@section('styles')
<style>
    .img-preview-wrapper { position: relative; display: inline-block; }
    .img-preview-wrapper .img-thumb { width: 70px; height: 70px; object-fit: cover; cursor: pointer; transition: transform 0.15s ease; border-radius: 6px; border: 1px solid #ddd; }
    .img-preview-wrapper .img-thumb:hover { transform: scale(1.15); box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
    .img-preview-wrapper .img-preview-hover { display: none; position: fixed; z-index: 9999; pointer-events: none; padding: 6px; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.35); }
    .img-preview-wrapper .img-preview-hover img { width: 260px; height: 220px; object-fit: contain; border-radius: 4px; }
    .img-preview-wrapper:hover .img-preview-hover { display: block; }

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
        <h2 class="fw-bold mb-0">Kardex (Entrada / Salida)</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="#">Reportes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Kardex</li>
            </ol>
        </nav>
    </div>
</div>

<!-- FILTROS -->
<div class="card card-custom p-4 mb-4">
    <form id="formFiltros" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-4">
            <label class="form-label fw-semibold">Lugar / Bodega:</label>
            <input type="hidden" name="id_bodega" id="filtro_bodega_id" value="{{ $bodegaActivaId ?? '' }}">
            <div class="input-group">
                <input type="text" class="form-control bg-white" id="filtro_bodega_nombre" readonly placeholder="Todas las bodegas" value="{{ $bodegaActivaNombre ?? '' }}" style="font-size:13px;">
                <button type="button" class="btn btn-outline-primary" onclick="abrirModalBodega()" title="Buscar bodega">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <button type="button" class="btn btn-outline-danger" id="filtro_bodega_clear" style="{{ ($bodegaActivaId ?? '') ? '' : 'display:none;' }}" onclick="limpiarBodega()" title="Limpiar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Artículo:</label>
            <input type="hidden" name="id_producto" id="filtro_producto_id">
            <div class="ot-search-wrapper">
                <div class="input-group">
                    <input type="text" class="form-control" id="filtro_producto_nombre"
                           placeholder="Escriba el nombre del artículo..."
                           autocomplete="off" style="font-size:13px;">
                    <button type="button" class="btn btn-outline-primary" onclick="fetchArticuloDD(document.getElementById('filtro_producto_nombre').value)" title="Buscar artículo">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="filtro_producto_clear" style="display:none;" onclick="limpiarProducto()" title="Limpiar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="ot-dropdown" id="artDropdown"></div>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Orden de Trabajo:</label>
            <input type="hidden" name="id_orden" id="filtro_orden_id">
            <div class="ot-search-wrapper">
                <div class="input-group">
                    <input type="text" class="form-control" id="filtro_orden_nombre"
                           placeholder="Escriba ID o nombre para buscar..."
                           autocomplete="off" style="font-size:13px;">
                    <button type="button" class="btn btn-outline-primary" onclick="fetchOT(document.getElementById('filtro_orden_nombre').value)" title="Buscar orden de trabajo">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="filtro_orden_clear" style="display:none;" onclick="limpiarOT()" title="Limpiar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="ot-dropdown" id="otDropdown"></div>
            </div>
        </div>
        <div class="col-md-12 d-flex gap-2 justify-content-end">
            <a href="#" class="btn btn-success px-4 py-2" id="btnExportExcel" style="display:none;" onclick="exportarExcel()">
                <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
            </a>
            <a href="#" class="btn btn-danger px-4 py-2" id="btnExportPdf" style="display:none;" onclick="exportarPdf()">
                <i class="fa-solid fa-file-pdf me-1"></i> Exportar a PDF
            </a>
            <button type="submit" class="btn btn-dark px-4 py-2" id="btnBuscar">
                <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
            </button>
        </div>
    </form>
</div>

<!-- TABLA RESULTADOS -->
<div class="card card-custom p-4 overflow-hidden">
    <div id="tablaKardexLoad" class="table-responsive">
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-boxes-stacked fa-3x mb-3 text-primary"></i>
            <p class="mb-0 fw-semibold">Seleccione los filtros y pulse Buscar para ver el reporte.</p>
        </div>
    </div>
</div>

<!-- MODAL BUSCAR BODEGA -->
<div class="modal fade" id="modalBodega" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1060;">
        <div class="modal-content border-0 shadow-lg" style="z-index: 1060;">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-warehouse me-2"></i> Seleccionar Lugar / Bodega</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" class="form-control" id="buscadorBodega" placeholder="Escribe para buscar por nombre..." oninput="buscarBodega()" style="font-size: 13px;">
                </div>
                <div class="list-group" id="listaBodega" style="max-height: 350px; overflow-y: auto;"></div>
                <div id="sinResultadosBodega" class="text-center text-muted py-4" style="display:none;">
                    <i class="fa-solid fa-warehouse fa-2x mb-2 text-warning"></i>
                    <p class="mb-0">No se encontraron bodegas</p>
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
    // === BODEGA ===
    function abrirModalBodega() {
        document.getElementById('buscadorBodega').value = '';
        buscarBodega();
        new bootstrap.Modal(document.getElementById('modalBodega')).show();
        setTimeout(() => document.getElementById('buscadorBodega').focus(), 400);
    }

    function buscarBodega() {
        const q = document.getElementById('buscadorBodega').value;
        fetch('{{ route("reportes.buscar_bodegas") }}?' + new URLSearchParams({ q }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const lista = document.getElementById('listaBodega');
            const sinRes = document.getElementById('sinResultadosBodega');
            if (data.length === 0) { lista.innerHTML = ''; sinRes.style.display = ''; return; }
            sinRes.style.display = 'none';
            lista.innerHTML = data.map(b =>
                `<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                    onclick="seleccionarBodega(${b.id_bodega}, '${(b.nombreBodega || '').replace(/'/g, "\\'")}')">
                    <span class="fw-semibold">${b.nombreBodega}</span>
                    <i class="fa-solid fa-chevron-right text-muted small"></i>
                </button>`
            ).join('');
        });
    }

    function seleccionarBodega(id, nombre) {
        document.getElementById('filtro_bodega_id').value = id;
        document.getElementById('filtro_bodega_nombre').value = nombre;
        document.getElementById('filtro_bodega_clear').style.display = '';
        bootstrap.Modal.getInstance(document.getElementById('modalBodega')).hide();
    }

    function limpiarBodega() {
        document.getElementById('filtro_bodega_id').value = '';
        document.getElementById('filtro_bodega_nombre').value = '';
        document.getElementById('filtro_bodega_clear').style.display = 'none';
    }

    // === ARTICULO (autocomplete) ===
    const inputArt = document.getElementById('filtro_producto_nombre');
    const dropdownArt = document.getElementById('artDropdown');
    const hiddenArt = document.getElementById('filtro_producto_id');
    const clearArt = document.getElementById('filtro_producto_clear');
    let debounceArt = null;

    inputArt.addEventListener('input', function() {
        clearTimeout(debounceArt);
        const q = this.value.trim();
        if (q.length < 1) { dropdownArt.style.display = 'none'; hiddenArt.value = ''; clearArt.style.display = 'none'; return; }
        debounceArt = setTimeout(() => fetchArticuloDD(q), 250);
    });
    inputArt.addEventListener('focus', function() {
        if (this.value.trim().length >= 1) fetchArticuloDD(this.value.trim());
    });

    function fetchArticuloDD(q) {
        fetch('{{ route("reportes.buscar_articulos") }}?' + new URLSearchParams({ q }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) {
                dropdownArt.innerHTML = '<div class="ot-empty"><i class="fa-solid fa-search me-1"></i> Sin resultados</div>';
            } else {
                dropdownArt.innerHTML = data.map(a =>
                    `<div class="ot-item" onclick="seleccionarArticulo(${a.id_producto}, '${(a.nombre || '').replace(/'/g, "\\'")}')">
                        <span class="ot-id">${a.nombre}</span>
                    </div>`
                ).join('');
            }
            dropdownArt.style.display = 'block';
        });
    }

    function seleccionarArticulo(id, nombre) {
        hiddenArt.value = id;
        inputArt.value = nombre;
        clearArt.style.display = '';
        dropdownArt.style.display = 'none';
    }

    function limpiarProducto() {
        hiddenArt.value = '';
        inputArt.value = '';
        clearArt.style.display = 'none';
        dropdownArt.style.display = 'none';
    }

    // === OT (autocomplete) ===
    const inputOT = document.getElementById('filtro_orden_nombre');
    const dropdownOT = document.getElementById('otDropdown');
    const hiddenOT = document.getElementById('filtro_orden_id');
    const clearOT = document.getElementById('filtro_orden_clear');
    let debounceOT = null;

    inputOT.addEventListener('input', function() {
        clearTimeout(debounceOT);
        const q = this.value.trim();
        if (q.length < 1) { dropdownOT.style.display = 'none'; hiddenOT.value = ''; clearOT.style.display = 'none'; return; }
        debounceOT = setTimeout(() => fetchOT(q), 250);
    });
    inputOT.addEventListener('focus', function() {
        if (this.value.trim().length >= 1) fetchOT(this.value.trim());
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.ot-search-wrapper')) {
            dropdownArt.style.display = 'none';
            dropdownOT.style.display = 'none';
        }
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
                    `<div class="ot-item" onclick="seleccionarOT(${ot.id_orden}, '${ot.identificador}', '${(ot.proyecto||'').replace(/'/g,"\\'")}', '${(ot.obra||'').replace(/'/g,"\\'")}')">
                        <span class="ot-id">${ot.identificador}</span>
                        <span class="ot-desc ms-2">${ot.proyecto || ''} / ${ot.obra || ''}</span>
                    </div>`
                ).join('');
            }
            dropdownOT.style.display = 'block';
        });
    }

    function seleccionarOT(id, identificador, proyecto, obra) {
        hiddenOT.value = id;
        inputOT.value = identificador + ' — ' + proyecto + ' / ' + obra;
        clearOT.style.display = '';
        dropdownOT.style.display = 'none';
    }

    function limpiarOT() {
        hiddenOT.value = '';
        inputOT.value = '';
        clearOT.style.display = 'none';
        dropdownOT.style.display = 'none';
    }

    // === BUSQUEDA AJAX ===
    function ejecutarBusqueda(page) {
        const formData = new FormData(document.getElementById('formFiltros'));
        if (page) formData.append('page', page);
        const params = new URLSearchParams(formData).toString();

        document.getElementById('btnBuscar').innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Buscando...';
        document.getElementById('tablaKardexLoad').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Generando reporte...</p></div>';

        fetch('{{ route("reportes.kardex") }}?' + params + '&buscar=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('tablaKardexLoad').innerHTML = data.html_tabla;
            document.getElementById('btnBuscar').innerHTML = '<i class="fa-solid fa-magnifying-glass me-1"></i> Buscar';

            document.getElementById('btnExportExcel').style.display = data.total > 0 ? '' : 'none';
            document.getElementById('btnExportPdf').style.display = data.total > 0 ? '' : 'none';
        });
    }

    function cargarPagina(page) {
        ejecutarBusqueda(page);
    }

    document.getElementById('formFiltros').addEventListener('submit', function(e) {
        e.preventDefault();
        ejecutarBusqueda(1);
    });

    // === EXPORTAR ===
    function exportarExcel() {
        const params = new URLSearchParams(new FormData(document.getElementById('formFiltros'))).toString();
        window.location.href = '{{ route("reportes.exportar_kardex") }}?' + params;
    }

    function exportarPdf() {
        const params = new URLSearchParams(new FormData(document.getElementById('formFiltros'))).toString();
        window.open('{{ route("reportes.exportar_kardex_pdf") }}?' + params, '_blank');
    }
</script>
@endsection
