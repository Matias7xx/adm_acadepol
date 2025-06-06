<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Curso>
 */
class CursoFactory extends Factory
{
    protected $model = Curso::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dataInicio = fake()->dateTimeBetween('now', '+3 months');
        $dataFim = fake()->dateTimeBetween($dataInicio, $dataInicio->format('Y-m-d') . ' +2 months');
        
        $modalidades = ['presencial', 'online', 'hibrido'];
        $status = ['aberto', 'fechado', 'suspenso', 'concluido'];
        $localizacoes = [
            'Auditório Principal - ACADEPOL',
            'Sala de Treinamento 1 - ACADEPOL', 
            'Laboratório de Informática - ACADEPOL',
            'Centro de Simulação - ACADEPOL',
            'Online - Plataforma EAD',
            'Campo de Treinamento - ACADEPOL'
        ];

        $tiposCurso = [
            'Curso de Formação Policial',
            'Especialização em Investigação Criminal',
            'Treinamento em Armamento e Tiro',
            'Curso de Direitos Humanos',
            'Capacitação em Tecnologia Policial',
            'Curso de Primeiros Socorros',
            'Treinamento em Defesa Pessoal',
            'Curso de Legislação Penal',
            'Especialização em Trânsito',
            'Curso de Inteligência Policial'
        ];

        $preRequisitos = [
            ['Ensino médio completo'],
            ['Idade mínima de 18 anos', 'Ensino médio completo'],
            ['Curso básico de informática', 'Ensino médio completo'],
            ['Experiência prévia em segurança pública'],
            ['Aprovação em exame físico', 'Curso de formação básica'],
            []
        ];

        $enxoval = [
            ['Uniforme completo', 'Calçado adequado', 'Material de escrita'],
            ['Equipamento de proteção individual', 'Uniforme operacional'],
            ['Roupas esportivas', 'Tênis para atividade física'],
            ['Material de estudo', 'Notebook/tablet'],
            ['Equipamentos específicos do curso'],
            []
        ];

        return [
            'nome' => fake()->randomElement($tiposCurso) . ' - ' . fake()->year(),
            'descricao' => fake()->paragraphs(3, true),
            'imagem' => null, // Será definido em states específicos se necessário
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'carga_horaria' => fake()->numberBetween(40, 200),
            'pre_requisitos' => fake()->randomElement($preRequisitos),
            'enxoval' => fake()->randomElement($enxoval),
            'localizacao' => fake()->randomElement($localizacoes),
            'capacidade_maxima' => fake()->numberBetween(15, 50),
            'modalidade' => fake()->randomElement($modalidades),
            'certificacao' => fake()->boolean(80), // 80% chance de ter certificação
            'certificacao_modelo' => fake()->boolean(80) ? fake()->sentence() : null,
            'status' => fake()->randomElement($status),
        ];
    }

    /**
     * Curso com status "aberto" para inscrições
     */
    public function aberto(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aberto',
            'data_inicio' => fake()->dateTimeBetween('+1 week', '+2 months'),
        ]);
    }

    /**
     * Curso já finalizado
     */
    public function finalizado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'concluido',
            'data_inicio' => fake()->dateTimeBetween('-6 months', '-2 months'),
            'data_fim' => fake()->dateTimeBetween('-2 months', '-1 week'),
        ]);
    }

    /**
     * Curso presencial
     */
    public function presencial(): static
    {
        return $this->state(fn (array $attributes) => [
            'modalidade' => 'presencial',
            'localizacao' => 'Auditório Principal - ACADEPOL',
        ]);
    }

    /**
     * Curso online
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'modalidade' => 'online',
            'localizacao' => 'Online - Plataforma EAD',
        ]);
    }

    /**
     * Curso com alta capacidade
     */
    public function altaCapacidade(): static
    {
        return $this->state(fn (array $attributes) => [
            'capacidade_maxima' => fake()->numberBetween(100, 200),
        ]);
    }

    /**
     * Curso com certificação
     */
    public function comCertificacao(): static
    {
        return $this->state(fn (array $attributes) => [
            'certificacao' => true,
            'certificacao_modelo' => 'Certificado de conclusão emitido pela ACADEPOL com carga horária de ' . $attributes['carga_horaria'] . ' horas.',
        ]);
    }

    /**
     * Curso sem certificação
     */
    public function semCertificacao(): static
    {
        return $this->state(fn (array $attributes) => [
            'certificacao' => false,
            'certificacao_modelo' => null,
        ]);
    }

    /**
     * Curso de curta duração
     */
    public function curtaDuracao(): static
    {
        return $this->state(function (array $attributes) {
            $dataInicio = fake()->dateTimeBetween('now', '+1 month');
            $dataFim = fake()->dateTimeBetween($dataInicio, $dataInicio->format('Y-m-d') . ' +1 week');
            
            return [
                'carga_horaria' => fake()->numberBetween(8, 40),
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
            ];
        });
    }

    /**
     * Curso de longa duração
     */
    public function longaDuracao(): static
    {
        return $this->state(function (array $attributes) {
            $dataInicio = fake()->dateTimeBetween('now', '+1 month');
            $dataFim = fake()->dateTimeBetween($dataInicio->format('Y-m-d') . ' +3 months', $dataInicio->format('Y-m-d') . ' +6 months');
            
            return [
                'carga_horaria' => fake()->numberBetween(200, 500),
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
            ];
        });
    }
}