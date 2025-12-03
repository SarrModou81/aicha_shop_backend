import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { CartItem, Cart } from '../models/cart.model';

@Injectable({
  providedIn: 'root'
})
export class CartService {
  private cartSubject = new BehaviorSubject<Cart>({ items: [], total: 0, count: 0 });
  public cart$ = this.cartSubject.asObservable();

  constructor(private http: HttpClient) {}

  getCart(): Observable<CartItem[]> {
    return this.http.get<CartItem[]>(`${environment.apiUrl}/client/cart`)
      .pipe(
        tap(items => this.updateCartState(items))
      );
  }

  addToCart(produit_id: number, quantity: number = 1): Observable<CartItem> {
    return this.http.post<CartItem>(`${environment.apiUrl}/client/cart/add`, { produit_id, quantity })
      .pipe(
        tap(() => this.refreshCart())
      );
  }

  updateCartItem(id: number, quantity: number): Observable<CartItem> {
    return this.http.put<CartItem>(`${environment.apiUrl}/client/cart/${id}`, { quantity })
      .pipe(
        tap(() => this.refreshCart())
      );
  }

  removeFromCart(id: number): Observable<any> {
    return this.http.delete(`${environment.apiUrl}/client/cart/${id}`)
      .pipe(
        tap(() => this.refreshCart())
      );
  }

  clearCart(): Observable<any> {
    return this.http.delete(`${environment.apiUrl}/client/cart`)
      .pipe(
        tap(() => this.updateCartState([]))
      );
  }

  private updateCartState(items: CartItem[]): void {
    const total = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const count = items.reduce((sum, item) => sum + item.quantity, 0);
    this.cartSubject.next({ items, total, count });
  }

  private refreshCart(): void {
    this.getCart().subscribe();
  }

  getCartCount(): number {
    return this.cartSubject.value.count;
  }

  getCartTotal(): number {
    return this.cartSubject.value.total;
  }
}
