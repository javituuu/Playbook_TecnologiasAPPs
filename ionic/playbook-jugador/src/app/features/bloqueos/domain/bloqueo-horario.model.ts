export interface CrearBloqueoRequest {
  dia_semana: string;
  hora_inicio: string;
  hora_fin: string;
}

export interface BloqueoHorario {
  id: number;
  dia_semana: string;
  hora_inicio: string;
  hora_fin: string;
}
