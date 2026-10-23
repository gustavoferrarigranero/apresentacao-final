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
        // Guarda as linhas de produtos que compõem uma encomenda.
        Schema::create('order_itens', function (Blueprint $table) {
            $table->id();
            // Mantém cada linha ligada a um produto existente.
            $table->foreignId('product_id')->constrained('produts');
            // Regista o valor unitário do produto no contexto da encomenda.
            $table->decimal('value', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_itens');
    }
};
