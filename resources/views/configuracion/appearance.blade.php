@extends('layouts.app')

@section('header-back')
<button class="btn-header-back" onclick="history.back()"><i class="fas fa-chevron-left"></i></button>
@endsection

@section('header-title')
Configuración de apariencia
@endsection

@section('content')
<div class="card ui-card container-card my-4 mx-auto" style="max-width:1000px;">
    <div class="card-header text-center pt-4">
        <h4 class="mb-0 fw-semibold"><i class="fas fa-palette me-2 text-primary"></i>Personalización visual</h4>
    </div>
    <div class="card-body px-4 pb-4">
        <nav class="config-subpages" aria-label="Secciones de configuración">
            <a href="{{ route('configuracion.index') }}"><i class="fas fa-building"></i><span>Datos generales</span></a>
            <a href="{{ route('configuracion.appearance') }}" class="active"><i class="fas fa-palette"></i><span>Apariencia</span></a>
        </nav>

        @php($lightTheme = array_merge(\App\Models\Configuracion::lightThemeDefaults(), old('light_theme', $config->light_theme ?? [])))
        <form action="{{ route('configuracion.appearance.update') }}" method="POST" data-theme-config>
            @csrf
            @method('PUT')
            <div class="theme-config-heading">
                <div><h5 class="mb-0">Colores del sistema</h5><p>Se aplican solamente al modo claro del POS. El modo oscuro y el catálogo no cambian.</p></div>
                <button type="button" class="theme-reset" data-theme-reset><i class="fas fa-rotate-left me-1"></i> Restaurar</button>
            </div>
            <label class="theme-enable-toggle">
                <input type="checkbox" name="light_theme[enabled]" value="1" data-theme-enabled @checked($lightTheme['enabled'])>
                <span><strong>Aplicar personalización</strong><small>Mientras esté desactivado, el POS conservará su apariencia original.</small></span>
            </label>
            <div class="theme-config-grid">
                <article class="theme-color-card"><strong>Color principal</strong><div class="theme-color-inputs"><label><input type="color" name="light_theme[accent]" value="{{ $lightTheme['accent'] }}" data-theme-color="accent"><span>Botones y activos</span></label></div><div class="theme-preview" data-theme-preview="accent"></div></article>
                @foreach(['header'=>'Encabezado principal','sidebar'=>'Menú lateral','footer'=>'Pie de página','table'=>'Encabezados de tablas','modal'=>'Encabezados de modales'] as $themeKey => $themeLabel)
                    <article class="theme-color-card">
                        <strong>{{ $themeLabel }}</strong>
                        <div class="theme-color-inputs"><label><input type="color" name="light_theme[{{ $themeKey }}_from]" value="{{ $lightTheme[$themeKey.'_from'] }}" data-theme-color="{{ $themeKey }}_from"><span>Color 1</span></label><label><input type="color" name="light_theme[{{ $themeKey }}_to]" value="{{ $lightTheme[$themeKey.'_to'] }}" data-theme-color="{{ $themeKey }}_to"><span>Color 2</span></label></div>
                        <label class="theme-gradient-toggle"><input type="checkbox" name="light_theme[{{ $themeKey }}_gradient]" value="1" data-theme-gradient="{{ $themeKey }}" @checked($lightTheme[$themeKey.'_gradient'])> Usar degradado</label>
                        <div class="theme-preview" data-theme-preview="{{ $themeKey }}"></div>
                    </article>
                @endforeach
            </div>
            <div class="text-center mt-4"><button type="submit" class="btn-soft btn-soft-success px-4"><i class="fas fa-save me-1"></i> Guardar apariencia</button></div>
        </form>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/light-theme-customizer.js') }}?v={{ filemtime(public_path('js/light-theme-customizer.js')) }}"></script>
@endpush
@endsection
