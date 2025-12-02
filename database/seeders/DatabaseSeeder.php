<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Marque;

class DatabaseSeeder extends Seeder
{
// database/seeders/DatabaseSeeder.php
    public function run()
    {
        // Créer admin
        User::create([
            'name' => 'Admin Aicha',
            'email' => 'admin@aicha.sn',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'phone' => '+221771234567',
            'address' => 'Parcelles Assainies Unité 16',
            'city' => 'Dakar'
        ]);

        // Créer catégories
        $categories = [
            ['name' => 'Vêtements Femmes'],
            ['name' => 'Vêtements Hommes'],
            ['name' => 'Enfants'],
            ['name' => 'Chaussures'],
            ['name' => 'Sacs'],
            ['name' => 'Accessoires']
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => \Str::slug($category['name'])
            ]);
        }
    }}
