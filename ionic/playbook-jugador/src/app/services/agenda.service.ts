import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';

export interface AgendaItem {
  id_partido: number;
  fecha_hora: string;
  liga: string;
  equipo_local: string;
  equipo_visitante: string;
  lugar: string;
  estado: string;
}

@Injectable({
  providedIn: 'root'
})
export class AgendaService {
  private readonly apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  getAgenda(): Observable<{ status: string; data: AgendaItem[] }> {
    return this.http.get<{ status: string; data: AgendaItem[] }>(`${this.apiUrl}/agenda`).pipe(
      catchError(this.handleError)
    );
  }

  private handleError(error: HttpErrorResponse): Observable<never> {
    let mensajeError = 'Ocurrió un error inesperado al conectar con el servidor.';
    if (error.error instanceof ErrorEvent) {
      mensajeError = `Error del cliente: ${error.error.message}`;
    } else {
      mensajeError = `Error ${error.status}: ${error.message || 'Fallo en la API'}`;
    }
    console.error('AgendaService Error:', mensajeError, error);
    return throwError(() => new Error(mensajeError));
  }
}
