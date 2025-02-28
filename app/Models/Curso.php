<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome', 'descricao', 'imagem', 'data_inicio', 'data_fim', 'carga_horaria',
        'pre_requisitos', 'enxoval', 'localizacao', 'capacidade_maxima', 'modalidade',
        'material_complementar', 'certificacao', 'certificacao_modelo', 'status'
    ];

    protected $casts = [
        'pre_requisitos' => 'array',
        'enxoval' => 'array',
        'material_complementar' => 'array',
        'certificacao' => 'boolean',
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    //O alunos não é uma coluna na tabela cursos, mas sim um relacionamento entre cursos e users (alunos) através da tabela matriculas.
    public function alunos()
    {
        return $this->belongsToMany(User::class, 'matriculas', 'curso_id', 'user_id')
            ->withTimestamps();
    } 

}