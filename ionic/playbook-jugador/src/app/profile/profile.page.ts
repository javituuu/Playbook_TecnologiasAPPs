import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { IonButton, IonContent, IonHeader, IonIcon, IonTitle, IonToolbar } from '@ionic/angular';
import { checkmarkCircleOutline, closeCircleOutline } from 'ionicons/icons';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.page.html',
  styleUrls: ['./profile.page.scss'],
  imports: [IonHeader, IonToolbar, IonTitle, IonContent, IonButton, IonIcon, RouterLink],
})
export class ProfilePage {
  readonly checkmarkCircleOutline = checkmarkCircleOutline;
  readonly closeCircleOutline = closeCircleOutline;
  disponible = true;

  cambiarDisponibilidad() {
    this.disponible = !this.disponible;
  }
}
