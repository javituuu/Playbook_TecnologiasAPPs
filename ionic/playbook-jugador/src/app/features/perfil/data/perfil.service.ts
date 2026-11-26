import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../../../environments/environment';
import { handleHttpError } from '../../../core/http/http-error.util';
import { PerfilResponse } from '../domain/perfil.model';

export type { PerfilJugador, PerfilResponse } from '../domain/perfil.model';

@Injectable({
  providedIn: 'root',
})
export class PerfilService {
  private readonly apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  getPerfil(): Observable<PerfilResponse> {
    return this.http
      .get<PerfilResponse>(`${this.apiUrl}/perfil`)
      .pipe(catchError(handleHttpError('PerfilService')));
  }
}
