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
    <tr>
        <td colspan="7" class="text-center py-5 text-muted">
            <i class="fa-solid fa-box-open fa-2x mb-3 text-warning"></i>
            <p class="mb-0">No se encontraron resultados con los filtros aplicados.</p>
        </td>
    </tr>
@endforelse
