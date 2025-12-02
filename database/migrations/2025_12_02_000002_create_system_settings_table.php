<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Clé du paramètre
            $table->text('value')->nullable(); // Valeur du paramètre
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general'); // general, payment, notification, delivery, etc.
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
};
