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
        Schema::create('noticias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao_curta');
            $table->longText('conteudo')->nullable()->default('');
            $table->string('imagem')->nullable();
            $table->json('carousel_images')->nullable();
            $table->boolean('destaque')->default(false);
            $table->date('data_publicacao');
            $table->enum('status', ['rascunho', 'publicado', 'arquivado'])->default('rascunho');
            $table->integer('visualizacoes')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('noticias');
    }
};
