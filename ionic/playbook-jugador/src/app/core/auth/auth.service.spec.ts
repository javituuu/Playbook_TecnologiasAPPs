import { TestBed } from '@angular/core/testing';
import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { Router, UrlTree, provideRouter } from '@angular/router';
import { provideIonicAngular } from '@ionic/angular';
import { Preferences } from '@capacitor/preferences';
import { environment } from '../../../environments/environment';
import { AuthService, authGuard, authInterceptor } from './auth.service';

// Node 22+ expone un localStorage global roto dentro de jsdom: mockeamos Preferences en memoria.
const almacen = vi.hoisted(() => new Map<string, string>());
vi.mock('@capacitor/preferences', () => ({
  Preferences: {
    get: async ({ key }: { key: string }) => ({ value: almacen.get(key) ?? null }),
    set: async ({ key, value }: { key: string; value: string }) => {
      almacen.set(key, value);
    },
    remove: async ({ key }: { key: string }) => {
      almacen.delete(key);
    },
  },
}));

const esperar = () => new Promise((r) => setTimeout(r));

describe('Auth (token, interceptor, guard)', () => {
  let auth: AuthService;
  let http: HttpClient;
  let httpMock: HttpTestingController;
  let router: Router;

  beforeEach(() => {
    almacen.clear();
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(withInterceptors([authInterceptor])),
        provideHttpClientTesting(),
        provideIonicAngular(),
        provideRouter([]),
      ],
    });
    auth = TestBed.inject(AuthService);
    http = TestBed.inject(HttpClient);
    httpMock = TestBed.inject(HttpTestingController);
    router = TestBed.inject(Router);
  });

  afterEach(() => {
    httpMock.verify();
    vi.restoreAllMocks();
  });

  it('agrega Authorization Bearer cuando hay token', async () => {
    auth.guardarToken('tok-123');
    await esperar();

    http.get(`${environment.apiUrl}/perfil`).subscribe();

    const req = httpMock.expectOne(`${environment.apiUrl}/perfil`);
    expect(req.request.headers.get('Authorization')).toBe('Bearer tok-123');
    req.flush({});
  });

  it('limpia el token y redirige a /login ante 401 en una ruta protegida', async () => {
    const navegar = vi.spyOn(router, 'navigateByUrl').mockResolvedValue(true);
    auth.guardarToken('tok-vencido');
    await esperar();

    http.get(`${environment.apiUrl}/perfil`).subscribe({ error: () => undefined });
    httpMock.expectOne(`${environment.apiUrl}/perfil`).flush({}, { status: 401, statusText: 'Unauthorized' });
    await esperar();

    expect(auth.token).toBeNull();
    expect((await Preferences.get({ key: 'playbook_auth_token' })).value).toBeNull();
    expect(navegar).toHaveBeenCalledWith('/login', { replaceUrl: true });
  });

  it('un 401 en /auth/login no redirige', async () => {
    const navegar = vi.spyOn(router, 'navigateByUrl').mockResolvedValue(true);

    auth.login('mal@correo.cl', 'mala').subscribe({ error: () => undefined });
    httpMock
      .expectOne(`${environment.apiUrl}/auth/login`)
      .flush({ message: 'Credenciales incorrectas.' }, { status: 401, statusText: 'Unauthorized' });
    await esperar();

    expect(navegar).not.toHaveBeenCalled();
  });

  it('authGuard sin token redirige a /login', async () => {
    const resultado = await TestBed.runInInjectionContext(() =>
      authGuard({} as any, {} as any)
    );

    expect(resultado).toBeInstanceOf(UrlTree);
    expect(router.serializeUrl(resultado as UrlTree)).toBe('/login');
    httpMock.expectNone(`${environment.apiUrl}/auth/verify`);
  });
});
