<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Produit;
use App\Models\Commande;
use App\Models\Category;
use App\Models\Marque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    // ========== GESTION UTILISATEURS ==========

    // Liste des utilisateurs
    public function getUsers(Request $request)
    {
        $users = User::withCount(['commandes', 'produits'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($users);
    }

    // Créer un vendeur
    public function createVendeur(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $vendeur = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'vendeur',
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city
        ]);

        return response()->json([
            'vendeur' => $vendeur,
            'message' => 'Vendeur créé avec succès'
        ], 201);
    }

    // Modifier utilisateur
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,' . $id,
            'role' => 'in:admin,vendeur,client',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user->update($request->only(['name', 'email', 'role', 'is_active']));

        return response()->json([
            'user' => $user,
            'message' => 'Utilisateur mis à jour avec succès'
        ]);
    }

    // Désactiver utilisateur
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activé' : 'désactivé';

        return response()->json([
            'user' => $user,
            'message' => "Utilisateur $status avec succès"
        ]);
    }

    // ========== GESTION PRODUITS ==========

    // Liste des produits en attente d'approbation
    public function getPendingProducts()
    {
        $products = Produit::with(['category', 'marque', 'vendeur', 'stock'])
            ->where('status', 'en_attente')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($products);
    }

    // Approuver produit
    public function approveProduct($id)
    {
        $product = Produit::findOrFail($id);
        $product->status = 'approuve';
        $product->save();

        // Créer une notification pour le vendeur
        Notification::create([
            'user_id' => $product->vendeur_id,
            'type' => 'produit_approve',
            'message' => "Votre produit '{$product->name}' a été approuvé"
        ]);

        return response()->json([
            'product' => $product,
            'message' => 'Produit approuvé avec succès'
        ]);
    }

    // Rejeter produit
    public function rejectProduct(Request $request, $id)
    {
        $product = Produit::findOrFail($id);
        $product->status = 'rejete';
        $product->save();

        Notification::create([
            'user_id' => $product->vendeur_id,
            'type' => 'produit_rejete',
            'message' => "Votre produit '{$product->name}' a été rejeté. Raison: " . $request->reason
        ]);

        return response()->json([
            'message' => 'Produit rejeté avec succès'
        ]);
    }

    // ========== GESTION CATEGORIES ==========

    // Liste des catégories
    public function getCategories()
    {
        $categories = Category::withCount('produits')->get();
        return response()->json($categories);
    }

    // Créer catégorie
    public function createCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'description' => $request->description,
            'image' => $request->hasFile('image') ? $request->file('image')->store('categories') : null
        ]);

        return response()->json([
            'category' => $category,
            'message' => 'Catégorie créée avec succès'
        ], 201);
    }

    // Modifier catégorie
    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $request->only(['name', 'description', 'is_active']);

        if ($request->has('name')) {
            $data['slug'] = \Str::slug($request->name);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories');
        }

        $category->update($data);

        return response()->json([
            'category' => $category,
            'message' => 'Catégorie mise à jour avec succès'
        ]);
    }

    // ========== GESTION MARQUES ==========

    // CRUD similaire aux catégories
    public function getMarques()
    {
        $marques = Marque::withCount('produits')->get();
        return response()->json($marques);
    }

    public function createMarque(Request $request)
    {
        // Similaire à createCategory
    }

    // ========== DASHBOARD STATISTIQUES ==========

    public function getDashboardStats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_vendeurs' => User::where('role', 'vendeur')->count(),
            'total_clients' => User::where('role', 'client')->count(),
            'total_products' => Produit::count(),
            'total_orders' => Commande::count(),
            'pending_orders' => Commande::where('status', 'en_attente')->count(),
            'total_revenue' => Commande::where('status', '!=', 'annulee')->sum('total'),
            'today_orders' => Commande::whereDate('created_at', today())->count(),
            'today_revenue' => Commande::whereDate('created_at', today())->where('status', '!=', 'annulee')->sum('total')
        ];

        // Graphique des commandes par mois
        $ordersByMonth = Commande::selectRaw('MONTH(created_at) as month, COUNT(*) as count, SUM(total) as revenue')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Produits les plus vendus
        $topProducts = DetailCommande::selectRaw('produit_id, produit_name, SUM(quantity) as total_sold')
            ->groupBy('produit_id', 'produit_name')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'orders_by_month' => $ordersByMonth,
            'top_products' => $topProducts
        ]);
    }

    // ========== GESTION COMMANDES ==========

    public function getAllOrders(Request $request)
    {
        $query = Commande::with(['user', 'details.produit', 'paiement', 'livraison'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20);

        return response()->json($orders);
    }

    // Mettre à jour statut commande
    public function updateOrderStatus(Request $request, $id)
    {
        $order = Commande::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:en_attente,confirmee,preparation,expediee,livree,annulee'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $order->status = $request->status;
        $order->save();

        // Notification au client
        Notification::create([
            'user_id' => $order->user_id,
            'type' => 'order_status_update',
            'message' => "Votre commande #{$order->order_number} est maintenant {$request->status}"
        ]);

        return response()->json([
            'order' => $order,
            'message' => 'Statut de commande mis à jour'
        ]);
    }

    // ========== GESTION AVIS ==========

    public function getReviews()
    {
        $reviews = Avis::with(['user', 'produit'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($reviews);
    }

    public function approveReview($id)
    {
        $review = Avis::findOrFail($id);
        $review->is_approved = true;
        $review->save();

        return response()->json([
            'review' => $review,
            'message' => 'Avis approuvé avec succès'
        ]);
    }

    public function deleteReview($id)
    {
        $review = Avis::findOrFail($id);
        $review->delete();

        return response()->json([
            'message' => 'Avis supprimé avec succès'
        ]);
    }

    // ========== LOGS ET SECURITE ==========

    public function getSecurityLogs()
    {
        // Implémenter selon les besoins
        return response()->json([
            'message' => 'Logs de sécurité (à implémenter)'
        ]);
    }
}
