<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Dormitorio;

class DormitorioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar tabela antes de popular
        Dormitorio::truncate();
        
        // Criar 18 dormitórios com configurações específicas
        for ($i = 1; $i <= 18; $i++) {
            $numero = 'D' . str_pad($i, 2, '0', STR_PAD_LEFT);
            
            // Definir capacidade e status específicos
            if ($i == 1 || $i == 2) {
                // Dormitórios 1 e 2: 8 vagas cada
                $capacidade = 8;
                $status = 'ativo';
                $observacoes = 'Dormitório ampliado com 8 vagas individuais';
            } elseif ($i == 13) {
                // Dormitório 13: RESERVADO para plantão
                $capacidade = 4;
                $status = 'reservado'; // Status especial para plantão
                $observacoes = 'RESERVADO EXCLUSIVAMENTE PARA PLANTÃO DA ACADEPOL - Não disponível para check-ins externos';
            } else {
                // Demais dormitórios: 4 vagas padrão
                $capacidade = 4;
                $status = 'ativo';
                $observacoes = 'Dormitório padrão com 4 vagas individuais';
            }
            
            Dormitorio::create([
                'numero' => $numero,
                'nome' => $i == 13 ? 'Dormitório 13 - PLANTÃO ACADEPOL' : 'Dormitório ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'capacidade_maxima' => $capacidade,
                'vagas_ocupadas' => 0,
                'status' => $status,
                'observacoes' => $observacoes
            ]);
        }
        
        echo "✅ Criados 18 dormitórios:\n";
        echo "   - D01 e D02: 8 vagas cada (ativo)\n";
        echo "   - D13: 4 vagas (RESERVADO para plantão)\n";
        echo "   - Demais: 4 vagas cada (ativo)\n";
    }
}