<table id="tbDatosStock" class="table table-bordered table-striped align-middle mb-0" style="width:100%">
    <thead class="table-dark">
        <tr>
            <th>Tipo Mov</th>
            <th>Sub Tipo Mov</th>
            <th>Lugar</th>
            <th>Ubicación</th>
            <th>No.Doc</th>
            <th>Producto</th>
            <th>Precio</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
            <th>Descuento</th>
            <th>Iva</th>
            <th>Total</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @php
            $lastProducto = null;
            $entCant = $salCant = 0;
            $entSub = $salSub = 0;
            $entDesc = $salDesc = 0;
            $entIva = $salIva = 0;
            $entTot = $salTot = 0;

            $groupRows = [];
        @endphp

        @foreach($resultados as $row)
            @php
                $productoActual = $row->producto;
            @endphp

            @if($lastProducto !== null && $productoActual !== $lastProducto)
                <!-- Imprimir filas del grupo anterior -->
                {!! implode('', $groupRows) !!}

                <!-- Imprimir Fila de Totales del grupo anterior -->
                @php
                    $totCant = $entCant - $salCant;
                    $totSub = $entSub - $salSub;
                    $totDesc = $entDesc - $salDesc;
                    $totIva = $entIva - $salIva;
                    $totTot = $entTot - $salTot;
                @endphp
                <tr style="background-color: #cde6fe; font-weight: bold;">
                    <td>TOTAL</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align:right;">TOTALES {{ $lastProducto }}:</td>
                    <td></td>
                    <td class="font-monospace">{{ number_format($totCant, 2) }}</td>
                    <td class="font-monospace">${{ number_format($totSub, 2) }}</td>
                    <td class="font-monospace">${{ number_format($totDesc, 2) }}</td>
                    <td class="font-monospace">${{ number_format($totIva, 2) }}</td>
                    <td class="font-monospace">${{ number_format($totTot, 2) }}</td>
                    <td></td>
                </tr>

                @php
                    $entCant = $salCant = 0;
                    $entSub = $salSub = 0;
                    $entDesc = $salDesc = 0;
                    $entIva = $salIva = 0;
                    $entTot = $salTot = 0;
                    $groupRows = [];
                @endphp
            @endif

            @php
                $lastProducto = $productoActual;

                if (strtoupper($row->tipo_movimiento) === 'ENTRADA') {
                    $entCant += floatval($row->cantidad);
                    $entSub += floatval($row->subtotal);
                    $entDesc += floatval($row->decuento);
                    $entIva += floatval($row->iva);
                    $entTot += floatval($row->total);
                } else {
                    $salCant += floatval($row->cantidad);
                    $salSub += floatval($row->subtotal);
                    $salDesc += floatval($row->decuento);
                    $salIva += floatval($row->iva);
                    $salTot += floatval($row->total);
                }

                $badgeClass = strtoupper($row->tipo_movimiento) === 'ENTRADA' ? 'bg-success' : 'bg-danger';
                $rowHtml = '<tr>
                    <td><span class="badge ' . $badgeClass . '">' . $row->tipo_movimiento . '</span></td>
                    <td>' . $row->sub_tipo_movimiento . '</td>
                    <td>' . $row->BodegaPrincipal . '</td>
                    <td>' . $row->BodegaSecundaria . '</td>
                    <td class="font-monospace">' . $row->no_documento . '</td>
                    <td class="fw-semibold text-dark">' . $row->producto . '</td>
                    <td class="font-monospace">$' . number_format($row->precio, 2) . '</td>
                    <td class="font-monospace fw-bold">' . number_format($row->cantidad, 2) . '</td>
                    <td class="font-monospace">$' . number_format($row->subtotal, 2) . '</td>
                    <td class="font-monospace">$' . number_format($row->decuento, 2) . '</td>
                    <td class="font-monospace">$' . number_format($row->iva, 2) . '</td>
                    <td class="font-monospace fw-semibold text-primary">$' . number_format($row->total, 2) . '</td>
                    <td>' . $row->fecha_captura . '</td>
                </tr>';

                $groupRows[] = $rowHtml;
            @endphp
        @endforeach

        @if($lastProducto !== null)
            <!-- Imprimir ultimo grupo restante -->
            {!! implode('', $groupRows) !!}
            @php
                $totCant = $entCant - $salCant;
                $totSub = $entSub - $salSub;
                $totDesc = $entDesc - $salDesc;
                $totIva = $entIva - $salIva;
                $totTot = $entTot - $salTot;
            @endphp
            <tr style="background-color: #cde6fe; font-weight: bold;">
                <td>TOTAL</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td style="text-align:right;">TOTALES {{ $lastProducto }}:</td>
                <td></td>
                <td class="font-monospace">{{ number_format($totCant, 2) }}</td>
                <td class="font-monospace">${{ number_format($totSub, 2) }}</td>
                <td class="font-monospace">${{ number_format($totDesc, 2) }}</td>
                <td class="font-monospace">${{ number_format($totIva, 2) }}</td>
                <td class="font-monospace">${{ number_format($totTot, 2) }}</td>
                <td></td>
            </tr>
        @endif
    </tbody>
</table>
