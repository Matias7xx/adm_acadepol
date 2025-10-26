<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requerimento extends Model
{
  use HasFactory;

  protected $table = 'requerimentos';

  protected $fillable = [
    'user_id',
    'tipo',
    'nome',
    'matricula',
    'email',
    'telefone',
    'cpf',
    'cargo',
    'orgao',
    'conteudo',
    'dados_adicionais',
    'documento',
    'documento_resposta',
    'status',
    'resposta',
    'data_resposta',
    'motivo_indeferimento',
  ];

  protected $casts = [
    'dados_adicionais' => 'array',
    'data_resposta' => 'datetime',
  ];

  /**
   * Relacionamento com o usuário que fez o requerimento
   */
  public function usuario()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * Escopo para filtrar requerimentos pendentes
   */
  public function scopePendente($query)
  {
    return $query->where('status', 'pendente');
  }

  /**
   * Escopo para filtrar requerimentos deferidos
   */
  public function scopeDeferido($query)
  {
    return $query->where('status', 'deferido');
  }

  /**
   * Escopo para filtrar requerimentos indeferidos
   */
  public function scopeIndeferido($query)
  {
    return $query->where('status', 'indeferido');
  }

  /**
   * Obter URL do documento anexado
   */
  public function getDocumentoUrlAttribute()
  {
    if (!$this->documento) {
      return null;
    }

    return asset('storage/' . $this->documento);
  }

  public function getDocumentoRespostaUrlAttribute()
  {
    if (!$this->documento_resposta) {
      return null;
    }

    return asset('storage/' . $this->documento_resposta);
  }
}
