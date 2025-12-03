import { Component, OnInit } from '@angular/core';
import { VendeurService } from '../../../core/services/vendeur.service';

export interface Order {
  id: number;
  order_number: string;
  client: { name: string; email: string };
  items: any[];
  total: number;
  status: string;
  created_at: string;
  payment_status: string;
}

@Component({
  selector: 'app-orders',
  templateUrl: './orders.component.html',
  styleUrl: './orders.component.scss'
})
export class OrdersComponent implements OnInit {
  orders: Order[] = [];
  loading = true;
  selectedOrder: Order | null = null;

  statusOptions = [
    { value: 'pending', label: 'En attente', color: 'warning' },
    { value: 'processing', label: 'En traitement', color: 'info' },
    { value: 'shipped', label: 'Expédiée', color: 'primary' },
    { value: 'delivered', label: 'Livrée', color: 'success' },
    { value: 'cancelled', label: 'Annulée', color: 'error' }
  ];

  constructor(private vendeurService: VendeurService) {}

  ngOnInit(): void {
    this.loadOrders();
  }

  loadOrders(): void {
    this.loading = true;
    this.vendeurService.getMyOrders().subscribe({
      next: (orders) => {
        this.orders = orders;
        this.loading = false;
      },
      error: (err: any) => {
        console.error('Erreur chargement commandes', err);
        this.loading = false;
      }
    });
  }

  getStatusLabel(status: string): string {
    const option = this.statusOptions.find(s => s.value === status);
    return option ? option.label : status;
  }

  getStatusColor(status: string): string {
    const option = this.statusOptions.find(s => s.value === status);
    return option ? option.color : 'default';
  }

  changeOrderStatus(order: Order, newStatus: string): void {
    if (!confirm(`Changer le statut de la commande #${order.order_number} à "${this.getStatusLabel(newStatus)}" ?`)) {
      return;
    }

    this.vendeurService.updateOrderStatus(order.id, newStatus).subscribe({
      next: () => {
        order.status = newStatus;
        alert('Statut mis à jour avec succès');
      },
      error: (err: any) => alert('Erreur: ' + (err.error?.message || 'Une erreur est survenue'))
    });
  }

  viewOrderDetails(order: Order): void {
    this.vendeurService.getOrderDetails(order.id).subscribe({
      next: (details) => {
        this.selectedOrder = details;
      },
      error: (err: any) => alert('Erreur: ' + (err.error?.message || 'Une erreur est survenue'))
    });
  }

  closeModal(): void {
    this.selectedOrder = null;
  }

  cancelOrder(order: Order): void {
    const reason = prompt('Raison de l\'annulation:');
    if (!reason) return;

    this.vendeurService.cancelOrder(order.id, reason).subscribe({
      next: () => {
        order.status = 'cancelled';
        alert('Commande annulée');
        this.loadOrders();
      },
      error: (err: any) => alert('Erreur: ' + (err.error?.message || 'Une erreur est survenue'))
    });
  }
}
