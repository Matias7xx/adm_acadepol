<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('imagem')->nullable();
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->integer('carga_horaria');
            $table->json('pre_requisitos')->nullable(); // IDs dos cursos pré-requisitos
            $table->json('enxoval')->nullable(); // Itens necessários para o curso
            $table->string('localizacao');
            $table->integer('capacidade_maxima')->default(0);
            $table->enum('modalidade', ['presencial', 'online', 'híbrido']);
            $table->json('material_complementar')->nullable();
            $table->boolean('certificacao')->default(false);
            $table->text('certificacao_modelo')->nullable();
            $table->enum('status', ['aberto', 'em andamento', 'concluído', 'cancelado'])->default('aberto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
