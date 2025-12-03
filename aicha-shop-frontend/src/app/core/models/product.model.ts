export interface Product {
  id: number;
  name: string;
  slug: string;
  description: string;
  price: number;
  compare_price?: number;
  category_id: number;
  marque_id: number;
  vendeur_id: number;
  is_featured: boolean;
  is_active: boolean;
  status: 'en_attente' | 'approuve' | 'rejete';
  images: string[];
  views: number;
  attributes?: ProductAttribute[];
  category?: Category;
  marque?: Marque;
  vendeur?: {
    id: number;
    name: string;
    shop_info?: any;
  };
  stock?: Stock;
  created_at: string;
  updated_at: string;
}

export interface ProductAttribute {
  name: string;
  value: string;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  icon?: string;
  parent_id?: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Marque {
  id: number;
  name: string;
  slug: string;
  logo?: string;
  description?: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Stock {
  id: number;
  produit_id: number;
  quantity: number;
  min_threshold: number;
  created_at: string;
  updated_at: string;
}
