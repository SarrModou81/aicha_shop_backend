import { Component, OnInit } from '@angular/core';
import { ProductService, ProductFilter } from '../../../core/services/product.service';
import { Product, Category, Marque } from '../../../core/models/product.model';
import { CartService } from '../../../core/services/cart.service';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-products',
  templateUrl: './products.component.html',
  styleUrl: './products.component.scss'
})
export class ProductsComponent implements OnInit {
  products: Product[] = [];
  categories: Category[] = [];
  marques: Marque[] = [];
  loading = true;
  
  filters: ProductFilter = {
    search: '',
    category_id: undefined,
    marque_id: undefined,
    min_price: undefined,
    max_price: undefined,
    sort_by: 'newest',
    page: 1,
    per_page: 12
  };

  pagination = {
    current_page: 1,
    last_page: 1,
    total: 0
  };

  constructor(
    private productService: ProductService,
    private cartService: CartService,
    public authService: AuthService
  ) {}

  ngOnInit(): void {
    this.loadCategories();
    this.loadMarques();
    this.loadProducts();
  }

  loadCategories(): void {
    this.productService.getCategories().subscribe({
      next: (categories) => this.categories = categories,
      error: (err) => console.error('Erreur chargement catégories', err)
    });
  }

  loadMarques(): void {
    this.productService.getMarques().subscribe({
      next: (marques) => this.marques = marques,
      error: (err) => console.error('Erreur chargement marques', err)
    });
  }

  loadProducts(): void {
    this.loading = true;
    this.productService.getProducts(this.filters).subscribe({
      next: (response) => {
        this.products = response.data;
        this.pagination = {
          current_page: response.current_page,
          last_page: response.last_page,
          total: response.total
        };
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement produits', err);
        this.loading = false;
      }
    });
  }

  onSearch(): void {
    this.filters.page = 1;
    this.loadProducts();
  }

  onFilterChange(): void {
    this.filters.page = 1;
    this.loadProducts();
  }

  onPageChange(page: number): void {
    this.filters.page = page;
    this.loadProducts();
    window.scrollTo(0, 0);
  }

  clearFilters(): void {
    this.filters = {
      search: '',
      category_id: undefined,
      marque_id: undefined,
      min_price: undefined,
      max_price: undefined,
      sort_by: 'newest',
      page: 1,
      per_page: 12
    };
    this.loadProducts();
  }

  addToCart(product: Product): void {
    if (!this.authService.isAuthenticated()) {
      alert('Veuillez vous connecter pour ajouter au panier');
      return;
    }

    this.cartService.addToCart(product.id, 1).subscribe({
      next: () => alert('Produit ajouté au panier'),
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }
}
