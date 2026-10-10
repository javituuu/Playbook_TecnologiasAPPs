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

@Injectable({
  providedIn: 'root'
})
export class PerfilService {
  private readonly apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  getPerfil(): Observable<PerfilResponse> {
    return this.http.get<PerfilResponse>(`${this.apiUrl}/perfil`).pipe(
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
    console.error('PerfilService Error:', mensajeError, error);
    return throwError(() => new Error(mensajeError));
  }
}
