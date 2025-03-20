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
        Schema::create('contatos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email');
            $table->string('telefone')->nullable();
            $table->string('assunto');
            $table->text('mensagem');
            $table->string('status')->default('pendente'); // pendente, respondido, arquivado
            $table->text('resposta')->nullable();
            $table->foreignId('respondido_por')->nullable()->constrained('users');
            $table->timestamp('data_resposta')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users'); // Se estiver logado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contatos');
    }
};