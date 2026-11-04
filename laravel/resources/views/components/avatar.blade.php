@props([
    'nombre' => '',
    'dorsal' => null,
    'foto' => null,
    'size' => 'md',
    'tamano' => null,
])
@php
    $iniciales = collect(explode(' ', trim((string) $nombre)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_substr($p, 0, 1))
        ->join('');
    $iniciales = $iniciales !== '' ? mb_strtoupper($iniciales) : '—';
    $estilos = [];
    if ($tamano) {
        $tamanoCss = is_numeric($tamano) ? "{$tamano}px" : $tamano;
        $estilos[] = "width: {$tamanoCss}";
        $estilos[] = "height: {$tamanoCss}";
    }
    $estiloAttr = $estilos !== [] ? implode('; ', $estilos) . ';' : null;
@endphp
<div {{ $attributes->merge(['class' => 'avatar avatar--' . $size, 'style' => $estiloAttr]) }}>
    @if ($foto)
        <img src="{{ $foto }}" alt="{{ $nombre ?: 'Avatar' }}" loading="lazy">
    @else
        <span class="avatar__iniciales">{{ $iniciales }}</span>
    @endif
    @if ($dorsal !== null && $dorsal !== '')
        <b class="avatar__dorsal">{{ $dorsal }}</b>
    @endif
</div>
