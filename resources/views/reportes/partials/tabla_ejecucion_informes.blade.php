@forelse($informes as $row)
    <tr>
        <td class="ps-3 fw-bold text-primary">INF-{{ str_pad($row->id_informe_diario_ejecucion, 5, '0', STR_PAD_LEFT) }}</td>
        <td>{{ $row->obra }}</td>
        <td>{{ $row->fecha ? date('d/m/Y', strtotime($row->fecha)) : '-' }}</td>
        <td>{{ $row->lugar ?: '-' }}</td>
        <td>{{ $row->ubicacion ?: '-' }}</td>
        <td>{{ $row->observacion ?: '-' }}</td>
        <td>
            @php
                $imgs = $imagenes->get($row->id_informe_diario_ejecucion, collect());
            @endphp
            @if($imgs->count() > 0)
                <div class="d-flex flex-wrap gap-1">
                    @foreach($imgs as $img)
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
                <span class="text-muted small">—</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center py-4 text-muted">
            <i class="fa-solid fa-clipboard fa-2x mb-2 text-warning"></i>
            <p class="mb-0">No se encontraron informes diarios para esta orden.</p>
        </td>
    </tr>
@endforelse
