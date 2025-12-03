export interface User {
  id: number;
  name: string;
  email: string;
  role: 'client' | 'vendeur' | 'admin';
  phone?: string;
  address?: string;
  city?: string;
  country?: string;
  is_active: boolean;
  shop_info?: {
    name?: string;
    description?: string;
    logo?: string;
  };
  is_validated?: boolean;
  validated_at?: string;
  created_at: string;
  updated_at: string;
}

export interface AuthResponse {
  user: User;
  token: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role: 'client' | 'vendeur';
  phone?: string;
  address?: string;
  city?: string;
  country?: string;
}
