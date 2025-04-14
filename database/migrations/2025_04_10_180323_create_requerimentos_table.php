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
        Schema::create('requerimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('tipo');
            $table->string('nome');
            $table->string('matricula');
            $table->string('email');
            $table->string('telefone');
            $table->string('cpf')->nullable();
            $table->string('cargo')->nullable();
            $table->string('orgao')->nullable();
            $table->text('conteudo');
            $table->json('dados_adicionais')->nullable();
            $table->string('status')->default('pendente');
            $table->text('resposta')->nullable();
            $table->text('motivo_indeferimento')->nullable();
            $table->timestamp('data_resposta')->nullable();
            $table->string('documento')->nullable();
            $table->string('documento_resposta')->nullable();
            $table->timestamps();
        });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('requerimentos');
        }
    };