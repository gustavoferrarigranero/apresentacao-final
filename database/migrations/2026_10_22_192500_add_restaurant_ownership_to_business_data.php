<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separa os catálogos por restaurante; nullable mantém compatibilidade com registos existentes.
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('restaurant_id')
                ->nullable()
                ->after('id')
                ->constrained()
                // Protege a integridade: não permite apagar restaurante ainda referenciado.
                ->restrictOnDelete();
        });

        // Associa cada produto ao restaurante responsável pelo respetivo catálogo.
        Schema::table('produts', function (Blueprint $table) {
            $table->foreignId('restaurant_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->restrictOnDelete();
        });

        // Identifica o restaurante que recebe e gere cada encomenda.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('restaurant_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Remove as associações na ordem inversa à criação.
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_id');
        });

        Schema::table('produts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_id');
        });
    }
};
