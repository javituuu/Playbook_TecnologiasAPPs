import { Component } from '@angular/core';
import { NgFor } from '@angular/common';
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
} from '@ionic/angular';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  imports: [
    NgFor,
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
  ],
})
export class HomePage {
  partidos = [
    { rival: 'UNAB', hora: 'Vie 16:00' },
    { rival: 'INACAP', hora: 'Sáb 20:30' },
    { rival: 'USM Gaming', hora: 'Dom 19:00' },
  ];

  confirmar() {
    alert('Citación confirmada');
  }
}
