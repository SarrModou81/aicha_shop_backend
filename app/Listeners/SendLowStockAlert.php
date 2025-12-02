<?php

namespace App\Listeners;

use App\Events\StockLow;
use App\Models\Notification;

class SendLowStockAlert
{
    /**
     * Handle the event.
     */
    public function handle(StockLow $event)
    {
        $stock = $event->stock;
        $produit = $stock->produit;

        if (!$produit) {
            return;
        }

        // Notification au vendeur
        Notification::create([
            'user_id' => $produit->vendeur_id,
            'type' => 'low_stock',
            'message' => "Stock faible pour le produit '{$produit->name}': {$stock->quantity} unités restantes"
        ]);
    }
}
