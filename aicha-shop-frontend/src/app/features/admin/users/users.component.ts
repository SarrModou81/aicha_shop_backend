import { Component, OnInit } from '@angular/core';
import { AdminService } from '../../../core/services/admin.service';
import { User } from '../../../core/models/user.model';

@Component({
  selector: 'app-users',
  templateUrl: './users.component.html',
  styleUrl: './users.component.scss'
})
export class UsersComponent implements OnInit {
  users: User[] = [];
  loading = true;

  constructor(private adminService: AdminService) {}

  ngOnInit(): void {
    this.loadUsers();
  }

  loadUsers(): void {
    this.loading = true;
    this.adminService.getUsers().subscribe({
      next: (users) => {
        this.users = users;
        this.loading = false;
      },
      error: () => this.loading = false
    });
  }

  toggleUserStatus(user: User): void {
    this.adminService.toggleUserStatus(user.id).subscribe({
      next: () => {
        user.is_active = !user.is_active;
        alert('Statut modifié');
      },
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }

  resetPassword(user: User): void {
    if (!confirm('Réinitialiser le mot de passe de cet utilisateur ?')) return;
    this.adminService.resetUserPassword(user.id).subscribe({
      next: () => alert('Mot de passe réinitialisé et envoyé par email'),
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }
}
