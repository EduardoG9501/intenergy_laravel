@extends('layouts.app')

@section('title', 'Informe Ordenes de Trabajo - Intenergy')

@section('styles')
<style>
    .search-input { border-left: 0; }
    .search-input:focus { border-color: #dee2e6; box-shadow: none; }
    .search-modal .list-group-item { cursor: pointer; padding: 8px 12px; font-size: 13px; }
    .search-modal .list-group-item:hover { background-color: #f0f7ff; }
    .filter-active { border-color: #0d6efd !important; background-color: #f0f7ff !important; }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Informe de Ordenes de Trabajo</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="#">Reportes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ordenes de Trabajo</li>
            </ol>
        </nav>
    </div>
</div>

<!-- FILTROS -->
<div class="card card-custom p-4 mb-4">
    <form id="formFiltros" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-2">
            <label class="form-label fw-semibold">Fecha Desde:</label>
            <input type="date" class="form-control" name="fecha_desde" value="{{ date('Y-m-01') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Fecha Hasta:</label>
            <input type="date" class="form-control" name="fecha_hasta" value="{{ date('Y-m-d') }}">
        </div>

        <!-- Proyecto con búsqueda -->
        <div class="col-md-2">
            <label class="form-label fw-semibold">Proyecto:</label>
            <input type="hidden" name="proyecto" id="filtro_proyecto_id">
            <div class="input-group">
                <input type="text" class="form-control bg-white" id="filtro_proyecto_nombre" readonly placeholder="Todos" style="font-size:13px;">
                <button type="button" class="btn btn-outline-primary" onclick="abrirModalProyecto()" title="Buscar proyecto">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <button type="button" class="btn btn-outline-danger" id="filtro_proyecto_clear" style="display:none;" onclick="limpiarProyecto()" title="Limpiar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Obra con búsqueda (dependiente del proyecto) -->
        <div class="col-md-2">
            <label class="form-label fw-semibold">Nombre de la Obra:</label>
            <input type="hidden" name="obra" id="filtro_obra_id">
            <div class="input-group">
                <input type="text" class="form-control bg-white" id="filtro_obra_nombre" readonly placeholder="Todas" style="font-size:13px;">
                <button type="button" class="btn btn-outline-primary" onclick="abrirModalObra()" title="Buscar obra">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <button type="button" class="btn btn-outline-danger" id="filtro_obra_clear" style="display:none;" onclick="limpiarObra()" title="Limpiar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Estado -->
        <div class="col-md-2">
            <label class="form-label fw-semibold">Estado:</label>
            <select class="form-select" name="estado">
                <option value="">Todos</option>
                @foreach($estados as $e)
                    <option value="{{ $e->id_estado_orden }}">{{ $e->estado }}</option>
                @endforeach
            </select>
        </div>

        <!-- Botones -->
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-dark flex-grow-1 py-2" id="btnBuscar">
                <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
            </button>
        </div>
    </form>

    <!-- Botones de exportación -->
    <div class="d-flex justify-content-end gap-2 mt-3" id="exportButtons" style="display:none !important;">
        <a href="#" class="btn btn-success btn-sm" id="btnExportExcel" onclick="exportarExcel()">
            <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
        </a>
        <a href="#" class="btn btn-danger btn-sm" id="btnExportPdf" onclick="exportarPdf()">
            <i class="fa-solid fa-file-pdf me-1"></i> Exportar a PDF
        </a>
    </div>
</div>

<!-- RESULTADOS -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaResultados">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">Id Orden</th>
                    <th>Identificador</th>
                    <th>Proyecto</th>
                    <th>Nombre De La Obra</th>
                    <th>Fecha</th>
                    <th>Lugar</th>
                    <th>Observación</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="tbodyResultados">
                @forelse($resultados as $row)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $row->id_orden }}</td>
                        <td class="fw-semibold text-primary">{{ $row->identificador }}</td>
                        <td>{{ $row->proyecto }}</td>
                        <td>{{ $row->obra }}</td>
                        <td>{{ $row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '-' }}</td>
                        <td>{{ $row->lugar ?: '-' }}</td>
                        <td>{{ $row->observacion ?: '-' }}</td>
                        <td>
                            @php
                                $badgeClass = match($row->id_estado_orden) {
                                    1 => 'bg-success',
                                    2 => 'bg-warning text-dark',
                                    default => 'bg-secondary'
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $row->estado_orden }}</span>
                        </td>
                    </tr>
                @empty
                    <tr id="rowEmpty">
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-clipboard-list fa-2x mb-3 text-warning"></i>
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

<!-- MODAL BUSCAR PROYECTO -->
<div class="modal fade search-modal" id="modalProyecto" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1060;">
        <div class="modal-content border-0 shadow-lg" style="z-index: 1060;">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-building me-2"></i> Seleccionar Proyecto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" class="form-control search-input" id="buscadorProyecto"
                           placeholder="Escribe para buscar proyecto..." oninput="buscarProyecto()">
                </div>
                <div class="list-group" id="listaProyectos" style="max-height: 300px; overflow-y: auto;"></div>
                <div id="sinResultadosProyecto" class="text-center text-muted py-4" style="display:none;">
                    <i class="fa-solid fa-search fa-2x mb-2 text-warning"></i>
                    <p class="mb-0">No se encontraron proyectos</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL BUSCAR OBRA -->
<div class="modal fade search-modal" id="modalObra" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1060;">
        <div class="modal-content border-0 shadow-lg" style="z-index: 1060;">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-hard-hat me-2"></i> Seleccionar Obra</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" class="form-control search-input" id="buscadorObra"
                           placeholder="Escribe para buscar obra..." oninput="buscarObra()">
                </div>
                <div id="obraFiltroInfo" class="alert alert-info py-2 small mb-3" style="display:none;">
                    <i class="fa-solid fa-filter me-1"></i> Filtrado por proyecto: <strong id="obraFiltroNombre"></strong>
                </div>
                <div class="list-group" id="listaObras" style="max-height: 300px; overflow-y: auto;"></div>
                <div id="sinResultadosObra" class="text-center text-muted py-4" style="display:none;">
                    <i class="fa-solid fa-search fa-2x mb-2 text-warning"></i>
                    <p class="mb-0">No se encontraron obras</p>
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

    // === BÚSQUEDA AJAX GENÉRICA ===
    function buscarAJAX(url, params, listaId, sinResultadosId, callback) {
        fetch(url + '?' + new URLSearchParams(params), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const lista = document.getElementById(listaId);
            const sinRes = document.getElementById(sinResultadosId);
            if (data.length === 0) {
                lista.innerHTML = '';
                sinRes.style.display = '';
                return;
            }
            sinRes.style.display = 'none';
            callback(lista, data);
        });
    }

    // === PROYECTO ===
    function abrirModalProyecto() {
        document.getElementById('buscadorProyecto').value = '';
        buscarProyecto();
        new bootstrap.Modal(document.getElementById('modalProyecto')).show();
        setTimeout(() => document.getElementById('buscadorProyecto').focus(), 400);
    }

    function buscarProyecto() {
        const q = document.getElementById('buscadorProyecto').value;
        buscarAJAX('{{ route("reportes.buscar_proyectos") }}', { q }, 'listaProyectos', 'sinResultadosProyecto', (lista, data) => {
            lista.innerHTML = data.map(p =>
                `<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                    onclick="seleccionarProyecto(${p.id}, '${p.nombre.replace(/'/g, "\\'")}')">
                    <span>${p.nombre}</span>
                    <i class="fa-solid fa-chevron-right text-muted small"></i>
                </button>`
            ).join('');
        });
    }

    function seleccionarProyecto(id, nombre) {
        document.getElementById('filtro_proyecto_id').value = id;
        document.getElementById('filtro_proyecto_nombre').value = nombre;
        document.getElementById('filtro_proyecto_clear').style.display = '';
        // Limpiar obra seleccionada al cambiar proyecto
        limpiarObra();
        bootstrap.Modal.getInstance(document.getElementById('modalProyecto')).hide();
    }

    function limpiarProyecto() {
        document.getElementById('filtro_proyecto_id').value = '';
        document.getElementById('filtro_proyecto_nombre').value = '';
        document.getElementById('filtro_proyecto_clear').style.display = 'none';
        limpiarObra();
    }

    // === OBRA (dependiente del proyecto) ===
    function abrirModalObra() {
        document.getElementById('buscadorObra').value = '';
        const idProyecto = document.getElementById('filtro_proyecto_id').value;
        const infoDiv = document.getElementById('obraFiltroInfo');
        const infoNombre = document.getElementById('obraFiltroNombre');
        if (idProyecto) {
            infoDiv.style.display = '';
            infoNombre.textContent = document.getElementById('filtro_proyecto_nombre').value;
        } else {
            infoDiv.style.display = 'none';
        }
        buscarObra();
        new bootstrap.Modal(document.getElementById('modalObra')).show();
        setTimeout(() => document.getElementById('buscadorObra').focus(), 400);
    }

    function buscarObra() {
        const q = document.getElementById('buscadorObra').value;
        const idProyecto = document.getElementById('filtro_proyecto_id').value;
        buscarAJAX('{{ route("reportes.buscar_obras") }}', { q, id_proyecto: idProyecto }, 'listaObras', 'sinResultadosObra', (lista, data) => {
            lista.innerHTML = data.map(o =>
                `<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                    onclick="seleccionarObra(${o.id}, '${o.nombre.replace(/'/g, "\\'")}')">
                    <span>${o.nombre}</span>
                    <i class="fa-solid fa-chevron-right text-muted small"></i>
                </button>`
            ).join('');
        });
    }

    function seleccionarObra(id, nombre) {
        document.getElementById('filtro_obra_id').value = id;
        document.getElementById('filtro_obra_nombre').value = nombre;
        document.getElementById('filtro_obra_clear').style.display = '';
        bootstrap.Modal.getInstance(document.getElementById('modalObra')).hide();
    }

    function limpiarObra() {
        document.getElementById('filtro_obra_id').value = '';
        document.getElementById('filtro_obra_nombre').value = '';
        document.getElementById('filtro_obra_clear').style.display = 'none';
    }

    // === BÚSQUEDA AJAX CON FILTROS ===
    document.getElementById('formFiltros').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const params = new URLSearchParams(formData).toString();

        document.getElementById('btnBuscar').innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Buscando...';

        fetch('{{ route("reportes.informe_ordenes_trabajo") }}?' + params + '&buscar=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('tbodyResultados').innerHTML = data.html;
            document.getElementById('resultCount').textContent = data.count;
            document.getElementById('resultInfo').style.display = data.count > 0 ? '' : 'none';
            document.getElementById('exportButtons').style.display = data.count > 0 ? 'flex' : 'none';
            document.getElementById('btnBuscar').innerHTML = '<i class="fa-solid fa-magnifying-glass me-1"></i> Buscar';
        });
    });

    // === EXPORTAR ===
    function exportarExcel() {
        const formData = new FormData(document.getElementById('formFiltros'));
        const params = new URLSearchParams(formData).toString();
        window.location.href = '{{ route("reportes.exportar_ordenes_trabajo") }}?' + params;
    }

    function exportarPdf() {
        const formData = new FormData(document.getElementById('formFiltros'));
        const params = new URLSearchParams(formData).toString();
        window.open('{{ route("reportes.exportar_ordenes_trabajo_pdf") }}?' + params, '_blank');
    }
</script>
@endsection
