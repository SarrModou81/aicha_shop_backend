import { Component, OnInit } from '@angular/core';
import { Router, NavigationEnd } from '@angular/router';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss'
})
export class AppComponent implements OnInit {
  title = 'aicha-shop-frontend';
  showHeaderFooter = true;

  constructor(private router: Router) {}

  ngOnInit(): void {
    // Masquer header/footer pour les routes admin et vendeur
    this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((event: any) => {
      const url = event.url;
      this.showHeaderFooter = !url.startsWith('/admin') && !url.startsWith('/vendeur');
    });
  }
}
