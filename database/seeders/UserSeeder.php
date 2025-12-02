<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Création des utilisateurs...');

        // ========== ADMIN ==========
        User::create([
            'name' => 'Administrateur',
            'email' => 'admin@aichashop.sn',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'phone' => '+221 77 123 45 67',
            'address' => 'Plateau, Dakar',
            'city' => 'Dakar',
            'country' => 'Sénégal',
            'is_active' => true,
            'email_verified_at' => now()
        ]);

        // ========== VENDEURS ==========
        $vendeurs = [
            [
                'name' => 'Fatou Diop',
                'email' => 'fatou@aichashop.sn',
                'phone' => '+221 77 234 56 78',
                'address' => 'Parcelles Assainies',
                'city' => 'Dakar',
                'shop_info' => [
                    'shop_name' => 'Boutique Fatou',
                    'shop_description' => 'Spécialiste en mode féminine africaine',
                ]
            ],
            [
                'name' => 'Mamadou Sow',
                'email' => 'mamadou@aichashop.sn',
                'phone' => '+221 77 345 67 89',
                'address' => 'Médina',
                'city' => 'Dakar',
                'shop_info' => [
                    'shop_name' => 'Mamadou Fashion',
                    'shop_description' => 'Vêtements et accessoires pour hommes',
                ]
            ],
            [
                'name' => 'Aïssatou Ba',
                'email' => 'aissatou@aichashop.sn',
                'phone' => '+221 77 456 78 90',
                'address' => 'Liberté 6',
                'city' => 'Dakar',
                'shop_info' => [
                    'shop_name' => 'Aïssatou Style',
                    'shop_description' => 'Chaussures et sacs de luxe',
                ]
            ],
            [
                'name' => 'Ibrahima Ndiaye',
                'email' => 'ibrahima@aichashop.sn',
                'phone' => '+221 77 567 89 01',
                'address' => 'HLM Grand Yoff',
                'city' => 'Dakar',
                'shop_info' => [
                    'shop_name' => 'Ibrahima Collections',
                    'shop_description' => 'Mode urbaine et streetwear',
                ]
            ]
        ];

        foreach ($vendeurs as $vendeur) {
            User::create([
                'name' => $vendeur['name'],
                'email' => $vendeur['email'],
                'password' => Hash::make('vendeur123'),
                'role' => 'vendeur',
                'phone' => $vendeur['phone'],
                'address' => $vendeur['address'],
                'city' => $vendeur['city'],
                'country' => 'Sénégal',
                'is_active' => true,
                'is_validated' => true,
                'validated_at' => now(),
                'validated_by' => 1, // Admin
                'shop_info' => $vendeur['shop_info']
                'email_verified_at' => now()
            ]);
        }

        // ========== CLIENTS ==========
        $clients = [
            ['name' => 'Khady Thiam', 'email' => 'khady@example.com', 'city' => 'Dakar'],
            ['name' => 'Moussa Diallo', 'email' => 'moussa@example.com', 'city' => 'Thiès'],
            ['name' => 'Astou Sarr', 'email' => 'astou@example.com', 'city' => 'Dakar'],
            ['name' => 'Ousmane Fall', 'email' => 'ousmane@example.com', 'city' => 'Saint-Louis'],
            ['name' => 'Mariama Cissé', 'email' => 'mariama@example.com', 'city' => 'Dakar'],
            ['name' => 'Cheikh Gueye', 'email' => 'cheikh@example.com', 'city' => 'Mbour'],
            ['name' => 'Aminata Seck', 'email' => 'aminata@example.com', 'city' => 'Kaolack'],
            ['name' => 'Babacar Diouf', 'email' => 'babacar@example.com', 'city' => 'Dakar'],
        ];

        foreach ($clients as $index => $client) {
            User::create([
                'name' => $client['name'],
                'email' => $client['email'],
                'password' => Hash::make('client123'),
                'role' => 'client',
                'phone' => '+221 77 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'address' => 'Adresse ' . ($index + 1),
                'city' => $client['city'],
                'country' => 'Sénégal',
                'is_active' => true,
                'email_verified_at' => now()
            ]);
        }

        $this->command->info('✓ Utilisateurs créés: 1 admin, 4 vendeurs, 8 clients');
    }
}
