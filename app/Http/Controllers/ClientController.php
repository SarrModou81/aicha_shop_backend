<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\Category;
use App\Models\Marque;
use App\Models\Panier;
use App\Models\Commande;
use App\Models\Avis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    // ========== PRODUITS ==========

    // Liste des produits
    public function getProducts(Request $request)
    {
        $query = Produit::with(['category', 'marque', 'stock', 'avis'])
            ->active()
            ->withAvg('avis', 'rating')
            ->withCount('avis');

        // Filtrage
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('marque')) {
            $query->where('marque_id', $request->marque);
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'popular':
                    $query->orderBy('views', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(12);

        return response()->json($products);
    }

    // Produit détails
    public function getProduct($slug)
    {
        $product = Produit::with(['category', 'marque', 'stock', 'vendeur'])
            ->with(['avis' => function($query) {
                $query->approved()->with('user');
            }])
            ->withAvg('avis', 'rating')
            ->withCount('avis')
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        // Augmenter les vues
        $product->increment('views');

        // Produits similaires
        $related = Produit::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->limit(4)
            ->get();

        return response()->json([
            'product' => $product,
            'related' => $related
        ]);
    }

    // Produits en vedette
    public function getFeaturedProducts()
    {
        $products = Produit::with(['category', 'marque', 'stock'])
            ->active()
            ->featured()
            ->limit(8)
            ->get();

        return response()->json($products);
    }

    // ========== CATEGORIES ET MARQUES ==========

    public function getCategoriesList()
    {
        $categories = Category::active()->withCount(['produits' => function($query) {
            $query->active();
        }])->get();

        return response()->json($categories);
    }

    public function getMarquesList()
    {
        $marques = Marque::active()->withCount(['produits' => function($query) {
            $query->active();
        }])->get();

        return response()->json($marques);
    }

    // ========== PANIER ==========

    // Voir panier
    public function getCart(Request $request)
    {
        $cart = Panier::with('produit.stock')
            ->where('user_id', $request->user()->id)
            ->get();

        $total = $cart->sum(function($item) {
            return $item->total;
        });

        return response()->json([
            'items' => $cart,
            'total' => $total,
            'count' => $cart->count()
        ]);
    }

    // Ajouter au panier
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $produit = Produit::findOrFail($request->produit_id);

        // Vérifier stock
        if ($produit->stock && $produit->stock->quantity < $request->quantity) {
            return response()->json([
                'message' => 'Stock insuffisant'
            ], 400);
        }

        $panier = Panier::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'produit_id' => $request->produit_id
            ],
            [
                'quantity' => $request->quantity
            ]
        );

        return response()->json([
            'panier' => $panier,
            'message' => 'Produit ajouté au panier'
        ]);
    }

    // Modifier quantité panier
    public function updateCartItem(Request $request, $id)
    {
        $panier = Panier::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Vérifier stock
        if ($panier->produit->stock && $panier->produit->stock->quantity < $request->quantity) {
            return response()->json([
                'message' => 'Stock insuffisant'
            ], 400);
        }

        $panier->quantity = $request->quantity;
        $panier->save();

        return response()->json([
            'panier' => $panier,
            'message' => 'Panier mis à jour'
        ]);
    }

    // Supprimer du panier
    public function removeFromCart(Request $request, $id)
    {
        $panier = Panier::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $panier->delete();

        return response()->json([
            'message' => 'Produit retiré du panier'
        ]);
    }

    // Vider panier
    public function clearCart(Request $request)
    {
        Panier::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'Panier vidé'
        ]);
    }

    // ========== COMMANDES ==========

    // Passer commande
    public function placeOrder(Request $request)
    {
        $user = $request->user();
        $cart = Panier::with('produit.stock')
            ->where('user_id', $user->id)
            ->get();

        if ($cart->isEmpty()) {
            return response()->json([
                'message' => 'Votre panier est vide'
            ], 400);
        }

        // Vérifier stock pour tous les produits
        foreach ($cart as $item) {
            if (!$item->produit->stock || $item->produit->stock->quantity < $item->quantity) {
                return response()->json([
                    'message' => "Stock insuffisant pour: {$item->produit->name}"
                ], 400);
            }
        }

        // Calculer total
        $subtotal = $cart->sum(function($item) {
            return $item->total;
        });

        $shipping = 2000; // Frais de livraison fixe
        $tax = $subtotal * 0.18; // TVA 18%
        $total = $subtotal + $shipping + $tax;

        // Créer commande
        $commande = Commande::create([
            'order_number' => 'CMD' . time() . rand(100, 999),
            'user_id' => $user->id,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'status' => 'en_attente',
            'notes' => $request->notes
        ]);

        // Créer détails commande
        foreach ($cart as $item) {
            DetailCommande::create([
                'commande_id' => $commande->id,
                'produit_id' => $item->produit_id,
                'produit_name' => $item->produit->name,
                'price' => $item->produit->price,
                'quantity' => $item->quantity,
                'total' => $item->total
            ]);

            // Mettre à jour stock
            $item->produit->stock->decrement('quantity', $item->quantity);
        }

        // Créer livraison
        Livraison::create([
            'commande_id' => $commande->id,
            'address' => $user->address,
            'city' => $user->city,
            'phone' => $user->phone,
            'status' => 'en_preparation'
        ]);

        // Vider panier
        Panier::where('user_id', $user->id)->delete();

        // Notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'order_placed',
            'message' => "Votre commande #{$commande->order_number} a été passée avec succès"
        ]);

        return response()->json([
            'commande' => $commande,
            'message' => 'Commande passée avec succès'
        ], 201);
    }

    // Liste des commandes client
    public function getMyOrders(Request $request)
    {
        $orders = Commande::with(['details.produit', 'paiement', 'livraison'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    // Détails commande
    public function getOrderDetails($orderNumber)
    {
        $order = Commande::with(['details.produit', 'paiement', 'livraison'])
            ->where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json($order);
    }

    // Annuler commande
    public function cancelOrder($id)
    {
        $order = Commande::where('user_id', auth()->id())
            ->where('id', $id)
            ->where('status', 'en_attente')
            ->firstOrFail();

        $order->status = 'annulee';
        $order->save();

        // Restaurer stock
        foreach ($order->details as $detail) {
            $stock = Stock::where('produit_id', $detail->produit_id)->first();
            if ($stock) {
                $stock->increment('quantity', $detail->quantity);
            }
        }

        Notification::create([
            'user_id' => $order->user_id,
            'type' => 'order_cancelled',
            'message' => "Votre commande #{$order->order_number} a été annulée"
        ]);

        return response()->json([
            'message' => 'Commande annulée avec succès'
        ]);
    }

    // ========== AVIS ==========

    // Ajouter avis
    public function addReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Vérifier si l'utilisateur a acheté ce produit
        $hasPurchased = DetailCommande::whereHas('commande', function($query) {
            $query->where('user_id', auth()->id());
        })->where('produit_id', $request->produit_id)->exists();

        if (!$hasPurchased) {
            return response()->json([
                'message' => 'Vous devez avoir acheté ce produit pour donner un avis'
            ], 403);
        }

        $review = Avis::create([
            'user_id' => auth()->id(),
            'produit_id' => $request->produit_id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json([
            'review' => $review,
            'message' => 'Avis ajouté avec succès'
        ], 201);
    }

    // Mes avis
    public function getMyReviews(Request $request)
    {
        $reviews = Avis::with('produit')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($reviews);
    }

    // ========== NOTIFICATIONS ==========

    public function getNotifications(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markNotificationAsRead($id)
    {
        $notification = Notification::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marquée comme lue'
        ]);
    }

    public function markAllNotificationsAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'Toutes les notifications marquées comme lues'
        ]);
    }
}
