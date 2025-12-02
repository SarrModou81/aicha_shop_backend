<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // sales, products, orders, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->json('filters')->nullable(); // Filtres appliqués
            $table->string('file_path')->nullable(); // Chemin du fichier généré
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->enum('status', ['en_cours', 'termine', 'erreur'])->default('en_cours');
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
};
