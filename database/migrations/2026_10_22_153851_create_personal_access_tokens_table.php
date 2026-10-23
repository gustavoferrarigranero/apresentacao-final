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
        // Armazena tokens Sanctum para autenticar clientes da API sem sessão web.
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            // Associação polimórfica permite emitir tokens para diferentes tipos de modelo.
            $table->morphs('tokenable');
            $table->text('name');
            // Guarda o hash do token; o valor legível só é apresentado ao criar o token.
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
