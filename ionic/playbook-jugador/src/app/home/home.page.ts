import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { NgFor, NgIf, DatePipe } from '@angular/common';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonImg, IonCard,
  IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent,
  IonList, IonItem, IonLabel, IonButton, IonFooter, IonTabBar,
  IonTabButton, IonIcon, IonSpinner,
} from '@ionic/angular';
import { addIcons } from 'ionicons';
import { calendarOutline, mailUnreadOutline, personOutline } from 'ionicons/icons';
import { JugadorService, AgendaItem, CitacionItem } from '../services/jugador.service';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  imports: [
    NgFor, NgIf, DatePipe,
    IonHeader, IonToolbar, IonTitle, IonContent, IonImg, IonCard,
    IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent,
    IonList, IonItem, IonLabel, IonButton, IonFooter, IonTabBar,
    IonTabButton, IonIcon, IonSpinner,
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

  constructor(
    private jugadorService: JugadorService,
    private cdr: ChangeDetectorRef
  ) {
    addIcons({ calendarOutline, mailUnreadOutline, personOutline });
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
}