import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { NgFor, NgIf, DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonImg, IonCard,
  IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent,
  IonList, IonItem, IonLabel, IonButton, IonFooter, IonTabBar,
  IonTabButton, IonIcon, IonSpinner, IonInput, IonSelect, IonSelectOption,
  ToastController
} from '@ionic/angular';
import { addIcons } from 'ionicons';
import { calendarOutline, mailUnreadOutline, personOutline, trashOutline, addOutline } from 'ionicons/icons';
import { JugadorService, AgendaItem, CitacionItem } from '../services/jugador.service';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  imports: [
    NgFor, NgIf, DatePipe, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonImg, IonCard,
    IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent,
    IonList, IonItem, IonLabel, IonButton, IonFooter, IonTabBar,
    IonTabButton, IonIcon, IonSpinner, IonInput, IonSelect, IonSelectOption
  ],
})
export class HomePage implements OnInit {
  /** Pestaña activa por defecto en el menú inferior */
  pestanaActiva: string = 'agenda';

  // --- Estados de Perfil ---
  perfil: any = null;
  cargandoPerfil: boolean = true;
  errorPerfil: boolean = false;

  // --- Estados de Agenda ---
  agenda: AgendaItem[] = [];
  cargandoAgenda: boolean = true;
  errorAgenda: boolean = false;

  // --- Estados de Citaciones ---
  citaciones: CitacionItem[] = [];
  cargandoCitaciones: boolean = true;
  errorCitaciones: boolean = false;

  // --- CRUD Horarios Bloqueados ---
  mostrandoFormulario: boolean = false;
  guardandoBloqueo: boolean = false;
  nuevoBloqueo = {
    dia_semana: '',
    hora_inicio: '',
    hora_fin: ''
  };

  constructor(
    private jugadorService: JugadorService,
    private cdr: ChangeDetectorRef,
    private toastController: ToastController
  ) {
    addIcons({ calendarOutline, mailUnreadOutline, personOutline, trashOutline, addOutline });
  }

  ngOnInit() {
    this.cargarPerfil();
    this.cargarAgenda();
    this.cargarCitaciones();
  }

  /**
   * Obtiene la información del perfil del jugador y sus horarios bloqueados a través del servicio.
   */
  cargarPerfil() {
    this.cargandoPerfil = true;
    this.errorPerfil = false;
    this.jugadorService.getPerfil().subscribe({
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

  /**
   * Obtiene la lista de partidos confirmados a través del servicio.
   */
  cargarAgenda() {
    this.cargandoAgenda = true;
    this.errorAgenda = false;
    this.jugadorService.getAgenda().subscribe({
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

  /**
   * Obtiene las invitaciones a partidos pendientes de respuesta a través del servicio.
   */
  cargarCitaciones() {
    this.cargandoCitaciones = true;
    this.errorCitaciones = false;
    this.jugadorService.getCitaciones().subscribe({
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

  /**
   * Envía la respuesta (Aceptar/Rechazar) a través del servicio con validación.
   * @param idCitacion ID de la citación a actualizar
   * @param estado Estado de respuesta ('confirmado' o 'rechazado')
   */
  responderCitacion(idCitacion: number, estado: 'confirmado' | 'rechazado') {
    this.jugadorService.responderCitacion(idCitacion, estado).subscribe({
      next: () => {
        // Remover visualmente la citación respondida de la lista de pendientes
        this.citaciones = this.citaciones.filter(c => c.id_citacion !== idCitacion);
        
        // Si el jugador confirmó, recargar la agenda para reflejar el nuevo partido
        if (estado === 'confirmado') {
          this.cargarAgenda();
        }
        
        this.cdr.detectChanges();
      },
      error: (err) => {
        alert(err.message || 'Error al conectar con el servidor.');
      }
    });
  }

  /**
   * Cambia la vista activa del dashboard.
   * @param nombre Nombre de la pestaña seleccionada ('agenda', 'citaciones' o 'perfil')
   */
  cambiarPestana(nombre: string) {
    this.pestanaActiva = nombre;
    this.cdr.detectChanges();
  }

  // ==========================================
  // MANTENEDOR CRUD: HORARIOS BLOQUEADOS
  // ==========================================

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

    this.jugadorService.crearBloqueo(this.nuevoBloqueo).subscribe({
      next: (res) => {
        this.mostrarMensaje('Horario bloqueado exitosamente', 'success');
        this.mostrandoFormulario = false;
        this.guardandoBloqueo = false;
        this.cargarPerfil(); // Recargar la lista
      },
      error: (err) => {
        this.mostrarMensaje(err.message || 'Error al guardar el bloqueo.', 'danger');
        this.guardandoBloqueo = false;
        this.cdr.detectChanges();
      }
    });
  }

  eliminarBloqueo(id: number) {
    this.jugadorService.borrarBloqueo(id).subscribe({
      next: () => {
        this.mostrarMensaje('Bloqueo eliminado correctamente', 'success');
        this.cargarPerfil(); // Recargar la lista
      },
      error: (err) => {
        this.mostrarMensaje(err.message || 'Error al eliminar.', 'danger');
      }
    });
  }

  async mostrarMensaje(mensaje: string, color: string) {
    const toast = await this.toastController.create({
      message: mensaje,
      duration: 2500,
      color: color,
      position: 'bottom'
    });
    toast.present();
  }
}