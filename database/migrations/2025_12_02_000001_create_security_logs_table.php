<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('action'); // login, logout, failed_login, password_change, etc.
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->enum('level', ['info', 'warning', 'danger'])->default('info');
            $table->json('metadata')->nullable(); // Données supplémentaires
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('security_logs');
    }
};
