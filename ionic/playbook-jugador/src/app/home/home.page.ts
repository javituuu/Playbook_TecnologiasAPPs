import { Component, OnInit, ChangeDetectorRef, ElementRef } from '@angular/core';
import { NgFor, NgIf, DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonSkeletonText, IonCard,
  IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent,
  IonList, IonItem, IonLabel, IonButton, IonFooter, IonTabBar,
  IonTabButton, IonIcon, IonSpinner, IonInput, IonSelect, IonSelectOption,
  ToastController
} from '@ionic/angular';
import { addIcons } from 'ionicons';
import { calendarOutline, calendarClearOutline, mailOutline, mailUnreadOutline, personOutline, trashOutline, addOutline, alertCircleOutline, timeOutline, logOutOutline, checkmarkCircleOutline, checkmarkOutline, closeOutline, closeCircleOutline, mailOpenOutline } from 'ionicons/icons';
import { Router } from '@angular/router';
import { Haptics, ImpactStyle } from '@capacitor/haptics';
import { AuthService } from '../services/auth.service';
import { PerfilService } from '../services/perfil.service';
import { AgendaService, AgendaItem } from '../services/agenda.service';
import { CitacionesService, CitacionItem } from '../services/citaciones.service';
import { BloqueosService } from '../services/bloqueos.service';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  imports: [
    NgFor, NgIf, DatePipe, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonSkeletonText, IonCard,
    IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent,
    IonList, IonItem, IonLabel, IonButton, IonFooter, IonTabBar,
    IonTabButton, IonIcon, IonSpinner, IonInput, IonSelect, IonSelectOption
  ],
})
export class HomePage implements OnInit {
  /** Las pestañas no son rutas, esta variable decide qué bloque se ve. */
  pestanaActiva: string = 'agenda';

  // Perfil: carga, error y datos
  perfil: any = null;
  cargandoPerfil: boolean = true;
  errorPerfil: boolean = false;

  // Agenda: carga, error y datos
  agenda: AgendaItem[] = [];
  cargandoAgenda: boolean = true;
  errorAgenda: boolean = false;

  // Citaciones: carga, error y datos
  citaciones: CitacionItem[] = [];
  cargandoCitaciones: boolean = true;
  errorCitaciones: boolean = false;

  // Formulario de bloqueos de horario
  mostrandoFormulario: boolean = false;
  guardandoBloqueo: boolean = false;
  nuevoBloqueo = {
    dia_semana: '',
    hora_inicio: '',
    hora_fin: ''
  };

  constructor(
    private perfilService: PerfilService,
    private agendaService: AgendaService,
    private citacionesService: CitacionesService,
    private bloqueosService: BloqueosService,
    private cdr: ChangeDetectorRef,
    private toastController: ToastController,
    private auth: AuthService,
    private router: Router,
    private host: ElementRef<HTMLElement>
  ) {
    addIcons({ calendarOutline, calendarClearOutline, mailOutline, mailUnreadOutline, personOutline, trashOutline, addOutline, alertCircleOutline, timeOutline, logOutOutline, checkmarkCircleOutline, checkmarkOutline, closeOutline, closeCircleOutline, mailOpenOutline });
  }

  get indicePestana() { return ['agenda', 'citaciones', 'perfil'].indexOf(this.pestanaActiva); }

  private get sinMovimiento() {
    return typeof matchMedia !== 'function' || matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  // Pequeño latido del icono de la agenda al llegar un partido nuevo
  private pulsoAgenda() {
    if (this.sinMovimiento) return;
    this.host.nativeElement.querySelector('.tab-agenda ion-icon')?.animate?.(
      [{ transform: 'scale(1)' }, { transform: 'scale(1.2)', offset: .4 }, { transform: 'scale(1)' }],
      { duration: 480, easing: 'cubic-bezier(.16,1,.3,1)' }
    );
  }

  ngOnInit() {
    this.cargarPerfil();
    this.cargarAgenda();
    this.cargarCitaciones();
  }

  cargarPerfil() {
    this.cargandoPerfil = true;
    this.errorPerfil = false;
    this.perfilService.getPerfil().subscribe({
      next: (res) => {
        this.perfil = res.data;
        this.cargandoPerfil = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.errorPerfil = true;
        this.cargandoPerfil = false;
        this.cdr.detectChanges();
      }
    });
  }

  nombreRival(nombre: string): string {
    return nombre && nombre !== '-' ? nombre : 'Por confirmar';
  }

  cargarAgenda() {
    this.cargandoAgenda = true;
    this.errorAgenda = false;
    this.agendaService.getAgenda().subscribe({
      next: (res) => {
        this.agenda = res.data;
        this.cargandoAgenda = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.errorAgenda = true;
        this.cargandoAgenda = false;
        this.cdr.detectChanges();
      }
    });
  }

  cargarCitaciones() {
    this.cargandoCitaciones = true;
    this.errorCitaciones = false;
    this.citacionesService.getCitaciones().subscribe({
      next: (res) => {
        this.citaciones = res.data;
        this.cargandoCitaciones = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.errorCitaciones = true;
        this.cargandoCitaciones = false;
        this.cdr.detectChanges();
      }
    });
  }

  responderCitacion(idCitacion: number, estado: 'confirmado' | 'rechazado') {
    this.citacionesService.responderCitacion(idCitacion, estado).subscribe({
      next: () => {
        const aplicar = () => {
          // Ya no está pendiente, la sacamos de la lista sin volver a pedirla
          this.citaciones = this.citaciones.filter(c => c.id_citacion !== idCitacion);

          // Al confirmar pasa a la agenda, así que la volvemos a pedir
          if (estado === 'confirmado') {
            this.cargarAgenda();
          }

          this.mostrarMensaje(estado === 'confirmado' ? 'Citación aceptada' : 'Citación rechazada', 'success', estado === 'confirmado' ? undefined : 'close-circle-outline');
          this.cdr.detectChanges();
        };

        if (estado === 'confirmado') Haptics.impact({ style: ImpactStyle.Light }).catch(() => {});
        const fin = () => { if (estado === 'confirmado') this.pulsoAgenda(); };

        // Sin View Transitions o con movimiento reducido: cambio directo
        if (!('startViewTransition' in document) || this.sinMovimiento) {
          aplicar();
          fin();
          return;
        }
        document.documentElement.dataset['respuesta'] = estado;
        (document as any).startViewTransition(aplicar).finished.finally(() => {
          delete document.documentElement.dataset['respuesta'];
          fin();
        });
      },
      error: (err) => {
        this.mostrarMensaje(err.message || 'No se pudo responder la citación.', 'danger');
      }
    });
  }

  cerrarSesion() {
    // Con éxito o con error igual salimos: el token local ya se limpia en el servicio
    const salir = () => this.router.navigateByUrl('/login', { replaceUrl: true });
    this.auth.logout().subscribe({ next: salir, error: salir });
  }

  cambiarPestana(nombre: string) {
    this.pestanaActiva = nombre;
    this.cdr.detectChanges();
  }

  // Mantenedor de horarios bloqueados

  mostrarFormulario() {
    this.mostrandoFormulario = true;
    this.nuevoBloqueo = { dia_semana: '', hora_inicio: '', hora_fin: '' };
    this.cdr.detectChanges();
  }

  cancelarFormulario() {
    this.mostrandoFormulario = false;
    this.cdr.detectChanges();
  }

  async guardarBloqueo() {
    if (!this.nuevoBloqueo.dia_semana || !this.nuevoBloqueo.hora_inicio || !this.nuevoBloqueo.hora_fin) {
      this.mostrarMensaje('Por favor, completa todos los campos.', 'warning');
      return;
    }

    this.guardandoBloqueo = true;
    this.cdr.detectChanges();

    this.bloqueosService.crearBloqueo(this.nuevoBloqueo).subscribe({
      next: (res) => {
        this.mostrarMensaje('Horario bloqueado exitosamente', 'success');
        this.mostrandoFormulario = false;
        this.guardandoBloqueo = false;
        this.cargarPerfil(); // Los bloqueos vienen dentro del perfil, por eso recargamos eso
      },
      error: (err) => {
        this.mostrarMensaje(err.message || 'Error al guardar el bloqueo.', 'danger');
        this.guardandoBloqueo = false;
        this.cdr.detectChanges();
      }
    });
  }

  eliminarBloqueo(id: number) {
    this.bloqueosService.borrarBloqueo(id).subscribe({
      next: () => {
        this.mostrarMensaje('Bloqueo eliminado correctamente', 'success');
        this.cargarPerfil(); // Los bloqueos vienen dentro del perfil, por eso recargamos eso
      },
      error: (err) => {
        this.mostrarMensaje(err.message || 'Error al eliminar.', 'danger');
      }
    });
  }

  async mostrarMensaje(mensaje: string, color: string, icono?: string) {
    const toast = await this.toastController.create({
      message: mensaje,
      duration: 2500,
      color: color,
      icon: icono ?? ({ success: 'checkmark-circle-outline', danger: 'alert-circle-outline', warning: 'time-outline' } as Record<string, string>)[color],
      position: 'bottom'
    });
    toast.present();
  }
}