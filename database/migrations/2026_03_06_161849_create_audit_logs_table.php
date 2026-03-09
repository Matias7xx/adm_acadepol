<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Identificação da ação
            $table->string('modulo', 100);           // auth, usuario, noticia, operacao, etc.
            $table->string('acao', 100);              // login, criar, editar, excluir, etc.
            $table->text('descricao')->nullable();     // Descrição da ação

            // Model afetado
            $table->string('model_type', 255)->nullable(); // App\Models\Noticia
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model_label', 255)->nullable(); // ex: título da notícia, nome do usuário

            // Dados antes e depois (para edições)
            $table->jsonb('dados_anteriores')->nullable();
            $table->jsonb('dados_novos')->nullable();

            // Status da ação
            $table->string('status', 20)->default('success'); // success, error, warning

            // Contexto do usuário
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 255)->nullable();
            $table->string('user_matricula', 50)->nullable();
            $table->string('user_email', 255)->nullable();

            // Contexto da requisição
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();

            $table->timestamps();

            // Índices para performance
            $table->index(['modulo', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
            $table->index('status');
            $table->index('acao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
