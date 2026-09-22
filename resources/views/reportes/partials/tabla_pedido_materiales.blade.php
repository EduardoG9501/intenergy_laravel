@php
    $agrupados = [];
    $totales = [];
    $granTotal = 0;
    foreach ($resultados as $row) {
        $key = $row->articulo ?: 'Sin artículo';
        if (!isset($agrupados[$key])) {
            $agrupados[$key] = [];
            $totales[$key] = 0;
        }
        $agrupados[$key][] = $row;
        $totales[$key] += $row->cantidad;
        $granTotal += $row->cantidad;
    }
@endphp

@if($resultados->isEmpty())
    <tr>
        <td colspan="8" class="text-center py-5 text-muted">
            <i class="fa-solid fa-box-open fa-2x mb-3 text-warning"></i>
            <p class="mb-0">No se encontraron resultados con los filtros aplicados.</p>
        </td>
    </tr>
@else
    @foreach($agrupados as $articulo => $rows)
        @foreach($rows as $row)
            <tr>
                <td class="text-center"><input type="checkbox" class="row-check"></td>
                <td class="fw-semibold">{{ $row->identificador }}</td>
                <td>{{ $row->proyecto }}</td>
                <td>{{ $row->obra }}</td>
                <td class="text-center">{{ $row->fecha ? date('Y-m-d', strtotime($row->fecha)) : '-' }}</td>
                <td class="text-center">{{ $row->lugar ?: '-' }}</td>
                <td>{{ $row->articulo ?: '-' }}</td>
                <td class="text-end fw-bold">{{ number_format($row->cantidad, 2) }}</td>
            </tr>
        @endforeach
        <tr class="row-subtotal">
            <td></td>
            <td colspan="5" class="fw-semibold text-muted">TOTAL</td>
            <td class="text-end fw-bold">TOTALES {{ strtoupper($articulo) }}:</td>
            <td class="text-end fw-bold">{{ number_format($totales[$articulo], 2) }}</td>
        </tr>
    @endforeach
    <tr class="row-grand-total">
        <td colspan="7" class="text-end fw-bold">TOTALES GENERALES:</td>
        <td class="text-end fw-bold">{{ number_format($granTotal, 2) }}</td>
    </tr>
@endif
