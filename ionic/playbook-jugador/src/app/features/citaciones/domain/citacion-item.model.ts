export interface CitacionItem {
  id_citacion: number;
  id_partido: number;
  fecha_hora: string;
  equipo_rival: string;
  liga: string;
  mensaje_coach: string;
  estado_respuesta: string;
}

export type RespuestaCitacion = 'confirmado' | 'rechazado';
