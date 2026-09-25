import { Component } from '@angular/core';
import { NgFor, NgIf } from '@angular/common';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonImg,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonList,
  IonItem,
  IonLabel,
  IonButton,
  IonFooter,
  IonTabBar,
  IonTabButton,
  IonIcon,
} from '@ionic/angular';
import { addIcons } from 'ionicons';
import { calendarOutline, mailUnreadOutline, personOutline } from 'ionicons/icons';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  imports: [
    NgFor,
    NgIf,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonImg,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardSubtitle,
    IonCardContent,
    IonList,
    IonItem,
    IonLabel,
    IonButton,
    IonFooter,
    IonTabBar,
    IonTabButton,
    IonIcon,
  ],
})
export class HomePage {
  // Variable para controlar qué pestaña se muestra
  pestanaActiva: string = 'agenda';

  partidos = [
    { rival: 'UNAB', hora: 'Vie 16:00' },
    { rival: 'INACAP', hora: 'Sáb 20:30' },
    { rival: 'USM Gaming', hora: 'Dom 19:00' },
  ];

  constructor() {
    // Registramos los íconos para poder usarlos en el HTML
    addIcons({ calendarOutline, mailUnreadOutline, personOutline });
  }

  confirmar() {
    alert('Citación confirmada');
  }

  cambiarPestana(nombre: string) {
    this.pestanaActiva = nombre;
  }
}