import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../../../environments/environment';
import { handleHttpError } from '../../../core/http/http-error.util';
import { CitacionItem, RespuestaCitacion } from '../domain/citacion-item.model';

export type { CitacionItem, RespuestaCitacion } from '../domain/citacion-item.model';

@Injectable({
  providedIn: 'root',
})
export class CitacionesService {
  private readonly apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  getCitaciones(): Observable<{ status: string; data: CitacionItem[] }> {
    return this.http
      .get<{ status: string; data: CitacionItem[] }>(`${this.apiUrl}/citaciones`)
      .pipe(catchError(handleHttpError('CitacionesService')));
  }

  responderCitacion(idCitacion: number, estado: RespuestaCitacion): Observable<any> {
    if (estado !== 'confirmado' && estado !== 'rechazado') {
      return throwError(() => new Error('Estado inválido. Debe ser "confirmado" o "rechazado".'));
    }

    return this.http
      .patch<any>(`${this.apiUrl}/citaciones/${idCitacion}/responder`, { estado })
      .pipe(catchError(handleHttpError('CitacionesService')));
  }
}
