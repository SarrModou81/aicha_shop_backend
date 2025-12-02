<?php
// database/migrations/2024_01_01_000008_create_detail_commandes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailCommandesTable extends Migration
{
    public function up()
    {
        // database/migrations/xxxx_xx_xx_create_detail_commandes_table.php
        Schema::create('detail_commandes', function (Blueprint $table) {
            $table->id();
            $table->integer('commande_id');
            $table->integer('produit_id');
            $table->string('produit_name');
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('detail_commandes');
    }
}
