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
        'user_agent'
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
            return substr($cpf, 0, 3) . '.' . 
                   substr($cpf, 3, 3) . '.' . 
                   substr($cpf, 6, 3) . '-' . 
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
            return '(' . substr($telefone, 0, 2) . ') ' . 
                   substr($telefone, 2, 5) . '-' . 
                   substr($telefone, 7, 4);
        }
        
        if (strlen($telefone) === 10) {
            return '(' . substr($telefone, 0, 2) . ') ' . 
                   substr($telefone, 2, 4) . '-' . 
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
            ->where(function($query) use ($dataInicial, $dataFinal) {
                $query->whereBetween('data_inicial', [$dataInicial, $dataFinal])
                    ->orWhereBetween('data_final', [$dataInicial, $dataFinal])
                    ->orWhere(function($query) use ($dataInicial, $dataFinal) {
                        $query->where('data_inicial', '<=', $dataInicial)
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
        return self::where('cpf', $cpf)->first();
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
}