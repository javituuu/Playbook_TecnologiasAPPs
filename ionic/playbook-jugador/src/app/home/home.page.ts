import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { NgFor, NgIf, DatePipe } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonImg, IonCard,
  IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent,
  IonList, IonItem, IonLabel, IonButton, IonFooter, IonTabBar,
  IonTabButton, IonIcon, IonSpinner,
} from '@ionic/angular';
import { addIcons } from 'ionicons';
import { calendarOutline, mailUnreadOutline, personOutline } from 'ionicons/icons';

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
  agenda: any[] = [];
  cargandoAgenda: boolean = true;
  errorAgenda: boolean = false;

  // --- Estados de Citaciones ---
  citaciones: any[] = [];
  cargandoCitaciones: boolean = true;
  errorCitaciones: boolean = false;

  constructor(private http: HttpClient, private cdr: ChangeDetectorRef) {
    addIcons({ calendarOutline, mailUnreadOutline, personOutline });
  }

  ngOnInit() {
    this.cargarPerfil();
    this.cargarAgenda();
    this.cargarCitaciones();
  }

  /**
   * Obtiene la información del perfil del jugador y sus horarios bloqueados.
   */
  cargarPerfil() {
    this.cargandoPerfil = true;
    this.errorPerfil = false;
    this.http.get('http://localhost:8000/api/perfil').subscribe({
      next: (res: any) => {
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
   * Obtiene la lista de partidos donde el jugador ha confirmado su asistencia.
   */
  cargarAgenda() {
    this.cargandoAgenda = true;
    this.errorAgenda = false;
    this.http.get('http://localhost:8000/api/agenda').subscribe({
      next: (res: any) => {
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
   * Obtiene las invitaciones a partidos que están pendientes de respuesta.
   */
  cargarCitaciones() {
    this.cargandoCitaciones = true;
    this.errorCitaciones = false;
    this.http.get('http://localhost:8000/api/citaciones').subscribe({
      next: (res: any) => {
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
   * Envía la respuesta (Aceptar/Rechazar) para una citación específica.
   * @param idCitacion ID de la citación a actualizar
   * @param estado Estado de respuesta ('confirmado' o 'rechazado')
   */
  responderCitacion(idCitacion: number, estado: string) {
    this.http.patch(`http://localhost:8000/api/citaciones/${idCitacion}/responder`, { estado }).subscribe({
      next: () => {
        // Remover visualmente la citación respondida de la lista de pendientes
        this.citaciones = this.citaciones.filter(c => c.id_citacion !== idCitacion);
        
        // Si el jugador confirmó, recargar la agenda para reflejar el nuevo partido
        if (estado === 'confirmado') {
          this.cargarAgenda();
        }
        
        this.cdr.detectChanges();
      },
      error: () => {
        alert('Error al conectar con el servidor. Intente de nuevo.');
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