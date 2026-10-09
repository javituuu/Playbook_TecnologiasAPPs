import { Component } from '@angular/core';
import { NgFor, NgIf } from '@angular/common';
import { RouterLink } from '@angular/router';
import { IonButton, IonContent, IonHeader, IonIcon, IonTitle, IonToolbar } from '@ionic/angular';
import { calendarOutline, checkmarkCircle, chevronForwardOutline } from 'ionicons/icons';

@Component({
  selector: 'app-team',
  templateUrl: './team.page.html',
  styleUrls: ['./team.page.scss'],
  imports: [NgFor, NgIf, IonHeader, IonToolbar, IonTitle, IonContent, IonButton, IonIcon, RouterLink],
})
export class TeamPage {
  readonly checkmarkCircle = checkmarkCircle;
  readonly chevronForwardOutline = chevronForwardOutline;
  readonly calendarOutline = calendarOutline;
  jugadorSeleccionado: string | null = null;

  readonly jugadores = [
    { nombre: 'Martina Rojas', nick: 'm4rti', rol: 'DUELISTA', juego: 'Jett · Raze', numero: '07', foto: 'assets/player-martina.jpg' },
    { nombre: 'Tomás Vega', nick: 'vega.exe', rol: 'INICIADOR', juego: 'Sova · Fade', numero: '12', foto: 'assets/player-vega.jpg' },
    { nombre: 'Nicolás Díaz', nick: 'ndiaz', rol: 'CONTROLADOR', juego: 'Omen · Brimstone', numero: '03', foto: 'assets/player-diaz.jpg' },
    { nombre: 'Sofía Muñoz', nick: 'sofi.zip', rol: 'CENTINELA', juego: 'Killjoy · Cypher', numero: '09', foto: 'assets/player-sofia.jpg' },
  ];

  seleccionar(nombre: string) {
    this.jugadorSeleccionado = this.jugadorSeleccionado === nombre ? null : nombre;
  }
}
