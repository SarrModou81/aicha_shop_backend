<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\Category;
use App\Models\Marque;
use App\Models\Stock;
use App\Models\Commande;
use App\Models\DetailCommande;
use App\Models\Notification;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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

    // ========== FONCTIONNALITÉS AVANCÉES ==========

    /**
     * Masquer/Activer produit
     */
    public function toggleProductVisibility(Request $request, $id)
    {
        $produit = Produit::where('vendeur_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $produit->is_active = !$produit->is_active;
        $produit->save();

        $status = $produit->is_active ? 'activé' : 'masqué';

        return response()->json([
            'produit' => $produit,
            'message' => "Produit $status avec succès"
        ]);
    }

    /**
     * Analyser les performances d'un produit spécifique
     */
    public function analyzeProduct(Request $request, $produitId)
    {
        $produit = Produit::where('vendeur_id', $request->user()->id)
            ->where('id', $produitId)
            ->with(['category', 'marque', 'stock'])
            ->withCount('avis')
            ->withAvg('avis', 'rating')
            ->firstOrFail();

        // Statistiques de ventes
        $sales = DetailCommande::where('produit_id', $produitId)
            ->whereHas('commande', function($query) {
                $query->where('status', '!=', 'annulee');
            })
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(quantity) as total_sold,
                SUM(total) as total_revenue
            ')
            ->first();

        // Ventes par mois (derniers 12 mois)
        $salesByMonth = DetailCommande::where('produit_id', $produitId)
            ->whereHas('commande', function($query) {
                $query->where('status', '!=', 'annulee');
            })
            ->selectRaw('
                DATE_FORMAT(detail_commandes.created_at, "%Y-%m") as month,
                SUM(quantity) as quantity,
                SUM(total) as revenue
            ')
            ->where('detail_commandes.created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Taux de conversion (vues vs achats)
        $conversionRate = $produit->views > 0
            ? ($sales->total_orders / $produit->views) * 100
            : 0;

        return response()->json([
            'produit' => $produit,
            'sales' => $sales,
            'sales_by_month' => $salesByMonth,
            'conversion_rate' => round($conversionRate, 2)
        ]);
    }

    /**
     * Générer un rapport de ventes
     */
    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:sales,products,inventory',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'format' => 'required|in:pdf,excel,csv'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $vendeurId = $request->user()->id;

        // Créer le rapport
        $report = Report::create([
            'user_id' => $vendeurId,
            'type' => $request->type,
            'title' => "Rapport " . $request->type . " - " . now()->format('Y-m-d'),
            'description' => "Rapport généré pour la période du {$request->date_from} au {$request->date_to}",
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'format' => $request->format,
            'filters' => $request->only(['type', 'date_from', 'date_to']),
            'status' => 'en_cours'
        ]);

        // Générer le rapport en arrière-plan
        // TODO: Utiliser une queue Laravel pour générer le rapport de manière asynchrone

        try {
            $data = $this->prepareReportData($vendeurId, $request->type, $request->date_from, $request->date_to);

            // Générer le fichier selon le format
            $fileName = "report_{$report->id}." . ($request->format === 'excel' ? 'xlsx' : $request->format);
            $filePath = "reports/{$fileName}";

            // TODO: Implémenter la génération de fichiers PDF/Excel/CSV
            // Pour l'instant, on simule la génération
            Storage::put($filePath, json_encode($data, JSON_PRETTY_PRINT));

            $report->file_path = $filePath;
            $report->status = 'termine';
            $report->save();

            // Notification
            Notification::create([
                'user_id' => $vendeurId,
                'type' => 'report_ready',
                'message' => "Votre rapport est prêt à être téléchargé"
            ]);

            return response()->json([
                'report' => $report,
                'message' => 'Rapport généré avec succès'
            ]);
        } catch (\Exception $e) {
            $report->status = 'erreur';
            $report->save();

            return response()->json([
                'message' => 'Erreur lors de la génération du rapport: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Préparer les données pour le rapport
     */
    protected function prepareReportData($vendeurId, $type, $dateFrom, $dateTo)
    {
        switch ($type) {
            case 'sales':
                return Commande::whereHas('details.produit', function($query) use ($vendeurId) {
                    $query->where('vendeur_id', $vendeurId);
                })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->with(['details' => function($query) use ($vendeurId) {
                    $query->whereHas('produit', function($q) use ($vendeurId) {
                        $q->where('vendeur_id', $vendeurId);
                    });
                }, 'user', 'paiement'])
                ->get();

            case 'products':
                return Produit::where('vendeur_id', $vendeurId)
                    ->with(['category', 'marque', 'stock'])
                    ->withCount(['detailCommandes' => function($query) use ($dateFrom, $dateTo) {
                        $query->whereBetween('created_at', [$dateFrom, $dateTo]);
                    }])
                    ->get();

            case 'inventory':
                return Produit::where('vendeur_id', $vendeurId)
                    ->with(['stock', 'category'])
                    ->get();

            default:
                return [];
        }
    }

    /**
     * Télécharger un rapport
     */
    public function downloadReport($reportId)
    {
        $report = Report::where('user_id', auth()->id())
            ->where('id', $reportId)
            ->firstOrFail();

        if ($report->status !== 'termine' || !$report->file_path) {
            return response()->json(['message' => 'Rapport non disponible'], 400);
        }

        if (!Storage::exists($report->file_path)) {
            return response()->json(['message' => 'Fichier non trouvé'], 404);
        }

        return Storage::download($report->file_path);
    }

    /**
     * Liste des rapports
     */
    public function getReports(Request $request)
    {
        $reports = Report::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($reports);
    }

    /**
     * Import CSV complet pour les stocks
     */
    public function importStockCSV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $vendeurId = $request->user()->id;
        $file = $request->file('csv_file');

        try {
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $header = array_shift($csvData); // Première ligne = en-têtes

            // Vérifier les en-têtes requis
            $requiredHeaders = ['product_id', 'quantity'];
            $missingHeaders = array_diff($requiredHeaders, $header);

            if (!empty($missingHeaders)) {
                return response()->json([
                    'message' => 'En-têtes manquants: ' . implode(', ', $missingHeaders)
                ], 400);
            }

            $results = [
                'success' => 0,
                'errors' => [],
                'total' => count($csvData)
            ];

            foreach ($csvData as $index => $row) {
                $rowData = array_combine($header, $row);
                $lineNumber = $index + 2; // +2 car index commence à 0 et on a enlevé l'en-tête

                try {
                    // Vérifier que le produit appartient au vendeur
                    $produit = Produit::where('id', $rowData['product_id'])
                        ->where('vendeur_id', $vendeurId)
                        ->first();

                    if (!$produit) {
                        $results['errors'][] = "Ligne $lineNumber: Produit non trouvé ou non autorisé";
                        continue;
                    }

                    // Mettre à jour le stock
                    $stock = Stock::where('produit_id', $produit->id)->first();

                    if ($stock) {
                        $stock->quantity = $rowData['quantity'];
                        if (isset($rowData['low_stock_threshold'])) {
                            $stock->low_stock_threshold = $rowData['low_stock_threshold'];
                        }
                        $stock->save();
                    } else {
                        Stock::create([
                            'produit_id' => $produit->id,
                            'quantity' => $rowData['quantity'],
                            'low_stock_threshold' => $rowData['low_stock_threshold'] ?? 10
                        ]);
                    }

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Ligne $lineNumber: " . $e->getMessage();
                }
            }

            return response()->json([
                'message' => "Import terminé: {$results['success']} succès sur {$results['total']}",
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler une commande (vendeur peut annuler avec raison)
     */
    public function cancelOrder(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $order = Commande::whereHas('details.produit', function($query) {
            $query->where('vendeur_id', auth()->id());
        })->findOrFail($orderId);

        if ($order->status === 'annulee') {
            return response()->json(['message' => 'Commande déjà annulée'], 400);
        }

        if (in_array($order->status, ['expediee', 'livree'])) {
            return response()->json(['message' => 'Commande déjà expédiée ou livrée'], 400);
        }

        $order->status = 'annulee';
        $order->notes = ($order->notes ?? '') . "\nAnnulée par le vendeur: " . $request->reason;
        $order->save();

        // Restaurer le stock
        foreach ($order->details as $detail) {
            $stock = Stock::where('produit_id', $detail->produit_id)->first();
            if ($stock) {
                $stock->increment('quantity', $detail->quantity);
            }
        }

        // Notification au client
        Notification::create([
            'user_id' => $order->user_id,
            'type' => 'order_cancelled',
            'message' => "Votre commande #{$order->order_number} a été annulée. Raison: {$request->reason}"
        ]);

        return response()->json([
            'message' => 'Commande annulée avec succès',
            'order' => $order
        ]);
    }
}
