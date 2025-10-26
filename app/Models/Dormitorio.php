<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dormitorio extends Model
{
  use HasFactory;

  protected $fillable = [
    'numero',
    'nome',
    'capacidade_maxima',
    'vagas_ocupadas',
    'status',
    'observacoes',
  ];

  protected $casts = [
    'capacidade_maxima' => 'integer',
    'vagas_ocupadas' => 'integer',
  ];

  /**
   * Ocupações ativas deste dormitório
   */
  public function ocupacoes()
  {
    return $this->hasMany(Ocupacao::class);
  }

  /**
   * Ocupações ativas (não liberadas)
   */
  public function ocupacoesAtivas()
  {
    return $this->hasMany(Ocupacao::class)->where('status', 'ocupado');
  }

  /**
   * Verifica se o dormitório tem vagas disponíveis
   */
  public function temVagasDisponiveis()
  {
    return $this->vagas_ocupadas < $this->capacidade_maxima &&
      $this->status === 'ativo';
  }

  /**
   * Verifica se o dormitório está disponível para check-ins
   */
  public function disponivelParaCheckin()
  {
    return $this->status === 'ativo' && $this->temVagasDisponiveis();
  }

  /**
   * Verifica se é o dormitório reservado para plantão
   */
  public function isReservadoPlantao()
  {
    return $this->numero === 'D13' || $this->status === 'reservado';
  }

  /**
   * Retorna o número de vagas disponíveis
   */
  public function getVagasDisponiveisAttribute()
  {
    return $this->capacidade_maxima - $this->vagas_ocupadas;
  }

  /**
   * Retorna as vagas ocupadas com detalhes
   */
  public function getVagasOcupadasDetalhes()
  {
    return $this->ocupacoesAtivas()
      ->with('reservavel')
      ->orderBy('numero_vaga')
      ->get();
  }

  /**
   * Retorna as vagas livres (números)
   */
  public function getVagasLivres()
  {
    $vagasOcupadas = $this->ocupacoesAtivas()->pluck('numero_vaga')->toArray();
    $todasVagas = range(1, $this->capacidade_maxima);
    return array_diff($todasVagas, $vagasOcupadas);
  }

  /**
   * Retorna a próxima vaga disponível
   */
  public function getProximaVagaDisponivel()
  {
    $vagasLivres = $this->getVagasLivres();
    return empty($vagasLivres) ? null : min($vagasLivres);
  }

  /**
   * Atualiza o contador de vagas ocupadas
   */
  public function atualizarVagasOcupadas()
  {
    $this->vagas_ocupadas = $this->ocupacoesAtivas()->count();
    $this->save();
  }

  /**
   * Scope para dormitórios ativos (EXCLUINDO os reservados)
   */
  public function scopeAtivos($query)
  {
    return $query->where('status', 'ativo');
  }

  /**
   * Scope para dormitórios disponíveis para check-in (ativos + com vagas)
   */
  public function scopeDisponiveis($query)
  {
    return $query
      ->where('status', 'ativo')
      ->whereRaw('vagas_ocupadas < capacidade_maxima');
  }

  /**
   * Scope para dormitórios ativos (ALIAS PARA COMPATIBILIDADE)
   */
  public function scopeAtivo($query)
  {
    return $this->scopeAtivos($query);
  }

  /**
   * Scope para dormitórios com vagas disponíveis (ATUALIZADO para excluir reservados)
   */
  public function scopeComVagasDisponiveis($query)
  {
    return $query
      ->where('status', 'ativo')
      ->whereRaw('vagas_ocupadas < capacidade_maxima');
  }

  /**
   * Retorna o status de ocupação em percentual
   */
  public function getPercentualOcupacaoAttribute()
  {
    if ($this->capacidade_maxima == 0) {
      return 0;
    }
    return round(($this->vagas_ocupadas / $this->capacidade_maxima) * 100, 1);
  }

  /**
   * Retorna a classe CSS baseada no status de ocupação
   */
  public function getClasseStatusAttribute()
  {
    // Se for reservado para plantão, usar cor específica
    if ($this->isReservadoPlantao()) {
      return 'bg-purple-100 text-purple-800 border-purple-300';
    }

    $percentual = $this->percentual_ocupacao;

    if ($percentual == 0) {
      return 'bg-green-100 text-green-800';
    }
    if ($percentual <= 50) {
      return 'bg-yellow-100 text-yellow-800';
    }
    if ($percentual < 100) {
      return 'bg-orange-100 text-orange-800';
    }
    return 'bg-red-100 text-red-800';
  }

  /**
   * Retorna texto de status formatado
   */
  public function getStatusTextoAttribute()
  {
    switch ($this->status) {
      case 'ativo':
        return 'Ativo';
      case 'inativo':
        return 'Inativo';
      case 'manutencao':
        return 'Em Manutenção';
      case 'reservado':
        return 'Reservado (Plantão)';
      default:
        return ucfirst($this->status);
    }
  }

  /**
   * Retorna informações do dormitório com capacidade dinâmica
   */
  public function getInfoCompleta()
  {
    return [
      'id' => $this->id,
      'numero' => $this->numero,
      'nome' => $this->nome,
      'capacidade_maxima' => $this->capacidade_maxima,
      'vagas_ocupadas' => $this->vagas_ocupadas,
      'vagas_disponiveis' => $this->vagas_disponiveis,
      'percentual_ocupacao' => $this->percentual_ocupacao,
      'status' => $this->status,
      'status_texto' => $this->status_texto,
      'classe_status' => $this->classe_status,
      'reservado_plantao' => $this->isReservadoPlantao(),
      'disponivel_checkin' => $this->disponivelParaCheckin(),
      'observacoes' => $this->observacoes,
    ];
  }
}
