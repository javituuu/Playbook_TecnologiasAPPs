import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

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
  private readonly apiUrl = environment.apiUrl;

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
      mensajeError = error.error?.message || `Error ${error.status}`;
    }
    console.error('AgendaService Error:', mensajeError, error);
    return throwError(() => new Error(mensajeError));
  }
}
