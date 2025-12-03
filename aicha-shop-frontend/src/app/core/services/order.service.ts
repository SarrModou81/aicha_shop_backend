import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Order, CreateOrderData } from '../models/order.model';

@Injectable({
  providedIn: 'root'
})
export class OrderService {

  constructor(private http: HttpClient) {}

  placeOrder(data: CreateOrderData): Observable<Order> {
    return this.http.post<Order>(`${environment.apiUrl}/client/orders`, data);
  }

  getMyOrders(): Observable<Order[]> {
    return this.http.get<Order[]>(`${environment.apiUrl}/client/orders`);
  }

  getOrderDetails(orderNumber: string): Observable<Order> {
    return this.http.get<Order>(`${environment.apiUrl}/client/orders/${orderNumber}`);
  }

  cancelOrder(id: number): Observable<any> {
    return this.http.post(`${environment.apiUrl}/client/orders/${id}/cancel`, {});
  }
}
