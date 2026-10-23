@props(['nombre'])
{{-- Íconos de Ionicons copiados desde la app Ionic, para que ambos frentes usen el mismo trazo. --}}
<span {{ $attributes->merge(['class' => 'icono']) }} aria-hidden="true">{!! file_get_contents(resource_path("svg/{$nombre}.svg")) !!}</span>
