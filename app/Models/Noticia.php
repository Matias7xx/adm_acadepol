<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Noticia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'noticias';

    protected $fillable = [
        'titulo',
        'descricao_curta',
        'conteudo',
        'imagem',
        'destaque',
        'data_publicacao',
        'status',
        'visualizacoes'
    ];

    protected $casts = [
        'destaque' => 'boolean',
        'data_publicacao' => 'date',
        'visualizacoes' => 'integer',
    ];

    /**
     * Escopo para filtrar apenas notícias publicadas
     */
    public function scopePublicado($query)
    {
        return $query->where('status', 'publicado')
                     ->where('data_publicacao', '<=', now());
    }

    /**
     * Escopo para filtrar notícias em destaque
     */
    public function scopeDestaque($query)
    {
        return $query->where('destaque', true);
    }

    /**
     * Incrementa contador de visualizações
     */
    public function incrementarVisualizacoes()
    {
        $this->increment('visualizacoes');
        return $this;
    }

    /**
     * Retorna URL da imagem com caminho completo
     */
    public function getImagemUrlAttribute()
    {
        if (!$this->imagem) {
            return null;
        }
        
        return asset('storage/' . $this->imagem);
    }

    /**
     * Retorna data formatada para exibição
     */
    public function getDataFormatadaAttribute()
    {
        return $this->data_publicacao ? $this->data_publicacao->format('d/m/Y') : '';
    }

    public function getSanitizedConteudoAttribute()
{
    // Sanitizar HTML para evitar XSS e manter formatação básica
    return \Mews\Purifier\Facades\Purifier::clean($this->conteudo, [
        'HTML.Allowed' => 'p,b,i,u,strong,em,h2,h3,h4,ul,ol,li,a[href|target],br,blockquote',
        'CSS.AllowedProperties' => [],
        'AutoFormat.AutoParagraph' => true,
        'AutoFormat.RemoveEmpty' => true,
    ]);
}
}