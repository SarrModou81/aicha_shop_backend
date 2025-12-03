import { Component, OnInit } from '@angular/core';
import { AuthService } from '../../../core/services/auth.service';
import { CartService } from '../../../core/services/cart.service';
import { User } from '../../../core/models/user.model';
import { Observable } from 'rxjs';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrl: './header.component.scss'
})
export class HeaderComponent implements OnInit {
  currentUser$: Observable<User | null>;
  cartCount$: Observable<any>;
  isMenuOpen = false;

  constructor(
    public authService: AuthService,
    public cartService: CartService
  ) {
    this.currentUser$ = this.authService.currentUser$;
    this.cartCount$ = this.cartService.cart$;
  }

  ngOnInit(): void {
    if (this.authService.isAuthenticated() && this.authService.isClient()) {
      this.cartService.getCart().subscribe();
    }
  }

  toggleMenu(): void {
    this.isMenuOpen = !this.isMenuOpen;
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
