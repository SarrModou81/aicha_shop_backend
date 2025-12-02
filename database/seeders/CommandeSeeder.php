<?php

namespace Database\Seeders;

use App\Models\Commande;
use App\Models\DetailCommande;
use App\Models\Paiement;
use App\Models\Livraison;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommandeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Création des commandes de test...');

        $clients = User::where('role', 'client')->get();
        $produits = Produit::with('stock')->where('status', 'approuve')->get();

        if ($clients->isEmpty() || $produits->isEmpty()) {
            $this->command->warn('⚠ Pas de clients ou produits disponibles pour créer des commandes');
            return;
        }

        $statuses = ['en_attente', 'confirmee', 'preparation', 'expediee', 'livree'];
        $paymentMethods = ['wave', 'orange_money', 'carte', 'especes'];
        $count = 0;

        // Créer 20 commandes de test
        for ($i = 0; $i < 20; $i++) {
            $client = $clients->random();
            $nbProduits = rand(1, 4);
            $selectedProduits = $produits->random($nbProduits);

            $subtotal = 0;
            $items = [];

            foreach ($selectedProduits as $produit) {
                $quantity = rand(1, 3);
                $price = $produit->price;
                $total = $price * $quantity;
                $subtotal += $total;

                $items[] = [
                    'produit' => $produit,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $total
                ];
            }

            $shipping = 2000;
            $tax = $subtotal * 0.18;
            $total = $subtotal + $shipping + $tax;

            // Créer la commande
            $commande = Commande::create([
                'order_number' => 'CMD' . date('Ymd') . rand(1000, 9999),
                'user_id' => $client->id,
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'tax' => $tax,
                'total' => $total,
                'status' => $statuses[array_rand($statuses)],
                'notes' => 'Commande de test',
                'created_at' => now()->subDays(rand(0, 30))
            ]);

            // Créer les détails
            foreach ($items as $item) {
                DetailCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $item['produit']->id,
                    'produit_name' => $item['produit']->name,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['total']
                ]);
            }

            // Créer le paiement
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
            $paymentStatus = in_array($commande->status, ['confirmee', 'preparation', 'expediee', 'livree'])
                ? 'paye'
                : 'en_attente';

            Paiement::create([
                'commande_id' => $commande->id,
                'payment_method' => $paymentMethod,
                'amount' => $total,
                'status' => $paymentStatus,
                'transaction_id' => strtoupper($paymentMethod) . '_' . uniqid(),
                'payment_details' => json_encode(['test' => true])
            ]);

            // Créer la livraison
            Livraison::create([
                'commande_id' => $commande->id,
                'address' => $client->address,
                'city' => $client->city,
                'phone' => $client->phone,
                'status' => $commande->status
            ]);

            $count++;
        }

        $this->command->info('✓ ' . $count . ' commandes de test créées');
    }
}
