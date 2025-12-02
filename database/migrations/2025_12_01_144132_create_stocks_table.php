<?php
// database/migrations/2024_01_01_000005_create_stocks_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStocksTable extends Migration
{
    public function up()
    {
        // database/migrations/xxxx_xx_xx_create_stocks_table.php
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('produit_id');
            $table->integer('quantity');
            $table->integer('low_stock_threshold')->default(10);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stocks');
    }
}
