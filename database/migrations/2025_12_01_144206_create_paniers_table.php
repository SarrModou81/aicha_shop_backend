<?php
// database/migrations/2024_01_01_000006_create_paniers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaniersTable extends Migration
{
    public function up()
    {
        // database/migrations/xxxx_xx_xx_create_paniers_table.php
        Schema::create('paniers', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('produit_id');
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paniers');
    }
}
