@forelse($liquidacionImagenes as $img)
    <div class="d-inline-flex align-items-center gap-2 border rounded p-2 bg-white me-2 mb-2" style="display:inline-flex !important;">
        <div class="img-preview-wrapper">
            <a href="{{ asset($img->ruta) }}" target="_blank">
                <img src="{{ $img->ruta }}" alt="{{ $img->nombre }}" class="rounded img-thumb-liq">
            </a>
            <div class="img-preview-hover-liq">
                <img src="{{ asset($img->ruta) }}" alt="preview" class="rounded shadow">
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarImagenLiq({{ $img->id_liquidacion_trabajo_imagen }})" title="Eliminar">
            <i class="fa-solid fa-trash-can"></i>
        </button>
    </div>
@empty
    <p class="text-muted mb-0" id="sinImagenesLiq">No hay imágenes adjuntas.</p>
@endforelse
