<?php
// database/migrations/2024_01_01_000010_create_livraisons_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLivraisonsTable extends Migration
{
    public function up()
    {
        // database/migrations/xxxx_xx_xx_create_livraisons_table.php
        Schema::create('livraisons', function (Blueprint $table) {
            $table->id();
            $table->integer('commande_id');
            $table->string('address');
            $table->string('city');
            $table->string('phone');
            $table->string('tracking_number')->nullable();
            $table->enum('status', ['en_preparation', 'expediee', 'en_livraison', 'livree'])->default('en_preparation');
            $table->date('estimated_delivery')->nullable();
            $table->date('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('livraisons');
    }
}
