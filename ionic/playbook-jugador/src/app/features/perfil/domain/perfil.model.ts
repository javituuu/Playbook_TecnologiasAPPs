import { BloqueoHorario } from '../../bloqueos/domain/bloqueo-horario.model';

export interface PerfilJugador {
  id: number;
  nombre_completo: string;
  email: string;
  club_actual: string;
  disciplina: string;
  rol: string;
  rango: string;
  estado: string;
  horarios_bloqueados: BloqueoHorario[];
}

export interface PerfilResponse {
  status: string;
  data: PerfilJugador;
}
