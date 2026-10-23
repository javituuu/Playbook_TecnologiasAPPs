import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { NgFor } from '@angular/common';
import { IonButton, IonContent, IonHeader, IonIcon, IonTitle, IonToolbar } from '@ionic/angular';
import { checkmarkCircle, ellipseOutline } from 'ionicons/icons';

type Exercise = { nombre: string; detalle: string; completado: boolean };

@Component({
  selector: 'app-training',
  templateUrl: './training.page.html',
  styleUrls: ['./training.page.scss'],
  imports: [NgFor, IonHeader, IonToolbar, IonTitle, IonContent, IonButton, IonIcon, RouterLink],
})
export class TrainingPage {
  readonly checkmarkCircle = checkmarkCircle;
  readonly ellipseOutline = ellipseOutline;
  readonly ejercicios: Exercise[] = [
    { nombre: 'Calentamiento de aim', detalle: 'Range · 10 min', completado: false },
    { nombre: 'Control de recoil', detalle: 'Vandal + Phantom · 15 min', completado: false },
    { nombre: 'Práctica de utilidad', detalle: 'Ascent · 20 min', completado: false },
    { nombre: 'Scrim de equipo', detalle: 'Partida personalizada · 45 min', completado: false },
  ];

  get completados() {
    return this.ejercicios.filter((ejercicio) => ejercicio.completado).length;
  }

  toggleEjercicio(ejercicio: Exercise) {
    ejercicio.completado = !ejercicio.completado;
  }

  reiniciar() {
    this.ejercicios.forEach((ejercicio) => (ejercicio.completado = false));
  }
}
