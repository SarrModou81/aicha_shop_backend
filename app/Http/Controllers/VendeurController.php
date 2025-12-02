<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\Category;
use App\Models\Marque;
use App\Models\Stock;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class VendeurController extends Controller
{
    // ========== DASHBOARD ==========

    public function getDashboardStats(Request $request)
    {
        $vendeurId = $request->user()->id;

        $stats = [
            'total_products' => Produit::where('vendeur_id', $vendeurId)->count(),
            'active_products' => Produit::where('vendeur_id', $vendeurId)
                ->where('is_active', true)
                ->where('status', 'approuve')
                ->count(),
            'pending_products' => Produit::where('vendeur_id', $vendeurId)
                ->where('status', 'en_attente')
                ->count(),
            'total_orders' => Commande::whereHas('details.produit', function($query) use ($vendeurId) {
                $query->where('vendeur_id', $vendeurId);
            })->count(),
            'total_revenue' => Commande::whereHas('details.produit', function($query) use ($vendeurId) {
                $query->where('vendeur_id', $vendeurId);
            })->where('status', '!=', 'annulee')->sum('total'),
            'low_stock' => Stock::whereHas('produit', function($query) use ($vendeurId) {
                $query->where('vendeur_id', $vendeurId);
            })->whereRaw('quantity <= low_stock_threshold')->count()
        ];

        // Commandes récentes
        $recentOrders = Commande::with(['details' => function($query) use ($vendeurId) {
            $query->whereHas('produit', function($q) use ($vendeurId) {
                $q->where('vendeur_id', $vendeurId);
            });
        }])->whereHas('details.produit', function($query) use ($vendeurId) {
            $query->where('vendeur_id', $vendeurId);
        })->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Produits les plus vendus
        $topProducts = Produit::where('vendeur_id', $vendeurId)
            ->withSum(['detailCommandes' => function($query) {
                $query->whereHas('commande', function($q) {
                    $q->where('status', '!=', 'annulee');
                });
            }], 'quantity')
            ->orderBy('detail_commandes_sum_quantity', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_orders' => $recentOrders,
            'top_products' => $topProducts
        ]);
    }

    // ========== GESTION PRODUITS ==========

    // Liste des produits du vendeur
    public function getMyProducts(Request $request)
    {
        $products = Produit::with(['category', 'marque', 'stock'])
            ->where('vendeur_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($products);
    }

    // Ajouter produit
    public function addProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'marque_id' => 'nullable|exists:marques,id',
            'quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'images' => 'required|array|min:1',
            'images.*' => 'image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Sauvegarder images
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('produits');
            }
        }

        // Créer produit
        $produit = Produit::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name) . '-' . time(),
            'description' => $request->description,
            'price' => $request->price,
            'compare_price' => $request->compare_price,
            'category_id' => $request->category_id,
            'marque_id' => $request->marque_id,
            'vendeur_id' => $request->user()->id,
            'status' => 'en_attente', // En attente d'approbation admin
            'images' => $imagePaths
        ]);

        // Créer stock
        Stock::create([
            'produit_id' => $produit->id,
            'quantity' => $request->quantity,
            'low_stock_threshold' => $request->low_stock_threshold ?? 10
        ]);

        // Notification admin pour approbation
        // ...

        return response()->json([
            'produit' => $produit,
            'message' => 'Produit ajouté avec succès. En attente d\'approbation.'
        ], 201);
    }

    // Modifier produit
    public function updateProduct(Request $request, $id)
    {
        $produit = Produit::where('vendeur_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'string',
            'price' => 'numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'category_id' => 'exists:categories,id',
            'marque_id' => 'nullable|exists:marques,id',
            'is_active' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $request->only(['name', 'description', 'price', 'compare_price',
            'category_id', 'marque_id', 'is_active']);

        if ($request->has('name')) {
            $data['slug'] = \Str::slug($request->name) . '-' . $produit->id;
        }

        // Gestion des images
        if ($request->hasFile('images')) {
            // Supprimer anciennes images
            if ($produit->images) {
                foreach ($produit->images as $oldImage) {
                    Storage::delete($oldImage);
                }
            }

            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('produits');
            }
            $data['images'] = $imagePaths;
        }

        $produit->update($data);

        // Si produit approuvé et modifié, retourner en attente d'approbation
        if ($produit->status === 'approuve' && $request->hasAny(['name', 'description', 'price', 'images'])) {
            $produit->status = 'en_attente';
            $produit->save();
        }

        return response()->json([
            'produit' => $produit,
            'message' => 'Produit mis à jour avec succès'
        ]);
    }

    // Supprimer produit
    public function deleteProduct(Request $request, $id)
    {
        $produit = Produit::where('vendeur_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        // Supprimer images
        if ($produit->images) {
            foreach ($produit->images as $image) {
                Storage::delete($image);
            }
        }

        $produit->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }

    // ========== GESTION STOCK ==========

    // Mettre à jour stock
    public function updateStock(Request $request, $produitId)
    {
        $produit = Produit::where('vendeur_id', $request->user()->id)
            ->where('id', $produitId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $stock = Stock::where('produit_id', $produitId)->first();

        if (!$stock) {
            $stock = Stock::create([
                'produit_id' => $produitId,
                'quantity' => $request->quantity,
                'low_stock_threshold' => $request->low_stock_threshold ?? 10
            ]);
        } else {
            $stock->update([
                'quantity' => $request->quantity,
                'low_stock_threshold' => $request->low_stock_threshold ?? $stock->low_stock_threshold
            ]);
        }

        return response()->json([
            'stock' => $stock,
            'message' => 'Stock mis à jour avec succès'
        ]);
    }

    // Liste des produits en rupture de stock
    public function getLowStockProducts(Request $request)
    {
        $lowStock = Produit::where('vendeur_id', $request->user()->id)
            ->whereHas('stock', function($query) {
                $query->whereRaw('quantity <= low_stock_threshold');
            })
            ->with('stock')
            ->paginate(20);

        return response()->json($lowStock);
    }

    // Importer stock via CSV
    public function importStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Logique d'importation CSV
        // ...

        return response()->json([
            'message' => 'Stock importé avec succès'
        ]);
    }

    // ========== GESTION COMMANDES ==========

    // Liste des commandes du vendeur
    public function getMyOrders(Request $request)
    {
        $orders = Commande::whereHas('details.produit', function($query) use ($request) {
            $query->where('vendeur_id', $request->user()->id);
        })
            ->with(['details' => function($query) use ($request) {
                $query->whereHas('produit', function($q) use ($request) {
                    $q->where('vendeur_id', $request->user()->id);
                })->with('produit');
            }, 'user', 'livraison'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    // Détails commande
    public function getOrderDetails($orderId)
    {
        $order = Commande::whereHas('details.produit', function($query) {
            $query->where('vendeur_id', auth()->id());
        })
            ->with(['details' => function($query) {
                $query->whereHas('produit', function($q) {
                    $q->where('vendeur_id', auth()->id());
                })->with('produit');
            }, 'user', 'livraison', 'paiement'])
            ->findOrFail($orderId);

        return response()->json($order);
    }

    // Mettre à jour statut commande
    public function updateOrderStatus(Request $request, $orderId)
    {
        $order = Commande::whereHas('details.produit', function($query) {
            $query->where('vendeur_id', auth()->id());
        })->findOrFail($orderId);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:confirmee,preparation,expediee'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $order->status = $request->status;
        $order->save();

        // Mettre à jour livraison si expédiée
        if ($request->status === 'expediee' && $order->livraison) {
            $order->livraison->status = 'expediee';
            $order->livraison->save();
        }

        // Notification client
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

    // ========== STATISTIQUES ==========

    public function getSalesStats(Request $request)
    {
        $vendeurId = $request->user()->id;

        // Ventes par mois
        $salesByMonth = Commande::selectRaw('MONTH(created_at) as month, COUNT(*) as orders, SUM(total) as revenue')
            ->whereHas('details.produit', function($query) use ($vendeurId) {
                $query->where('vendeur_id', $vendeurId);
            })
            ->whereYear('created_at', date('Y'))
            ->where('status', '!=', 'annulee')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Catégories les plus vendues
        $topCategories = Category::selectRaw('categories.name, SUM(detail_commandes.quantity) as total_sold')
            ->join('produits', 'categories.id', '=', 'produits.category_id')
            ->join('detail_commandes', 'produits.id', '=', 'detail_commandes.produit_id')
            ->join('commandes', 'detail_commandes.commande_id', '=', 'commandes.id')
            ->where('produits.vendeur_id', $vendeurId)
            ->where('commandes.status', '!=', 'annulee')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'sales_by_month' => $salesByMonth,
            'top_categories' => $topCategories
        ]);
    }

    // ========== PROFIL VENDEUR ==========

    public function updateShopInfo(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'shop_name' => 'nullable|string|max:255',
            'shop_description' => 'nullable|string',
            'shop_logo' => 'nullable|image|max:2048',
            'shop_banner' => 'nullable|image|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Créer ou mettre à jour les informations de boutique
        $shopInfo = [
            'shop_name' => $request->shop_name,
            'shop_description' => $request->shop_description
        ];

        if ($request->hasFile('shop_logo')) {
            $shopInfo['shop_logo'] = $request->file('shop_logo')->store('shop_logos');
        }

        if ($request->hasFile('shop_banner')) {
            $shopInfo['shop_banner'] = $request->file('shop_banner')->store('shop_banners');
        }

        $user->shop_info = $shopInfo;
        $user->save();

        return response()->json([
            'user' => $user,
            'message' => 'Informations boutique mises à jour'
        ]);
    }
}
