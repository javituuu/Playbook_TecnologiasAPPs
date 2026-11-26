import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../../../environments/environment';
import { handleHttpError } from '../../../core/http/http-error.util';
import { AgendaItem } from '../domain/agenda-item.model';

export type { AgendaItem } from '../domain/agenda-item.model';

@Injectable({
  providedIn: 'root',
})
export class AgendaService {
  private readonly apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  getAgenda(): Observable<{ status: string; data: AgendaItem[] }> {
    return this.http
      .get<{ status: string; data: AgendaItem[] }>(`${this.apiUrl}/agenda`)
      .pipe(catchError(handleHttpError('AgendaService')));
  }
}
