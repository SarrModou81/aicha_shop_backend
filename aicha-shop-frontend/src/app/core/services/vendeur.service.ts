import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Product } from '../models/product.model';

export interface VendeurStats {
  total_products: number;
  active_products: number;
  total_orders: number;
  pending_orders: number;
  total_revenue: number;
  low_stock_count: number;
}

export interface SalesStats {
  total_sales: number;
  total_orders: number;
  average_order: number;
  sales_by_period: {date: string; amount: number}[];
  top_products: {name: string; sales: number}[];
}

@Injectable({
  providedIn: 'root'
})
export class VendeurService {

  constructor(private http: HttpClient) {}

  getDashboardStats(): Observable<VendeurStats> {
    return this.http.get<VendeurStats>(`${environment.apiUrl}/vendeur/dashboard`);
  }

  getMyProducts(): Observable<Product[]> {
    return this.http.get<Product[]>(`${environment.apiUrl}/vendeur/products`);
  }

  addProduct(data: FormData): Observable<Product> {
    return this.http.post<Product>(`${environment.apiUrl}/vendeur/products`, data);
  }

  updateProduct(id: number, data: FormData): Observable<Product> {
    return this.http.post<Product>(`${environment.apiUrl}/vendeur/products/${id}?_method=PUT`, data);
  }

  deleteProduct(id: number): Observable<any> {
    return this.http.delete(`${environment.apiUrl}/vendeur/products/${id}`);
  }

  toggleProductVisibility(id: number): Observable<any> {
    return this.http.post(`${environment.apiUrl}/vendeur/products/${id}/toggle`, {});
  }

  updateStock(produitId: number, quantity: number): Observable<any> {
    return this.http.put(`${environment.apiUrl}/vendeur/products/${produitId}/stock`, { quantity });
  }

  getLowStockProducts(): Observable<Product[]> {
    return this.http.get<Product[]>(`${environment.apiUrl}/vendeur/products/low-stock`);
  }

  getMyOrders(): Observable<any[]> {
    return this.http.get<any[]>(`${environment.apiUrl}/vendeur/orders`);
  }

  getOrderDetails(id: number): Observable<any> {
    return this.http.get<any>(`${environment.apiUrl}/vendeur/orders/${id}`);
  }

  updateOrderStatus(id: number, status: string): Observable<any> {
    return this.http.put(`${environment.apiUrl}/vendeur/orders/${id}/status`, { status });
  }

  cancelOrder(id: number, reason: string): Observable<any> {
    return this.http.post(`${environment.apiUrl}/vendeur/orders/${id}/cancel`, { reason });
  }

  getSalesStats(period?: string): Observable<SalesStats> {
    let url = `${environment.apiUrl}/vendeur/stats/sales`;
    if (period) {
      url += `?period=${period}`;
    }
    return this.http.get<SalesStats>(url);
  }

  generateReport(params: any): Observable<any> {
    return this.http.post(`${environment.apiUrl}/vendeur/reports/generate`, params);
  }

  getReports(): Observable<any[]> {
    return this.http.get<any[]>(`${environment.apiUrl}/vendeur/reports`);
  }
}
