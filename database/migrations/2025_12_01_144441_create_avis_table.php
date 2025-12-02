<?php
// database/migrations/2024_01_01_000011_create_avis_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvisTable extends Migration
{
    public function up()
    {
        // database/migrations/xxxx_xx_xx_create_avis_table.php
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('produit_id');
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('avis');
    }
}
