<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Création des catégories...');

        $categories = [
            [
                'name' => 'Vêtements Femme',
                'description' => 'Robes, jupes, tops, pantalons pour femmes'
            ],
            [
                'name' => 'Vêtements Homme',
                'description' => 'Chemises, pantalons, costumes pour hommes'
            ],
            [
                'name' => 'Chaussures',
                'description' => 'Chaussures pour hommes, femmes et enfants'
            ],
            [
                'name' => 'Sacs et Maroquinerie',
                'description' => 'Sacs à main, portefeuilles, ceintures'
            ],
            [
                'name' => 'Accessoires',
                'description' => 'Bijoux, montres, lunettes, écharpes'
            ],
            [
                'name' => 'Mode Africaine',
                'description' => 'Tenues traditionnelles, bazin, wax'
            ],
            [
                'name' => 'Sportswear',
                'description' => 'Vêtements et chaussures de sport'
            ],
            [
                'name' => 'Enfants',
                'description' => 'Vêtements et chaussures pour enfants'
            ],
            [
                'name' => 'Bijoux',
                'description' => 'Colliers, bracelets, boucles d\'oreilles'
            ],
            [
                'name' => 'Montres',
                'description' => 'Montres pour hommes et femmes'
            ]
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
                'is_active' => true
            ]);
        }

        $this->command->info('✓ ' . count($categories) . ' catégories créées');
    }
}
