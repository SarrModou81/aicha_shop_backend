<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Création des paramètres système...');

        $settings = [
            // Général
            ['key' => 'site_name', 'value' => 'AICHA SHOP', 'type' => 'string', 'group' => 'general', 'description' => 'Nom du site'],
            ['key' => 'site_description', 'value' => 'Plateforme e-commerce au Sénégal', 'type' => 'string', 'group' => 'general', 'description' => 'Description du site'],
            ['key' => 'contact_email', 'value' => 'contact@aichashop.sn', 'type' => 'string', 'group' => 'general', 'description' => 'Email de contact'],
            ['key' => 'contact_phone', 'value' => '+221 33 123 45 67', 'type' => 'string', 'group' => 'general', 'description' => 'Téléphone de contact'],

            // Paiement
            ['key' => 'commission_rate', 'value' => '10', 'type' => 'decimal', 'group' => 'payment', 'description' => 'Taux de commission en %'],
            ['key' => 'tax_rate', 'value' => '18', 'type' => 'decimal', 'group' => 'payment', 'description' => 'Taux de TVA en %'],
            ['key' => 'default_shipping_cost', 'value' => '2000', 'type' => 'decimal', 'group' => 'payment', 'description' => 'Frais de livraison par défaut'],
            ['key' => 'currency', 'value' => 'XOF', 'type' => 'string', 'group' => 'payment', 'description' => 'Devise'],
            ['key' => 'currency_symbol', 'value' => 'FCFA', 'type' => 'string', 'group' => 'payment', 'description' => 'Symbole de devise'],
            ['key' => 'enable_wave', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Activer Wave'],
            ['key' => 'enable_orange_money', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Activer Orange Money'],
            ['key' => 'enable_free_money', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Activer Free Money'],
            ['key' => 'enable_card_payment', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Activer paiement par carte'],
            ['key' => 'enable_cash_payment', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Activer paiement en espèces'],

            // Stock
            ['key' => 'low_stock_threshold', 'value' => '10', 'type' => 'integer', 'group' => 'stock', 'description' => 'Seuil de stock faible'],
            ['key' => 'enable_stock_alerts', 'value' => 'true', 'type' => 'boolean', 'group' => 'stock', 'description' => 'Activer alertes stock'],
            ['key' => 'auto_hide_out_of_stock', 'value' => 'false', 'type' => 'boolean', 'group' => 'stock', 'description' => 'Masquer automatiquement produits en rupture'],

            // Notifications
            ['key' => 'enable_email_notifications', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Activer notifications email'],
            ['key' => 'enable_sms_notifications', 'value' => 'false', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Activer notifications SMS'],
            ['key' => 'enable_push_notifications', 'value' => 'false', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Activer notifications push'],
            ['key' => 'notify_new_order', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Notifier nouvelle commande'],
            ['key' => 'notify_order_status', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Notifier changement statut commande'],
            ['key' => 'notify_low_stock', 'value' => 'true', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Notifier stock faible'],

            // Sécurité
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'group' => 'security', 'description' => 'Nombre max tentatives connexion'],
            ['key' => 'lockout_duration', 'value' => '15', 'type' => 'integer', 'group' => 'security', 'description' => 'Durée blocage en minutes'],
            ['key' => 'session_lifetime', 'value' => '120', 'type' => 'integer', 'group' => 'security', 'description' => 'Durée session en minutes'],
            ['key' => 'require_email_verification', 'value' => 'false', 'type' => 'boolean', 'group' => 'security', 'description' => 'Vérification email obligatoire'],

            // Upload
            ['key' => 'max_image_size', 'value' => '2048', 'type' => 'integer', 'group' => 'upload', 'description' => 'Taille max image en KB'],
            ['key' => 'max_file_size', 'value' => '5120', 'type' => 'integer', 'group' => 'upload', 'description' => 'Taille max fichier en KB'],
            ['key' => 'allowed_image_types', 'value' => 'jpg,jpeg,png,gif,webp', 'type' => 'string', 'group' => 'upload', 'description' => 'Types images autorisés'],

            // Commandes
            ['key' => 'auto_approve_orders', 'value' => 'false', 'type' => 'boolean', 'group' => 'orders', 'description' => 'Approuver commandes automatiquement'],
            ['key' => 'cancel_unpaid_after', 'value' => '24', 'type' => 'integer', 'group' => 'orders', 'description' => 'Annuler commandes non payées après (heures)'],
            ['key' => 'min_order_amount', 'value' => '1000', 'type' => 'decimal', 'group' => 'orders', 'description' => 'Montant minimum commande'],

            // Produits
            ['key' => 'auto_approve_products', 'value' => 'false', 'type' => 'boolean', 'group' => 'products', 'description' => 'Approuver produits automatiquement'],
            ['key' => 'require_product_approval', 'value' => 'true', 'type' => 'boolean', 'group' => 'products', 'description' => 'Approbation admin requise'],
            ['key' => 'products_per_page', 'value' => '12', 'type' => 'integer', 'group' => 'products', 'description' => 'Produits par page'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
        }

        $this->command->info('✓ ' . count($settings) . ' paramètres système créés');
    }
}
