import { Component, ChangeDetectorRef, OnDestroy } from '@angular/core';
import { NgIf } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import {
  IonContent, IonList, IonItem,
  IonInput, IonButton, IonIcon, IonSpinner
} from '@ionic/angular';
import { addIcons } from 'ionicons';
import { alertCircleOutline, timeOutline } from 'ionicons/icons';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-login',
  templateUrl: 'login.page.html',
  styleUrls: ['login.page.scss'],
  imports: [
    NgIf, FormsModule,
    IonContent, IonList, IonItem,
    IonInput, IonButton, IonIcon, IonSpinner
  ],
})
export class LoginPage implements OnDestroy {
  email = '';
  password = '';
  cargando = false;
  error = '';
  bloqueadoHasta = 0; // segundos que faltan para poder reintentar (429)
  private temporizador: any;

  constructor(private auth: AuthService, private router: Router, private cdr: ChangeDetectorRef) {
    addIcons({ alertCircleOutline, timeOutline });
  }

  async entrar() {
    if (this.cargando || this.bloqueadoHasta > 0) return;
    this.cargando = true;
    this.error = '';
    this.cdr.detectChanges();

    this.auth.login(this.email, this.password).subscribe({
      next: () => this.router.navigateByUrl('/home', { replaceUrl: true }),
      error: (err: HttpErrorResponse) => {
        this.cargando = false;
        if (err.status === 401) {
          this.error = 'Correo o contraseña incorrectos.';
        } else if (err.status === 403 || err.status === 422) {
          this.error = err.error?.message || `Error ${err.status}`;
        } else if (err.status === 429) {
          this.error = 'Demasiados intentos fallidos. Espera 1 minuto antes de reintentar.';
          this.bloquear();
        } else {
          this.error = 'No se pudo conectar con el servidor.';
        }
        this.cdr.detectChanges();
      }
    });
  }

  private bloquear() {
    this.bloqueadoHasta = 60;
    clearInterval(this.temporizador);
    this.temporizador = setInterval(() => {
      this.bloqueadoHasta--;
      if (this.bloqueadoHasta <= 0) clearInterval(this.temporizador);
      this.cdr.detectChanges();
    }, 1000);
  }

  ngOnDestroy() {
    clearInterval(this.temporizador);
  }
}
