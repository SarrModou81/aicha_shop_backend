<?php

namespace Database\Seeders;

use App\Models\Marque;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarqueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Création des marques...');

        $marques = [
            // Marques internationales
            'Nike', 'Adidas', 'Puma', 'Reebok',
            'Zara', 'H&M', 'Mango',
            'Lacoste', 'Ralph Lauren', 'Tommy Hilfiger',
            'Gucci', 'Louis Vuitton', 'Prada',
            'Rolex', 'Casio', 'Fossil',

            // Marques africaines
            'Woodin', 'Vlisco', 'Da Viva',
            'Maison Château Rouge', 'Orange Culture',
            'Maxhosa', 'Loza Maléombho',

            // Marques locales sénégalaises
            'Dakar Fashion Week', 'Senegal Brand',
            'Made in Senegal', 'African Touch'
        ];

        foreach ($marques as $marque) {
            Marque::create([
                'name' => $marque,
                'slug' => Str::slug($marque),
                'description' => "Marque {$marque} - Qualité et style",
                'is_active' => true
            ]);
        }

        $this->command->info('✓ ' . count($marques) . ' marques créées');
    }
}
