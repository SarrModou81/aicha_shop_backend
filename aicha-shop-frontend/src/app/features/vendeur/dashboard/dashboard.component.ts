import { Component, OnInit } from '@angular/core';
import { VendeurService, VendeurStats } from '../../../core/services/vendeur.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss'
})
export class DashboardComponent implements OnInit {
  stats: VendeurStats | null = null;
  loading = true;

  constructor(private vendeurService: VendeurService) {}

  ngOnInit(): void {
    this.loadStats();
  }

  loadStats(): void {
    this.loading = true;
    this.vendeurService.getDashboardStats().subscribe({
      next: (stats) => {
        this.stats = stats;
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement stats', err);
        this.loading = false;
      }
    });
  }
}
