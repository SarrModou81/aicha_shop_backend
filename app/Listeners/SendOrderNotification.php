<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Notification;
use App\Models\User;

class SendOrderNotification
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event)
    {
        $order = $event->order;

        // Notification au client
        Notification::create([
            'user_id' => $order->user_id,
            'type' => 'order_placed',
            'message' => "Votre commande #{$order->order_number} a été enregistrée avec succès"
        ]);

        // Notifier les vendeurs concernés
        $vendeurIds = $order->details->pluck('produit.vendeur_id')->unique();

        foreach ($vendeurIds as $vendeurId) {
            if ($vendeurId) {
                Notification::create([
                    'user_id' => $vendeurId,
                    'type' => 'new_order',
                    'message' => "Vous avez reçu une nouvelle commande #{$order->order_number}"
                ]);
            }
        }

        // Notifier les admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'new_order',
                'message' => "Nouvelle commande #{$order->order_number} passée"
            ]);
        }
    }
}
