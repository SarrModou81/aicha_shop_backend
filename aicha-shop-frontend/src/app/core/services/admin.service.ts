import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { User } from '../models/user.model';
import { Product, Category, Marque } from '../models/product.model';

@Injectable({
  providedIn: 'root'
})
export class AdminService {

  constructor(private http: HttpClient) {}

  // Dashboard
  getDashboardStats(): Observable<any> {
    return this.http.get(`${environment.apiUrl}/admin/dashboard`);
  }

  // Users
  getUsers(role?: string): Observable<User[]> {
    let url = `${environment.apiUrl}/admin/users`;
    if (role) url += `?role=${role}`;
    return this.http.get<User[]>(url);
  }

  createVendeur(data: any): Observable<User> {
    return this.http.post<User>(`${environment.apiUrl}/admin/users/vendeurs`, data);
  }

  updateUser(id: number, data: any): Observable<User> {
    return this.http.put<User>(`${environment.apiUrl}/admin/users/${id}`, data);
  }

  toggleUserStatus(id: number): Observable<any> {
    return this.http.post(`${environment.apiUrl}/admin/users/${id}/toggle-status`, {});
  }

  resetUserPassword(id: number): Observable<any> {
    return this.http.post(`${environment.apiUrl}/admin/users/${id}/reset-password`, {});
  }

  // Products
  getPendingProducts(): Observable<Product[]> {
    return this.http.get<Product[]>(`${environment.apiUrl}/admin/products/pending`);
  }

  approveProduct(id: number): Observable<any> {
    return this.http.post(`${environment.apiUrl}/admin/products/${id}/approve`, {});
  }

  rejectProduct(id: number, reason: string): Observable<any> {
    return this.http.post(`${environment.apiUrl}/admin/products/${id}/reject`, { reason });
  }

  deleteProduct(id: number): Observable<any> {
    return this.http.delete(`${environment.apiUrl}/admin/products/${id}`);
  }

  // Categories & Marques
  getCategories(): Observable<Category[]> {
    return this.http.get<Category[]>(`${environment.apiUrl}/admin/categories`);
  }

  createCategory(data: any): Observable<Category> {
    return this.http.post<Category>(`${environment.apiUrl}/admin/categories`, data);
  }

  updateCategory(id: number, data: any): Observable<Category> {
    return this.http.put<Category>(`${environment.apiUrl}/admin/categories/${id}`, data);
  }

  deleteCategory(id: number): Observable<any> {
    return this.http.delete(`${environment.apiUrl}/admin/categories/${id}`);
  }

  getMarques(): Observable<Marque[]> {
    return this.http.get<Marque[]>(`${environment.apiUrl}/admin/marques`);
  }

  createMarque(data: any): Observable<Marque> {
    return this.http.post<Marque>(`${environment.apiUrl}/admin/marques`, data);
  }

  // Orders
  getAllOrders(): Observable<any[]> {
    return this.http.get<any[]>(`${environment.apiUrl}/admin/orders`);
  }

  updateOrderStatus(id: number, status: string): Observable<any> {
    return this.http.put(`${environment.apiUrl}/admin/orders/${id}/status`, { status });
  }

  // Settings
  getSettings(group?: string): Observable<any> {
    let url = `${environment.apiUrl}/admin/settings`;
    if (group) url += `/${group}`;
    return this.http.get(url);
  }

  updateSetting(key: string, value: any): Observable<any> {
    return this.http.put(`${environment.apiUrl}/admin/settings/${key}`, { value });
  }
}
