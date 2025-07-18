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
        Schema::create('ocupacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dormitorio_id')->constrained('dormitorios')->onDelete('cascade');
            $table->morphs('reservavel'); // Pode ser alojamento ou visitante
            $table->integer('numero_vaga'); // Número da vaga dentro do dormitório (1, 2, 3, 4)
            $table->timestamp('checkin_at')->nullable(); // Data e hora do check-in
            $table->timestamp('checkout_at')->nullable(); // Data e hora do check-out
            $table->foreignId('checkin_por')->nullable()->constrained('users')->onDelete('set null'); // Admin que fez o check-in
            $table->foreignId('checkout_por')->nullable()->constrained('users')->onDelete('set null'); // Admin que fez o check-out
            $table->enum('status', ['ocupado', 'liberado'])->default('ocupado');
            $table->text('observacoes')->nullable(); // Observações sobre a ocupação
            $table->timestamps();
            
            // Índices para melhor performance
            $table->index(['dormitorio_id', 'numero_vaga']);
            $table->index('status');
            $table->index(['checkin_at', 'checkout_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocupacoes');
    }
};