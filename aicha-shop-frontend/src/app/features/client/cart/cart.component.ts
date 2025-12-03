import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { CartService } from '../../../core/services/cart.service';
import { CartItem } from '../../../core/models/cart.model';

@Component({
  selector: 'app-cart',
  templateUrl: './cart.component.html',
  styleUrl: './cart.component.scss'
})
export class CartComponent implements OnInit {
  cartItems: CartItem[] = [];
  loading = true;
  total = 0;

  constructor(
    private cartService: CartService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadCart();
  }

  loadCart(): void {
    this.loading = true;
    this.cartService.getCart().subscribe({
      next: (items) => {
        this.cartItems = items;
        this.calculateTotal();
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement panier', err);
        this.loading = false;
      }
    });
  }

  calculateTotal(): void {
    this.total = this.cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  }

  updateQuantity(item: CartItem, newQuantity: number): void {
    if (newQuantity < 1) return;
    
    if (item.produit?.stock && newQuantity > item.produit.stock.quantity) {
      alert('Quantité non disponible en stock');
      return;
    }

    this.cartService.updateCartItem(item.id, newQuantity).subscribe({
      next: () => {
        item.quantity = newQuantity;
        this.calculateTotal();
      },
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }

  removeItem(item: CartItem): void {
    if (!confirm('Voulez-vous vraiment supprimer cet article ?')) return;

    this.cartService.removeFromCart(item.id).subscribe({
      next: () => {
        this.cartItems = this.cartItems.filter(i => i.id !== item.id);
        this.calculateTotal();
      },
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }

  clearCart(): void {
    if (!confirm('Voulez-vous vraiment vider le panier ?')) return;

    this.cartService.clearCart().subscribe({
      next: () => {
        this.cartItems = [];
        this.total = 0;
      },
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }

  proceedToCheckout(): void {
    if (this.cartItems.length === 0) {
      alert('Votre panier est vide');
      return;
    }
    this.router.navigate(['/checkout']);
  }
}
