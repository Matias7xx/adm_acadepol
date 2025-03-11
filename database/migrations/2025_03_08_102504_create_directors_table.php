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
        Schema::create('directors', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('imagem')->nullable();
            $table->date('data_inicio');
            $table->date('data_fim')->nullable(); // Permite NULL para diretores atuais
            $table->text('historico')->nullable();
            $table->json('realizacoes')->nullable(); // Armazena uma lista de realizações
            $table->boolean('atual')->default(false); // Indica se é o diretor atual
            $table->integer('ordem')->default(0); // Para controlar a ordem de exibição
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directors');
    }
};