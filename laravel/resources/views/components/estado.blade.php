@props([
    'valor',
    'dot' => true,
])

@php
    $raw = $valor;
    $enumLabel = null;
    if ($raw instanceof \BackedEnum) {
        if (method_exists($raw, 'label')) {
            $enumLabel = $raw->label();
        }
        $cadena = (string) $raw->value;
    } else {
        $cadena = (string) $raw;
    }

    $clave = strtolower(trim($cadena));

    $tono = match ($clave) {
        'publicado', 'programado', 'finalizado', 'titular', 'aceptada', 'confirmado', 'confirmada', 'en_curso', 'finalizada', 'activo', 'activa', 'ok' => 'ok',
        'tentativo', 'en_prueba', 'pendiente', 'inscripciones', 'aviso', 'advertencia', 'advertencia_blanda' => 'aviso',
        'conflicto', 'cancelado', 'rechazada', 'rechazado', 'inactivo', 'inactiva', 'vacante', 'error', 'violacion_dura' => 'error',
        default => 'neutro',
    };

    if ($clave === 'tentativo') {
        // En la base de datos se guarda como 'tentativo'; de cara al usuario mostramos 'Por confirmar'.
        $etiqueta = 'Por confirmar';
    } elseif ($clave === 'en_prueba') {
        $etiqueta = 'En prueba';
    } elseif ($clave === 'en_curso') {
        $etiqueta = 'En curso';
    } elseif ($clave === 'violacion_dura') {
        $etiqueta = 'Conflicto crítico';
    } elseif ($clave === 'advertencia_blanda') {
        $etiqueta = 'Advertencia';
    } elseif ($clave === 'admin_club') {
        $etiqueta = 'Administrador de club';
    } elseif ($clave === 'admin_liga') {
        $etiqueta = 'Administrador de liga';
    } elseif ($enumLabel !== null) {
        $etiqueta = $enumLabel;
    } else {
        $limpio = preg_replace('/[_\-]+/', ' ', trim($cadena));
        $etiqueta = \Illuminate\Support\Str::ucfirst(mb_strtolower($limpio));
    }
@endphp

<span {{ $attributes->merge(['class' => "estado estado--{$tono}"]) }}>
    @if ($dot)
        <span class="estado__dot" aria-hidden="true"></span>
    @endif
    <span class="estado__texto">{{ $etiqueta }}</span>
</span>
