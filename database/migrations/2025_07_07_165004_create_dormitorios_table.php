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
        Schema::create('dormitorios', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 10)->unique(); // Número do dormitório (ex: D01, D02, etc.)
            $table->string('nome')->nullable(); // Nome descritivo opcional
            $table->integer('capacidade_maxima')->default(4); // Capacidade máxima (padrão 4)
            $table->integer('vagas_ocupadas')->default(0); // Vagas atualmente ocupadas
            $table->enum('status', ['ativo', 'inativo', 'manutencao', 'reservado'])->default('ativo'); // sem ->change()
            $table->text('observacoes')->nullable(); // Observações sobre o dormitório
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dormitorios');
    }
};