<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitante extends Model
{
  use HasFactory;

  protected $table = 'visitantes';

  protected $fillable = [
    'nome',
    'cpf',
    'rg',
    'orgao_expedidor_rg',
    'data_nascimento',
    'sexo',
    'telefone',
    'email',
    'endereco',
    'orgao_trabalho',
    'cargo',
    'matricula_funcional',
    'tipo_orgao',
    'documento_identidade',
    'documento_funcional',
    'documento_comprobatorio',
    'data_inicial',
    'data_final',
    'motivo',
    'condicao',
    'status',
    'motivo_rejeicao',
    'ip',
    'user_agent',
  ];

  protected $casts = [
    'endereco' => 'array',
    'data_nascimento' => 'date',
    'data_inicial' => 'date',
    'data_final' => 'date',
  ];

  /**
   * Formatar endereço para exibição
   */
  public function getEnderecoFormatadoAttribute()
  {
    $endereco = $this->endereco;

    if (!is_array($endereco)) {
      return '';
    }

    $partes = [];

    if (!empty($endereco['rua'])) {
      $partes[] = $endereco['rua'];

      if (!empty($endereco['numero'])) {
        $partes[0] .= ', ' . $endereco['numero'];
      }
    }

    if (!empty($endereco['bairro'])) {
      $partes[] = $endereco['bairro'];
    }

    if (!empty($endereco['cidade'])) {
      $partes[] = $endereco['cidade'];
    }

    if (!empty($endereco['uf'])) {
      $partes[] = $endereco['uf'];
    }

    return implode(' - ', $partes);
  }

  /**
   * Obter duração da estadia em dias
   */
  public function getDuracaoAttribute()
  {
    return $this->data_inicial->diffInDays($this->data_final);
  }

  /**
   * Formatar CPF para exibição
   */
  public function getFormattedCpfAttribute()
  {
    if (empty($this->cpf)) {
      return '';
    }

    $cpf = preg_replace('/[^0-9]/', '', $this->cpf);

    if (strlen($cpf) === 11) {
      return substr($cpf, 0, 3) .
        '.' .
        substr($cpf, 3, 3) .
        '.' .
        substr($cpf, 6, 3) .
        '-' .
        substr($cpf, 9, 2);
    }

    return $this->cpf;
  }

  /**
   * Formatar telefone para exibição
   */
  public function getFormattedTelefoneAttribute()
  {
    if (empty($this->telefone)) {
      return '';
    }

    $telefone = preg_replace('/[^0-9]/', '', $this->telefone);

    if (strlen($telefone) === 11) {
      return '(' .
        substr($telefone, 0, 2) .
        ') ' .
        substr($telefone, 2, 5) .
        '-' .
        substr($telefone, 7, 4);
    }

    if (strlen($telefone) === 10) {
      return '(' .
        substr($telefone, 0, 2) .
        ') ' .
        substr($telefone, 2, 4) .
        '-' .
        substr($telefone, 6, 4);
    }

    return $this->telefone;
  }

  /**
   * Obter URLs dos documentos
   */
  public function getDocumentoIdentidadeUrlAttribute()
  {
    return $this->documento_identidade
      ? asset('storage/' . $this->documento_identidade)
      : null;
  }

  public function getDocumentoFuncionalUrlAttribute()
  {
    return $this->documento_funcional
      ? asset('storage/' . $this->documento_funcional)
      : null;
  }

  public function getDocumentoComprobatorioUrlAttribute()
  {
    return $this->documento_comprobatorio
      ? asset('storage/' . $this->documento_comprobatorio)
      : null;
  }

  /**
   * Verificar se visitante já tem reserva em determinado período
   */
  public function hasReservaConflito($dataInicial, $dataFinal)
  {
    return self::where('cpf', $this->cpf)
      ->where('status', 'aprovada')
      ->where(function ($query) use ($dataInicial, $dataFinal) {
        $query
          ->whereBetween('data_inicial', [$dataInicial, $dataFinal])
          ->orWhereBetween('data_final', [$dataInicial, $dataFinal])
          ->orWhere(function ($query) use ($dataInicial, $dataFinal) {
            $query
              ->where('data_inicial', '<=', $dataInicial)
              ->where('data_final', '>=', $dataFinal);
          });
      })
      ->exists();
  }

  /**
   * Buscar visitante por CPF
   */
  public static function findByCpf($cpf)
  {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return self::where('cpf', $cpf)
      ->latest('created_at') // Busca o mais recente
      ->first();
  }

  public static function cpfJaTemReserva($cpf)
  {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return self::where('cpf', $cpf)->exists();
  }

  /**
   * Escopo para filtrar por status
   */
  public function scopePendente($query)
  {
    return $query->where('status', 'pendente');
  }

  public function scopeAprovada($query)
  {
    return $query->where('status', 'aprovada');
  }

  public function scopeRejeitada($query)
  {
    return $query->where('status', 'rejeitada');
  }

  /**
   * Escopo para filtrar por tipo de órgão
   */
  public function scopeTipoOrgao($query, $tipo)
  {
    return $query->where('tipo_orgao', $tipo);
  }

  /**
   * Relacionamento com ocupações
   */
  public function ocupacoes()
  {
    return $this->morphMany(Ocupacao::class, 'reservavel');
  }

  /**
   * Ocupação atual (ativa)
   */
  public function ocupacaoAtual()
  {
    return $this->morphOne(Ocupacao::class, 'reservavel')
      ->where('status', 'ocupado')
      ->latest();
  }

  /**
   * Retorna o dormitório atual se houver ocupação ativa
   */
  public function getDormitorioAtual()
  {
    $ocupacao = $this->ocupacaoAtual;
    return $ocupacao ? $ocupacao->dormitorio : null;
  }

  /**
   * Retorna a vaga atual se houver ocupação ativa
   */
  public function getVagaAtual()
  {
    $ocupacao = $this->ocupacaoAtual;
    return $ocupacao ? $ocupacao->numero_vaga : null;
  }

  /**
   * Verifica se pode fazer check-in
   */
  public function podeCheckin()
  {
    // 1. Verificar se a reserva está aprovada
    if ($this->status !== 'aprovada') {
      return false;
    }

    // 2. IMPORTANTE: Se já teve QUALQUER ocupação, não pode mais fazer check-in
    if ($this->ocupacoes()->exists()) {
      return false;
    }

    // 3. Verificar período com flexibilidade (libera o checkin 1 dia antes)
    $hoje = now()->startOfDay();
    $dataInicial =
      $this->data_inicial instanceof \Carbon\Carbon
        ? $this->data_inicial->startOfDay()
        : \Carbon\Carbon::parse($this->data_inicial)->startOfDay();
    $dataFinal =
      $this->data_final instanceof \Carbon\Carbon
        ? $this->data_final->endOfDay()
        : \Carbon\Carbon::parse($this->data_final)->endOfDay();

    // Permitir check-in até 1 dia antes da data inicial
    $dataInicialFlexivel = $dataInicial->copy()->subDay();

    // Permitir check-in de 1 dia antes da data inicial até a data final
    if ($hoje->lt($dataInicialFlexivel) || $hoje->gt($dataFinal)) {
      return false;
    }

    return true;
  }

  /**
   * Retorna mensagem explicando por que não pode fazer check-in
   */
  public function motivoNaoPodeCheckin()
  {
    if ($this->status !== 'aprovada') {
      return 'Reserva não está aprovada.';
    }

    $hoje = now()->startOfDay();
    $dataInicial =
      $this->data_inicial instanceof \Carbon\Carbon
        ? $this->data_inicial->startOfDay()
        : \Carbon\Carbon::parse($this->data_inicial)->startOfDay();
    $dataFinal =
      $this->data_final instanceof \Carbon\Carbon
        ? $this->data_final->endOfDay()
        : \Carbon\Carbon::parse($this->data_final)->endOfDay();

    $dataInicialFlexivel = $dataInicial->copy()->subDay();

    if ($hoje->lt($dataInicialFlexivel)) {
      return 'Check-in só pode ser feito a partir de ' .
        $dataInicialFlexivel->format('d/m/Y') .
        ' (1 dia antes da reserva).';
    }

    if ($hoje->gt($dataFinal)) {
      return 'Período da reserva expirou em ' .
        $dataFinal->format('d/m/Y') .
        '.';
    }

    if ($this->temOcupacaoAtiva()) {
      return 'Já possui check-in ativo.';
    }

    return 'Reserva apta para check-in.';
  }

  /**
   * Verifica se tem ocupação ativa (apenas ocupações com status 'ocupado')
   */
  public function temOcupacaoAtiva()
  {
    return $this->ocupacoes()->where('status', 'ocupado')->exists();
  }

  /**
   * Verifica se pode fazer check-out
   */
  public function podeCheckout()
  {
    return $this->status === 'aprovada' && $this->temOcupacaoAtiva();
  }

  /**
   * Retorna a ocupação ativa atual
   */
  public function getOcupacaoAtivaAttribute()
  {
    return $this->ocupacaoAtual;
  }

  /**
   * Verifica se está no período da reserva
   */
  public function estaNoPeriodoReserva()
  {
    $hoje = now()->startOfDay();
    $dataInicial =
      $this->data_inicial instanceof \Carbon\Carbon
        ? $this->data_inicial->startOfDay()
        : \Carbon\Carbon::parse($this->data_inicial)->startOfDay();
    $dataFinal =
      $this->data_final instanceof \Carbon\Carbon
        ? $this->data_final->endOfDay()
        : \Carbon\Carbon::parse($this->data_final)->endOfDay();

    return $hoje->gte($dataInicial) && $hoje->lte($dataFinal);
  }

  /**
   * Verifica se já teve check-out (histórico)
   */
  public function jaTeveCheckout()
  {
    return $this->ocupacoes()
      ->where('status', 'liberado')
      ->whereNotNull('checkout_at')
      ->exists();
  }

  /**
   * Pode fazer novo check-in (mesmo após checkout)
   */
  public function podeNovoCheckin()
  {
    // Se está no período e não tem ocupação ativa, pode fazer check-in
    // mesmo que já tenha tido checkout antes
    return $this->estaNoPeriodoReserva() && !$this->temOcupacaoAtiva();
  }
}
