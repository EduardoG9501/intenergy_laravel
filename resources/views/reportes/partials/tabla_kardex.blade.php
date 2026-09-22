<table id="tablaKardex" class="table table-bordered table-striped align-middle mb-0">
    <thead class="table-dark">
        <tr>
            <th>ID ORDEN</th>
            <th>PROYECTO</th>
            <th>NOMBRE DE LA OBRA</th>
            <th>Lugar</th>
            <th>Fecha</th>
            <th>Tipo Movimiento</th>
            <th>Sub Tipo Movimiento</th>
            <th>Articulo</th>
            <th>Cantidad</th>
            <th>Stock Actual</th>
            <th>#Documento</th>
        </tr>
    </thead>
    <tbody>
    @forelse($resultados as $row)
        <tr>
            <td class="fw-bold font-monospace text-primary">{{ $row->id_orden }}</td>
            <td class="fw-semibold">{{ $row->proyecto }}</td>
            <td>{{ $row->nombre_obra }}</td>
            <td>{{ $row->lugar }}</td>
            <td>{{ $row->fecha }}</td>
            <td>
                <span class="badge @if($row->tipo_movimiento == 'ENTRADA') bg-success @else bg-danger @endif">
                    {{ $row->tipo_movimiento }}
                </span>
            </td>
            <td>{{ $row->sub_tipo_movimiento }}</td>
            <td class="fw-bold text-dark">{{ $row->articulo }}</td>
            <td class="fw-bold text-primary font-monospace">{{ number_format($row->cantidad, 2) }}</td>
            <td class="fw-bold font-monospace {{ ($row->stock_actual ?? 0) > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($row->stock_actual ?? 0, 2) }}</td>
            <td class="font-monospace small">#{{ $row->documento }}</td>
        </tr>
    @empty
        <tr><td colspan="11" class="text-center py-4 text-muted">No se encontraron resultados.</td></tr>
    @endforelse
    </tbody>
</table>

@if($resultados->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3 px-2">
    <small class="text-muted">
        Mostrando {{ $resultados->firstItem() }} - {{ $resultados->lastItem() }} de {{ $resultados->total() }} registros
    </small>
    <nav>
        <ul class="pagination pagination-sm mb-0">
            {{-- Anterior --}}
            <li class="page-item {{ $resultados->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="#" onclick="cargarPagina({{ $resultados->currentPage() - 1 }}); return false;">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            </li>

            {{-- Numeros de pagina --}}
            @php
                $start = max(1, $resultados->currentPage() - 2);
                $end = min($resultados->lastPage(), $resultados->currentPage() + 2);
            @endphp

            @if($start > 1)
                <li class="page-item"><a class="page-link" href="#" onclick="cargarPagina(1); return false;">1</a></li>
                @if($start > 2)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
            @endif

            @for($i = $start; $i <= $end; $i++)
                <li class="page-item {{ $i == $resultados->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="#" onclick="cargarPagina({{ $i }}); return false;">{{ $i }}</a>
                </li>
            @endfor

            @if($end < $resultados->lastPage())
                @if($end < $resultados->lastPage() - 1)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
                <li class="page-item"><a class="page-link" href="#" onclick="cargarPagina({{ $resultados->lastPage() }}); return false;">{{ $resultados->lastPage() }}</a></li>
            @endif

            {{-- Siguiente --}}
            <li class="page-item {{ $resultados->currentPage() == $resultados->lastPage() ? 'disabled' : '' }}">
                <a class="page-link" href="#" onclick="cargarPagina({{ $resultados->currentPage() + 1 }}); return false;">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
</div>
@endif
