@props(['nombre'])
{{-- Mismos íconos SVG de Ionicons para mantener consistencia con la app móvil. --}}
<span {{ $attributes->merge(['class' => 'icono']) }} data-icono="{{ $nombre }}" aria-hidden="true">{!! file_get_contents(resource_path("svg/{$nombre}.svg")) !!}</span>
