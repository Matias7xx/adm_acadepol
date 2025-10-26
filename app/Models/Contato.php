<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contato extends Model
{
  use HasFactory;

  protected $table = 'contatos';

  protected $fillable = [
    'nome',
    'email',
    'telefone',
    'assunto',
    'mensagem',
    'status',
    'resposta',
    'respondido_por',
    'data_resposta',
    'ip',
    'user_agent',
    'user_id',
  ];

  protected $casts = [
    'data_resposta' => 'datetime',
  ];

  /**
   * Usuário que enviou a mensagem (se estiver logado)
   */
  public function usuario()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * Usuário que respondeu a mensagem
   */
  public function respondente()
  {
    return $this->belongsTo(User::class, 'respondido_por');
  }

  /**
   * Escopo para filtrar mensagens pendentes
   */
  public function scopePendente($query)
  {
    return $query->where('status', 'pendente');
  }

  /**
   * Escopo para filtrar mensagens respondidas
   */
  public function scopeRespondido($query)
  {
    return $query->where('status', 'respondido');
  }

  /**
   * Escopo para filtrar mensagens arquivadas
   */
  public function scopeArquivado($query)
  {
    return $query->where('status', 'arquivado');
  }
}
