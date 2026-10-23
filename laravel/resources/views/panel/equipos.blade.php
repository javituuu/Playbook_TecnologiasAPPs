@extends('layouts.panel')

@section('titulo', 'Equipos')

@section('contenido')
    <header class="cabecera">
        <h1>Equipos</h1>
        @if ($equipos->isNotEmpty())
            @php $incompletos = collect($faltantes)->filter()->count(); @endphp
            <p>
                {{ trans_choice(':count equipo|:count equipos', $equipos->count()) }}
                @if ($incompletos > 0) · {{ $incompletos }} con titulares pendientes @endif
            </p>
        @endif
    </header>

    @if ($equipos->isEmpty())
        <div class="vacio">
            <x-icono nombre="people-outline" />
            <p>Todavía no hay equipos registrados.</p>
        </div>
    @else
        <div class="tabla">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Equipo</th>
                        <th scope="col">Club</th>
                        <th scope="col">Disciplina</th>
                        <th scope="col">Titulares</th>
                        <th scope="col">Plantel</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($equipos as $equipo)
                        @php
                            $requeridos = $equipo->disciplina?->titulares_requeridos ?? 5;
                            $falta = $faltantes[$equipo->id] ?? [];
                        @endphp
                        <tr class="enlazada">
                            <td class="fuerte">
                                <a href="{{ route('equipos.show', $equipo) }}">{{ $equipo->nombre }}</a>
                                @unless ($equipo->activo)
                                    <x-estado valor="inactivo" />
                                @endunless
                            </td>
                            <td class="tenue" data-label="Club">{{ $equipo->club?->siglas ?? '—' }}</td>
                            <td data-label="Disciplina">{{ $equipo->disciplina?->nombre ?? '—' }}</td>
                            <td data-label="Titulares">
                                <span @class(['num', 'falta' => $falta !== []])>
                                    {{ $equipo->titulares_count }}/{{ $requeridos }}@if ($falta !== []) · falta {{ \Illuminate\Support\Arr::join($falta, ', ', ' y ') }}@endif
                                </span>
                            </td>
                            <td class="num" data-label="Plantel">{{ $equipo->membresias_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
