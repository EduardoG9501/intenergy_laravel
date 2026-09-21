@extends('layouts.app')

@section('title', 'Inicio - Intenergy')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom p-4 text-white" style="background: linear-gradient(135deg, #0b1a30 0%, #1e3c72 100%); border:none;">
            <h1 class="display-5 font-weight-bold">¡Bienvenido, {{ Auth::user()->nombre }}!</h1>
            <p class="lead">Sistema de control de inventario, operaciones y ejecución de obras de Intenergy.</p>
            <hr class="my-4 border-info">
            <p>Usa la barra de navegación superior para gestionar los artículos, bodegas, movimientos y órdenes de trabajo de forma rápida y segura.</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Card Articulos -->
    <div class="col-md-4">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary me-3">
                    <i class="fa-solid fa-boxes-stacked fa-2x"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0">Artículos</h5>
                    <small class="text-muted">Catálogo de productos</small>
                </div>
            </div>
            <p class="card-text">Administra el catálogo completo de productos, precios, stock inicial y proveedores.</p>
            <a href="{{ url('/articulos') }}" class="btn btn-primary-custom mt-auto">Gestionar Artículos</a>
        </div>
    </div>

    <!-- Card Bodegas -->
    <div class="col-md-4">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success me-3">
                    <i class="fa-solid fa-warehouse fa-2x"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0">Bodegas</h5>
                    <small class="text-muted">Ubicaciones y sucursales</small>
                </div>
            </div>
            <p class="card-text">Controla las ubicaciones físicas del inventario, bodegas principales y sub-bodegas secundarias.</p>
            <a href="{{ url('/bodegas') }}" class="btn btn-success mt-auto text-white" style="background: linear-gradient(45deg, #2ecc71 0%, #27ae60 100%); border:none;">Gestionar Bodegas</a>
        </div>
    </div>

    <!-- Card Ordenes -->
    <div class="col-md-4">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info me-3">
                    <i class="fa-solid fa-file-contract fa-2x"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0">Órdenes de Trabajo</h5>
                    <small class="text-muted">Operaciones y Obras</small>
                </div>
            </div>
            <p class="card-text">Genera, edita y supervisa las órdenes de trabajo, vinculándolas a proyectos específicos.</p>
            <a href="{{ url('/ordenes-trabajo') }}" class="btn btn-info mt-auto text-white" style="background: linear-gradient(45deg, #00d2ff 0%, #00a8cc 100%); border:none;">Gestionar Órdenes</a>
        </div>
    </div>
</div>
@endsection
