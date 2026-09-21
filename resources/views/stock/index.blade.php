@extends('layouts.app')

@section('title', 'Consulta de Stock - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Existencias de Productos (Stock)</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Existencias</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Filtros de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('stock.index') }}" method="GET" class="row g-3">
        <div class="col-md-5">
            <label class="form-label fw-semibold">Buscar Producto</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Nombre o código de barra..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Filtrar por Bodega Principal</label>
            <select class="form-select" name="id_bodega" onchange="this.form.submit()">
                <option value="">Todas las Bodegas</option>
                @foreach($bodegas as $bdg)
                    <option value="{{ $bdg->id_bodega }}" @if(request('id_bodega') == $bdg->id_bodega) selected @endif>{{ $bdg->nombreBodega }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-grid align-items-end">
            <button type="submit" class="btn btn-dark py-2">Aplicar Filtros</button>
        </div>
    </form>
</div>

<!-- Listado de Existencias -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">Código de Barra</th>
                    <th>Producto</th>
                    <th>Bodega Principal</th>
                    <th>Bodega Destino (Secundaria)</th>
                    <th class="text-end pe-4">Cantidad Disponible (Stock)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stock as $stk)
                    <tr>
                        <td class="ps-4 fw-bold font-monospace">{{ $stk->articulo?->codigobarra ?: 'N/A' }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $stk->articulo?->nombre }}</div>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary">{{ $stk->bodegaPrincipal?->nombreBodega }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $stk->bodegaSecundaria?->nombreBodega }}</span>
                        </td>
                        <td class="text-end pe-4">
                            @if($stk->cantidad <= 5)
                                <span class="h6 fw-bold text-danger">{{ number_format($stk->cantidad, 2) }}</span>
                            @else
                                <span class="h6 fw-bold text-success">{{ number_format($stk->cantidad, 2) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-boxes-stacked fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron registros de existencias en el inventario.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($stock->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $stock->links() }}
        </div>
    @endif
</div>
@endsection
