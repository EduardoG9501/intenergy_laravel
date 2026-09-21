@extends('layouts.app')

@section('title', 'Detalle Informe Diario - Intenergy')

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
                            <input type="hidden" name="id_producto" id="selectArticulo" required>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control bg-white" id="nombreArticulo" placeholder="Selecciona artículo..." readonly>
                                <button type="button" class="btn btn-outline-primary" onclick="abrirModalArticulos()">
                                    <i class="fa-solid fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" id="clearArticulo" onclick="limpiarArticulo()" style="display:none;">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
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
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Artículo</th>
                                    <th>Cantidad</th>
                                    <th>Lote</th>
                                    <th class="text-center pe-3" style="width:80px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($informe->articulos as $art)
                                    <tr>
                                        <td class="ps-3 fw-semibold">{{ $art->producto?->nombre }}</td>
                                        <td>{{ number_format($art->cantidad, 2) }}</td>
                                        <td>{{ $art->lote ?: '-' }}</td>
                                        <td class="text-center pe-3">
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

<!-- MODAL SELECCIONAR ARTICULO -->
<div class="modal fade" id="modalSeleccionarArticulo" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1060;">
        <div class="modal-content border-0 shadow-lg" style="z-index: 1060;">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-box me-2"></i> Seleccionar Artículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" class="form-control" id="buscadorArticuloModal" placeholder="Escribe para buscar artículo..." oninput="filtrarArticulosModal()">
                </div>
                <div class="list-group" id="listaArticulosModal" style="max-height: 350px; overflow-y: auto;"></div>
                <div id="sinResultadosArticulo" class="text-center text-muted py-4" style="display:none;">
                    <i class="fa-solid fa-search fa-2x mb-2 text-warning"></i>
                    <p class="mb-0">No se encontraron artículos</p>
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

    // === TAB PERSISTENCE ===
    function saveTabState(subTab) {
        if (subTab) sessionStorage.setItem('inf_subTab', subTab);
    }

    function restoreTabState() {
        const subTab = sessionStorage.getItem('inf_subTab');
        if (subTab) {
            const tabBtn = document.querySelector(`[data-bs-target="#${subTab}"]`);
            if (tabBtn) {
                bootstrap.Tab.getOrCreateInstance(tabBtn).show();
            }
        }
        sessionStorage.removeItem('inf_subTab');
    }

    document.addEventListener('DOMContentLoaded', restoreTabState);

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

    function eliminarArticulo(idArt) {
        confirmar('¿Remover artículo?', () => {
            saveTabState('pane-articulos');
            postAction(`/informes-diarios/${INF_ID}/articulo/${idArt}/delete`)
            .then(r => r.success ? toast('Artículo removido', 'pane-articulos') : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === BUSCADOR DE ARTICULOS EN MODAL ===
    var arrArticulos = {!! json_encode($articulos->map(fn($a) => ['id' => $a->id_producto, 'nombre' => $a->nombre])) !!};

    function abrirModalArticulos() {
        document.getElementById('buscadorArticuloModal').value = '';
        renderizarListaArticulos('');
        var modal = new bootstrap.Modal(document.getElementById('modalSeleccionarArticulo'));
        modal.show();
        setTimeout(function() { document.getElementById('buscadorArticuloModal').focus(); }, 400);
    }

    function renderizarListaArticulos(busqueda) {
        var lista = document.getElementById('listaArticulosModal');
        var sinResultados = document.getElementById('sinResultadosArticulo');
        var busq = busqueda.toLowerCase().trim();
        var html = '';
        var visibles = 0;

        for (var i = 0; i < arrArticulos.length; i++) {
            var art = arrArticulos[i];
            var nombreLower = art.nombre.toLowerCase();
            if (busq === '' || nombreLower.indexOf(busq) !== -1) {
                html += '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" onclick="seleccionarArticuloModal(' + art.id + ', this.getAttribute(\x27data-nombre\x27))" data-nombre="' + art.nombre.replace(/"/g, '&quot;') + '">' +
                    '<span>' + art.nombre + '</span>' +
                    '<i class="fa-solid fa-chevron-right text-muted small"></i>' +
                    '</button>';
                visibles++;
            }
        }

        lista.innerHTML = html;
        sinResultados.style.display = visibles === 0 ? '' : 'none';
    }

    function filtrarArticulosModal() {
        var busqueda = document.getElementById('buscadorArticuloModal').value;
        renderizarListaArticulos(busqueda);
    }

    function seleccionarArticuloModal(id, nombre) {
        document.getElementById('selectArticulo').value = id;
        document.getElementById('nombreArticulo').value = nombre;
        document.getElementById('clearArticulo').style.display = '';
        var modal = bootstrap.Modal.getInstance(document.getElementById('modalSeleccionarArticulo'));
        if (modal) modal.hide();
    }

    function limpiarArticulo() {
        document.getElementById('selectArticulo').value = '';
        document.getElementById('nombreArticulo').value = '';
        document.getElementById('clearArticulo').style.display = 'none';
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
</script>
@endsection
