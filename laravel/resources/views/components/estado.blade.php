@props(['valor'])
@php
    $valor = $valor instanceof \BackedEnum ? $valor->value : (string) $valor;
    $tono = match ($valor) {
        'publicado', 'programado', 'finalizado', 'titular', 'aceptada' => 'ok',
        'tentativo', 'en_prueba', 'pendiente' => 'aviso',
        'conflicto', 'cancelado', 'rechazada' => 'error',
        default => 'neutro',
    };
    // "Tentativo" es el término del dominio; en pantalla se lee mejor como "Por confirmar".
    $etiqueta = $valor === 'tentativo' ? 'Por confirmar' : \Illuminate\Support\Str::ucfirst(str_replace('_', ' ', $valor));
@endphp
<span class="estado estado--{{ $tono }}">{{ $etiqueta }}</span>
