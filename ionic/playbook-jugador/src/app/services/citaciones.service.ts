import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

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
export class CitacionesService {
  private readonly apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  getCitaciones(): Observable<{ status: string; data: CitacionItem[] }> {
    return this.http.get<{ status: string; data: CitacionItem[] }>(`${this.apiUrl}/citaciones`).pipe(
      catchError(this.handleError)
    );
  }

  responderCitacion(idCitacion: number, estado: 'confirmado' | 'rechazado'): Observable<any> {
    if (estado !== 'confirmado' && estado !== 'rechazado') {
      return throwError(() => new Error('Estado inválido. Debe ser "confirmado" o "rechazado".'));
    }

    return this.http.patch<any>(`${this.apiUrl}/citaciones/${idCitacion}/responder`, { estado }).pipe(
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
    console.error('CitacionesService Error:', mensajeError, error);
    return throwError(() => new Error(mensajeError));
  }
}
