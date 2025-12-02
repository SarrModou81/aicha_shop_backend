<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Création des zones de livraison...');

        $zones = [
            [
                'name' => 'Dakar Centre',
                'description' => 'Plateau, Médina, Fann, Point E',
                'shipping_cost' => 1500,
                'estimated_days' => 1,
                'cities' => ['Dakar', 'Plateau', 'Médina', 'Fann', 'Point E', 'Almadies']
            ],
            [
                'name' => 'Dakar Périphérie',
                'description' => 'Parcelles, Guédiawaye, Pikine, Rufisque',
                'shipping_cost' => 2000,
                'estimated_days' => 2,
                'cities' => ['Parcelles Assainies', 'Guédiawaye', 'Pikine', 'Rufisque', 'Thiaroye']
            ],
            [
                'name' => 'Thiès',
                'description' => 'Ville de Thiès et environs',
                'shipping_cost' => 3000,
                'estimated_days' => 2,
                'cities' => ['Thiès', 'Tivaouane', 'Mbour', 'Kayar']
            ],
            [
                'name' => 'Saint-Louis',
                'description' => 'Ville de Saint-Louis et environs',
                'shipping_cost' => 4000,
                'estimated_days' => 3,
                'cities' => ['Saint-Louis', 'Dagana', 'Podor', 'Richard Toll']
            ],
            [
                'name' => 'Kaolack',
                'description' => 'Kaolack et région centre',
                'shipping_cost' => 3500,
                'estimated_days' => 3,
                'cities' => ['Kaolack', 'Fatick', 'Kaffrine', 'Gossas']
            ],
            [
                'name' => 'Ziguinchor',
                'description' => 'Casamance - Ziguinchor et environs',
                'shipping_cost' => 5000,
                'estimated_days' => 4,
                'cities' => ['Ziguinchor', 'Bignona', 'Oussouye', 'Sédhiou']
            ],
            [
                'name' => 'Autres Régions',
                'description' => 'Tambacounda, Kolda, Matam, Louga',
                'shipping_cost' => 4500,
                'estimated_days' => 4,
                'cities' => ['Tambacounda', 'Kolda', 'Matam', 'Louga', 'Kédougou', 'Diourbel']
            ]
        ];

        foreach ($zones as $zone) {
            DeliveryZone::create($zone);
        }

        $this->command->info('✓ ' . count($zones) . ' zones de livraison créées');
    }
}
