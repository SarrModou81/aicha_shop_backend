import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ProductService } from '../../../core/services/product.service';
import { CartService } from '../../../core/services/cart.service';
import { AuthService } from '../../../core/services/auth.service';
import { Product } from '../../../core/models/product.model';

@Component({
  selector: 'app-product-detail',
  templateUrl: './product-detail.component.html',
  styleUrl: './product-detail.component.scss'
})
export class ProductDetailComponent implements OnInit {
  product: Product | null = null;
  loading = true;
  quantity = 1;
  selectedImage = 0;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private productService: ProductService,
    private cartService: CartService,
    public authService: AuthService
  ) {}

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      const slug = params['slug'];
      if (slug) {
        this.loadProduct(slug);
      }
    });
  }

  loadProduct(slug: string): void {
    this.loading = true;
    this.productService.getProduct(slug).subscribe({
      next: (product) => {
        this.product = product;
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement produit', err);
        this.loading = false;
        this.router.navigate(['/products']);
      }
    });
  }

  selectImage(index: number): void {
    this.selectedImage = index;
  }

  increaseQuantity(): void {
    if (this.product && this.product.stock) {
      if (this.quantity < this.product.stock.quantity) {
        this.quantity++;
      }
    } else {
      this.quantity++;
    }
  }

  decreaseQuantity(): void {
    if (this.quantity > 1) {
      this.quantity--;
    }
  }

  addToCart(): void {
    if (!this.authService.isAuthenticated()) {
      alert('Veuillez vous connecter pour ajouter au panier');
      this.router.navigate(['/auth/login']);
      return;
    }

    if (!this.product) return;

    this.cartService.addToCart(this.product.id, this.quantity).subscribe({
      next: () => {
        alert('Produit ajouté au panier');
        this.quantity = 1;
      },
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }

  buyNow(): void {
    if (!this.authService.isAuthenticated()) {
      alert('Veuillez vous connecter pour acheter');
      this.router.navigate(['/auth/login']);
      return;
    }

    this.addToCart();
    setTimeout(() => {
      this.router.navigate(['/cart']);
    }, 500);
  }
}
