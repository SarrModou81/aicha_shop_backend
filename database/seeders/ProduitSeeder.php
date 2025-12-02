<?php

namespace Database\Seeders;

use App\Models\Produit;
use App\Models\Stock;
use App\Models\Category;
use App\Models\Marque;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProduitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Création des produits...');

        $vendeurs = User::where('role', 'vendeur')->get();
        $categories = Category::all();
        $marques = Marque::all();

        $produits = [
            // Vêtements Femme
            ['Robe Wax Africaine', 'Magnifique robe en wax avec motifs africains', 25000, 30000, 'Mode Africaine'],
            ['Ensemble Bazin Brodé', 'Ensemble complet bazin riche brodé main', 45000, 55000, 'Mode Africaine'],
            ['Robe Cocktail Élégante', 'Robe de soirée élégante pour occasions spéciales', 35000, 42000, 'Vêtements Femme'],
            ['Jupe Plissée Mode', 'Jupe plissée tendance, plusieurs couleurs', 15000, 18000, 'Vêtements Femme'],
            ['Top Crop Fashion', 'Top crop moderne et confortable', 8000, 12000, 'Vêtements Femme'],

            // Vêtements Homme
            ['Chemise Homme Classique', 'Chemise classique pour bureau', 12000, 15000, 'Vêtements Homme'],
            ['Pantalon Chino Élégant', 'Pantalon chino coupe moderne', 18000, 22000, 'Vêtements Homme'],
            ['Costume 3 Pièces', 'Costume complet veste + pantalon + gilet', 85000, 100000, 'Vêtements Homme'],
            ['T-shirt Polo Premium', 'Polo de qualité, plusieurs couleurs', 15000, 18000, 'Vêtements Homme'],

            // Chaussures
            ['Baskets Nike Air', 'Baskets Nike confortables pour sport', 45000, 55000, 'Chaussures'],
            ['Sandales Femme Été', 'Sandales élégantes pour l\'été', 12000, 15000, 'Chaussures'],
            ['Escarpins Talon Haut', 'Escarpins élégants talon 8cm', 25000, 30000, 'Chaussures'],
            ['Mocassins Homme Cuir', 'Mocassins en cuir véritable', 35000, 42000, 'Chaussures'],
            ['Chaussures Sport Adidas', 'Chaussures de sport Adidas', 40000, 48000, 'Sportswear'],

            // Sacs et Maroquinerie
            ['Sac à Main Luxe', 'Sac à main de luxe en cuir', 55000, 65000, 'Sacs et Maroquinerie'],
            ['Portefeuille Homme', 'Portefeuille en cuir avec porte-cartes', 15000, 18000, 'Sacs et Maroquinerie'],
            ['Sac à Dos Urbain', 'Sac à dos moderne pour ville', 25000, 30000, 'Sacs et Maroquinerie'],
            ['Ceinture Cuir Premium', 'Ceinture en cuir véritable', 12000, 15000, 'Sacs et Maroquinerie'],

            // Accessoires
            ['Collier Or 18K', 'Collier en or 18 carats', 150000, 180000, 'Bijoux'],
            ['Bracelet Argent', 'Bracelet en argent 925', 25000, 30000, 'Bijoux'],
            ['Boucles d\'Oreilles Perles', 'Boucles d\'oreilles avec perles', 18000, 22000, 'Bijoux'],
            ['Montre Homme Sport', 'Montre sportive étanche', 45000, 55000, 'Montres'],
            ['Montre Femme Élégante', 'Montre élégante avec bracelet acier', 65000, 75000, 'Montres'],
            ['Lunettes de Soleil Ray-Ban', 'Lunettes de soleil protection UV', 35000, 42000, 'Accessoires'],
            ['Écharpe Cachemire', 'Écharpe douce en cachemire', 22000, 28000, 'Accessoires'],

            // Enfants
            ['Ensemble Bébé Coton', 'Ensemble complet pour bébé', 15000, 18000, 'Enfants'],
            ['Robe Petite Fille', 'Jolie robe pour petite fille', 12000, 15000, 'Enfants'],
            ['Pantalon Garçon', 'Pantalon confortable pour garçon', 10000, 12000, 'Enfants'],
            ['Baskets Enfant', 'Baskets colorées pour enfants', 18000, 22000, 'Enfants'],

            // Mode Africaine
            ['Boubou Homme Brodé', 'Boubou traditionnel brodé', 55000, 65000, 'Mode Africaine'],
            ['Kaftan Femme Luxe', 'Kaftan de luxe pour occasions', 75000, 90000, 'Mode Africaine'],
            ['Ensemble Wax Complet', 'Ensemble wax chemise + pantalon', 35000, 42000, 'Mode Africaine'],
        ];

        $count = 0;
        foreach ($produits as $produitData) {
            $vendeur = $vendeurs->random();
            $categoryName = $produitData[4];
            $category = $categories->firstWhere('name', $categoryName) ?? $categories->random();
            $marque = $marques->random();

            $produit = Produit::create([
                'name' => $produitData[0],
                'slug' => Str::slug($produitData[0]) . '-' . uniqid(),
                'description' => $produitData[1] . '. Produit de qualité disponible chez ' . $vendeur->shop_info['shop_name'] . '.',
                'price' => $produitData[2],
                'compare_price' => $produitData[3],
                'category_id' => $category->id,
                'marque_id' => $marque->id,
                'vendeur_id' => $vendeur->id,
                'is_featured' => rand(0, 100) > 80, // 20% de chance d'être en vedette
                'is_active' => true,
                'status' => 'approuve',
                'views' => rand(10, 500),
                'images' => [
                    'produits/sample-' . rand(1, 10) . '.jpg',
                    'produits/sample-' . rand(1, 10) . '.jpg'
                ],
                'attributes' => [
                    'tailles' => ['S', 'M', 'L', 'XL'],
                    'couleurs' => ['Noir', 'Blanc', 'Bleu', 'Rouge'],
                    'matiere' => ['Coton', 'Polyester', 'Cuir'][rand(0, 2)]
                ]
            ]);

            // Créer le stock
            Stock::create([
                'produit_id' => $produit->id,
                'quantity' => rand(5, 100),
                'low_stock_threshold' => 10
            ]);

            $count++;
        }

        $this->command->info('✓ ' . $count . ' produits créés avec leur stock');
    }
}
