import { NgModule } from '@angular/core';
import { SharedModule } from '../../shared/shared.module';
import { VendeurRoutingModule } from './vendeur-routing.module';
import { VendeurLayoutComponent } from './layout/vendeur-layout.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ProductsComponent } from './products/products.component';
import { OrdersComponent } from './orders/orders.component';
import { StatsComponent } from './stats/stats.component';

@NgModule({
  declarations: [
    VendeurLayoutComponent,
    DashboardComponent,
    ProductsComponent,
    OrdersComponent,
    StatsComponent
  ],
  imports: [
    SharedModule,
    VendeurRoutingModule
  ]
})
export class VendeurModule { }
