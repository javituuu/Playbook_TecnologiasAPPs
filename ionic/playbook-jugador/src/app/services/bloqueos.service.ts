import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';

@Injectable({
  providedIn: 'root'
})
export class BloqueosService {
  private readonly apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

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

  private handleError(error: HttpErrorResponse): Observable<never> {
    let mensajeError = 'Ocurrió un error inesperado al conectar con el servidor.';
    if (error.error instanceof ErrorEvent) {
      mensajeError = `Error del cliente: ${error.error.message}`;
    } else {
      mensajeError = `Error ${error.status}: ${error.message || 'Fallo en la API'}`;
    }
    console.error('BloqueosService Error:', mensajeError, error);
    return throwError(() => new Error(mensajeError));
  }
}
