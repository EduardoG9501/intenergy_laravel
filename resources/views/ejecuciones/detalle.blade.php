@extends('layouts.app')

@section('title', 'Detalle de Ejecución de Obra - Intenergy')

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
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Material / Artículo *</label>
                                <select class="form-select select2-articulos-ejec" name="id_producto" id="mat_id_producto" required>
                                    <option value="" disabled selected>Selecciona artículo...</option>
                                    @foreach($articulos as $art)
                                        <option value="{{ $art->id_producto }}">{{ $art->nombre }} - {{ $art->referencia ?: 'Sin Ref' }}</option>
                                    @endforeach
                                </select>
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
                    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Materiales Utilizados</h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-success fw-semibold" id="exportMaterialesExcel">
                                <i class="fa-solid fa-file-excel me-1"></i> Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-danger fw-semibold" id="exportMaterialesPDF">
                                <i class="fa-solid fa-file-pdf me-1"></i> PDF
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaMateriales">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Artículo</th>
                                    <th>Bodega Origen</th>
                                    <th>Cantidad</th>
                                    <th>Estado</th>
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
                                            @if($det->all_contabilizado)
                                                <span class="badge bg-success">Descargado</span>
                                            @elseif($det->any_contabilizado)
                                                <span class="badge bg-info">Parcial</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            @endif
                                        </td>
                                        @if($ejecucion->id_estado_ejecucion_obra == 1)
                                            <td class="text-center pe-4">
                                                @if(!$det->any_contabilizado)
                                                    <button class="btn btn-sm btn-outline-warning me-1" onclick="modificarCantidadMaterial({{ $det->id_producto }}, {{ $det->cantidad }})" title="Editar cantidad">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarMaterial({{ $det->id_producto }})" title="Eliminar material">
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
                    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Mano de Obra Diaria</h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-success fw-semibold" id="exportHorasExcel">
                                <i class="fa-solid fa-file-excel me-1"></i> Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-danger fw-semibold" id="exportHorasPDF">
                                <i class="fa-solid fa-file-pdf me-1"></i> PDF
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaHoras">
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
                                                <button class="btn btn-sm btn-outline-warning me-1" onclick="modificarHoras({{ $hd->id_horas_trabajo_diario_detalle }}, '{{ date('H:i', strtotime($hd->hora_entrada)) }}', '{{ date('H:i', strtotime($hd->hora_salida)) }}')" title="Editar horas">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
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
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center mb-3 rounded">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-file-lines me-2"></i> Informes Diarios</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success fw-semibold" id="exportInformeCompleto">
                            <i class="fa-solid fa-file-excel me-1"></i> Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-danger fw-semibold" id="exportInformeCompletoPDF">
                            <i class="fa-solid fa-file-pdf me-1"></i> PDF
                        </button>
                    </div>
                </div>

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
                                                <select class="form-select form-select-sm select2-articulos-informe" name="id_producto" required data-informe-id="{{ $inf->id_informe_diario_ejecucion }}">
                                                    <option value="" disabled selected>Selecciona artículo...</option>
                                                    @foreach($articulos as $art)
                                                        <option value="{{ $art->id_producto }}">{{ $art->nombre }}</option>
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
                                    @endif
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-3">Artículo</th>
                                                    <th>Cantidad</th>
                                                    <th>Lote</th>
                                                    @if($ejecucion->id_estado_ejecucion_obra == 1)
                                                        <th class="text-center pe-3" style="width:120px;">Acción</th>
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
                                                                <button class="btn btn-sm btn-outline-warning me-1" onclick="modificarCantidadInforme({{ $inf->id_informe_diario_ejecucion }}, {{ $art->id_informe_diario_articulo }}, {{ $art->cantidad }})" title="Editar cantidad">
                                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                                </button>
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

    function postJSON(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data)
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
            if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        }
        if (informeId) {
            const collapse = document.getElementById(`collapse-informe-${informeId}`);
            if (collapse && !collapse.classList.contains('show')) new bootstrap.Collapse(collapse, { toggle: true });
        }
        if (subTab && informeId) {
            setTimeout(() => {
                const subTabBtn = document.querySelector(`[data-bs-target="#${subTab}-${informeId}"]`);
                if (subTabBtn) bootstrap.Tab.getOrCreateInstance(subTabBtn).show();
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

    // === SELECT2 INIT ===
    $(document).ready(function() {
        $('.select2-articulos-ejec').select2({ width: '100%', placeholder: 'Buscar artículo...', allowClear: true, language: { noResults: () => "No se encontraron artículos", searching: () => "Buscando..." } });
        $('.select2-articulos-informe').select2({ width: '100%', placeholder: 'Buscar artículo...', allowClear: true, language: { noResults: () => "No se encontraron artículos", searching: () => "Buscando..." } });
    });

    // === MAIN TAB: MATERIALES ===
    document.getElementById('formAddMaterial')?.addEventListener('submit', function(e) {
        e.preventDefault();
        saveTabState('pills-materiales');
        postForm(`{{ route("ejecuciones.storeDetail", $ejecucion->id_ejecucion_obra) }}`, new FormData(this))
        .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
    });

    function modificarCantidadMaterial(idProducto, cantidadActual) {
        Swal.fire({
            title: 'Modificar Cantidad',
            text: 'Ingrese la nueva cantidad total para este material:',
            input: 'number', inputValue: cantidadActual,
            inputAttributes: { step: '0.01', min: '0.01' },
            showCancelButton: true, confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar', confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                saveTabState('pills-materiales');
                postJSON(`/ejecuciones/${EJEC_ID}/detalle-producto/${idProducto}/update-cantidad`, { cantidad: result.value })
                .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
            }
        });
    }

    function eliminarMaterial(idProducto) {
        confirmar('¿Remover material? Se eliminarán todos los registros pendientes de este producto.', () => {
            saveTabState('pills-materiales');
            postAction(`/ejecuciones/${EJEC_ID}/detalle-producto/${idProducto}/delete`)
            .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
        });
    }

    // === MAIN TAB: MANO DE OBRA ===
    document.getElementById('formAddHoras')?.addEventListener('submit', function(e) {
        e.preventDefault();
        saveTabState('pills-horas');
        postForm(`{{ route("ejecuciones.storeDiario", $ejecucion->id_ejecucion_obra) }}`, new FormData(this))
        .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
    });

    function modificarHoras(idHoras, entrada, salida) {
        Swal.fire({
            title: 'Modificar Horas de Trabajo',
            html:
                '<div class="text-start">' +
                '<label class="form-label fw-semibold">Hora Entrada</label>' +
                '<input type="time" id="swal-entrada" class="swal2-input" value="' + entrada + '" style="margin-bottom:10px;">' +
                '<label class="form-label fw-semibold">Hora Salida</label>' +
                '<input type="time" id="swal-salida" class="swal2-input" value="' + salida + '">' +
                '</div>',
            focusConfirm: false,
            preConfirm: () => {
                return { hora_entrada: document.getElementById('swal-entrada').value, hora_salida: document.getElementById('swal-salida').value };
            },
            showCancelButton: true, confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar', confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed) {
                saveTabState('pills-horas');
                postJSON(`/ejecuciones/${EJEC_ID}/diario/${idHoras}/update`, result.value)
                .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
            }
        });
    }

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
    document.getElementById('formAddInforme')?.addEventListener('submit', function(e) {
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

    document.getElementById('formEditInforme')?.addEventListener('submit', function(e) {
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

    function modificarCantidadInforme(idInf, idArt, cantidadActual) {
        Swal.fire({
            title: 'Modificar Cantidad',
            text: 'Ingrese la nueva cantidad:',
            input: 'number', inputValue: cantidadActual,
            inputAttributes: { step: '0.01', min: '0.01' },
            showCancelButton: true, confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar', confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                saveTabState('pills-informe', idInf, 'sub-articulos');
                postJSON(`/ejecuciones/${EJEC_ID}/informe-diario/${idInf}/articulo/${idArt}/update-cantidad`, { cantidad: result.value })
                .then(r => r.success ? reload() : Swal.fire('Error', r.mensaje, 'error'));
            }
        });
    }

    function eliminarArticulo(idInf, idArt) {
        confirmar('¿Remover artículo?', () => {
            saveTabState('pills-informe', idInf, 'sub-articulos');
            postAction(`/ejecuciones/${EJEC_ID}/informe-diario/${idInf}/articulo/${idArt}/delete`)
            .then(r => r.success ? toast('Artículo removido', 'pills-informe', idInf, 'sub-articulos') : Swal.fire('Error', r.mensaje, 'error'));
        });
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

    // === EXPORT FUNCTIONS ===
    // Export Materiales
    document.getElementById('exportMaterialesExcel')?.addEventListener('click', function() {
        var table = document.getElementById('tablaMateriales');
        if (!table) return;
        var hasAcciones = table.querySelector('th.text-center')?.innerText.trim().toUpperCase().includes('ACCION');
        var lastIdx = hasAcciones ? table.querySelectorAll('thead tr th').length - 1 : 99;
        var headers = ['Artículo', 'Bodega Origen', 'Cantidad', 'Estado'];
        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            if (row.querySelector('td[colspan]')) return;
            var cells = Array.from(row.cells);
            if (hasAcciones) cells.pop();
            bodyRows.push(cells.map(c => c.innerText.trim()));
        });
        if (bodyRows.length === 0) { Swal.fire('Atención', 'No hay datos para exportar.', 'warning'); return; }
        var workbook = new ExcelJS.Workbook();
        var ws = workbook.addWorksheet('Materiales');
        ws.columns = headers.map(h => ({ key: h, width: 35 }));
        var infoProy = ws.addRow(['Proyecto:', @json($ejecucion->orden?->proyecto?->nombre ?? '-')]);
        var infoObra = ws.addRow(['Nombre de Obra:', @json($ejecucion->orden?->obra?->nombre ?? '-')]);
        var infoFecha = ws.addRow(['Fecha Ejecución:', @json($ejecucion->fecha_solicitud)]);
        var titleRow = ws.addRow(['Materiales - Ejecución EJEC-{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}']);
        ws.addRow([]);
        var headerRow = ws.addRow(headers);
        [infoProy, infoObra, infoFecha].forEach(r => { r.getCell(1).font = { bold: true, color: { argb: "FF0B1A30" } }; });
        titleRow.eachCell(cell => { cell.font = { bold: true, size: 12, color: { argb: "FF0B1A30" } }; });
        headerRow.eachCell(cell => { cell.font = { bold: true, color: { argb: "FFFFFFFF" } }; cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } }; cell.alignment = { horizontal: "center", vertical: "middle" }; });
        bodyRows.forEach(row => { var obj = {}; headers.forEach((h, i) => obj[h] = row[i]); ws.addRow(obj); });
        workbook.xlsx.writeBuffer().then(buffer => { saveAs(new Blob([buffer], { type: "application/octet-stream" }), 'Materiales_Ejecucion_{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}.xlsx'); });
    });
    document.getElementById('exportMaterialesPDF')?.addEventListener('click', function() {
        var table = document.getElementById('tablaMateriales');
        if (!table) return;
        var hasAcciones = table.querySelector('th.text-center')?.innerText.trim().toUpperCase().includes('ACCION');
        var headers = ['Artículo', 'Bodega Origen', 'Cantidad', 'Estado'];
        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            if (row.querySelector('td[colspan]')) return;
            var cells = Array.from(row.cells);
            if (hasAcciones) cells.pop();
            bodyRows.push(cells.map(c => c.innerText.trim()));
        });
        if (bodyRows.length === 0) { Swal.fire('Atención', 'No hay datos para exportar.', 'warning'); return; }
        const doc = new window.jspdf.jsPDF();
        doc.setFontSize(11); doc.setTextColor(11, 26, 48); doc.setFont('helvetica', 'bold');
        doc.text('Proyecto: ' + (@json($ejecucion->orden?->proyecto?->nombre ?? '-')), 14, 16);
        doc.text('Nombre de Obra: ' + (@json($ejecucion->orden?->obra?->nombre ?? '-')), 14, 22);
        doc.text('Fecha Ejecución: ' + (@json($ejecucion->fecha_solicitud)), 14, 28);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(14);
        doc.text('Materiales - Ejecución EJEC-{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}', 14, 38);
        doc.autoTable({ head: [headers], body: bodyRows, startY: 46, headStyles: { fillColor: [11, 26, 48] }, alternateRowStyles: { fillColor: [245, 247, 251] } });
        doc.save('Materiales_Ejecucion_{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}.pdf');
    });

    // Export Horas
    document.getElementById('exportHorasExcel')?.addEventListener('click', function() {
        var table = document.getElementById('tablaHoras');
        if (!table) return;
        var hasAcciones = table.querySelector('th.text-center')?.innerText.trim().toUpperCase().includes('ACCION');
        var headers = ['Empleado', 'Entrada', 'Salida', 'H. Normales', 'H. Extras', 'H. Extraord.'];
        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            if (row.querySelector('td[colspan]')) return;
            var cells = Array.from(row.cells);
            if (hasAcciones) cells.pop();
            bodyRows.push(cells.map(c => c.innerText.trim()));
        });
        if (bodyRows.length === 0) { Swal.fire('Atención', 'No hay datos para exportar.', 'warning'); return; }
        var workbook = new ExcelJS.Workbook();
        var ws = workbook.addWorksheet('Horas');
        ws.columns = headers.map(h => ({ header: h, key: h, width: 25 }));
        ws.getRow(1).eachCell(cell => { cell.font = { bold: true, color: { argb: "FFFFFFFF" } }; cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } }; });
        bodyRows.forEach(row => { var obj = {}; headers.forEach((h, i) => obj[h] = row[i]); ws.addRow(obj); });
        workbook.xlsx.writeBuffer().then(buffer => { saveAs(new Blob([buffer], { type: "application/octet-stream" }), 'Horas_Ejecucion_{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}.xlsx'); });
    });
    document.getElementById('exportHorasPDF')?.addEventListener('click', function() {
        var table = document.getElementById('tablaHoras');
        if (!table) return;
        var hasAcciones = table.querySelector('th.text-center')?.innerText.trim().toUpperCase().includes('ACCION');
        var headers = ['Empleado', 'Entrada', 'Salida', 'H. Normales', 'H. Extras', 'H. Extraord.'];
        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            if (row.querySelector('td[colspan]')) return;
            var cells = Array.from(row.cells);
            if (hasAcciones) cells.pop();
            bodyRows.push(cells.map(c => c.innerText.trim()));
        });
        if (bodyRows.length === 0) { Swal.fire('Atención', 'No hay datos para exportar.', 'warning'); return; }
        const doc = new window.jspdf.jsPDF();
        doc.setFontSize(14); doc.setTextColor(11, 26, 48);
        doc.text('Horas de Trabajo - Ejecución EJEC-{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}', 14, 20);
        doc.autoTable({ head: [headers], body: bodyRows, startY: 28, headStyles: { fillColor: [11, 26, 48] }, alternateRowStyles: { fillColor: [245, 247, 251] } });
        doc.save('Horas_Ejecucion_{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}.pdf');
    });

    // Export Informe Diario completo (todos los sub-tabs)
    document.getElementById('exportInformeCompleto')?.addEventListener('click', function() {
        var informesData = {!! json_encode($informesDiarios->map(fn($inf) => [
            'id' => $inf->id_informe_diario_ejecucion,
            'fecha' => $inf->fecha,
            'descripcion' => $inf->descripcion,
            'observacion' => $inf->observacion,
            'lugar' => $inf->lugar,
            'empleados' => $inf->empleados->map(fn($e) => $e->empleado?->nombres_apellidos ?? 'N/A'),
            'articulos' => $inf->articulos->map(fn($a) => ['nombre' => $a->producto?->nombre ?? 'N/A', 'cantidad' => $a->cantidad, 'lote' => $a->lote]),
            'descripciones' => $inf->detalles->map(fn($d) => $d->descripcion),
        ])) !!};

        if (informesData.length === 0) { Swal.fire('Atención', 'No hay informes diarios para exportar.', 'warning'); return; }

        var workbook = new ExcelJS.Workbook();
        var ws = workbook.addWorksheet('Informes Diarios');
        ws.columns = [
            { header: 'ID Informe', key: 'id', width: 15 },
            { header: 'Fecha', key: 'fecha', width: 15 },
            { header: 'Descripción', key: 'descripcion', width: 30 },
            { header: 'Observación', key: 'observacion', width: 30 },
            { header: 'Lugar', key: 'lugar', width: 20 },
            { header: 'Empleados', key: 'empleados', width: 40 },
            { header: 'Artículos', key: 'articulos', width: 50 },
            { header: 'Descripciones', key: 'descripciones', width: 40 }
        ];
        ws.getRow(1).eachCell(cell => { cell.font = { bold: true, color: { argb: "FFFFFFFF" } }; cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } }; });

        informesData.forEach(inf => {
            var arts = inf.articulos.map(a => a.nombre + ' (x' + a.cantidad + ')' + (a.lote ? ' Lote:' + a.lote : '')).join(', ');
            var emps = inf.empleados.join(', ');
            var descs = inf.descripciones.join(' | ');
            ws.addRow({
                id: 'INF-' + String(inf.id).padStart(5, '0'),
                fecha: inf.fecha,
                descripcion: inf.descripcion || 'Sin descripción',
                observacion: inf.observacion || 'Sin observación',
                lugar: inf.lugar || 'N/A',
                empleados: emps || 'Sin empleados',
                articulos: arts || 'Sin artículos',
                descripciones: descs || 'Sin descripciones'
            });
        });

        workbook.xlsx.writeBuffer().then(buffer => { saveAs(new Blob([buffer], { type: "application/octet-stream" }), 'Informes_Diarios_EJEC-{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}.xlsx'); });
    });

    document.getElementById('exportInformeCompletoPDF')?.addEventListener('click', function() {
        var informesData = {!! json_encode($informesDiarios->map(fn($inf) => [
            'id' => $inf->id_informe_diario_ejecucion,
            'fecha' => $inf->fecha,
            'descripcion' => $inf->descripcion,
            'observacion' => $inf->observacion,
            'lugar' => $inf->lugar,
            'empleados' => $inf->empleados->map(fn($e) => $e->empleado?->nombres_apellidos ?? 'N/A'),
            'articulos' => $inf->articulos->map(fn($a) => ['nombre' => $a->producto?->nombre ?? 'N/A', 'cantidad' => $a->cantidad, 'lote' => $a->lote]),
            'descripciones' => $inf->detalles->map(fn($d) => $d->descripcion),
        ])) !!};

        if (informesData.length === 0) { Swal.fire('Atención', 'No hay informes diarios para exportar.', 'warning'); return; }

        const doc = new window.jspdf.jsPDF('p', 'mm', 'a4');
        doc.setFontSize(16); doc.setTextColor(11, 26, 48);
        doc.text('Informes Diarios - EJEC-{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}', 14, 15);

        var y = 25;
        informesData.forEach((inf, idx) => {
            if (y > 260) { doc.addPage(); y = 15; }
            doc.setFontSize(11); doc.setTextColor(11, 26, 48); doc.setFont(undefined, 'bold');
            doc.text('INF-' + String(inf.id).padStart(5, '0') + ' | Fecha: ' + inf.fecha + (inf.lugar ? ' | Lugar: ' + inf.lugar : ''), 14, y);
            y += 6; doc.setFont(undefined, 'normal'); doc.setFontSize(9); doc.setTextColor(80);
            if (inf.descripcion) { doc.text('Descripción: ' + inf.descripcion, 14, y); y += 5; }
            if (inf.observacion) { doc.text('Observación: ' + inf.observacion, 14, y); y += 5; }
            if (inf.empleados.length > 0) { doc.text('Empleados: ' + inf.empleados.join(', '), 14, y); y += 5; }
            if (inf.articulos.length > 0) {
                var arts = inf.articulos.map(a => a.nombre + ' (x' + a.cantidad + ')' + (a.lote ? ' Lote:' + a.lote : '')).join(', ');
                doc.text('Artículos: ' + arts, 14, y); y += 5;
            }
            if (inf.descripciones.length > 0) { doc.text('Descripciones: ' + inf.descripciones.join(' | '), 14, y); y += 5; }
            y += 4;
        });

        doc.save('Informes_Diarios_EJEC-{{ str_pad($ejecucion->id_ejecucion_obra, 5, '0', STR_PAD_LEFT) }}.pdf');
    });
</script>
@endsection
