import { Injectable } from '@angular/core';
import { CanActivate, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class GuestGuard implements CanActivate {

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  canActivate(): boolean {
    if (!this.authService.isAuthenticated()) {
      return true;
    }

    // Redirect to appropriate dashboard based on role
    const user = this.authService.getCurrentUser();
    if (user) {
      switch (user.role) {
        case 'admin':
          this.router.navigate(['/admin/dashboard']);
          break;
        case 'vendeur':
          this.router.navigate(['/vendeur/dashboard']);
          break;
        default:
          this.router.navigate(['/']);
      }
    }
    return false;
  }
}
