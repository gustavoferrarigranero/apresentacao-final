<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Acrescenta à conta os dados de contacto e a associação opcional a um restaurante.
        Schema::table('users', function (Blueprint $table) {
            // Clientes podem não estar associados a um restaurante; contas de equipa podem estar.
            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants');
            $table->string('address')->nullable();
            $table->string('contact')->nullable();
            $table->string('nif')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
