import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../../../environments/environment';
import { handleHttpError } from '../../../core/http/http-error.util';
import { CrearBloqueoRequest } from '../domain/bloqueo-horario.model';

export type { BloqueoHorario, CrearBloqueoRequest } from '../domain/bloqueo-horario.model';

@Injectable({
  providedIn: 'root',
})
export class BloqueosService {
  private readonly apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  crearBloqueo(datos: CrearBloqueoRequest): Observable<any> {
    return this.http
      .post<any>(`${this.apiUrl}/bloqueos`, datos)
      .pipe(catchError(handleHttpError('BloqueosService')));
  }

  borrarBloqueo(id: number): Observable<any> {
    return this.http
      .delete<any>(`${this.apiUrl}/bloqueos/${id}`)
      .pipe(catchError(handleHttpError('BloqueosService')));
  }
}
