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
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos nullable para certificados livres (externos)
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas')->onDelete('cascade')
                  ->comment('NULL para certificados adicionados manualmente pelo admin');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->onDelete('cascade')
                  ->comment('NULL para cursos externos não cadastrados no sistema');
            
            // Dados do certificado
            $table->string('numero_certificado')->unique()->comment('Ex: ACADEPOL-2025-0001');
            $table->string('arquivo_path')->comment('Caminho do arquivo PDF');
            $table->datetime('data_emissao');
            $table->datetime('data_conclusao_curso');
            $table->integer('carga_horaria');
            
            $table->string('nome_aluno');
            $table->string('cpf_aluno');
            $table->string('nome_curso');
            
            // Controle e origem
            $table->boolean('ativo')->default(true)->comment('Para permitir revalidação se necessário');
            $table->enum('tipo_origem', ['matricula', 'curso_sistema', 'curso_externo'])
                  ->default('matricula')
                  ->comment('matricula=fluxo normal, curso_sistema=admin add curso existente, curso_externo=admin add curso livre');
            
            $table->timestamps();
            
            // Índices para consultas rápidas
            $table->index(['user_id', 'curso_id'], 'idx_certificados_user_curso');
            $table->index(['user_id', 'tipo_origem'], 'idx_certificados_user_tipo');
            $table->index(['numero_certificado'], 'idx_certificados_numero');
            $table->index(['data_emissao'], 'idx_certificados_data_emissao');
            $table->index(['tipo_origem', 'ativo'], 'idx_certificados_tipo_ativo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};