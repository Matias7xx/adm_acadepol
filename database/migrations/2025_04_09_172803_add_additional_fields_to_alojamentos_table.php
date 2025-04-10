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
        Schema::table('alojamentos', function (Blueprint $table) {
            $table->date('data_nascimento')->nullable()->after('cpf');
            $table->string('rg')->nullable()->after('data_nascimento');
            $table->string('orgao_expedidor')->nullable()->after('rg');
            $table->enum('sexo', ['masculino', 'feminino'])->nullable()->after('orgao_expedidor');
            $table->string('uf')->nullable()->after('sexo');
            $table->string('documento_comprobatorio')->nullable()->after('motivo_rejeicao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alojamentos', function (Blueprint $table) {
            $table->dropColumn([
                'data_nascimento',
                'rg',
                'orgao_expedidor',
                'sexo',
                'uf',
                'documento_comprobatorio'
            ]);
        });
    }
};