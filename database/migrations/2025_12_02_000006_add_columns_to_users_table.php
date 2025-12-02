<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('shop_info')->nullable()->after('is_active'); // Informations boutique pour vendeurs
            $table->boolean('is_validated')->default(false)->after('is_active'); // Validation du compte vendeur
            $table->timestamp('validated_at')->nullable()->after('is_validated');
            $table->foreignId('validated_by')->nullable()->constrained('users')->after('validated_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['shop_info', 'is_validated', 'validated_at', 'validated_by']);
        });
    }
};
