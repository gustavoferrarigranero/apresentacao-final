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
        // Guarda o catálogo de produtos, incluindo preço, stock e categoria.
        Schema::create('produts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->decimal('price', 10, 2);
            // Quantidade disponível em stock; as operações de encomenda atualizam este valor.
            $table->integer('quantity');
            // Cada produto pertence a uma categoria existente.
            $table->foreignId('category_id')->constrained('categories');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produts');
    }
};
