import { Component, OnInit } from '@angular/core';
import { AuthService } from '../../../core/services/auth.service';
import { User } from '../../../core/models/user.model';

@Component({
  selector: 'app-admin-layout',
  templateUrl: './admin-layout.component.html',
  styleUrls: ['./admin-layout.component.scss']
})
export class AdminLayoutComponent implements OnInit {
  currentUser: User | null = null;
  sidebarOpen = true;

  menuItems = [
    { icon: '📊', label: 'Tableau de Bord', route: '/admin/dashboard' },
    { icon: '👥', label: 'Utilisateurs', route: '/admin/users' },
    { icon: '📦', label: 'Produits', route: '/admin/products' },
    { icon: '🛍️', label: 'Commandes', route: '/admin/orders' },
    { icon: '⚙️', label: 'Paramètres', route: '/admin/settings' }
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
