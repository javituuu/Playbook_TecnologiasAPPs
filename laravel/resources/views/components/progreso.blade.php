@props([
    'valor' => null,
    'porcentaje' => null,
    'color' => 'acento',
    'altura' => 6,
])
@php
    $pctRaw = $porcentaje ?? $valor ?? 0;
    $porcentajeFinal = max(0, min(100, (float) $pctRaw));
    $porcentajeRedondeado = round($porcentajeFinal, 1);
    if ((float) (int) $porcentajeRedondeado === (float) $porcentajeRedondeado) {
        $porcentajeRedondeado = (int) $porcentajeRedondeado;
    }
@endphp
<div {{ $attributes->merge(['class' => 'progreso progreso--' . $color, 'aria-label' => $porcentajeRedondeado . '%']) }} role="progressbar" aria-valuenow="{{ $porcentajeRedondeado }}" aria-valuemin="0" aria-valuemax="100">
    <div class="progreso__pista" style="height: {{ $altura }}px;">
        <div class="progreso__barra" style="width: {{ $porcentajeRedondeado }}%;"></div>
    </div>
</div>
