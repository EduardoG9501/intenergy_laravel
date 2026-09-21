@extends('layouts.app')

@section('title', 'Configuración IVA - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Configuración IVA</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="#">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Configuración IVA</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card card-custom p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3" style="width:60px;">Id</th>
                    <th>Porcentaje (%)</th>
                    <th>Código SRI</th>
                    <th>Código Porc. IVA</th>
                    <th class="text-center" style="width:120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ivas as $iva)
                <tr>
                    <td class="ps-3 fw-bold">{{ $iva->id_iva }}</td>
                    <td>
                        <span class="badge bg-primary fs-6">{{ $iva->monto }}%</span>
                    </td>
                    <td>{{ $iva->CODIGO_SRI }}</td>
                    <td>{{ $iva->Codigo_Porc_Iva }}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="abrirModalEditar({{ $iva->id_iva }}, {{ $iva->monto }}, '{{ $iva->CODIGO_SRI }}', '{{ $iva->Codigo_Porc_Iva }}')" title="Editar">
                            <i class="fa-solid fa-pen-to-square"></i> Editar
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No hay registros de IVA configurados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL EDITAR IVA -->
<div class="modal fade" id="modalEditarIva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-pen-to-square me-2"></i> Editar Configuración IVA</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarIva" onsubmit="guardarIva(event)">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_iva" id="edit_id_iva">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Porcentaje (%)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="monto" id="edit_monto" step="0.01" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Código SRI</label>
                        <input type="text" class="form-control" name="CODIGO_SRI" id="edit_codigo_sri" maxlength="20" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Código Porc. IVA</label>
                        <input type="text" class="form-control" name="Codigo_Porc_Iva" id="edit_codigo_porc_iva" maxlength="20" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnGuardarIva">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function abrirModalEditar(id, monto, codigoSri, codigoPorc) {
        document.getElementById('edit_id_iva').value = id;
        document.getElementById('edit_monto').value = monto;
        document.getElementById('edit_codigo_sri').value = codigoSri;
        document.getElementById('edit_codigo_porc_iva').value = codigoPorc;
        new bootstrap.Modal(document.getElementById('modalEditarIva')).show();
    }

    function guardarIva(e) {
        e.preventDefault();
        const id = document.getElementById('edit_id_iva').value;
        const btn = document.getElementById('btnGuardarIva');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...';
        btn.disabled = true;

        const formData = new FormData(document.getElementById('formEditarIva'));

        fetch(`{{ url('/configuracion/iva') }}/${id}/update`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(r => {
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar';
            btn.disabled = false;

            if (r.success) {
                Swal.fire({ title: 'Guardado', text: r.mensaje, icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        })
        .catch(() => {
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar';
            btn.disabled = false;
            Swal.fire('Error', 'Error de conexión.', 'error');
        });
    }
</script>
@endsection
