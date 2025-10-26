<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ocupacao extends Model
{
  use HasFactory;

  protected $table = 'ocupacoes';

  protected $fillable = [
    'dormitorio_id',
    'reservavel_type',
    'reservavel_id',
    'numero_vaga',
    'checkin_at',
    'checkout_at',
    'checkin_por',
    'checkout_por',
    'status',
    'observacoes',
  ];

  protected $casts = [
    'checkin_at' => 'datetime',
    'checkout_at' => 'datetime',
    'numero_vaga' => 'integer',
  ];

  /**
   * Relacionamento com o dormitório
   */
  public function dormitorio()
  {
    return $this->belongsTo(Dormitorio::class);
  }

  /**
   * Relacionamento polimórfico com reserva (pode ser Alojamento ou Visitante)
   */
  public function reservavel()
  {
    return $this->morphTo();
  }

  /**
   * Usuário que fez o check-in
   */
  public function checkinPor()
  {
    return $this->belongsTo(User::class, 'checkin_por');
  }

  /**
   * Usuário que fez o check-out
   */
  public function checkoutPor()
  {
    return $this->belongsTo(User::class, 'checkout_por');
  }

  /**
   * Scope para ocupações ativas
   */
  public function scopeAtivas($query)
  {
    return $query->where('status', 'ocupado');
  }

  /**
   * Scope para ocupações liberadas
   */
  public function scopeLiberadas($query)
  {
    return $query->where('status', 'liberado');
  }

  /**
   * Verifica se a ocupação está ativa
   */
  public function isAtiva()
  {
    return $this->status === 'ocupado' && is_null($this->checkout_at);
  }

  /**
   * Realiza o check-in
   */
  public function realizarCheckin($userId)
  {
    $this->checkin_at = now();
    $this->checkin_por = $userId;
    $this->status = 'ocupado';
    $this->save();

    // Atualizar contador do dormitório
    $this->dormitorio->atualizarVagasOcupadas();
  }

  /**
   * Realiza o check-out
   */
  public function realizarCheckout($userId)
  {
    $this->checkout_at = now();
    $this->checkout_por = $userId;
    $this->status = 'liberado';
    $this->save();

    // Atualizar contador do dormitório
    $this->dormitorio->atualizarVagasOcupadas();
  }

  /**
   * Retorna a duração da estadia
   */
  public function getDuracaoEstadia()
  {
    if (!$this->checkin_at) {
      return null;
    }

    $fim = $this->checkout_at ?: now();
    return $this->checkin_at->diffForHumans($fim, true);
  }

  /**
   * Retorna o nome do hóspede
   */
  public function getNomeHospedeAttribute()
  {
    return $this->reservavel ? $this->reservavel->nome : 'N/A';
  }

  /**
   * Retorna o tipo de reserva formatado
   */
  public function getTipoReservaAttribute()
  {
    return $this->reservavel_type === 'App\Models\Alojamento'
      ? 'Usuário'
      : 'Visitante';
  }

  /**
   * Retorna informações resumidas da reserva
   */
  public function getResumoReservaAttribute()
  {
    if (!$this->reservavel) {
      return 'Reserva não encontrada';
    }

    $nome = $this->reservavel->nome;
    $tipo = $this->tipo_reserva;
    $dataInicio = $this->reservavel->data_inicial
      ? $this->reservavel->data_inicial->format('d/m/Y')
      : 'N/A';
    $dataFim = $this->reservavel->data_final
      ? $this->reservavel->data_final->format('d/m/Y')
      : 'N/A';

    return "{$nome} ({$tipo}) - {$dataInicio} a {$dataFim}";
  }
}
