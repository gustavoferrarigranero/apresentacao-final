<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Define o perfil de autorização de cada conta; contas existentes começam como clientes.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->index();
        });

        // Permite registar várias unidades do mesmo produto numa linha da encomenda.
        Schema::table('order_itens', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1);
        });
    }

    public function down(): void
    {
        // Reverte primeiro a quantidade das linhas e depois o perfil das contas.
        Schema::table('order_itens', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
