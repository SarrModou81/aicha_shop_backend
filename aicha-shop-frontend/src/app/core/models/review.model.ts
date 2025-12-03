export interface Review {
  id: number;
  user_id: number;
  produit_id: number;
  rating: number;
  comment: string;
  status: 'en_attente' | 'approuve' | 'rejete';
  user?: {
    id: number;
    name: string;
  };
  created_at: string;
  updated_at: string;
}

export interface CreateReviewData {
  produit_id: number;
  rating: number;
  comment: string;
}
