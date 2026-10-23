@extends('layouts.panel')

@section('titulo', 'Fixture')

@section('contenido')
    <header class="cabecera">
        <h1>Fixture</h1>
        @if ($grupos->isNotEmpty())
            <p>
                {{ trans_choice(':count partido|:count partidos', $grupos->sum->count()) }}
                @if ($tentativos > 0) · {{ $tentativos }} por confirmar @endif
            </p>
        @endif
    </header>

    @forelse ($grupos as $nombre => $partidos)
        @php $liga = $partidos->first()->liga; @endphp
        <section class="bloque" aria-labelledby="grupo-{{ $loop->index }}">
            <div class="bloque__titulo">
                <h2 id="grupo-{{ $loop->index }}">{{ $liga ? $nombre : 'Prácticas internas' }}</h2>
                @if ($liga)
                    <span>{{ $liga->disciplina?->nombre }} · Temporada {{ $liga->temporada }}</span>
                @endif
            </div>

            <div class="tabla">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Partido</th>
                            <th scope="col">Jornada</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Hora</th>
                            <th scope="col">Lugar</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($partidos as $partido)
                            {{-- Los partidos que ya pasaron van atenuados para que los próximos destaquen --}}
                            <tr @class(['pasado' => $partido->fecha_hora_inicio?->isPast()])>
                                <td class="fuerte">
                                    @if ($partido->equipoLocal)
                                        <a class="equipo" href="{{ route('equipos.show', $partido->equipoLocal) }}">{{ $partido->equipoLocal->nombre }}</a>
                                    @else
                                        Sin equipo
                                    @endif
                                    @if ($partido->equipoVisitante)
                                        <span class="vs">vs</span>
                                        <a class="equipo" href="{{ route('equipos.show', $partido->equipoVisitante) }}">{{ $partido->equipoVisitante->nombre }}</a>
                                    @endif
                                </td>
                                <td class="num" data-label="Jornada">{{ $partido->jornada_numero ?? '—' }}</td>
                                <td data-label="Fecha">{{ $partido->fecha_hora_inicio ? str_replace('.', '', $partido->fecha_hora_inicio->locale('es')->isoFormat('ddd D MMM')) : 'Sin fecha' }}</td>
                                <td class="num" data-label="Hora">{{ $partido->fecha_hora_inicio?->format('H:i') ?? '—' }}</td>
                                <td class="tenue" data-label="Lugar">{{ $partido->lugar ?? 'Por definir' }}</td>
                                <td data-label="Estado"><x-estado :valor="$partido->estado" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="vacio">
            <x-icono nombre="calendar-outline" />
            <p>Todavía no hay partidos cargados.</p>
        </div>
    @endforelse
@endsection
