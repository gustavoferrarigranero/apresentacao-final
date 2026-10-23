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
        // Guarda credenciais Passkey/WebAuthn associadas às contas de utilizador.
        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            // Apaga as credenciais quando a conta associada é removida.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Cada credencial WebAuthn deve pertencer a uma única chave registada.
            $table->string('credential_id')->unique();
            $table->json('credential');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passkeys');
    }
};
