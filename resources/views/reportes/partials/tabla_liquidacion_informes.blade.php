<div class="liq-informes-wrap">
    <div class="liq-informes-heading">INFORMES DIARIO ({{ $informes->count() }} REGISTROS)</div>
    <div class="table-responsive">
        <table class="table liq-informes-table mb-0">
            <thead>
                <tr class="liq-inf-head">
                    <th>NOMBRE DE LA OBRA</th>
                    <th>ORDEN TRABAJO</th>
                    <th>FECHA</th>
                    <th>LUGAR</th>
                    <th>UBICACION</th>
                    <th>OBSERVACION</th>
                </tr>
            </thead>
            <tbody>
                @forelse($informes as $row)
                    @php
                        $inf = $informesDetalles->get($row->id_informe_diario_ejecucion);
                        $arts = $inf ? $inf->articulos : collect();
                        $emps = $inf ? $inf->empleados : collect();
                        $descs = $inf ? $inf->detalles : collect();
                        $imgs = $imagenes->get($row->id_informe_diario_ejecucion, collect());
                        $nFilas = max($emps->count(), $arts->count(), $descs->count(), 1);
                    @endphp
                    <tr class="liq-inf-summary">
                        <td>{{ $row->obra ?: '-' }}</td>
                        <td>{{ $row->identificador ?: '-' }}</td>
                        <td>{{ $row->fecha ? date('Y-m-d', strtotime($row->fecha)) : '-' }}</td>
                        <td>{{ $row->lugar ?: '-' }}</td>
                        <td>{{ $row->ubicacion ?: '-' }}</td>
                        <td>{{ $row->observacion ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="6" class="liq-inf-detail-cell">
                            <table class="liq-inf-detail">
                                <thead>
                                    <tr>
                                        <th style="width:30%;">Artículo</th>
                                        <th style="width:25%;">Empleados</th>
                                        <th style="width:20%;">Imágenes</th>
                                        <th style="width:25%;">Descripción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($i = 0; $i < $nFilas; $i++)
                                        @php
                                            $art = $arts[$i] ?? null;
                                            $emp = $emps[$i] ?? null;
                                            $desc = $descs[$i]->descripcion ?? ($i === 0 ? ($inf->descripcion ?? '') : '');
                                            $imgsFila = $imgs->filter(fn($img, $idx) => $idx % $nFilas === $i)->values();
                                        @endphp
                                        <tr>
                                            <td>
                                                @if($art)
                                                    <strong>{{ $art->producto->nombre ?? 'N/A' }}</strong>
                                                    <div class="small text-muted">({{ $art->cantidad }}) [Lote: {{ $art->lote ?: '-' }}]</div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($emp && $emp->empleado)
                                                    {{ $emp->empleado->nombres_apellidos }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($imgsFila->count() > 0)
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($imgsFila as $img)
                                                            <div class="img-preview-wrapper">
                                                                <a href="{{ $img->ruta_imagen }}" target="_blank">
                                                                    <img src="{{ $img->ruta_imagen }}" alt="img" class="rounded border img-thumb">
                                                                </a>
                                                                <div class="img-preview-hover">
                                                                    <img src="{{ $img->ruta_imagen }}" alt="preview" class="rounded shadow">
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $desc ?: '-' }}</td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-clipboard fa-2x mb-2 text-warning"></i>
                            <p class="mb-0">No se encontraron informes diarios para esta orden.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
