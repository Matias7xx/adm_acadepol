<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alojamento extends Model
{
    use HasFactory;

    protected $table = 'alojamentos';

    protected $fillable = [
        'user_id',
        'nome',
        'cargo',
        'matricula',
        'orgao',
        'cpf',
        'data_nascimento',
        'rg',
        'orgao_expedidor',
        'sexo',
        'uf',
        'motivo',
        'condicao',
        'email',
        'telefone',
        'endereco',
        'data_inicial',
        'data_final',
        'status',
        'motivo_rejeicao',
        'documento_comprobatorio'
    ];

    protected $casts = [
        'endereco' => 'array',
        'data_inicial' => 'date',
        'data_final' => 'date',
        'data_nascimento' => 'date',
    ];

    /**
     * Relacionamento com o usuário que fez a reserva
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Verificar se uma determinada data está disponível para reserva
     */
    public static function verificarDisponibilidade($dataInicial, $dataFinal)
    {
        // Verificar se existem reservas aprovadas no mesmo período
        $reservasConflitantes = self::where('status', 'aprovada')
            ->where(function($query) use ($dataInicial, $dataFinal) {
                $query->whereBetween('data_inicial', [$dataInicial, $dataFinal])
                    ->orWhereBetween('data_final', [$dataInicial, $dataFinal])
                    ->orWhere(function($query) use ($dataInicial, $dataFinal) {
                        $query->where('data_inicial', '<=', $dataInicial)
                            ->where('data_final', '>=', $dataFinal);
                    });
            })
            ->count();
        
        return $reservasConflitantes === 0;
    }

    /**
     * Obter duração da estadia em dias
     */
    public function getDuracaoAttribute()
    {
        // determina a duração da estadia . $this->data_inicial->diffInDays($this->data_final) + 1 acrescenta 1 dia
        return $this->data_inicial->diffInDays($this->data_final);
    }

    /**
     * Formatar endereço para exibição
     */
    public function getEnderecoFormatadoAttribute()
    {
        $endereco = json_decode($this->endereco, true);
        
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
        
        return implode(' - ', $partes);
    }

    /**
     * Obter o caminho para o documento comprobatório
     */
    public function getDocumentoUrlAttribute()
    {
        if (!$this->documento_comprobatorio) {
            return null;
        }
        
        return asset('storage/' . $this->documento_comprobatorio);
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

    // 3. Verificar período (1 dia antes)
    $hoje = now()->startOfDay();
    $dataInicial = $this->data_inicial instanceof \Carbon\Carbon 
        ? $this->data_inicial->startOfDay() 
        : \Carbon\Carbon::parse($this->data_inicial)->startOfDay();
    $dataFinal = $this->data_final instanceof \Carbon\Carbon 
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
    $dataInicial = $this->data_inicial instanceof \Carbon\Carbon 
        ? $this->data_inicial->startOfDay() 
        : \Carbon\Carbon::parse($this->data_inicial)->startOfDay();
    $dataFinal = $this->data_final instanceof \Carbon\Carbon 
        ? $this->data_final->endOfDay() 
        : \Carbon\Carbon::parse($this->data_final)->endOfDay();

    $dataInicialFlexivel = $dataInicial->copy()->subDay();

    if ($hoje->lt($dataInicialFlexivel)) {
        return 'Check-in só pode ser feito a partir de ' . $dataInicialFlexivel->format('d/m/Y') . ' (1 dia antes da reserva).';
    }

    if ($hoje->gt($dataFinal)) {
        return 'Período da reserva expirou em ' . $dataFinal->format('d/m/Y') . '.';
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
    return $this->ocupacoes()
        ->where('status', 'ocupado')
        ->exists();
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
    $dataInicial = $this->data_inicial instanceof \Carbon\Carbon 
        ? $this->data_inicial->startOfDay() 
        : \Carbon\Carbon::parse($this->data_inicial)->startOfDay();
    $dataFinal = $this->data_final instanceof \Carbon\Carbon 
        ? $this->data_final->endOfDay() 
        : \Carbon\Carbon::parse($this->data_final)->endOfDay();

    return $hoje->gte($dataInicial) && $hoje->lte($dataFinal);
}
}