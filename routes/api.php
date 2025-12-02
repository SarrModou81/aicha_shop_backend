<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\VendeurController;
use App\Http\Controllers\PaiementController;
use Illuminate\Support\Facades\Route;

// ========== ROUTES PUBLIQUES ==========
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Produits publics (sans authentification)
Route::get('/products', [ClientController::class, 'getProducts']);
Route::get('/products/{slug}', [ClientController::class, 'getProduct']);
Route::get('/products/featured', [ClientController::class, 'getFeaturedProducts']);
Route::get('/categories', [ClientController::class, 'getCategoriesList']);
Route::get('/marques', [ClientController::class, 'getMarquesList']);

// ========== ROUTES AUTHENTIFIÉES ==========
Route::middleware('auth:sanctum')->group(function () {

    // ========== AUTH ==========
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // ========== CLIENT ==========
    Route::prefix('client')->group(function () {
        // Panier
        Route::get('/cart', [ClientController::class, 'getCart']);
        Route::post('/cart/add', [ClientController::class, 'addToCart']);
        Route::put('/cart/{id}', [ClientController::class, 'updateCartItem']);
        Route::delete('/cart/{id}', [ClientController::class, 'removeFromCart']);
        Route::delete('/cart', [ClientController::class, 'clearCart']);

        // Commandes
        Route::post('/orders', [ClientController::class, 'placeOrder']);
        Route::get('/orders', [ClientController::class, 'getMyOrders']);
        Route::get('/orders/{orderNumber}', [ClientController::class, 'getOrderDetails']);
        Route::post('/orders/{id}/cancel', [ClientController::class, 'cancelOrder']);

        // Avis
        Route::post('/reviews', [ClientController::class, 'addReview']);
        Route::get('/reviews', [ClientController::class, 'getMyReviews']);

        // Notifications
        Route::get('/notifications', [ClientController::class, 'getNotifications']);
        Route::post('/notifications/{id}/read', [ClientController::class, 'markNotificationAsRead']);
        Route::post('/notifications/read-all', [ClientController::class, 'markAllNotificationsAsRead']);
    });

    // ========== PAIEMENT ==========
    Route::prefix('payment')->group(function () {
        Route::post('/initiate', [PaiementController::class, 'initiatePayment']);
        Route::get('/{id}/status', [PaiementController::class, 'checkPaymentStatus']);
    });

    // Webhook public pour paiement (pas d'auth)
    Route::post('/payment/webhook', [PaiementController::class, 'paymentWebhook'])->withoutMiddleware('auth:sanctum');

    // ========== VENDEUR ==========
    Route::middleware('role:vendeur')->prefix('vendeur')->group(function () {
        // Dashboard
        Route::get('/dashboard', [VendeurController::class, 'getDashboardStats']);

        // Produits
        Route::get('/products', [VendeurController::class, 'getMyProducts']);
        Route::post('/products', [VendeurController::class, 'addProduct']);
        Route::put('/products/{id}', [VendeurController::class, 'updateProduct']);
        Route::delete('/products/{id}', [VendeurController::class, 'deleteProduct']);
        Route::post('/products/{id}/toggle', [VendeurController::class, 'toggleProductVisibility']);
        Route::get('/products/{id}/analyze', [VendeurController::class, 'analyzeProduct']);

        // Stock
        Route::put('/products/{produitId}/stock', [VendeurController::class, 'updateStock']);
        Route::get('/products/low-stock', [VendeurController::class, 'getLowStockProducts']);
        Route::post('/stock/import-csv', [VendeurController::class, 'importStockCSV']);

        // Commandes
        Route::get('/orders', [VendeurController::class, 'getMyOrders']);
        Route::get('/orders/{id}', [VendeurController::class, 'getOrderDetails']);
        Route::put('/orders/{id}/status', [VendeurController::class, 'updateOrderStatus']);
        Route::post('/orders/{id}/cancel', [VendeurController::class, 'cancelOrder']);

        // Statistiques
        Route::get('/stats/sales', [VendeurController::class, 'getSalesStats']);

        // Rapports
        Route::post('/reports/generate', [VendeurController::class, 'generateReport']);
        Route::get('/reports', [VendeurController::class, 'getReports']);
        Route::get('/reports/{id}/download', [VendeurController::class, 'downloadReport']);

        // Boutique
        Route::put('/shop', [VendeurController::class, 'updateShopInfo']);
    });

    // ========== ADMIN ==========
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'getDashboardStats']);
        Route::get('/system-info', [AdminController::class, 'getSystemInfo']);

        // ===== Utilisateurs =====
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminController::class, 'getUsers']);
            Route::post('/vendeurs', [AdminController::class, 'createVendeur']);
            Route::put('/{id}', [AdminController::class, 'updateUser']);
            Route::post('/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
            Route::post('/{id}/reset-password', [AdminController::class, 'resetUserPassword']);
        });

        // Vendeurs
        Route::prefix('vendeurs')->group(function () {
            Route::get('/pending', [AdminController::class, 'getPendingVendeurs']);
            Route::post('/{id}/validate', [AdminController::class, 'validateVendeur']);
        });

        // ===== Produits =====
        Route::prefix('products')->group(function () {
            Route::get('/pending', [AdminController::class, 'getPendingProducts']);
            Route::post('/{id}/approve', [AdminController::class, 'approveProduct']);
            Route::post('/{id}/reject', [AdminController::class, 'rejectProduct']);
            Route::delete('/{id}', [AdminController::class, 'deleteProduct']);
        });

        // ===== Catégories =====
        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminController::class, 'getCategories']);
            Route::post('/', [AdminController::class, 'createCategory']);
            Route::put('/{id}', [AdminController::class, 'updateCategory']);
            Route::delete('/{id}', [AdminController::class, 'deleteCategory']);
        });

        // ===== Marques =====
        Route::prefix('marques')->group(function () {
            Route::get('/', [AdminController::class, 'getMarques']);
            Route::post('/', [AdminController::class, 'createMarque']);
            Route::put('/{id}', [AdminController::class, 'updateMarque']);
        });

        // ===== Commandes =====
        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminController::class, 'getAllOrders']);
            Route::put('/{id}/status', [AdminController::class, 'updateOrderStatus']);
        });

        // ===== Avis =====
        Route::prefix('reviews')->group(function () {
            Route::get('/', [AdminController::class, 'getReviews']);
            Route::post('/{id}/approve', [AdminController::class, 'approveReview']);
            Route::put('/{id}', [AdminController::class, 'updateReview']);
            Route::delete('/{id}', [AdminController::class, 'deleteReview']);
        });

        // ===== Paramètres système =====
        Route::prefix('settings')->group(function () {
            Route::get('/', [AdminController::class, 'getSettings']);
            Route::get('/{group}', [AdminController::class, 'getSettings']);
            Route::put('/{key}', [AdminController::class, 'updateSetting']);
        });

        // ===== Zones de livraison =====
        Route::prefix('delivery-zones')->group(function () {
            Route::get('/', [AdminController::class, 'getDeliveryZones']);
            Route::post('/', [AdminController::class, 'createDeliveryZone']);
            Route::put('/{id}', [AdminController::class, 'updateDeliveryZone']);
            Route::delete('/{id}', [AdminController::class, 'deleteDeliveryZone']);
        });

        // ===== Commissions =====
        Route::prefix('commissions')->group(function () {
            Route::get('/', [AdminController::class, 'getCommissions']);
            Route::post('/{id}/pay', [AdminController::class, 'payCommission']);
            Route::post('/configure-rate', [AdminController::class, 'configureCommissionRate']);
        });

        // ===== Notifications =====
        Route::prefix('notifications')->group(function () {
            Route::get('/settings', [AdminController::class, 'getNotificationSettings']);
            Route::put('/settings', [AdminController::class, 'updateNotificationSettings']);
        });

        // ===== Sécurité =====
        Route::prefix('security')->group(function () {
            Route::get('/logs', [AdminController::class, 'getSecurityLogs']);
            Route::get('/audit-transactions', [AdminController::class, 'auditTransactions']);
        });

        // ===== Paiements =====
        Route::prefix('payments')->group(function () {
            Route::post('/{id}/refund', [PaiementController::class, 'refundPayment']);
        });

        // ===== Rapports =====
        Route::post('/reports/generate', [AdminController::class, 'generateGlobalReport']);

        // ===== Maintenance =====
        Route::prefix('maintenance')->group(function () {
            Route::post('/clear-cache', [AdminController::class, 'clearCache']);
        });
    });
});
