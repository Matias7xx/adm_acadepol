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
        Schema::create('visitantes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cpf', 20);
            $table->string('rg', 20);
            $table->string('orgao_expedidor_rg', 20);
            $table->date('data_nascimento');
            $table->enum('sexo', ['masculino', 'feminino']);
            $table->string('telefone', 20);
            $table->string('email');
            $table->json('endereco')->nullable();
            $table->string('orgao_trabalho');
            $table->string('cargo');
            $table->string('matricula_funcional')->nullable();
            $table->string('tipo_orgao');
            $table->string('documento_identidade')->nullable();
            $table->string('documento_funcional')->nullable();
            $table->string('documento_comprobatorio')->nullable();
            $table->date('data_inicial');
            $table->date('data_final');
            $table->text('motivo');
            $table->string('condicao');
            $table->string('status')->default('pendente'); // pendente, aprovada, rejeitada
            $table->text('motivo_rejeicao')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('cpf');
            $table->index('status');
            $table->index(['data_inicial', 'data_final']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitantes');
    }
};