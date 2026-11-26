import { HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';

export function handleHttpError(context: string) {
  return (error: HttpErrorResponse): Observable<never> => {
    let mensajeError = 'Ocurrió un error inesperado al conectar con el servidor.';
    if (error.error instanceof ErrorEvent) {
      mensajeError = `Error del cliente: ${error.error.message}`;
    } else {
      mensajeError = error.error?.message || `Error ${error.status}`;
    }
    console.error(`${context} Error:`, mensajeError, error);
    return throwError(() => new Error(mensajeError));
  };
}
