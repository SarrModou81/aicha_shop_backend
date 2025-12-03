import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
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

  formData = {
    name: '',
    description: '',
    price: 0,
    compare_price: 0,
    category_id: '',
    marque_id: '',
    quantity: 0,
    low_stock_threshold: 10,
    is_featured: false
  };

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

  editProduct(product: Product): void {
    this.editingProduct = product;
    this.formData = {
      name: product.name,
      description: product.description || '',
      price: product.price,
      compare_price: product.compare_price || 0,
      category_id: product.category_id?.toString() || '',
      marque_id: product.marque_id?.toString() || '',
      quantity: product.stock?.quantity || 0,
      low_stock_threshold: product.stock?.low_stock_threshold || 10,
      is_featured: product.is_featured || false
    };
    this.showForm = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  saveProduct(form: NgForm): void {
    if (!form.valid) return;

    const productData = {
      ...this.formData,
      category_id: parseInt(this.formData.category_id),
      marque_id: parseInt(this.formData.marque_id)
    };

    if (this.editingProduct) {
      // Update existing product
      this.vendeurService.updateProduct(this.editingProduct.id, productData).subscribe({
        next: () => {
          alert('Produit mis à jour avec succès');
          this.loadProducts();
          this.cancelForm();
        },
        error: (err) => alert('Erreur: ' + (err.error?.message || 'Une erreur est survenue'))
      });
    } else {
      // Create new product
      this.vendeurService.createProduct(productData).subscribe({
        next: () => {
          alert('Produit créé avec succès');
          this.loadProducts();
          this.cancelForm();
        },
        error: (err) => alert('Erreur: ' + (err.error?.message || 'Une erreur est survenue'))
      });
    }
  }

  cancelForm(): void {
    this.showForm = false;
    this.editingProduct = null;
    this.formData = {
      name: '',
      description: '',
      price: 0,
      compare_price: 0,
      category_id: '',
      marque_id: '',
      quantity: 0,
      low_stock_threshold: 10,
      is_featured: false
    };
  }

  toggleProductVisibility(product: Product): void {
    this.vendeurService.toggleProductVisibility(product.id).subscribe({
      next: () => {
        product.is_active = !product.is_active;
        alert('Statut modifié avec succès');
      },
      error: (err) => alert('Erreur: ' + (err.error?.message || 'Une erreur est survenue'))
    });
  }

  deleteProduct(product: Product): void {
    if (!confirm(`Voulez-vous vraiment supprimer "${product.name}" ?`)) return;

    this.vendeurService.deleteProduct(product.id).subscribe({
      next: () => {
        this.products = this.products.filter(p => p.id !== product.id);
        alert('Produit supprimé avec succès');
      },
      error: (err) => alert('Erreur: ' + (err.error?.message || 'Une erreur est survenue'))
    });
  }
}
