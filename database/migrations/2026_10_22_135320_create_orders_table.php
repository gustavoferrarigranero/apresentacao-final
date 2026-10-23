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
        // Regista encomendas, o estado operacional e os dados necessários à entrega.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Controla o percurso da encomenda desde pendente até concluída ou cancelada.
            $table->enum('payment_status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->decimal('payment_value', 8, 2)->default(0);
            // Identifica o cliente que fez a encomenda e o utilizador associado ao registo.
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('user_id')->constrained('users');
            $table->string('address');
            $table->date('delivery_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
