import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';

export interface PerfilResponse {
  status: string;
  data: {
    id: number;
    nombre_completo: string;
    email: string;
    club_actual: string;
    disciplina: string;
    rol: string;
    rango: string;
    estado: string;
    horarios_bloqueados: Array<{
      dia_semana: string;
      hora_inicio: string;
      hora_fin: string;
    }>;
  };
}

export interface AgendaItem {
  id_partido: number;
  fecha_hora: string;
  liga: string;
  equipo_local: string;
  equipo_visitante: string;
  lugar: string;
  estado: string;
}

export interface CitacionItem {
  id_citacion: number;
  id_partido: number;
  fecha_hora: string;
  equipo_rival: string;
  liga: string;
  mensaje_coach: string;
  estado_respuesta: string;
}

@Injectable({
  providedIn: 'root'
})
export class JugadorService {
  /** URL base para comunicarse con la API de Laravel */
  private readonly apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  /**
   * Obtiene el perfil del jugador y sus horarios bloqueados.
   */
  getPerfil(): Observable<PerfilResponse> {
    return this.http.get<PerfilResponse>(`${this.apiUrl}/perfil`).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene la agenda de partidos confirmados.
   */
  getAgenda(): Observable<{ status: string; data: AgendaItem[] }> {
    return this.http.get<{ status: string; data: AgendaItem[] }>(`${this.apiUrl}/agenda`).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene las citaciones pendientes de respuesta.
   */
  getCitaciones(): Observable<{ status: string; data: CitacionItem[] }> {
    return this.http.get<{ status: string; data: CitacionItem[] }>(`${this.apiUrl}/citaciones`).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Responde a una citación con validación de condición previa.
   * @param idCitacion ID de la citación a actualizar
   * @param estado Estado de respuesta ('confirmado' o 'rechazado')
   */
  responderCitacion(idCitacion: number, estado: 'confirmado' | 'rechazado'): Observable<any> {
    // Operación condicional: Validar que el estado sea válido antes de enviar
    if (estado !== 'confirmado' && estado !== 'rechazado') {
      return throwError(() => new Error('Estado inválido. Debe ser "confirmado" o "rechazado".'));
    }

    return this.http.patch<any>(`${this.apiUrl}/citaciones/${idCitacion}/responder`, { estado }).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Manejador centralizado de errores HTTP.
   */
  private handleError(error: HttpErrorResponse): Observable<never> {
    let mensajeError = 'Ocurrió un error inesperado al conectar con el servidor.';

    if (error.error instanceof ErrorEvent) {
      // Error del lado del cliente
      mensajeError = `Error del cliente: ${error.error.message}`;
    } else {
      // Error del lado del servidor (código 4xx o 5xx)
      mensajeError = `Error ${error.status}: ${error.message || 'Fallo en la API'}`;
    }

    console.error('JugadorService Error:', mensajeError, error);
    return throwError(() => new Error(mensajeError));
  }
}
