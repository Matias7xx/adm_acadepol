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
        Schema::create('alojamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nome');
            $table->string('cargo');
            $table->string('matricula');
            $table->string('orgao');
            $table->string('cpf');
            $table->text('motivo');
            $table->string('condicao');
            $table->string('email');
            $table->string('telefone');
            $table->json('endereco')->nullable();
            $table->date('data_inicial');
            $table->date('data_final');
            $table->string('status')->default('pendente'); // pendente, aprovada, rejeitada
            $table->text('motivo_rejeicao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alojamentos');
    }
};