<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('matriculas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
        $table->string('status')->default('pendente'); // Pendente, Aprovada, Rejeitada
        $table->json('dados_adicionais')->nullable(); //Armazena informações do formulário de inscrição
        $table->text('motivo_rejeicao')->nullable()->after('status');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
