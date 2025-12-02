<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom de la zone (Dakar, Thiès, etc.)
            $table->text('description')->nullable();
            $table->decimal('shipping_cost', 10, 2)->default(0); // Coût de livraison
            $table->integer('estimated_days')->default(2); // Délai estimé en jours
            $table->json('cities')->nullable(); // Liste des villes couvertes
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_zones');
    }
};
