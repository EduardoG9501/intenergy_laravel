@php
    $navBodegas = [];
    if (Auth::check()) {
        if (Auth::user()->email === 'admin') {
            $navBodegas = \App\Models\Bodega::where('es_bodega_secundaria', 0)->where('estado', 1)->orderBy('nombreBodega')->get();
        } else {
            $navBodegaIds = \App\Models\PermisoBodega::where('id_usuario', Auth::id())->where('estado', 1)->pluck('id_bodega')->toArray();
            $navBodegas = \App\Models\Bodega::whereIn('id_bodega', $navBodegaIds)->where('es_bodega_secundaria', 0)->where('estado', 1)->orderBy('nombreBodega')->get();
        }
    }
    $activeBodegaId = session('bodega_seleccionada');
    if (!$activeBodegaId && count($navBodegas) > 0) {
        $activeBodegaId = $navBodegas[0]->id_bodega;
        session(['bodega_seleccionada' => $activeBodegaId]);
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Intenergy - Sistema de Inventario')</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (for premium icons) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Outfit Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f5f7fb;
            color: #333;
        }
        .navbar-custom {
            background-color: #0b1a30; /* Dark premium blue */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .navbar-custom .navbar-brand {
            color: #00d2ff;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .navbar-custom .nav-link {
            color: #e0e0e0;
            font-weight: 500;
            transition: color 0.3s;
        }
        .navbar-custom .nav-link:hover, .navbar-custom .nav-link.active {
            color: #00d2ff;
        }
        .navbar-custom .dropdown-menu {
            background-color: #0b1a30;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .navbar-custom .dropdown-item {
            color: #e0e0e0;
            font-weight: 500;
            transition: all 0.3s;
        }
        .navbar-custom .dropdown-item:hover {
            background-color: rgba(0, 210, 255, 0.1);
            color: #00d2ff;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background-color: #fff;
        }
        .footer-custom {
            background-color: #0b1a30;
            color: #b0bec5;
            padding: 20px 0;
            margin-top: 50px;
            font-size: 14px;
        }
        .btn-primary-custom {
            background: linear-gradient(45deg, #00d2ff 0%, #3a7bd5 100%);
            border: none;
            color: white;
            font-weight: 600;
        }
        .btn-primary-custom:hover {
            background: linear-gradient(45deg, #3a7bd5 0%, #00d2ff 100%);
            box-shadow: 0 4px 12px rgba(0, 210, 255, 0.3);
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('inicio') }}">
                <i class="fa-solid fa-bolt me-2"></i>INTENERGY
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link @if(Route::is('inicio')) active @endif" href="{{ route('inicio') }}">
                            <i class="fa-solid fa-house me-1"></i> Inicio
                        </a>
                    </li>
                    
                    <!-- Registros General -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-folder me-1"></i> Registros General
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/articulos') }}">Artículos</a></li>
                            <li><a class="dropdown-item" href="{{ url('/categorias') }}">Categorías</a></li>
                            <li><a class="dropdown-item" href="{{ url('/bodegas') }}">Bodegas</a></li>
                            <li><a class="dropdown-item" href="{{ url('/proveedores') }}">Proveedores</a></li>
                            {{-- <li><a class="dropdown-item" href="{{ url('/clientes') }}">Clientes</a></li> --}}
                            <li><a class="dropdown-item" href="{{ url('/empleados') }}">Empleados</a></li>
                            <li><a class="dropdown-item" href="{{ url('/cargos') }}">Cargos</a></li>
                            <li><hr class="dropdown-divider bg-light"></li>
                            <li><a class="dropdown-item" href="{{ url('/proyectos') }}">Proyectos</a></li>
                            <li><a class="dropdown-item" href="{{ url('/obras') }}">Nombre de Obra</a></li>
                        </ul>
                    </li>

                    <!-- Inventario -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-boxes-stacked me-1"></i> Inventario
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/movimientos') }}">Movimientos</a></li>
                            <li><a class="dropdown-item" href="{{ url('/stock') }}">Stock</a></li>
                        </ul>
                    </li>

                    <!-- Operaciones -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-gears me-1"></i> Operaciones
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/ordenes') }}">Orden de Trabajo</a></li>
                            <li><a class="dropdown-item" href="{{ url('/informes-diarios') }}">Informes Diarios</a></li>
                            <li><a class="dropdown-item" href="{{ url('/pedidos') }}">Pedido de Materiales</a></li>
                            <li><a class="dropdown-item" href="{{ url('/ejecuciones') }}">Ejecución de Obra</a></li>
                        </ul>
                    </li>

                    <!-- Reportes -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-chart-line me-1"></i> Reportes
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/reportes/informe-ordenes-trabajo') }}">Informe Ordenes de Trabajo</a></li>
                            <li><a class="dropdown-item" href="{{ url('/reportes/informe-pedido-materiales') }}">Informe de Pedido de Materiales</a></li>
                            <li><a class="dropdown-item" href="{{ url('/reportes/reporte-ejecucion-obra') }}">Reporte de Ejecución de Obra</a></li>
                            <li><a class="dropdown-item" href="{{ url('/reportes/liquidacion-trabajo') }}">Liquidación de Trabajo</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ url('/reportes/kardex') }}">Kardex (Entrada / Salida)</a></li>
                        </ul>
                    </li>

                    <!-- Admin -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user-shield me-1"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/usuarios') }}">Usuarios</a></li>
                            <li><a class="dropdown-item" href="{{ url('/configuracion/iva') }}">Configuración IVA</a></li>
                            <li><a class="dropdown-item" href="{{ url('/backup') }}">Respaldo BD</a></li>
                        </ul>
                    </li>
                </ul>

                <!-- Usuario e info -->
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user me-1 text-info"></i> {{ Auth::user()->nombre }} {{ Auth::user()->apellido }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="px-3 py-2" style="min-width: 220px;">
                                    <label for="navbar_bodega_seleccionada" class="form-label small fw-bold text-muted mb-1">Bodega activa:</label>
                                    <select id="navbar_bodega_seleccionada" class="form-select form-select-sm text-dark bg-light fw-semibold">
                                        @foreach($navBodegas as $nb)
                                            <option value="{{ $nb->id_bodega }}" @if($nb->id_bodega == $activeBodegaId) selected @endif>{{ $nb->nombreBodega }}</option>
                                        @endforeach
                                    </select>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fa-solid fa-right-from-bracket me-2"></i> Cerrar Sesión
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer-custom text-center">
        <div class="container">
            <span class="text-muted">© {{ date('Y') }} Intenergy - Inteligencia en Energía. Todos los derechos reservados.</span>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Cargar bodega seleccionada persistida desde localStorage si aplica
            const activeBod = localStorage.getItem('bodega_seleccionada');
            const select = $('#navbar_bodega_seleccionada');
            if (activeBod && select.val() != activeBod && select.find(`option[value="${activeBod}"]`).length > 0) {
                select.val(activeBod);
                // Asegurarse de que el backend este sincronizado con el localStorage
                sincronizarBodegaActiva(activeBod, false);
            }

            select.change(function() {
                const idBodega = $(this).val();
                sincronizarBodegaActiva(idBodega, true);
            });
        });

        function sincronizarBodegaActiva(idBodega, recargar) {
            fetch('{{ route("select_bodega_activa") }}', {
                method: 'POST',
                body: JSON.stringify({ id_bodega: idBodega }),
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(resp => resp.json())
            .then(r => {
                if (r.success) {
                    localStorage.setItem('bodega_seleccionada', idBodega);
                    if (recargar) {
                        location.reload();
                    }
                }
            });
        }
    </script>
    @yield('scripts')
</body>
</html>
