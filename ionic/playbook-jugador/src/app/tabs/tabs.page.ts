import { Component } from '@angular/core';
import { IonIcon, IonLabel, IonRouterOutlet, IonTabBar, IonTabButton, IonTabs } from '@ionic/angular';
import { barbellOutline, calendarOutline, peopleOutline, personCircleOutline } from 'ionicons/icons';

@Component({
  selector: 'app-tabs',
  templateUrl: './tabs.page.html',
  styleUrls: ['./tabs.page.scss'],
  imports: [IonTabs, IonRouterOutlet, IonTabBar, IonTabButton, IonIcon, IonLabel],
})
export class TabsPage {
  readonly calendarOutline = calendarOutline;
  readonly peopleOutline = peopleOutline;
  readonly barbellOutline = barbellOutline;
  readonly personCircleOutline = personCircleOutline;
}
