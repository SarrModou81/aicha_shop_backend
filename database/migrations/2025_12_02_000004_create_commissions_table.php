<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendeur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('commande_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('order_amount', 10, 2); // Montant de la commande
            $table->decimal('commission_rate', 5, 2); // Taux de commission en %
            $table->decimal('commission_amount', 10, 2); // Montant de la commission
            $table->decimal('vendeur_amount', 10, 2); // Montant pour le vendeur
            $table->enum('status', ['en_attente', 'paye', 'annule'])->default('en_attente');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['vendeur_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('commissions');
    }
};
