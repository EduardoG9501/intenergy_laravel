@forelse($horas as $row)
    <tr>
        <td class="ps-3 fw-bold text-primary">{{ $row->identificador }}</td>
        <td>{{ $row->proyecto }}</td>
        <td>{{ $row->obra }}</td>
        <td>{{ $row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '-' }}</td>
        <td>{{ $row->lugar ?: '-' }}</td>
        <td class="fw-semibold">{{ $row->empleado }}</td>
        <td class="font-monospace">{{ $row->hora_entrada }}</td>
        <td class="font-monospace">{{ $row->hora_salida }}</td>
        <td class="text-center fw-bold">{{ number_format($row->cantidad_horas_normal, 2) }}</td>
        <td class="text-center fw-bold text-warning">{{ number_format($row->cantidad_horas_extra, 2) }}</td>
        <td class="text-center fw-bold text-danger">{{ number_format($row->cantidad_horas_extraordinaria, 2) }}</td>
    </tr>
@empty
    <tr>
        <td colspan="11" class="text-center py-4 text-muted">
            <i class="fa-solid fa-clock fa-2x mb-2 text-warning"></i>
            <p class="mb-0">No se encontraron horas de trabajo para esta orden.</p>
        </td>
    </tr>
@endforelse
