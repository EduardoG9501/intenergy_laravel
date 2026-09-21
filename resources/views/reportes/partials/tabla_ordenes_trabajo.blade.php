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
    <tr>
        <td colspan="8" class="text-center py-5 text-muted">
            <i class="fa-solid fa-clipboard-list fa-2x mb-3 text-warning"></i>
            <p class="mb-0">No se encontraron resultados con los filtros aplicados.</p>
        </td>
    </tr>
@endforelse
