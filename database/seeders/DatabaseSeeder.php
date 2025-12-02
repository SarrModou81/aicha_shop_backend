<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Démarrage du seeding de la base de données...');
        $this->command->newLine();

        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            MarqueSeeder::class,
            DeliveryZoneSeeder::class,
            SystemSettingSeeder::class,
            ProduitSeeder::class,
            // CommandeSeeder::class, // Décommenter si vous voulez des commandes de test
        ]);

        $this->command->newLine();
        $this->command->info('🎉 Base de données peuplée avec succès !');
        $this->command->newLine();
        $this->command->info('📧 Identifiants Admin:');
        $this->command->info('   Email: admin@aichashop.sn');
        $this->command->info('   Password: admin123');
        $this->command->newLine();
        $this->command->info('📧 Identifiants Vendeurs:');
        $this->command->info('   Email: fatou@aichashop.sn, mamadou@aichashop.sn, etc.');
        $this->command->info('   Password: vendeur123');
        $this->command->newLine();
        $this->command->info('📧 Identifiants Clients:');
        $this->command->info('   Email: khady@example.com, moussa@example.com, etc.');
        $this->command->info('   Password: client123');
    }
}
