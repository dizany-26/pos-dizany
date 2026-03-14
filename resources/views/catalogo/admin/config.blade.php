@extends('layouts.app')

@section('content')

<div class="container-fluid px-3">

    <div class="card ui-card container-card my-4">

        <div class="card-header text-center pt-4">
            <h4 class="mb-0 fw-semibold">
                <i class="fas fa-store me-2 text-primary"></i>
                Panel de Configuración del Catálogo
            </h4>
        </div>

        <div class="card-body px-4 pb-4">

            <form action="{{ route('catalogo.admin.config.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">

                    <!-- COLUMNA IZQUIERDA -->
                    <div class="col-md-6">

                        <div class="mb-3">
                            <label class="form-label">Nombre de Empresa</label>
                            <input type="text" name="nombre_empresa" class="form-control"
                                   value="{{ $config->nombre_empresa ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rubro</label>
                            <input type="text" name="rubro" class="form-control"
                                   value="{{ $config->rubro ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Teléfono (WhatsApp)</label>
                            <input type="text" name="telefono" class="form-control"
                                   value="{{ $config->telefono ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Correo</label>
                            <input type="email" name="correo" class="form-control"
                                   value="{{ $config->correo ?? '' }}">
                        </div>

                    </div>

                    <!-- COLUMNA DERECHA -->
                    <div class="col-md-6">

                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea name="direccion" class="form-control"
                                      rows="2">{{ $config->direccion ?? '' }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mensaje de Bienvenida</label>
                            <textarea name="mensaje_bienvenida" class="form-control"
                                      rows="3">{{ $config->mensaje_bienvenida ?? '' }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Texto Botón WhatsApp</label>
                            <input type="text" name="texto_boton_whatsapp" class="form-control"
                                   value="{{ $config->texto_boton_whatsapp ?? 'Comprar por WhatsApp' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Logo del Catálogo</label>

                            @if(!empty($config->logo))
                                <div class="mb-2">
                                    <img src="{{ asset('uploads/config/' . $config->logo) }}" height="60">
                                </div>
                            @endif

                            <input type="file" name="logo" class="form-control">
                        </div>

                    </div>

                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn-soft btn-soft-success px-4">
                        <i class="fas fa-save me-1"></i>
                        Guardar Cambios
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

@endsection
