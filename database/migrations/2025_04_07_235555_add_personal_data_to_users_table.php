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
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf')->nullable()->after('matricula');
            $table->string('cargo')->nullable()->after('cpf');
            $table->string('orgao')->nullable()->after('cargo');
            $table->string('telefone')->nullable()->after('orgao');
            $table->date('data_nascimento')->nullable()->after('telefone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'cargo', 'orgao', 'telefone', 'data_nascimento']);
        });
    }
};