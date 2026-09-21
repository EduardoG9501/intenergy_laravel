@extends('layouts.app')

@section('title', 'Liquidación de Trabajo - Intenergy')

@section('styles')
<!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    .accordion-custom .accordion-button { font-weight: 700; font-size: 14px; }
    .accordion-custom .accordion-button:not(.collapsed) { background-color: #f0f7ff; color: #0d6efd; }
    .section-count { font-size: 12px; padding: 2px 10px; border-radius: 20px; }

    .img-preview-wrapper { position: relative; display: inline-block; }
    .img-preview-wrapper .img-thumb { width: 70px; height: 70px; object-fit: cover; cursor: pointer; transition: transform 0.15s ease; border-radius: 6px; border: 1px solid #ddd; }
    .img-preview-wrapper .img-thumb:hover { transform: scale(1.15); box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
    .img-preview-wrapper .img-preview-hover { display: none; position: fixed; z-index: 9999; pointer-events: none; padding: 6px; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.35); }
    .img-preview-wrapper .img-preview-hover img { width: 260px; height: 220px; object-fit: contain; border-radius: 4px; }
    .img-preview-wrapper:hover .img-preview-hover { display: block; }

    .img-thumb-liq { width: 80px; height: 80px; object-fit: cover; cursor: pointer; border: 1px solid #ddd; transition: transform 0.15s; }
    .img-thumb-liq:hover { transform: scale(1.15); box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
    .img-preview-hover-liq { display: none; position: fixed; z-index: 9999; pointer-events: none; padding: 6px; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.35); }
    .img-preview-hover-liq img { width: 260px; height: 220px; object-fit: contain; border-radius: 4px; }
    .img-preview-wrapper:hover .img-preview-hover-liq { display: block; }

    .note-editor .note-toolbar { background-color: #f8f9fa; }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Liquidación de Trabajo</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="#">Reportes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Liquidación de Trabajo</li>
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

<!-- RESULTADOS - ACCORDION -->
<div class="accordion accordion-custom" id="accordionLiq">

    <!-- 1. MATERIALES -->
    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMateriales" aria-expanded="true">
                <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i> Materiales a Utilizar
                <span class="badge bg-primary section-count ms-2" id="countMateriales">0</span>
            </button>
        </h2>
        <div id="collapseMateriales" class="accordion-collapse collapse show" data-bs-parent="#accordionLiq">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th class="ps-3">Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Articulo</th><th>Cantidad</th><th>Contabilizado</th></tr>
                        </thead>
                        <tbody id="tbodyMateriales"><tr><td colspan="8" class="text-center py-4 text-muted">Use los filtros para buscar.</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. HORAS DE TRABAJO -->
    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHoras">
                <i class="fa-solid fa-clock me-2 text-warning"></i> Horas de Trabajo
                <span class="badge bg-warning text-dark section-count ms-2" id="countHoras">0</span>
            </button>
        </h2>
        <div id="collapseHoras" class="accordion-collapse collapse" data-bs-parent="#accordionLiq">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th class="ps-3">Id Orden</th><th>Proyecto</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Empleado</th><th>H.Entrada</th><th>H.Salida</th><th class="text-center">H.Normal</th><th class="text-center">H.Extras</th><th class="text-center">H.Extraordinarias</th></tr>
                        </thead>
                        <tbody id="tbodyHoras"><tr><td colspan="11" class="text-center py-4 text-muted">Use los filtros para buscar.</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. INFORMES DIARIOS -->
    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInformes">
                <i class="fa-solid fa-clipboard me-2 text-success"></i> Informes Diarios
                <span class="badge bg-success section-count ms-2" id="countInformes">0</span>
            </button>
        </h2>
        <div id="collapseInformes" class="accordion-collapse collapse" data-bs-parent="#accordionLiq">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th class="ps-3">Id Informe</th><th>Nombre De La Obra</th><th>Fecha</th><th>Lugar</th><th>Ubicación</th><th>Observación</th><th>Imágenes</th></tr>
                        </thead>
                        <tbody id="tbodyInformes"><tr><td colspan="7" class="text-center py-4 text-muted">Use los filtros para buscar.</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. MANO DE OBRA (editable) -->
    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseManoObra">
                <i class="fa-solid fa-hard-hat me-2 text-info"></i> Mano de Obra
            </button>
        </h2>
        <div id="collapseManoObra" class="accordion-collapse collapse" data-bs-parent="#accordionLiq">
            <div class="accordion-body">
                <input type="hidden" id="liq_id">
                <div id="summernoteManoObra"></div>
                <div class="text-end mt-3">
                    <button type="button" class="btn btn-primary-custom px-4" onclick="guardarLiquidacion()">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Mano de Obra
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. IMPORTANTE (editable) -->
    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImportante">
                <i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i> Importante
            </button>
        </h2>
        <div id="collapseImportante" class="accordion-collapse collapse" data-bs-parent="#accordionLiq">
            <div class="accordion-body">
                <div id="summernoteImportante"></div>
                <div class="text-end mt-3">
                    <button type="button" class="btn btn-primary-custom px-4" onclick="guardarLiquidacion()">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Importante
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. IMÁGENES ADJUNTAS (editable) -->
    <div class="accordion-item border-0 shadow-sm mb-3 rounded">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImagenesLiq">
                <i class="fa-solid fa-images me-2 text-secondary"></i> Imágenes Adjuntas
                <span class="badge bg-secondary section-count ms-2" id="countImagenesLiq">0</span>
            </button>
        </h2>
        <div id="collapseImagenesLiq" class="accordion-collapse collapse" data-bs-parent="#accordionLiq">
            <div class="accordion-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Seleccionar imágenes:</label>
                    <input type="file" class="form-control" id="inputImagenesLiq" multiple accept="image/*">
                </div>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-success px-4" onclick="subirImagenesLiq()">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Subir Imágenes
                    </button>
                </div>
                <hr>
                <div id="contenedorImagenesLiq" class="d-flex flex-wrap align-items-center gap-2">
                    <p class="text-muted mb-0" id="sinImagenesLiq">No hay imágenes adjuntas.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL BUSCAR OT -->
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
                    <input type="text" class="form-control" id="buscadorOT" placeholder="Escribe para buscar por ID, identificador, proyecto u obra..." oninput="buscarOT()" style="font-size: 13px;">
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
    let liqId = null;

    // Init Summernote editors
    $(document).ready(function() {
        $('#summernoteManoObra').summernote({
            height: 200,
            placeholder: 'Describa detalladamente la mano de obra utilizada...',
            lang: 'es-ES',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'hr']],
                ['view', ['fullscreen', 'codeview']]
            ]
        });
        $('#summernoteImportante').summernote({
            height: 200,
            placeholder: 'Información importante a registrar...',
            lang: 'es-ES',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'hr']],
                ['view', ['fullscreen', 'codeview']]
            ]
        });
    });

    // === BUSCAR OT ===
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

    // === BÚSQUEDA AJAX ===
    document.getElementById('formFiltros').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const params = new URLSearchParams(formData).toString();

        document.getElementById('btnBuscar').innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Buscando...';

        fetch('{{ route("reportes.liquidacion_trabajo") }}?' + params + '&buscar=1', {
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
            document.getElementById('countImagenesLiq').textContent = data.count_imagenes_liq;

            liqId = data.liquidacion_id;
            document.getElementById('liq_id').value = liqId || '';

            $('#summernoteManoObra').summernote('code', data.mano_obra || '');
            $('#summernoteImportante').summernote('code', data.importante || '');

            document.getElementById('contenedorImagenesLiq').innerHTML = data.html_imagenes_liq;

            document.getElementById('btnBuscar').innerHTML = '<i class="fa-solid fa-magnifying-glass me-1"></i> Buscar';

            const hasData = data.count_materiales > 0 || data.count_horas > 0 || data.count_informes > 0;
            document.getElementById('btnExportExcel').style.display = hasData ? '' : 'none';
            document.getElementById('btnExportPdf').style.display = hasData ? '' : 'none';

            initHoverPreview();
        });
    });

    // === GUARDAR LIQUIDACION ===
    function guardarLiquidacion() {
        if (!liqId) { Swal.fire('Error', 'Primero seleccione una Orden de Trabajo.', 'error'); return; }

        const manoObra = $('#summernoteManoObra').summernote('code');
        const importante = $('#summernoteImportante').summernote('code');

        const formData = new FormData();
        formData.append('id_liquidacion', liqId);
        formData.append('mano_obra', manoObra);
        formData.append('importante', importante);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route("reportes.guardar_liquidacion") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(r => {
            if (r.success) { Swal.fire({ title: 'Guardado', text: r.mensaje, icon: 'success', timer: 1500, showConfirmButton: false }); }
            else { Swal.fire('Error', r.mensaje, 'error'); }
        });
    }

    // === SUBIR IMÁGENES ===
    function subirImagenesLiq() {
        if (!liqId) { Swal.fire('Error', 'Primero seleccione una Orden de Trabajo.', 'error'); return; }

        const input = document.getElementById('inputImagenesLiq');
        if (!input.files.length) { Swal.fire('Atención', 'Seleccione al menos una imagen.', 'warning'); return; }

        const formData = new FormData();
        formData.append('id_liquidacion', liqId);
        formData.append('_token', '{{ csrf_token() }}');
        for (let i = 0; i < input.files.length; i++) {
            formData.append('imagenes[]', input.files[i]);
        }

        fetch('{{ route("reportes.subir_imagen_liquidacion") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(r => {
            if (r.success) {
                input.value = '';
                Swal.fire({ title: 'Subido', text: r.mensaje, icon: 'success', timer: 1500, showConfirmButton: false });
                // Recargar imágenes
                document.getElementById('formFiltros').dispatchEvent(new Event('submit'));
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    }

    // === ELIMINAR IMAGEN ===
    function eliminarImagenLiq(id) {
        Swal.fire({ title: '¿Eliminar imagen?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sí, eliminar' })
        .then(result => {
            if (result.isConfirmed) {
                fetch(`{{ url('/reportes/eliminar-imagen-liquidacion') }}/${id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(r => {
                    if (r.success) {
                        Swal.fire({ title: 'Eliminada', text: r.mensaje, icon: 'success', timer: 1200, showConfirmButton: false });
                        document.getElementById('formFiltros').dispatchEvent(new Event('submit'));
                    }
                });
            }
        });
    }

    // === HOVER PREVIEW ===
    function initHoverPreview() {
        document.addEventListener('mousemove', function(e) {
            const preview = document.querySelector('.img-preview-wrapper:hover .img-preview-hover, .img-preview-wrapper:hover .img-preview-hover-liq');
            if (preview) {
                let x = e.clientX + 15;
                let y = e.clientY - 120;
                if (y < 10) y = e.clientY + 15;
                if (x + 280 > window.innerWidth) x = e.clientX - 290;
                preview.style.left = x + 'px';
                preview.style.top = y + 'px';
            }
        });
    }

    // === EXPORTAR ===
    function exportarExcel() {
        const params = new URLSearchParams(new FormData(document.getElementById('formFiltros'))).toString();
        window.location.href = '{{ route("reportes.exportar_liquidacion_trabajo") }}?' + params;
    }

    function exportarPdf() {
        const params = new URLSearchParams(new FormData(document.getElementById('formFiltros'))).toString();
        window.open('{{ route("reportes.exportar_liquidacion_trabajo_pdf") }}?' + params, '_blank');
    }
</script>
@endsection
