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
      id: number;
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
  /** Es el `php artisan serve` local, puerto 8000. */
  private readonly apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  getPerfil(): Observable<PerfilResponse> {
    return this.http.get<PerfilResponse>(`${this.apiUrl}/perfil`).pipe(
      catchError(this.handleError)
    );
  }

  /** Solo partidos con citación confirmada. */
  getAgenda(): Observable<{ status: string; data: AgendaItem[] }> {
    return this.http.get<{ status: string; data: AgendaItem[] }>(`${this.apiUrl}/agenda`).pipe(
      catchError(this.handleError)
    );
  }

  /** Solo citaciones sin responder. */
  getCitaciones(): Observable<{ status: string; data: CitacionItem[] }> {
    return this.http.get<{ status: string; data: CitacionItem[] }>(`${this.apiUrl}/citaciones`).pipe(
      catchError(this.handleError)
    );
  }


  responderCitacion(idCitacion: number, estado: 'confirmado' | 'rechazado'): Observable<any> {
    // Si el estado es otro el backend responde 400, así que ni hacemos la llamada.
    if (estado !== 'confirmado' && estado !== 'rechazado') {
      return throwError(() => new Error('Estado inválido. Debe ser "confirmado" o "rechazado".'));
    }

    return this.http.patch<any>(`${this.apiUrl}/citaciones/${idCitacion}/responder`, { estado }).pipe(
      catchError(this.handleError)
    );
  }

  crearBloqueo(datos: { dia_semana: string, hora_inicio: string, hora_fin: string }): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/bloqueos`, datos).pipe(
      catchError(this.handleError)
    );
  }

  borrarBloqueo(id: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}/bloqueos/${id}`).pipe(
      catchError(this.handleError)
    );
  }

  /** Deja todos los fallos HTTP como un Error con mensaje listo para mostrar en el toast. */
  private handleError(error: HttpErrorResponse): Observable<never> {
    let mensajeError = 'Ocurrió un error inesperado al conectar con el servidor.';

    if (error.error instanceof ErrorEvent) {
      // Ni llegó al servidor (red caída, CORS...)
      mensajeError = `Error del cliente: ${error.error.message}`;
    } else {
      // Llegó al servidor y respondió 4xx o 5xx
      mensajeError = `Error ${error.status}: ${error.message || 'Fallo en la API'}`;
    }

    console.error('JugadorService Error:', mensajeError, error);
    return throwError(() => new Error(mensajeError));
  }
}
