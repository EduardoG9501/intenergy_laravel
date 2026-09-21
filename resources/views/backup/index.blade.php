@extends('layouts.app')

@section('title', 'Copias de Seguridad - Intenergy')

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css"/>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Copia de Seguridad (Backup)</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Backup</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary-custom px-4 py-2" id="btnGenerarBackup">
        <i class="fa-solid fa-database me-2"></i> Realizar Copia de Seguridad
    </button>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Listado de copias -->
<div class="card card-custom p-4">
    <h4 class="fw-bold mb-3 text-dark">Respaldos Realizados</h4>
    <div class="table-responsive">
        <table id="gridBackup" class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3" style="width: 10%;">ID</th>
                    <th style="width: 30%;">Fecha Creación</th>
                    <th style="width: 45%;">Ruta Física del SQL</th>
                    <th class="text-center pe-3" style="width: 15%;">Descargar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $b)
                    <tr>
                        <td class="ps-3 fw-bold font-monospace">{{ $b->id }}</td>
                        <td>{{ $b->fecha_crea }}</td>
                        <td class="font-monospace small">{{ $b->ruta }}</td>
                        <td class="text-center pe-3">
                            <a href="{{ route('backup.download', $b->id) }}" class="btn btn-sm btn-success px-3">
                                <i class="fa-solid fa-download me-1"></i> Descargar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-database fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se han realizado copias de seguridad de la base de datos.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($backups->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $backups->links() }}
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#gridBackup').DataTable({
            responsive: true,
            destroy: true,
            ordering: false,
            paging: false,
            searching: false,
            info: false
        });

        // Crear backup
        $('#btnGenerarBackup').click(function() {
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Generando respaldo...');

            fetch('{{ route("backup.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(resp => resp.json())
            .then(r => {
                if (r.success) {
                    Swal.fire({
                        title: '¡Respaldado!',
                        text: r.mensaje,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-database me-2"></i> Realizar Copia de Seguridad');
                    Swal.fire('Error', r.mensaje, 'error');
                }
            })
            .catch(err => {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-database me-2"></i> Realizar Copia de Seguridad');
                Swal.fire('Error', 'Hubo un fallo de comunicación con el servidor.', 'error');
            });
        });
    });
</script>
@endsection
