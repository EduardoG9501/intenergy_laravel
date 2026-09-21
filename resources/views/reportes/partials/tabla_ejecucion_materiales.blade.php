@forelse($materiales as $row)
    <tr>
        <td class="ps-3 fw-bold text-primary">{{ $row->identificador }}</td>
        <td>{{ $row->proyecto }}</td>
        <td>{{ $row->obra }}</td>
        <td>{{ $row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '-' }}</td>
        <td>{{ $row->lugar ?: '-' }}</td>
        <td class="fw-semibold">{{ $row->articulo ?: '-' }}</td>
        <td class="fw-bold text-primary">{{ number_format($row->cantidad, 2) }}</td>
        <td>
            @if($row->Contabilizado)
                <span class="badge bg-success">Sí</span>
            @else
                <span class="badge bg-warning text-dark">No</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center py-4 text-muted">
            <i class="fa-solid fa-boxes-stacked fa-2x mb-2 text-warning"></i>
            <p class="mb-0">No se encontraron materiales para esta orden.</p>
        </td>
    </tr>
@endforelse
