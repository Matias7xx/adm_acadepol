<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
  use HasFactory;

  protected $fillable = [
    'curso_id',
    'user_id',
    'dados_adicionais',
    'status',
    'motivo_rejeicao',
  ];

  protected $casts = [
    'dados_adicionais' => 'array', // Para salvar dados do formulário em JSON
  ];

  public function curso()
  {
    return $this->belongsTo(Curso::class);
  }

  public function aluno()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function certificado()
  {
    return $this->hasOne(Certificado::class);
  }

  /**
   * Verificar se a matrícula tem certificado
   */
  public function temCertificado()
  {
    return $this->certificado()->exists();
  }
}
