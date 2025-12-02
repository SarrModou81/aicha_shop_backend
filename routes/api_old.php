<?php

// routes/api.php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\VendeurController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes pour tous les utilisateurs authentifiés
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Routes client (accessibles à tous)
    Route::prefix('client')->group(function () {
        Route::get('/products', [ClientController::class, 'getProducts']);
        Route::get('/products/{slug}', [ClientController::class, 'getProduct']);
        Route::get('/products/featured', [ClientController::class, 'getFeaturedProducts']);
        Route::get('/categories', [ClientController::class, 'getCategoriesList']);
        Route::get('/marques', [ClientController::class, 'getMarquesList']);

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

    // Routes vendeur
    Route::middleware('role:vendeur')->prefix('vendeur')->group(function () {
        Route::get('/dashboard', [VendeurController::class, 'getDashboardStats']);

        // Produits
        Route::get('/products', [VendeurController::class, 'getMyProducts']);
        Route::post('/products', [VendeurController::class, 'addProduct']);
        Route::put('/products/{id}', [VendeurController::class, 'updateProduct']);
        Route::delete('/products/{id}', [VendeurController::class, 'deleteProduct']);

        // Stock
        Route::put('/products/{produitId}/stock', [VendeurController::class, 'updateStock']);
        Route::get('/products/low-stock', [VendeurController::class, 'getLowStockProducts']);
        Route::post('/stock/import', [VendeurController::class, 'importStock']);

        // Commandes
        Route::get('/orders', [VendeurController::class, 'getMyOrders']);
        Route::get('/orders/{id}', [VendeurController::class, 'getOrderDetails']);
        Route::put('/orders/{id}/status', [VendeurController::class, 'updateOrderStatus']);

        // Statistiques
        Route::get('/stats/sales', [VendeurController::class, 'getSalesStats']);

        // Boutique
        Route::put('/shop', [VendeurController::class, 'updateShopInfo']);
    });

    // Routes admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Utilisateurs
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/vendeurs', [AdminController::class, 'createVendeur']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);

        // Produits
        Route::get('/products/pending', [AdminController::class, 'getPendingProducts']);
        Route::post('/products/{id}/approve', [AdminController::class, 'approveProduct']);
        Route::post('/products/{id}/reject', [AdminController::class, 'rejectProduct']);

        // Catégories
        Route::get('/categories', [AdminController::class, 'getCategories']);
        Route::post('/categories', [AdminController::class, 'createCategory']);
        Route::put('/categories/{id}', [AdminController::class, 'updateCategory']);

        // Marques
        Route::get('/marques', [AdminController::class, 'getMarques']);
        Route::post('/marques', [AdminController::class, 'createMarque']);
        Route::put('/marques/{id}', [AdminController::class, 'updateMarque']);

        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'getDashboardStats']);

        // Commandes
        Route::get('/orders', [AdminController::class, 'getAllOrders']);
        Route::put('/orders/{id}/status', [AdminController::class, 'updateOrderStatus']);

        // Avis
        Route::get('/reviews', [AdminController::class, 'getReviews']);
        Route::post('/reviews/{id}/approve', [AdminController::class, 'approveReview']);
        Route::delete('/reviews/{id}', [AdminController::class, 'deleteReview']);

        // Sécurité
        Route::get('/security-logs', [AdminController::class, 'getSecurityLogs']);
    });
});
