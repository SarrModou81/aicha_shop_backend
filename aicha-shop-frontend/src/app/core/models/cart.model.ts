export interface CartItem {
  id: number;
  user_id: number;
  produit_id: number;
  quantity: number;
  price: number;
  produit?: {
    id: number;
    name: string;
    slug: string;
    price: number;
    images: string[];
    stock?: {
      quantity: number;
    };
  };
  created_at: string;
  updated_at: string;
}

export interface Cart {
  items: CartItem[];
  total: number;
  count: number;
}
