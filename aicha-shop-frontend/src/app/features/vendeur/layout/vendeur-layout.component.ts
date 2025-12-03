import { Component, OnInit } from '@angular/core';
import { AuthService } from '../../../core/services/auth.service';
import { User } from '../../../core/models/user.model';

@Component({
  selector: 'app-vendeur-layout',
  templateUrl: './vendeur-layout.component.html',
  styleUrls: ['./vendeur-layout.component.scss']
})
export class VendeurLayoutComponent implements OnInit {
  currentUser: User | null = null;
  sidebarOpen = true;

  menuItems = [
    { icon: '📊', label: 'Tableau de Bord', route: '/vendeur/dashboard' },
    { icon: '📦', label: 'Mes Produits', route: '/vendeur/products' },
    { icon: '🛍️', label: 'Commandes', route: '/vendeur/orders' },
    { icon: '📈', label: 'Statistiques', route: '/vendeur/stats' }
  ];

  constructor(public authService: AuthService) {}

  ngOnInit(): void {
    this.authService.currentUser$.subscribe(user => {
      this.currentUser = user;
    });
  }

  toggleSidebar(): void {
    this.sidebarOpen = !this.sidebarOpen;
  }

  logout(): void {
    this.authService.logout().subscribe({
      next: () => {},
      error: () => {
        localStorage.clear();
        window.location.href = '/auth/login';
      }
    });
  }
}
