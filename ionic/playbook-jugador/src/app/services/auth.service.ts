import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { CanActivateFn, Router } from '@angular/router';
import { ToastController } from '@ionic/angular';
import { Preferences } from '@capacitor/preferences';
import { catchError, finalize, firstValueFrom, map, of, tap, throwError } from 'rxjs';
import { environment } from '../../environments/environment';

const CLAVE_TOKEN = 'playbook_auth_token';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private http = inject(HttpClient);
  token: string | null = null;

  async cargarToken() {
    this.token = (await Preferences.get({ key: CLAVE_TOKEN })).value;
  }

  login(email: string, password: string) {
    return this.http
      .post<{ data: { access_token: string } }>(`${environment.apiUrl}/auth/login`, {
        email,
        password,
        device_name: 'ionic-jugador',
        rol_esperado: 'jugador',
      })
      .pipe(tap((res) => this.guardarToken(res.data.access_token)));
  }

  guardarToken(token: string) {
    this.token = token;
    Preferences.set({ key: CLAVE_TOKEN, value: token });
  }

  limpiar() {
    this.token = null;
    Preferences.remove({ key: CLAVE_TOKEN });
  }

  verificar() {
    return this.http.get(`${environment.apiUrl}/auth/verify`);
  }

  logout() {
    return this.http
      .post(`${environment.apiUrl}/auth/logout`, {})
      .pipe(finalize(() => this.limpiar()));
  }
}

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const router = inject(Router);
  const toast = inject(ToastController);

  const headers: Record<string, string> = { Accept: 'application/json' };
  if (auth.token) headers['Authorization'] = `Bearer ${auth.token}`;

  return next(req.clone({ setHeaders: headers })).pipe(
    catchError((err: HttpErrorResponse) => {
      const esLogin = req.url.includes('/auth/login');
      if (err.status === 401 && !esLogin) {
        auth.limpiar();
        router.navigateByUrl('/login', { replaceUrl: true });
      } else if (err.status === 403 && !esLogin) {
        toast
          .create({
            message: err.error?.message ?? 'Tu cuenta no tiene permisos para acceder a esta aplicación o recurso.',
            color: 'danger',
            icon: 'alert-circle-outline',
            duration: 3500,
          })
          .then((t) => t.present());
      }
      return throwError(() => err);
    })
  );
};

export const authGuard: CanActivateFn = async () => {
  const auth = inject(AuthService);
  const router = inject(Router);
  const login = router.createUrlTree(['/login']);

  // Al reabrir la app el token está solo en Preferences: lo cargamos antes de decidir
  if (!auth.token) await auth.cargarToken();
  if (!auth.token) return login;

  // ponytail: me() no revisa "activo"; si la cuenta está desactivada cada llamada responde 403 y el toast lo maneja
  return firstValueFrom(
    auth.verificar().pipe(
      map(() => true),
      catchError((err: HttpErrorResponse) => {
        if (err.status === 401) {
          auth.limpiar();
          return of(login);
        }
        // Sin red u otro error: dejamos pasar y Home muestra el reintento
        return of(true);
      })
    )
  );
};
