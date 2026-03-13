@extends('layouts.app')

{{-- BOTÓN ATRÁS (opcional) --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-chevron-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Panel General
@endsection

{{-- BOTONES DERECHA --}}
@section('header-buttons')
{{-- vacio --}}
@endsection

@section('content')
<div class="card ui-card container-card my-4 mx-auto" style="max-width: 1000px;">
    <div class="card-header text-center pt-4">
        <h4 class="mb-0 fw-semibold">
            <i class="fas fa-cog me-2 text-primary"></i>
            Panel de Configuración General
        </h4>
    </div>
    <div class="card-body px-4 pb-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form action="{{ route('configuracion.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Columna izquierda -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nombre_empresa" class="form-label">Nombre de empresa:</label>
                        <input type="text" name="nombre_empresa" id="nombre_empresa" class="form-control" value="{{ $config->nombre_empresa }}">
                    </div>

                    <div class="mb-3">
                        <label for="ruc" class="form-label">RUC:</label>
                        <input type="text" name="ruc" id="ruc" class="form-control" value="{{ $config->ruc }}">
                    </div>

                    <div class="mb-3">
                        <label for="moneda" class="form-label">Moneda:</label>
                        <input type="text" name="moneda" id="moneda" class="form-control" value="{{ $config->moneda }}">
                    </div>

                    <div class="mb-3">
                        <label for="igv" class="form-label">IGV (%):</label>
                        <input type="number" step="0.01" name="igv" id="igv" class="form-control" value="{{ $config->igv }}">
                    </div>

                </div>

                <!-- Columna derecha -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección:</label>
                        <textarea name="direccion" id="direccion" class="form-control" rows="4">{{ $config->direccion }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono:</label>
                        <input type="text" name="telefono" id="telefono" class="form-control" value="{{ $config->telefono }}">
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo:</label>
                        <input type="email" name="correo" id="correo" class="form-control" value="{{ $config->correo }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Logo actual:</label><br>
                        @if($config->logo)
                            <img src="{{ asset($config->logo) }}" alt="Logo" width="100" class="mb-2">
                        @endif
                        <input type="file" name="logo" class="form-control mt-2">
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
