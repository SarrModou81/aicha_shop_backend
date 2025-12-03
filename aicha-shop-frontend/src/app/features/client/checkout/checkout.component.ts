import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { CartService } from '../../../core/services/cart.service';
import { OrderService } from '../../../core/services/order.service';
import { CartItem } from '../../../core/models/cart.model';

@Component({
  selector: 'app-checkout',
  templateUrl: './checkout.component.html',
  styleUrl: './checkout.component.scss'
})
export class CheckoutComponent implements OnInit {
  checkoutForm: FormGroup;
  cartItems: CartItem[] = [];
  total = 0;
  loading = false;
  submitting = false;

  constructor(
    private fb: FormBuilder,
    private cartService: CartService,
    private orderService: OrderService,
    private router: Router
  ) {
    this.checkoutForm = this.fb.group({
      shipping_address: ['', [Validators.required]],
      shipping_city: ['', [Validators.required]],
      shipping_country: ['Sénégal', [Validators.required]],
      shipping_phone: ['', [Validators.required, Validators.pattern(/^[0-9]{9,15}$/)]],
      payment_method: ['espece', [Validators.required]],
      notes: ['']
    });
  }

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
        
        if (this.cartItems.length === 0) {
          alert('Votre panier est vide');
          this.router.navigate(['/cart']);
        }
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

  onSubmit(): void {
    if (this.checkoutForm.invalid) {
      alert('Veuillez remplir tous les champs obligatoires');
      return;
    }

    if (this.cartItems.length === 0) {
      alert('Votre panier est vide');
      return;
    }

    this.submitting = true;
    this.orderService.placeOrder(this.checkoutForm.value).subscribe({
      next: (order) => {
        alert('Commande passée avec succès! Numéro: ' + order.order_number);
        this.router.navigate(['/orders']);
      },
      error: (err) => {
        alert('Erreur: ' + (err.error?.message || 'Erreur lors de la commande'));
        this.submitting = false;
      }
    });
  }
}
