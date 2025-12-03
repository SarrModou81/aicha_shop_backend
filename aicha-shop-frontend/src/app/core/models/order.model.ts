export interface Order {
  id: number;
  order_number: string;
  user_id: number;
  status: 'en_attente' | 'confirmee' | 'en_preparation' | 'expediee' | 'livree' | 'annulee';
  total_amount: number;
  payment_method: 'espece' | 'carte' | 'wave' | 'orange_money' | 'free_money';
  payment_status: 'en_attente' | 'payee' | 'echouee' | 'remboursee';
  shipping_address: string;
  shipping_city: string;
  shipping_country: string;
  shipping_phone: string;
  notes?: string;
  items?: OrderItem[];
  paiement?: Payment;
  livraison?: Livraison;
  created_at: string;
  updated_at: string;
}

export interface OrderItem {
  id: number;
  commande_id: number;
  produit_id: number;
  quantity: number;
  unit_price: number;
  total_price: number;
  produit?: {
    id: number;
    name: string;
    slug: string;
    images: string[];
  };
  created_at: string;
  updated_at: string;
}

export interface Payment {
  id: number;
  commande_id: number;
  payment_method: string;
  amount: number;
  status: string;
  transaction_id?: string;
  payment_details?: any;
  created_at: string;
  updated_at: string;
}

export interface Livraison {
  id: number;
  commande_id: number;
  tracking_number?: string;
  delivery_date?: string;
  status: string;
  notes?: string;
  created_at: string;
  updated_at: string;
}

export interface CreateOrderData {
  payment_method: 'espece' | 'carte' | 'wave' | 'orange_money' | 'free_money';
  shipping_address: string;
  shipping_city: string;
  shipping_country: string;
  shipping_phone: string;
  notes?: string;
}
