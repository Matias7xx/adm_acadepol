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
        return $this->data_inicial->diffInDays($this->data_final) + 1;
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
}