<?php
// database/migrations/2024_01_01_000009_create_paiements_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaiementsTable extends Migration
{
    public function up()
    {
        // database/migrations/xxxx_xx_xx_create_paiements_table.php
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->integer('commande_id');
            $table->string('payment_method')->nullable();
            $table->enum('status', ['en_attente', 'paye', 'echec', 'rembourse'])->default('en_attente');
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable();
            $table->text('payment_details')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paiements');
    }
}
