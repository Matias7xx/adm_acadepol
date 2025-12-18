<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\UploadHelper;

class Noticia extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'noticias';

  protected $fillable = [
    'titulo',
    'descricao_curta',
    'conteudo',
    'imagem',
    'carousel_images',
    'destaque',
    'data_publicacao',
    'status',
    'visualizacoes',
  ];

  protected $casts = [
    'destaque' => 'boolean',
    'data_publicacao' => 'date',
    'visualizacoes' => 'integer',
    'carousel_images' => 'array',
  ];

  /**
   * Escopo para filtrar apenas notícias publicadas
   */
  public function scopePublicado($query)
  {
    return $query
      ->where('status', 'publicado')
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

  public function getImagemAttribute($value)
  {
    if (!$value) {
      return null;
    }

    // Se já é uma URL completa (começa com http), retorna como está
    if (str_starts_with($value, 'http')) {
      return $value;
    }

    // Se já tem o prefixo /storage/, retorna como está
    if (str_starts_with($value, '/storage/')) {
      return $value;
    }

    // Se é um caminho antigo que começa com /images/, retorna como está
    if (str_starts_with($value, '/images/')) {
      return $value;
    }

    // Caso contrário, assume que é um caminho do storage e adiciona o prefixo
    return '/storage/' . $value;
  }

  /**
   * Retorna data formatada para exibição
   */
  public function getDataFormatadaAttribute()
  {
    return $this->data_publicacao
      ? $this->data_publicacao->format('d/m/Y')
      : '';
  }

  /**
   * Retorna conteúdo sanitizado
   */
  public function getSanitizedConteudoAttribute()
  {
    if (!$this->conteudo) {
      return '';
    }

    return \Mews\Purifier\Facades\Purifier::clean($this->conteudo, [
      'HTML.Allowed' =>
        'p,b,i,u,strong,em,h2,h3,h4,ul,ol,li,a[href|target],br,blockquote,img[src|alt|width|height|class],iframe[src|width|height|frameborder|allowfullscreen],table,tr,td,th',
      'CSS.AllowedProperties' =>
        'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align,width,height',
      'AutoFormat.AutoParagraph' => true,
      'AutoFormat.RemoveEmpty' => true,
      'HTML.SafeIframe' => true,
      'URI.SafeIframeRegexp' =>
        '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%',
    ]);
  }

  /**
   * Boot method para cleanup automático
   */
  protected static function boot()
  {
    parent::boot();

    // Ao deletar notícia, limpar arquivos associados
    static::deleting(function ($noticia) {
      // Remover imagem de capa
      if ($noticia->attributes['imagem']) {
        UploadHelper::deleteImage($noticia->attributes['imagem']);
      }

      // Remover imagens do conteúdo
      if ($noticia->conteudo) {
        // Buscar todas as imagens no conteúdo que são do nosso storage
        preg_match_all(
          '/<img[^>]+src="([^"]*\/storage\/noticias\/[^"]+)"[^>]*>/i',
          $noticia->conteudo,
          $matches,
        );

        if (!empty($matches[1])) {
          foreach ($matches[1] as $imageSrc) {
            // Extrair o caminho relativo do storage
            $relativePath = str_replace(
              '/storage/',
              '',
              parse_url($imageSrc, PHP_URL_PATH),
            );
            UploadHelper::deleteImage($relativePath);
          }
        }

        // Limpar pasta da notícia
        $pastaNoticia =
          'noticias/' . UploadHelper::sanitizeFolderName($noticia->titulo);
        UploadHelper::cleanupEmptyFolder($pastaNoticia);
      }
    });
  }
}
