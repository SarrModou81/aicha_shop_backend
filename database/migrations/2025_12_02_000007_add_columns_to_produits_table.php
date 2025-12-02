<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->integer('views')->default(0)->after('images'); // Nombre de vues
            $table->json('attributes')->nullable()->after('views'); // Attributs (taille, couleur, etc.)
        });
    }

    public function down()
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn(['views', 'attributes']);
        });
    }
};
