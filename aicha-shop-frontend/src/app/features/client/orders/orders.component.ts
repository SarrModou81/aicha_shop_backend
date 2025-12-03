import { Component, OnInit } from '@angular/core';
import { OrderService } from '../../../core/services/order.service';
import { Order } from '../../../core/models/order.model';

@Component({
  selector: 'app-orders',
  templateUrl: './orders.component.html',
  styleUrl: './orders.component.scss'
})
export class OrdersComponent implements OnInit {
  orders: Order[] = [];
  selectedOrder: Order | null = null;
  loading = true;
  showModal = false;

  statusLabels: {[key: string]: string} = {
    'en_attente': 'En attente',
    'confirmee': 'Confirmée',
    'en_preparation': 'En préparation',
    'expediee': 'Expédiée',
    'livree': 'Livrée',
    'annulee': 'Annulée'
  };

  statusColors: {[key: string]: string} = {
    'en_attente': 'warning',
    'confirmee': 'info',
    'en_preparation': 'info',
    'expediee': 'primary',
    'livree': 'success',
    'annulee': 'error'
  };

  paymentStatusLabels: {[key: string]: string} = {
    'en_attente': 'En attente',
    'payee': 'Payée',
    'echouee': 'Échouée',
    'remboursee': 'Remboursée'
  };

  constructor(private orderService: OrderService) {}

  ngOnInit(): void {
    this.loadOrders();
  }

  loadOrders(): void {
    this.loading = true;
    this.orderService.getMyOrders().subscribe({
      next: (orders) => {
        this.orders = orders;
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement commandes', err);
        this.loading = false;
      }
    });
  }

  viewOrderDetails(order: Order): void {
    this.orderService.getOrderDetails(order.order_number).subscribe({
      next: (orderDetails) => {
        this.selectedOrder = orderDetails;
        this.showModal = true;
      },
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }

  closeModal(): void {
    this.showModal = false;
    this.selectedOrder = null;
  }

  cancelOrder(order: Order): void {
    if (!confirm('Voulez-vous vraiment annuler cette commande ?')) return;

    this.orderService.cancelOrder(order.id).subscribe({
      next: () => {
        alert('Commande annulée avec succès');
        this.loadOrders();
        this.closeModal();
      },
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }

  getStatusClass(status: string): string {
    return 'badge-' + (this.statusColors[status] || 'info');
  }
}
