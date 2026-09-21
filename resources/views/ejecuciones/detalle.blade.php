@extends('layouts.app')

@section('title', 'Detalle de Ejecución de Obra - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Detalle de Ejecución: <span class="text-primary font-monospace">EJEC-{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}</span></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ejecuciones.index') }}">Ejecuciones</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalle</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('ejecuciones.index') }}" class="btn btn-outline-dark px-4 py-2">
        <i class="fa-solid fa-arrow-left me-2"></i> Volver a Lista
    </a>
</div>

<div class="row g-4">
    <!-- Información General -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 h-100">
            <h4 class="fw-bold mb-4">Ficha de Ejecución</h4>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Orden de Trabajo:</span>
                    <span class="fw-bold text-dark font-monospace">{{ $ejecucion->orden?->identificador }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Nombre de Obra:</span>
                    <span class="fw-semibold">{{ $ejecucion->orden?->obra?->nombre }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Proyecto:</span>
                    <span class="fw-semibold text-truncate" style="max-width: 180px;">{{ $ejecucion->orden?->proyecto?->nombre }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Fecha Ejecución:</span>
                    <span>{{ $ejecucion->fecha_solicitud }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">¿Es Feriado?:</span>
                    <span>{{ $ejecucion->feriado ? 'Sí (Recargo)' : 'No (Normal)' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <span class="text-muted fw-semibold">Estado Proceso:</span>
                    <span class="badge @if($ejecucion->id_estado_ejecucion_obra == 1) bg-warning text-dark @else bg-success @endif px-3 py-2">
                        {{ $ejecucion->estadoEjecucion?->estado }}
                    </span>
                </li>
            </ul>

            <div class="bg-light p-3 rounded mb-4">
                <span class="fw-semibold text-muted d-block mb-1">Observación:</span>
                <p class="mb-0 text-dark small">{{ $ejecucion->observacion ?: 'Sin observaciones.' }}</p>
            </div>

            @if($ejecucion->id_estado_ejecucion_obra == 1)
                <!-- Botón de Contabilizar -->
                <button type="button" class="btn btn-success w-100 py-3 fw-bold shadow mt-2" onclick="confirmarContabilizacion()">
                    <i class="fa-solid fa-cloud-arrow-down me-2"></i> Descargar Stock de Bodega
                </button>
            @endif
        </div>
    </div>

    <!-- Gestión de Materiales y Horas -->
    <div class="col-lg-8">
        <!-- Pestañas -->
        <ul class="nav nav-pills nav-fill mb-4" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold py-3" id="pills-materiales-tab" data-bs-toggle="pill" data-bs-target="#pills-materiales" type="button" role="tab" aria-controls="pills-materiales" aria-selected="true">
                    <i class="fa-solid fa-boxes-stacked me-2"></i> Materiales Utilizados
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-3" id="pills-horas-tab" data-bs-toggle="pill" data-bs-target="#pills-horas" type="button" role="tab" aria-controls="pills-horas" aria-selected="false">
                    <i class="fa-solid fa-user-clock me-2"></i> Mano de Obra Diaria
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-3" id="pills-informe-tab" data-bs-toggle="pill" data-bs-target="#pills-informe" type="button" role="tab" aria-controls="pills-informe" aria-selected="false">
                    <i class="fa-solid fa-file-lines me-2"></i> Informe Diario
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <!-- TAB MATERIALES -->
            <div class="tab-pane fade show active" id="pills-materiales" role="tabpanel" aria-labelledby="pills-materiales-tab">
                @if($ejecucion->id_estado_ejecucion_obra == 1)
                    <!-- Agregar Material -->
                    <div class="card card-custom p-4 mb-4">
                        <h4 class="fw-bold mb-3">Registrar Material Utilizado</h4>
                        <form id="formAddMaterial" class="row g-3">
                            @csrf
                            <input type="hidden" name="id_producto" id="mat_id_producto" required>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Material / Artículo *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-white" id="mat_nombre_articulo" readonly placeholder="Haz clic en buscar para seleccionar artículo..." required>
                                    <button type="button" class="btn btn-outline-primary" onclick="abrirModalArticuloMat()" title="Buscar artículo">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" id="mat_clear_articulo" style="display:none;" onclick="limpiarArticuloMat()" title="Limpiar selección">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Cantidad *</label>
                                <input type="number" step="0.01" class="form-control" name="cantidad" required placeholder="0.00">
                            </div>
                            <div class="col-md-12 d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-primary-custom px-4 py-2">
                                    <i class="fa-solid fa-plus me-1"></i> Añadir Material
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <!-- Tabla Materiales -->
                <div class="card card-custom p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Artículo</th>
                                    <th>Bodega Origen</th>
                                    <th>Cantidad</th>
                                    <th>Contabilizado</th>
                                    @if($ejecucion->id_estado_ejecucion_obra == 1)
                                        <th class="text-center pe-4">Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($detalles as $det)
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">{{ $det->producto?->nombre }}</td>
                                        <td>{{ $det->bodegaLugar?->nombreBodega }}</td>
                                        <td class="fw-bold text-primary">{{ number_format($det->cantidad, 2) }}</td>
                                        <td>
                                            @if($det->Contabilizado)
                                                <span class="badge bg-success">Sí (Descargado)</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            @endif
                                        </td>
                                        @if($ejecucion->id_estado_ejecucion_obra == 1)
                                            <td class="text-center pe-4">
                                                @if(!$det->Contabilizado)
                                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarMaterial({{ $det->id_ejecucion_obra_detalle_materiales_utilizar }})">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted small">Cerrado</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-boxes-stacked fa-2x mb-3 text-warning"></i>
                                            <p class="mb-0">No se han registrado materiales consumidos hoy.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB MANO DE OBRA -->
            <div class="tab-pane fade" id="pills-horas" role="tabpanel" aria-labelledby="pills-horas-tab">
                @if($ejecucion->id_estado_ejecucion_obra == 1)
                    <!-- Agregar Mano de Obra -->
                    <div class="card card-custom p-4 mb-4">
                        <h4 class="fw-bold mb-3">Registrar Mano de Obra Diaria</h4>
                        <form id="formAddHoras" class="row g-3">
                            @csrf
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Trabajador / Empleado *</label>
                                <select class="form-select" name="id_empleado" required>
                                    <option value="" selected disabled>Selecciona Empleado</option>
                                    @foreach($empleados as $emp)
                                        <option value="{{ $emp->id_empleados }}">{{ $emp->nombres_apellidos }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Hora Entrada *</label>
                                <input type="time" class="form-control" name="hora_entrada" required value="08:00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Hora Salida *</label>
                                <input type="time" class="form-control" name="hora_salida" required value="17:00">
                            </div>
                            <div class="col-md-12 d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-primary-custom px-4 py-2">
                                    <i class="fa-solid fa-plus me-1"></i> Registrar Horas
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <!-- Tabla Horas -->
                <div class="card card-custom p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Empleado</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>H. Normales</th>
                                    <th>H. Extras</th>
                                    <th>H. Extraord.</th>
                                    @if($ejecucion->id_estado_ejecucion_obra == 1)
                                        <th class="text-center pe-4">Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($horasDiarias as $hd)
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">{{ $hd->empleado?->nombres_apellidos }}</td>
                                        <td>{{ date('H:i', strtotime($hd->hora_entrada)) }}</td>
                                        <td>{{ date('H:i', strtotime($hd->hora_salida)) }}</td>
                                        <td class="fw-bold text-success">{{ number_format($hd->cantidad_normales ?? $hd->cantidad_horas_normal, 2) }}</td>
                                        <td class="fw-bold text-primary">{{ number_format($hd->cantidad_extras ?? $hd->cantidad_horas_extra, 2) }}</td>
                                        <td class="fw-bold text-danger">{{ number_format($hd->cantidad_extraordinarias ?? $hd->cantidad_horas_extraordinaria, 2) }}</td>
                                        @if($ejecucion->id_estado_ejecucion_obra == 1)
                                            <td class="text-center pe-4">
                                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarHoras({{ $hd->id_horas_trabajo_diario_detalle }})">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-user-slash fa-2x mb-3 text-warning"></i>
                                            <p class="mb-0">No se han registrado horas de trabajo hoy.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB INFORME DIARIO -->
            <div class="tab-pane fade" id="pills-informe" role="tabpanel" aria-labelledby="pills-informe-tab">
                @if($ejecucion->id_estado_ejecucion_obra == 1)
                    <div class="card card-custom p-4 mb-4">
                        <h4 class="fw-bold mb-3">Registrar Informe Diario</h4>
                        <form id="formAddInforme" class="row g-3">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha *</label>
                                <input type="date" class="form-control" name="fecha" required value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Bodega (Lugar)</label>
                                <input type="text" class="form-control" value="{{ $bodegaActiva?->nombreBodega ?? 'Sin bodega activa' }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Descripción</label>
                                <textarea class="form-control" name="descripcion" rows="2" placeholder="Descripción de actividades..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control" name="observacion" rows="2" placeholder="Observaciones adicionales..."></textarea>
                            </div>
                            <div class="col-md-12 d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-primary-custom px-4 py-2">
                                    <i class="fa-solid fa-plus me-1"></i> Agregar Informe
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                @forelse($informesDiarios as $inf)
                    <div class="card card-custom mb-4 overflow-hidden" id="informe-{{ $inf->id_informe_diario_ejecucion }}">
                        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#collapse-informe-{{ $inf->id_informe_diario_ejecucion }}">
                            <div>
                                <h5 class="fw-bold mb-0">
                                    <i class="fa-solid fa-file-lines me-2"></i>
                                    INF-{{ str_pad($inf->id_informe_diario_ejecucion, 5, '0', STR_PAD_LEFT) }}
                                    <span class="ms-3 badge bg-light text-dark">{{ $inf->fecha }}</span>
                                    @if($inf->lugar)
                                        <span class="ms-2 badge bg-info text-white"><i class="fa-solid fa-location-dot me-1"></i> {{ $inf->lugar }}</span>
                                    @endif
                                </h5>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if($inf->empleados->count())
                                    <span class="badge bg-success"><i class="fa-solid fa-users me-1"></i> {{ $inf->empleados->count() }}</span>
                                @endif
                                @if($inf->articulos->count())
                                    <span class="badge bg-primary"><i class="fa-solid fa-box me-1"></i> {{ $inf->articulos->count() }}</span>
                                @endif
                                @if($inf->imagenes->count())
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-image me-1"></i> {{ $inf->imagenes->count() }}</span>
                                @endif
                                @if($inf->detalles->count())
                                    <span class="badge bg-secondary"><i class="fa-solid fa-align-left me-1"></i> {{ $inf->detalles->count() }}</span>
                                @endif
                                @if($ejecucion->id_estado_ejecucion_obra == 1)
                                    <button class="btn btn-sm btn-outline-info ms-2" onclick="editarInforme({{ $inf->id_informe_diario_ejecucion }}, '{{ $inf->fecha }}', '{{ addslashes($inf->descripcion) }}', '{{ addslashes($inf->observacion) }}')" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarInforme({{ $inf->id_informe_diario_ejecucion }})" title="Eliminar">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                @endif
                                <i class="fa-solid fa-chevron-down text-white ms-2"></i>
                            </div>
                        </div>

                        <div class="collapse" id="collapse-informe-{{ $inf->id_informe_diario_ejecucion }}">
                            @if($inf->descripcion || $inf->observacion)
                                <div class="px-4 pt-4">
                                    @if($inf->descripcion)
                                        <p class="mb-1"><strong>Descripción:</strong> {{ $inf->descripcion }}</p>
                                    @endif
                                    @if($inf->observacion)
                                        <p class="mb-0 text-muted"><strong>Observaciones:</strong> {{ $inf->observacion }}</p>
                                    @endif
                                </div>
                            @endif

                            <ul class="nav nav-tabs nav-fill mx-3 mt-3" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active fw-bold" id="sub-tab-empleados-{{ $inf->id_informe_diario_ejecucion }}" data-bs-toggle="tab" data-bs-target="#sub-empleados-{{ $inf->id_informe_diario_ejecucion }}" type="button" role="tab">
                                        <i class="fa-solid fa-users me-1"></i> Empleados <span class="badge bg-dark ms-1">{{ $inf->empleados->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-bold" id="sub-tab-articulos-{{ $inf->id_informe_diario_ejecucion }}" data-bs-toggle="tab" data-bs-target="#sub-articulos-{{ $inf->id_informe_diario_ejecucion }}" type="button" role="tab">
                                        <i class="fa-solid fa-box me-1"></i> Artículos <span class="badge bg-dark ms-1">{{ $inf->articulos->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-bold" id="sub-tab-imagenes-{{ $inf->id_informe_diario_ejecucion }}" data-bs-toggle="tab" data-bs-target="#sub-imagenes-{{ $inf->id_informe_diario_ejecucion }}" type="button" role="tab">
                                        <i class="fa-solid fa-image me-1"></i> Imágenes <span class="badge bg-dark ms-1">{{ $inf->imagenes->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-bold" id="sub-tab-descripcion-{{ $inf->id_informe_diario_ejecucion }}" data-bs-toggle="tab" data-bs-target="#sub-descripcion-{{ $inf->id_informe_diario_ejecucion }}" type="button" role="tab">
                                        <i class="fa-solid fa-align-left me-1"></i> Descripción <span class="badge bg-dark ms-1">{{ $inf->detalles->count() }}</span>
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content p-4">
                                <!-- SUB-TAB: EMPLEADOS -->
                                <div class="tab-pane fade show active" id="sub-empleados-{{ $inf->id_informe_diario_ejecucion }}" role="tabpanel">
                                    @if($ejecucion->id_estado_ejecucion_obra == 1)
                                        <form class="row g-2 mb-3" onsubmit="agregarEmpleado(event, {{ $inf->id_informe_diario_ejecucion }})">
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
                                    @endif
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-3">Empleado</th>
                                                    @if($ejecucion->id_estado_ejecucion_obra == 1)
                                                        <th class="text-center pe-3" style="width:80px;">Acción</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($inf->empleados as $emp)
                                                    <tr>
                                                        <td class="ps-3 fw-semibold">{{ $emp->empleado?->nombres_apellidos }}</td>
                                                        @if($ejecucion->id_estado_ejecucion_obra == 1)
                                                            <td class="text-center pe-3">
                                                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarEmpleado({{ $inf->id_informe_diario_ejecucion }}, {{ $emp->id_informe_diario_empleado }})">
                                                                    <i class="fa-solid fa-xmark"></i>
                                                                </button>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="2" class="text-center text-muted py-3">Sin empleados asignados.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- SUB-TAB: ARTICULOS -->
                                <div class="tab-pane fade" id="sub-articulos-{{ $inf->id_informe_diario_ejecucion }}" role="tabpanel">
                                    @if($ejecucion->id_estado_ejecucion_obra == 1)
                                        <form class="row g-2 mb-3" onsubmit="agregarArticulo(event, {{ $inf->id_informe_diario_ejecucion }})">
                                            @csrf
                                            <div class="col-md-5">
                                                <label class="form-label fw-semibold small">Artículo *</label>
                                                <input type="hidden" name="id_producto" id="selectArticulo-{{ $inf->id_informe_diario_ejecucion }}" required>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control bg-white" id="nombreArticulo-{{ $inf->id_informe_diario_ejecucion }}" placeholder="Selecciona artículo..." readonly>
                                                    <button type="button" class="btn btn-outline-primary" onclick="abrirModalArticulos({{ $inf->id_informe_diario_ejecucion }})">
                                                        <i class="fa-solid fa-search"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" id="clearArticulo-{{ $inf->id_informe_diario_ejecucion }}" onclick="limpiarArticulo({{ $inf->id_informe_diario_ejecucion }})" style="display:none;">
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
                                    @endif
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-3">Artículo</th>
                                                    <th>Cantidad</th>
                                                    <th>Lote</th>
                                                    @if($ejecucion->id_estado_ejecucion_obra == 1)
                                                        <th class="text-center pe-3" style="width:80px;">Acción</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($inf->articulos as $art)
                                                    <tr>
                                                        <td class="ps-3 fw-semibold">{{ $art->producto?->nombre }}</td>
                                                        <td>{{ number_format($art->cantidad, 2) }}</td>
                                                        <td>{{ $art->lote ?: '-' }}</td>
                                                        @if($ejecucion->id_estado_ejecucion_obra == 1)
                                                            <td class="text-center pe-3">
                                                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarArticulo({{ $inf->id_informe_diario_ejecucion }}, {{ $art->id_informe_diario_articulo }})">
                                                                    <i class="fa-solid fa-xmark"></i>
                                                                </button>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center text-muted py-3">Sin artículos registrados.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- SUB-TAB: IMAGENES -->
                                <div class="tab-pane fade" id="sub-imagenes-{{ $inf->id_informe_diario_ejecucion }}" role="tabpanel">
                                    @if($ejecucion->id_estado_ejecucion_obra == 1)
                                        <form class="row g-2 mb-3" onsubmit="agregarImagen(event, {{ $inf->id_informe_diario_ejecucion }})">
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
                                    @endif
                                    <div class="row g-3">
                                        @forelse($inf->imagenes as $img)
                                            <div class="col-md-3">
                                                <div class="card h-100 border-0 shadow-sm">
                                                    <img src="{{ $img->ruta_imagen }}" class="card-img-top" style="height:150px; object-fit:cover;" alt="{{ $img->descripcion }}">
                                                    <div class="card-body p-2">
                                                        <p class="card-text small text-muted mb-1">{{ $img->descripcion ?: 'Sin descripción' }}</p>
                                                        @if($ejecucion->id_estado_ejecucion_obra == 1)
                                                            <button class="btn btn-sm btn-outline-danger w-100" onclick="eliminarImagen({{ $inf->id_informe_diario_ejecucion }}, {{ $img->id_informe_diario_imagen }})">
                                                                <i class="fa-solid fa-trash-can me-1"></i> Eliminar
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="col-12 text-center text-muted py-4">Sin imágenes adjuntas.</div>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- SUB-TAB: DESCRIPCION -->
                                <div class="tab-pane fade" id="sub-descripcion-{{ $inf->id_informe_diario_ejecucion }}" role="tabpanel">
                                    @if($ejecucion->id_estado_ejecucion_obra == 1)
                                        <form class="row g-2 mb-3" onsubmit="agregarDescripcion(event, {{ $inf->id_informe_diario_ejecucion }})">
                                            @csrf
                                            <div class="col-md-10">
                                                <input type="text" class="form-control form-control-sm" name="descripcion" required placeholder="Descripción detallada de la actividad...">
                                            </div>
                                            <div class="col-md-2 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-sm btn-primary-custom"><i class="fa-solid fa-plus me-1"></i> Agregar</button>
                                            </div>
                                        </form>
                                    @endif
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-3">Descripción</th>
                                                    @if($ejecucion->id_estado_ejecucion_obra == 1)
                                                        <th class="text-center pe-3" style="width:80px;">Acción</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($inf->detalles as $det)
                                                    <tr>
                                                        <td class="ps-3">{{ $det->descripcion }}</td>
                                                        @if($ejecucion->id_estado_ejecucion_obra == 1)
                                                            <td class="text-center pe-3">
                                                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarDescripcion({{ $inf->id_informe_diario_ejecucion }}, {{ $det->id_informe_diario_detalle }})">
                                                                    <i class="fa-solid fa-xmark"></i>
                                                                </button>
                                                            </td>
                                                        @endif
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
                @empty
                    <div class="card card-custom p-5 text-center">
                        <i class="fa-solid fa-file-lines fa-3x mb-3 text-warning"></i>
                        <h5 class="text-muted">No hay informes diarios registrados</h5>
                        <p class="text-muted mb-0">Crea un informe usando el formulario superior.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR INFORME -->
<div class="modal fade" id="modalEditInforme" tabindex="-1" aria-labelledby="modalEditInformeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0 bg-info text-white">
                <h4 class="fw-bold mb-0" id="modalEditInformeLabel">Editar Informe Diario</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditInforme">
                @csrf
                <input type="hidden" name="id_informe" id="edit_id_informe">
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha *</label>
                            <input type="date" class="form-control" name="fecha" id="edit_fecha" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Observaciones</label>
                            <textarea class="form-control" name="observacion" id="edit_observacion" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-danger px-4 py-2 me-2" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-success px-4 py-2">GUARDAR</button>
                </div>
            </form>
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
    @if($ejecucion->id_estado_ejecucion_obra == 1)
    const EJEC_ID = {{ $ejecucion->id_ejecucion_obra }};
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
    function saveTabState(mainTab, informeId, subTab) {
        sessionStorage.setItem('ejec_mainTab', mainTab);
        if (informeId) sessionStorage.setItem('ejec_informeId', informeId);
        if (subTab) sessionStorage.setItem('ejec_subTab', subTab);
    }

    function restoreTabState() {
        const mainTab = sessionStorage.getItem('ejec_mainTab');
        const informeId = sessionStorage.getItem('ejec_informeId');
        const subTab = sessionStorage.getItem('ejec_subTab');

        if (mainTab) {
            const tabBtn = document.querySelector(`[data-bs-target="#${mainTab}"]`);
            if (tabBtn) {
                bootstrap.Tab.getOrCreateInstance(tabBtn).show();
            }
        }

        if (informeId) {
            const collapse = document.getElementById(`collapse-informe-${informeId}`);
            if (collapse && !collapse.classList.contains('show')) {
                new bootstrap.Collapse(collapse, { toggle: true });
            }
        }

        if (subTab && informeId) {
            setTimeout(() => {
                const subTabBtn = document.querySelector(`[data-bs-target="#${subTab}-${informeId}"]`);
                if (subTabBtn) {
                    bootstrap.Tab.getOrCreateInstance(subTabBtn).show();
                }
            }, 350);
        }

        sessionStorage.removeItem('ejec_mainTab');
        sessionStorage.removeItem('ejec_informeId');
        sessionStorage.removeItem('ejec_subTab');
    }

    document.addEventListener('DOMContentLoaded', restoreTabState);

    function reload() { location.reload(); }

    function toast(msg, mainTab, informeId, subTab) {
        if (mainTab) saveTabState(mainTab, informeId, subTab);
        Swal.fire({ title: msg, icon: 'success', timer: 1200, showConfirmButton: false });
        setTimeout(reload, 1200);
    }

    function confirmar(msg, fn) {
        Swal.fire({ title: msg, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#aaa', confirmButtonText: 'Sí, continuar' })
        .then(r => { if (r.isConfirmed) fn(); });
    }

    // === MAIN TAB: MATERIALES ===
    document.getElementById('formAddMaterial').addEventListener('submit', function(e) {
        e.preventDefault();
        saveTabState('pills-materiales');
        postForm(`{{ route("ejecuciones.storeDetail", $ejecucion->id_ejecucion_obra) }}`, new FormData(this))
        .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
    });

    function eliminarMaterial(id) {
        confirmar('¿Remover material?', () => {
            saveTabState('pills-materiales');
            postAction(`/ejecuciones/${EJEC_ID}/detalle/${id}/delete`)
            .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === MAIN TAB: MANO DE OBRA ===
    document.getElementById('formAddHoras').addEventListener('submit', function(e) {
        e.preventDefault();
        saveTabState('pills-horas');
        postForm(`{{ route("ejecuciones.storeDiario", $ejecucion->id_ejecucion_obra) }}`, new FormData(this))
        .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
    });

    function eliminarHoras(id) {
        confirmar('¿Eliminar registro de horas?', () => {
            saveTabState('pills-horas');
            postAction(`/ejecuciones/${EJEC_ID}/diario/${id}/delete`)
            .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // Contabilizar
    function confirmarContabilizacion() {
        confirmar('¿Descargar stock de bodega? Solo se descontarán los materiales nuevos pendientes. Puede seguir agregando más materiales después.', () => {
            postAction(`{{ route("ejecuciones.contabilizar", $ejecucion->id_ejecucion_obra) }}`)
            .then(r => {
                if (r.success) { Swal.fire({ title: '¡Descargado!', text: r.mensaje, icon: 'success', timer: 2000, showConfirmButton: false }).then(reload); }
                else { Swal.fire('Error', r.mensaje, 'error'); }
            });
        });
    }

    // === MAIN TAB: INFORME DIARIO ===
    document.getElementById('formAddInforme').addEventListener('submit', function(e) {
        e.preventDefault();
        saveTabState('pills-informe');
        postForm(`{{ route("ejecuciones.storeInformeDiario", $ejecucion->id_ejecucion_obra) }}`, new FormData(this))
        .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
    });

    function editarInforme(id, fecha, descripcion, observacion) {
        document.getElementById('edit_id_informe').value = id;
        document.getElementById('edit_fecha').value = fecha;
        document.getElementById('edit_descripcion').value = descripcion || '';
        document.getElementById('edit_observacion').value = observacion || '';
        new bootstrap.Modal(document.getElementById('modalEditInforme')).show();
    }

    document.getElementById('formEditInforme').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('edit_id_informe').value;
        saveTabState('pills-informe');
        postForm(`/ejecuciones/${EJEC_ID}/informe-diario/${id}/update`, new FormData(this))
        .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
    });

    function eliminarInforme(id) {
        confirmar('¿Eliminar informe diario?', () => {
            saveTabState('pills-informe');
            postAction(`/ejecuciones/${EJEC_ID}/informe-diario/${id}/delete`)
            .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === SUB-TAB: EMPLEADOS ===
    function agregarEmpleado(e, idInf) {
        e.preventDefault();
        saveTabState('pills-informe', idInf, 'sub-empleados');
        const fd = new FormData(e.target);
        postForm(`/ejecuciones/${EJEC_ID}/informe-diario/${idInf}/empleado`, fd)
        .then(r => r.success ? toast('Empleado agregado', 'pills-informe', idInf, 'sub-empleados') : Swal.fire('Error', r.mensaje, 'error'));
    }

    function eliminarEmpleado(idInf, idEmp) {
        confirmar('¿Remover empleado?', () => {
            saveTabState('pills-informe', idInf, 'sub-empleados');
            postAction(`/ejecuciones/${EJEC_ID}/informe-diario/${idInf}/empleado/${idEmp}/delete`)
            .then(r => r.success ? toast('Empleado removido', 'pills-informe', idInf, 'sub-empleados') : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === SUB-TAB: ARTICULOS ===
    function agregarArticulo(e, idInf) {
        e.preventDefault();
        saveTabState('pills-informe', idInf, 'sub-articulos');
        const fd = new FormData(e.target);
        postForm(`/ejecuciones/${EJEC_ID}/informe-diario/${idInf}/articulo`, fd)
        .then(r => r.success ? toast('Artículo agregado', 'pills-informe', idInf, 'sub-articulos') : Swal.fire('Error', r.mensaje, 'error'));
    }

    function eliminarArticulo(idInf, idArt) {
        confirmar('¿Remover artículo?', () => {
            saveTabState('pills-informe', idInf, 'sub-articulos');
            postAction(`/ejecuciones/${EJEC_ID}/informe-diario/${idInf}/articulo/${idArt}/delete`)
            .then(r => r.success ? toast('Artículo removido', 'pills-informe', idInf, 'sub-articulos') : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === BUSCADOR DE ARTICULOS EN MODAL ===
    var articuloActualInfId = null;
    var arrArticulos = {!! json_encode($articulos->map(fn($a) => ['id' => $a->id_producto, 'nombre' => $a->nombre])) !!};

    function abrirModalArticulos(informeId) {
        articuloActualInfId = informeId;
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
        if (!articuloActualInfId) return;
        document.getElementById('selectArticulo-' + articuloActualInfId).value = id;
        document.getElementById('nombreArticulo-' + articuloActualInfId).value = nombre;
        document.getElementById('clearArticulo-' + articuloActualInfId).style.display = '';
        var modal = bootstrap.Modal.getInstance(document.getElementById('modalSeleccionarArticulo'));
        if (modal) modal.hide();
    }

    function limpiarArticulo(informeId) {
        document.getElementById('selectArticulo-' + informeId).value = '';
        document.getElementById('nombreArticulo-' + informeId).value = '';
        document.getElementById('clearArticulo-' + informeId).style.display = 'none';
    }

    // === BUSCADOR DE ARTICULOS PARA MATERIALES (TAB PRINCIPAL) ===
    function abrirModalArticuloMat() {
        document.getElementById('buscadorArticuloModal').value = '';
        renderizarListaArticulos('');
        articuloActualInfId = '__mat__';
        var modal = new bootstrap.Modal(document.getElementById('modalSeleccionarArticulo'));
        modal.show();
        setTimeout(function() { document.getElementById('buscadorArticuloModal').focus(); }, 400);
    }

    function seleccionarArticuloModal(id, nombre) {
        if (articuloActualInfId === '__mat__') {
            document.getElementById('mat_id_producto').value = id;
            document.getElementById('mat_nombre_articulo').value = nombre;
            document.getElementById('mat_clear_articulo').style.display = '';
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalSeleccionarArticulo'));
            if (modal) modal.hide();
            return;
        }
        if (!articuloActualInfId) return;
        document.getElementById('selectArticulo-' + articuloActualInfId).value = id;
        document.getElementById('nombreArticulo-' + articuloActualInfId).value = nombre;
        document.getElementById('clearArticulo-' + articuloActualInfId).style.display = '';
        var modal = bootstrap.Modal.getInstance(document.getElementById('modalSeleccionarArticulo'));
        if (modal) modal.hide();
    }

    function limpiarArticuloMat() {
        document.getElementById('mat_id_producto').value = '';
        document.getElementById('mat_nombre_articulo').value = '';
        document.getElementById('mat_clear_articulo').style.display = 'none';
    }

    // === SUB-TAB: IMAGENES ===
    function agregarImagen(e, idInf) {
        e.preventDefault();
        saveTabState('pills-informe', idInf, 'sub-imagenes');
        const fd = new FormData(e.target);
        fetch(`/ejecuciones/${EJEC_ID}/informe-diario/${idInf}/imagen`, {
            method: 'POST', body: fd,
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json())
        .then(r => r.success ? toast('Imagen subida', 'pills-informe', idInf, 'sub-imagenes') : Swal.fire('Error', r.mensaje, 'error'));
    }

    function eliminarImagen(idInf, idImg) {
        confirmar('¿Eliminar imagen?', () => {
            saveTabState('pills-informe', idInf, 'sub-imagenes');
            postAction(`/ejecuciones/${EJEC_ID}/informe-diario/${idInf}/imagen/${idImg}/delete`)
            .then(r => r.success ? toast('Imagen eliminada', 'pills-informe', idInf, 'sub-imagenes') : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === SUB-TAB: DESCRIPCION ===
    function agregarDescripcion(e, idInf) {
        e.preventDefault();
        saveTabState('pills-informe', idInf, 'sub-descripcion');
        const fd = new FormData(e.target);
        postForm(`/ejecuciones/${EJEC_ID}/informe-diario/${idInf}/descripcion`, fd)
        .then(r => r.success ? toast('Descripción agregada', 'pills-informe', idInf, 'sub-descripcion') : Swal.fire('Error', r.mensaje, 'error'));
    }

    function eliminarDescripcion(idInf, idDesc) {
        confirmar('¿Eliminar descripción?', () => {
            saveTabState('pills-informe', idInf, 'sub-descripcion');
            postAction(`/ejecuciones/${EJEC_ID}/informe-diario/${idInf}/descripcion/${idDesc}/delete`)
            .then(r => r.success ? toast('Descripción eliminada', 'pills-informe', idInf, 'sub-descripcion') : Swal.fire('Error', r.mensaje, 'error'));
        });
    }
    @endif
</script>
@endsection
