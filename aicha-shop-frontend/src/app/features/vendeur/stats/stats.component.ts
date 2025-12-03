import { Component, OnInit } from '@angular/core';
import { VendeurService, SalesStats } from '../../../core/services/vendeur.service';

@Component({
  selector: 'app-stats',
  templateUrl: './stats.component.html',
  styleUrl: './stats.component.scss'
})
export class StatsComponent implements OnInit {
  stats: SalesStats | null = null;
  loading = true;
  selectedPeriod = 'month';

  periods = [
    { value: 'day', label: "Aujourd'hui" },
    { value: 'week', label: 'Cette semaine' },
    { value: 'month', label: 'Ce mois' },
    { value: 'year', label: 'Cette année' }
  ];

  constructor(private vendeurService: VendeurService) {}

  ngOnInit(): void {
    this.loadStats();
  }

  loadStats(): void {
    this.loading = true;
    this.vendeurService.getSalesStats(this.selectedPeriod).subscribe({
      next: (stats) => {
        this.stats = stats;
        this.loading = false;
      },
      error: (err: any) => {
        console.error('Erreur chargement statistiques', err);
        this.loading = false;
      }
    });
  }

  changePeriod(period: string): void {
    this.selectedPeriod = period;
    this.loadStats();
  }

  getMaxSales(): number {
    if (!this.stats || !this.stats.sales_by_period || this.stats.sales_by_period.length === 0) {
      return 0;
    }
    return Math.max(...this.stats.sales_by_period.map(s => s.amount));
  }

  getBarHeight(amount: number): number {
    const max = this.getMaxSales();
    if (max === 0) return 0;
    return (amount / max) * 100;
  }

  formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    const options: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' };
    return date.toLocaleDateString('fr-FR', options);
  }
}
