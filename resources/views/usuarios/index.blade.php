@extends('layouts.app')

@section('title', 'Administración de Usuarios - Intenergy')

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css"/>
<style>
    .panel-heading-custom {
        background-color: #0b1a30;
        color: white;
        padding: 10px 15px;
        border-radius: 6px 6px 0 0;
        cursor: pointer;
    }
    .panel-body-custom {
        padding: 15px;
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 6px 6px;
        background-color: #f8f9fa;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Administración de Usuarios</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Usuarios</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4">
    <!-- Formulario Crear -->
    <div class="col-lg-4">
        <div class="card card-custom p-4">
            <h4 class="fw-bold mb-4 text-dark">Registrar Nuevo Usuario</h4>
            <form id="frmRegistro">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre *</label>
                    <input type="text" class="form-control" name="nombre" required placeholder="Ej. Juan">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Apellido *</label>
                    <input type="text" class="form-control" name="apellido" required placeholder="Ej. Pérez">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Usuario (Email) *</label>
                    <input type="email" class="form-control" name="email" required placeholder="Ej. juan.perez@empresa.com">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Contraseña *</label>
                    <input type="password" class="form-control" name="password" required placeholder="Mínimo 4 caracteres">
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary-custom py-2">
                        <i class="fa-solid fa-user-plus me-2"></i> Registrar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Listado de Usuarios -->
    <div class="col-lg-8">
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Usuarios Registrados</h4>
            </div>
            <div class="table-responsive">
                <table id="tablaUsuarios" class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Nombre Completo</th>
                            <th>Email (Usuario)</th>
                            <th>Estado</th>
                            <th class="text-center pe-3" style="width: 25%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usuarios as $u)
                            <tr class="@if($u->estado == 0) table-light text-muted @endif">
                                <td class="ps-3 fw-bold font-monospace">{{ $u->id_usuario }}</td>
                                <td class="fw-semibold">{{ $u->nombre }} {{ $u->apellido }}</td>
                                <td>{{ $u->email }}</td>
                                <td>
                                    @if($u->estado == 1)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    @if($u->estado == 1)
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editUsuario({{ $u->id_usuario }})" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning me-1" onclick="abrirPermisosUsuario({{ $u->id_usuario }}, '{{ $u->nombre }} {{ $u->apellido }}')" title="Permisos">
                                            <i class="fa-solid fa-key"></i>
                                        </button>
                                        @if($u->email !== 'admin')
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUsuario({{ $u->id_usuario }})" title="Desactivar">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        @endif
                                    @else
                                        <button class="btn btn-sm btn-outline-success" onclick="restoreUsuario({{ $u->id_usuario }})" title="Restaurar">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($usuarios->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $usuarios->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Permisos -->
<div class="modal fade" id="modalPermisosUsuario" tabindex="-1" aria-labelledby="modalPermisosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0">Permisos para: <span class="text-primary" id="perm_username"></span></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPermisosUsuario">
                @csrf
                <input type="hidden" id="perm_id_usuario" name="id_usuario">
                <div class="modal-body py-4">
                    <!-- Acordeon de Menús -->
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-bars-progress me-2"></i> Acceso a Pantallas y Módulos</h5>
                    <div id="contenedorMenus" class="mb-4">
                        <!-- Generado por JS -->
                    </div>

                    <!-- Bodegas Permitidas -->
                    <h5 class="fw-bold mb-3 border-top pt-4"><i class="fa-solid fa-warehouse me-2"></i> Bodegas Autorizadas</h5>
                    <div class="mb-3">
                        <input type="text" class="form-control" id="filtroBodegas" placeholder="Buscar bodega en la lista...">
                    </div>
                    <div id="listaBodegas" class="p-3 border rounded bg-light" style="max-height: 200px; overflow-y: auto;">
                        <!-- Generado por JS -->
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary-custom px-4 py-2" id="guardarPermisos">Guardar Permisos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom p-3 border-0">
            <div class="modal-header border-0 pb-0">
                <h4 class="fw-bold mb-0" id="modalEditLabel">Actualizar Usuario</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre *</label>
                        <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Apellido *</label>
                        <input type="text" class="form-control" name="apellido" id="edit_apellido" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Usuario (Email) *</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contraseña</label>
                        <input type="password" class="form-control" name="password" id="edit_password" placeholder="Dejar en blanco si no desea cambiarla">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-center">
                    <button type="button" class="btn btn-light px-4 py-2 me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">Actualizar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tablaUsuarios').DataTable({
            responsive: true,
            destroy: true,
            ordering: false,
            paging: false,
            searching: false,
            info: false
        });

        // Filtrado en vivo de bodegas en permisos
        $('#filtroBodegas').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#listaBodegas .form-check').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    });

    // Guardar nuevo usuario
    document.getElementById('frmRegistro').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ route("usuarios.store") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                location.reload();
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });

    // Cargar datos en modal editar
    function editUsuario(id) {
        fetch(`/usuarios/${id}`)
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                document.getElementById('edit_id').value = r.usuario.id_usuario;
                document.getElementById('edit_nombre').value = r.usuario.nombre;
                document.getElementById('edit_apellido').value = r.usuario.apellido;
                document.getElementById('edit_email').value = r.usuario.email;
                document.getElementById('edit_password').value = '';

                const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
                modal.show();
            }
        });
    }

    // Actualizar usuario
    document.getElementById('formEdit').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('edit_id').value;
        const formData = new FormData(this);

        fetch(`/usuarios/${id}/update`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                location.reload();
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });

    // Desactivar usuario
    function deleteUsuario(id) {
        Swal.fire({
            title: '¿Desactivar Usuario?',
            text: 'Esta acción inhabilitará el acceso de este usuario al sistema.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, desactivar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/usuarios/${id}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(resp => resp.json())
                .then(r => {
                    if (r.success) {
                        location.reload();
                    } else {
                        Swal.fire('Error', r.mensaje, 'error');
                    }
                });
            }
        });
    }

    // Restaurar usuario
    function restoreUsuario(id) {
        fetch(`/usuarios/${id}/restore`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                location.reload();
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    }

    // --- LOGICA DE PERMISOS ---
    function abrirPermisosUsuario(id, fullName) {
        $('#perm_id_usuario').val(id);
        $('#perm_username').text(fullName);

        fetch(`/usuarios/${id}/permisos`)
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                // Renderizar Menús
                let grupos = {};
                r.menus.forEach(menu => {
                    if (!grupos[menu.menu_padre]) grupos[menu.menu_padre] = [];
                    grupos[menu.menu_padre].push(menu);
                });

                let menuHtml = `<div class="accordion" id="accordionPermisos">`;
                let idx = 0;
                for (let padre in grupos) {
                    menuHtml += `
                    <div class="accordion-item mb-2 border">
                        <h2 class="accordion-header" id="heading${idx}">
                            <button class="accordion-button bg-light text-dark py-2 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${idx}" aria-expanded="false" aria-controls="collapse${idx}">
                                <strong>${padre}</strong>
                            </button>
                        </h2>
                        <div id="collapse${idx}" class="accordion-collapse collapse" aria-labelledby="heading${idx}" data-bs-parent="#accordionPermisos">
                            <div class="accordion-body bg-white py-2">
                    `;
                    grupos[padre].forEach(menu => {
                        let checked = r.permisos_menu.includes(menu.id_menu) ? 'checked' : '';
                        menuHtml += `
                        <div class="form-check my-2">
                            <input class="form-check-input" type="checkbox" name="menus[]" value="${menu.id_menu}" id="m${menu.id_menu}" ${checked}>
                            <label class="form-check-label text-dark" for="m${menu.id_menu}">
                                ${menu.menu_hijo}
                            </label>
                        </div>`;
                    });
                    menuHtml += `
                            </div>
                        </div>
                    </div>`;
                    idx++;
                }
                menuHtml += `</div>`;
                $('#contenedorMenus').html(menuHtml);

                // Renderizar Bodegas
                let bodegaHtml = '';
                r.bodegas.forEach(bod => {
                    let checked = r.permisos_bodega.includes(bod.id_bodega) ? 'checked' : '';
                    bodegaHtml += `
                    <div class="form-check my-1">
                        <input class="form-check-input checkBodega" type="checkbox" name="bodegas[]" value="${bod.id_bodega}" id="b${bod.id_bodega}" ${checked}>
                        <label class="form-check-label text-dark" for="b${bod.id_bodega}">
                            ${bod.nombreBodega}
                        </label>
                    </div>`;
                });
                $('#listaBodegas').html(bodegaHtml);

                const modal = new bootstrap.Modal(document.getElementById('modalPermisosUsuario'));
                modal.show();
            }
        });
    }

    // Guardar permisos
    $('#guardarPermisos').click(function() {
        var id = $('#perm_id_usuario').val();
        var menus = [];
        $('input[name="menus[]"]:checked').each(function() {
            menus.push($(this).val());
        });

        var bodegas = [];
        $('input[name="bodegas[]"]:checked').each(function() {
            bodegas.push($(this).val());
        });

        fetch(`/usuarios/${id}/permisos`, {
            method: 'POST',
            body: JSON.stringify({ menus: menus, bodegas: bodegas }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(r => {
            if (r.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalPermisosUsuario')).hide();
                Swal.fire('¡Éxito!', r.mensaje, 'success');
            } else {
                Swal.fire('Error', r.mensaje, 'error');
            }
        });
    });
</script>
@endsection
