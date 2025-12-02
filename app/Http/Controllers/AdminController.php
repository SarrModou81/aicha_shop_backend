<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Produit;
use App\Models\Commande;
use App\Models\Category;
use App\Models\Marque;
use App\Models\Avis;
use App\Models\DetailCommande;
use App\Models\Notification;
use App\Models\SecurityLog;
use App\Models\SystemSetting;
use App\Models\DeliveryZone;
use App\Models\Commission;
use App\Models\Report;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class AdminControllerEnriched extends Controller
{
    // ========== GESTION UTILISATEURS ==========

    public function getUsers(Request $request)
    {
        $query = User::withCount(['commandes', 'produits']);

        // Filtrer par rôle
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filtrer par statut
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Recherche
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($users);
    }

    public function createVendeur(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'auto_validate' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $vendeur = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'vendeur',
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'is_validated' => $request->auto_validate ?? false,
            'validated_at' => $request->auto_validate ? now() : null,
            'validated_by' => $request->auto_validate ? auth()->id() : null
        ]);

        // Notification au vendeur
        Notification::create([
            'user_id' => $vendeur->id,
            'type' => 'account_created',
            'message' => 'Votre compte vendeur a été créé avec succès'
        ]);

        // Log de sécurité
        SecurityLog::logAction(
            auth()->id(),
            'create_vendeur',
            "Nouveau vendeur créé: {$vendeur->email}",
            'info',
            ['vendeur_id' => $vendeur->id]
        );

        return response()->json([
            'vendeur' => $vendeur,
            'message' => 'Vendeur créé avec succès'
        ], 201);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,' . $id,
            'role' => 'in:admin,vendeur,client',
            'is_active' => 'boolean',
            'phone' => 'string',
            'address' => 'string',
            'city' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $oldData = $user->toArray();
        $user->update($request->only(['name', 'email', 'role', 'is_active', 'phone', 'address', 'city']));

        // Log de sécurité
        SecurityLog::logAction(
            auth()->id(),
            'update_user',
            "Utilisateur modifié: {$user->email}",
            'info',
            ['user_id' => $user->id, 'changes' => array_diff_assoc($user->toArray(), $oldData)]
        );

        return response()->json([
            'user' => $user,
            'message' => 'Utilisateur mis à jour avec succès'
        ]);
    }

    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activé' : 'désactivé';

        // Notification à l'utilisateur
        Notification::create([
            'user_id' => $user->id,
            'type' => 'account_status_changed',
            'message' => "Votre compte a été $status"
        ]);

        // Log de sécurité
        SecurityLog::logAction(
            auth()->id(),
            'toggle_user_status',
            "Utilisateur $status: {$user->email}",
            'warning',
            ['user_id' => $user->id, 'new_status' => $user->is_active]
        );

        return response()->json([
            'user' => $user,
            'message' => "Utilisateur $status avec succès"
        ]);
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetUserPassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Notification à l'utilisateur
        Notification::create([
            'user_id' => $user->id,
            'type' => 'password_reset',
            'message' => 'Votre mot de passe a été réinitialisé par un administrateur'
        ]);

        // Log de sécurité
        SecurityLog::logAction(
            auth()->id(),
            'reset_user_password',
            "Mot de passe réinitialisé pour: {$user->email}",
            'warning',
            ['user_id' => $user->id]
        );

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }

    /**
     * Valider un compte vendeur
     */
    public function validateVendeur($id)
    {
        $vendeur = User::where('role', 'vendeur')->findOrFail($id);

        $vendeur->is_validated = true;
        $vendeur->validated_at = now();
        $vendeur->validated_by = auth()->id();
        $vendeur->save();

        // Notification au vendeur
        Notification::create([
            'user_id' => $vendeur->id,
            'type' => 'account_validated',
            'message' => 'Votre compte vendeur a été validé. Vous pouvez maintenant commencer à vendre.'
        ]);

        return response()->json([
            'vendeur' => $vendeur,
            'message' => 'Vendeur validé avec succès'
        ]);
    }

    /**
     * Liste des vendeurs en attente de validation
     */
    public function getPendingVendeurs()
    {
        $vendeurs = User::where('role', 'vendeur')
            ->where('is_validated', false)
            ->withCount('produits')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($vendeurs);
    }

    // ========== GESTION PRODUITS ==========

    public function getPendingProducts()
    {
        $products = Produit::with(['category', 'marque', 'vendeur', 'stock'])
            ->where('status', 'en_attente')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($products);
    }

    public function approveProduct($id)
    {
        $product = Produit::findOrFail($id);
        $product->status = 'approuve';
        $product->is_active = true;
        $product->save();

        // Notification au vendeur
        Notification::create([
            'user_id' => $product->vendeur_id,
            'type' => 'produit_approve',
            'message' => "Votre produit '{$product->name}' a été approuvé et est maintenant visible"
        ]);

        return response()->json([
            'product' => $product,
            'message' => 'Produit approuvé avec succès'
        ]);
    }

    public function rejectProduct(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $product = Produit::findOrFail($id);
        $product->status = 'rejete';
        $product->save();

        // Notification au vendeur
        Notification::create([
            'user_id' => $product->vendeur_id,
            'type' => 'produit_rejete',
            'message' => "Votre produit '{$product->name}' a été rejeté. Raison: {$request->reason}"
        ]);

        return response()->json([
            'message' => 'Produit rejeté avec succès'
        ]);
    }

    /**
     * Supprimer un produit (admin only)
     */
    public function deleteProduct(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $product = Produit::findOrFail($id);

        // Notification au vendeur
        Notification::create([
            'user_id' => $product->vendeur_id,
            'type' => 'produit_supprime',
            'message' => "Votre produit '{$product->name}' a été supprimé. Raison: {$request->reason}"
        ]);

        // Log de sécurité
        SecurityLog::logAction(
            auth()->id(),
            'delete_product',
            "Produit supprimé: {$product->name}",
            'warning',
            ['product_id' => $product->id, 'reason' => $request->reason]
        );

        $product->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }

    // ========== GESTION CATEGORIES ==========

    public function getCategories()
    {
        $categories = Category::withCount('produits')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

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

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);

        // Vérifier s'il y a des produits dans cette catégorie
        if ($category->produits()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer une catégorie contenant des produits'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }

    // ========== GESTION MARQUES ==========

    public function getMarques()
    {
        $marques = Marque::withCount('produits')
            ->orderBy('name')
            ->get();

        return response()->json($marques);
    }

    public function createMarque(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:marques',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $marque = Marque::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'description' => $request->description,
            'logo' => $request->hasFile('logo') ? $request->file('logo')->store('marques') : null
        ]);

        return response()->json([
            'marque' => $marque,
            'message' => 'Marque créée avec succès'
        ], 201);
    }

    public function updateMarque(Request $request, $id)
    {
        $marque = Marque::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:marques,name,' . $id,
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $request->only(['name', 'description', 'is_active']);

        if ($request->has('name')) {
            $data['slug'] = \Str::slug($request->name);
        }

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('marques');
        }

        $marque->update($data);

        return response()->json([
            'marque' => $marque,
            'message' => 'Marque mise à jour avec succès'
        ]);
    }

    // ========== DASHBOARD STATISTIQUES ==========

    public function getDashboardStats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_vendeurs' => User::where('role', 'vendeur')->count(),
            'active_vendeurs' => User::where('role', 'vendeur')->where('is_validated', true)->count(),
            'pending_vendeurs' => User::where('role', 'vendeur')->where('is_validated', false)->count(),
            'total_clients' => User::where('role', 'client')->count(),
            'total_products' => Produit::count(),
            'active_products' => Produit::where('is_active', true)->where('status', 'approuve')->count(),
            'pending_products' => Produit::where('status', 'en_attente')->count(),
            'total_orders' => Commande::count(),
            'pending_orders' => Commande::where('status', 'en_attente')->count(),
            'total_revenue' => Commande::where('status', '!=', 'annulee')->sum('total'),
            'total_commissions' => Commission::where('status', 'paye')->sum('commission_amount'),
            'pending_commissions' => Commission::where('status', 'en_attente')->sum('commission_amount'),
            'today_orders' => Commande::whereDate('created_at', today())->count(),
            'today_revenue' => Commande::whereDate('created_at', today())->where('status', '!=', 'annulee')->sum('total'),
            'month_revenue' => Commande::whereMonth('created_at', now()->month)->where('status', '!=', 'annulee')->sum('total')
        ];

        // Graphique des commandes par mois
        $ordersByMonth = Commande::selectRaw('MONTH(created_at) as month, COUNT(*) as count, SUM(total) as revenue')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Produits les plus vendus
        $topProducts = DetailCommande::selectRaw('produit_id, produit_name, SUM(quantity) as total_sold, SUM(total) as revenue')
            ->whereHas('commande', function($query) {
                $query->where('status', '!=', 'annulee');
            })
            ->groupBy('produit_id', 'produit_name')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();

        // Meilleurs vendeurs
        $topVendeurs = User::where('role', 'vendeur')
            ->withCount(['produits' => function($query) {
                $query->where('status', 'approuve');
            }])
            ->withSum(['produits as total_sales' => function($query) {
                $query->join('detail_commandes', 'produits.id', '=', 'detail_commandes.produit_id')
                      ->join('commandes', 'detail_commandes.commande_id', '=', 'commandes.id')
                      ->where('commandes.status', '!=', 'annulee');
            }], 'detail_commandes.total')
            ->orderBy('total_sales', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'orders_by_month' => $ordersByMonth,
            'top_products' => $topProducts,
            'top_vendeurs' => $topVendeurs
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

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function($q2) use ($request) {
                      $q2->where('name', 'like', "%{$request->search}%")
                         ->orWhere('email', 'like', "%{$request->search}%");
                  });
            });
        }

        $orders = $query->paginate(20);

        return response()->json($orders);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $order = Commande::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:en_attente,confirmee,preparation,expediee,livree,annulee'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();

        // Mettre à jour livraison
        if ($order->livraison) {
            $order->livraison->status = $request->status;
            $order->livraison->save();
        }

        // Notification au client
        Notification::create([
            'user_id' => $order->user_id,
            'type' => 'order_status_update',
            'message' => "Votre commande #{$order->order_number} est maintenant {$request->status}"
        ]);

        // Log de sécurité
        SecurityLog::logAction(
            auth()->id(),
            'update_order_status',
            "Statut commande modifié: {$order->order_number} ({$oldStatus} -> {$request->status})",
            'info',
            ['order_id' => $order->id, 'old_status' => $oldStatus, 'new_status' => $request->status]
        );

        return response()->json([
            'order' => $order,
            'message' => 'Statut de commande mis à jour'
        ]);
    }

    // ========== GESTION AVIS ==========

    public function getReviews(Request $request)
    {
        $query = Avis::with(['user', 'produit'])
            ->orderBy('created_at', 'desc');

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->is_approved);
        }

        $reviews = $query->paginate(20);

        return response()->json($reviews);
    }

    public function approveReview($id)
    {
        $review = Avis::findOrFail($id);
        $review->is_approved = true;
        $review->save();

        // Notification à l'utilisateur
        Notification::create([
            'user_id' => $review->user_id,
            'type' => 'review_approved',
            'message' => 'Votre avis a été approuvé et est maintenant visible'
        ]);

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

    public function updateReview(Request $request, $id)
    {
        $review = Avis::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $review->comment = $request->comment;
        $review->save();

        return response()->json([
            'review' => $review,
            'message' => 'Avis modifié avec succès'
        ]);
    }

    // ========== LOGS ET SECURITE ==========

    public function getSecurityLogs(Request $request)
    {
        $query = SecurityLog::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50);

        return response()->json($logs);
    }

    // ========== GESTION PARAMETRES SYSTEME ==========

    public function getSettings($group = null)
    {
        if ($group) {
            return response()->json(SystemSetting::getByGroup($group));
        }

        $settings = SystemSetting::orderBy('group')->orderBy('key')->get();

        return response()->json($settings);
    }

    public function updateSetting(Request $request, $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'type' => 'nullable|in:string,integer,boolean,json,array',
            'group' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $setting = SystemSetting::set(
            $key,
            $request->value,
            $request->type ?? 'string',
            $request->group ?? 'general'
        );

        // Log de sécurité
        SecurityLog::logAction(
            auth()->id(),
            'update_setting',
            "Paramètre système modifié: {$key}",
            'warning',
            ['key' => $key, 'value' => $request->value]
        );

        return response()->json([
            'setting' => $setting,
            'message' => 'Paramètre mis à jour avec succès'
        ]);
    }

    // ========== GESTION ZONES DE LIVRAISON ==========

    public function getDeliveryZones()
    {
        $zones = DeliveryZone::orderBy('name')->get();

        return response()->json($zones);
    }

    public function createDeliveryZone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'shipping_cost' => 'required|numeric|min:0',
            'estimated_days' => 'required|integer|min:1',
            'cities' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $zone = DeliveryZone::create($request->all());

        return response()->json([
            'zone' => $zone,
            'message' => 'Zone de livraison créée avec succès'
        ], 201);
    }

    public function updateDeliveryZone(Request $request, $id)
    {
        $zone = DeliveryZone::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'shipping_cost' => 'numeric|min:0',
            'estimated_days' => 'integer|min:1',
            'cities' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $zone->update($request->all());

        return response()->json([
            'zone' => $zone,
            'message' => 'Zone de livraison mise à jour avec succès'
        ]);
    }

    public function deleteDeliveryZone($id)
    {
        $zone = DeliveryZone::findOrFail($id);
        $zone->delete();

        return response()->json([
            'message' => 'Zone de livraison supprimée avec succès'
        ]);
    }

    // ========== GESTION COMMISSIONS ==========

    public function getCommissions(Request $request)
    {
        $query = Commission::with(['vendeur', 'commande'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vendeur_id')) {
            $query->where('vendeur_id', $request->vendeur_id);
        }

        $commissions = $query->paginate(20);

        return response()->json($commissions);
    }

    public function payCommission($id)
    {
        $commission = Commission::findOrFail($id);

        if ($commission->status === 'paye') {
            return response()->json(['message' => 'Commission déjà payée'], 400);
        }

        $commission->status = 'paye';
        $commission->paid_at = now();
        $commission->save();

        // Notification au vendeur
        Notification::create([
            'user_id' => $commission->vendeur_id,
            'type' => 'commission_paid',
            'message' => "Une commission de {$commission->vendeur_amount} FCFA vous a été versée"
        ]);

        return response()->json([
            'commission' => $commission,
            'message' => 'Commission payée avec succès'
        ]);
    }

    public function configureCommissionRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rate' => 'required|numeric|min:0|max:100',
            'vendeur_id' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if ($request->vendeur_id) {
            // Commission personnalisée pour un vendeur
            // TODO: Implémenter la logique de commission personnalisée
        } else {
            // Commission globale
            SystemSetting::set('commission_rate', $request->rate, 'decimal', 'payment');
        }

        return response()->json([
            'message' => 'Taux de commission configuré avec succès'
        ]);
    }

    // ========== GESTION NOTIFICATIONS ==========

    public function getNotificationSettings()
    {
        return response()->json(SystemSetting::getByGroup('notification'));
    }

    public function updateNotificationSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'order_notifications' => 'boolean',
            'payment_notifications' => 'boolean',
            'stock_notifications' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        foreach ($request->all() as $key => $value) {
            SystemSetting::set($key, $value, 'boolean', 'notification');
        }

        return response()->json([
            'message' => 'Paramètres de notification mis à jour avec succès'
        ]);
    }

    // ========== AUDIT DES TRANSACTIONS ==========

    public function auditTransactions(Request $request)
    {
        $query = Paiement::with(['commande.user', 'commande.details.produit'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->has('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        $transactions = $query->paginate(50);

        // Statistiques
        $stats = [
            'total_amount' => $query->sum('amount'),
            'total_transactions' => $query->count(),
            'success_rate' => $query->count() > 0
                ? ($query->where('status', 'paye')->count() / $query->count()) * 100
                : 0
        ];

        return response()->json([
            'transactions' => $transactions,
            'stats' => $stats
        ]);
    }

    // ========== MAINTENANCE SYSTEME ==========

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        SecurityLog::logAction(
            auth()->id(),
            'clear_cache',
            'Cache système vidé',
            'info'
        );

        return response()->json([
            'message' => 'Cache vidé avec succès'
        ]);
    }

    public function getSystemInfo()
    {
        $info = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'database_connection' => config('database.default'),
            'storage_path' => storage_path(),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'mail_driver' => config('mail.default')
        ];

        return response()->json($info);
    }

    /**
     * Générer un rapport global pour l'admin
     */
    public function generateGlobalReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:sales,users,products,transactions',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'format' => 'required|in:pdf,excel,csv'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $report = Report::create([
            'user_id' => auth()->id(),
            'type' => 'admin_' . $request->type,
            'title' => "Rapport Admin " . $request->type . " - " . now()->format('Y-m-d'),
            'description' => "Rapport global généré pour la période du {$request->date_from} au {$request->date_to}",
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'format' => $request->format,
            'filters' => $request->all(),
            'status' => 'termine'
        ]);

        return response()->json([
            'report' => $report,
            'message' => 'Rapport généré avec succès'
        ]);
    }
}
