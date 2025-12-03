import { Component, OnInit } from '@angular/core';
import { VendeurService } from '../../../core/services/vendeur.service';
import { ProductService } from '../../../core/services/product.service';
import { Product, Category, Marque } from '../../../core/models/product.model';

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
  showForm = false;
  editingProduct: Product | null = null;

  constructor(
    private vendeurService: VendeurService,
    private productService: ProductService
  ) {}

  ngOnInit(): void {
    this.loadProducts();
    this.loadCategories();
    this.loadMarques();
  }

  loadProducts(): void {
    this.loading = true;
    this.vendeurService.getMyProducts().subscribe({
      next: (products) => {
        this.products = products;
        this.loading = false;
      },
      error: () => this.loading = false
    });
  }

  loadCategories(): void {
    this.productService.getCategories().subscribe({
      next: (categories) => this.categories = categories
    });
  }

  loadMarques(): void {
    this.productService.getMarques().subscribe({
      next: (marques) => this.marques = marques
    });
  }

  toggleProductVisibility(product: Product): void {
    this.vendeurService.toggleProductVisibility(product.id).subscribe({
      next: () => {
        product.is_active = !product.is_active;
        alert('Statut modifié');
      },
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }

  deleteProduct(product: Product): void {
    if (!confirm('Supprimer ce produit ?')) return;
    this.vendeurService.deleteProduct(product.id).subscribe({
      next: () => {
        this.products = this.products.filter(p => p.id !== product.id);
        alert('Produit supprimé');
      },
      error: (err) => alert('Erreur: ' + err.error?.message)
    });
  }
}
