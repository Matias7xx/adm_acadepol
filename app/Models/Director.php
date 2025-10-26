<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\UploadHelper;

class Director extends Model
{
  use HasFactory;

  protected $table = 'directors';

  protected $fillable = [
    'nome',
    'imagem',
    'data_inicio',
    'data_fim',
    'historico',
    'realizacoes',
    'atual',
    'ordem',
  ];

  protected $casts = [
    'realizacoes' => 'array',
    'data_inicio' => 'date',
    'data_fim' => 'date',
    'atual' => 'boolean',
  ];

  public function getImagemUrlAttribute()
  {
    if (!$this->imagem) {
      return '/images/placeholder-profile.jpg';
    }

    return UploadHelper::getPublicUrl($this->imagem);
  }

  /**
   * Retorna o período formatado (ex: "10/02/2023 - ATUALMENTE" ou "11/09/2013 - 13/04/2022")
   */
  public function getPeriodoFormatadoAttribute()
  {
    $inicio = $this->data_inicio->format('d/m/Y');

    if ($this->atual || $this->data_fim === null) {
      return $inicio . ' - ATUALMENTE';
    } else {
      return $inicio . ' - ' . $this->data_fim->format('d/m/Y');
    }
  }

  /**
   * Verifica ao salvar se este diretor está marcado como atual.
   * Se sim, desmarca outros diretores marcados como atual.
   */
  protected static function booted()
  {
    static::saving(function ($diretor) {
      if ($diretor->atual) {
        // Desmarca outros diretores atuais
        static::where('id', '!=', $diretor->id)
          ->where('atual', true)
          ->update(['atual' => false]);
      }
    });
  }
}
