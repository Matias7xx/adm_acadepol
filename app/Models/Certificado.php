<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Helpers\StorageHelper;

class Certificado extends Model
{
  use HasFactory;

  protected $fillable = [
    'matricula_id',
    'user_id',
    'curso_id',
    'numero_certificado',
    'arquivo_path',
    'data_emissao',
    'data_conclusao_curso',
    'carga_horaria',
    'nome_aluno',
    'cpf_aluno',
    'nome_curso',
    'ativo',
    'tipo_origem',
  ];

  protected $casts = [
    'data_emissao' => 'datetime',
    'data_conclusao_curso' => 'datetime',
    'ativo' => 'boolean',
  ];

  // Constantes para tipos de origem
  const TIPO_MATRICULA = 'matricula';
  const TIPO_CURSO_SISTEMA = 'curso_sistema';
  const TIPO_CURSO_EXTERNO = 'curso_externo';

  /**
   * Relacionamento com a matrícula (pode ser null)
   */
  public function matricula()
  {
    return $this->belongsTo(Matricula::class);
  }

  /**
   * Relacionamento com o usuário/aluno
   */
  public function aluno()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * Relacionamento com o curso (pode ser null para cursos externos)
   */
  public function curso()
  {
    return $this->belongsTo(Curso::class);
  }

  /**
   * Gerar número único do certificado
   */
  public static function gerarNumeroCertificado()
  {
    $ano = date('Y');
    $ultimoNumero = self::whereYear('data_emissao', $ano)
      ->orderBy('id', 'desc')
      ->first();

    $proximoNumero = $ultimoNumero
      ? intval(substr($ultimoNumero->numero_certificado, -4)) + 1
      : 1;

    return 'ACADEPOL-' .
      $ano .
      '-' .
      str_pad($proximoNumero, 4, '0', STR_PAD_LEFT);
  }

  /**
   * VERIFICAR SE O ARQUIVO EXISTE NO MINIO VIA STORAGEHELPER
   */
  public function arquivoExiste()
  {
    try {
      return StorageHelper::certificados()->exists($this->arquivo_path);
    } catch (\Exception $e) {
      \Log::error('Erro ao verificar existência do certificado no MinIO', [
        'certificado_id' => $this->id,
        'arquivo_path' => $this->arquivo_path,
        'error' => $e->getMessage(),
      ]);
      return false;
    }
  }

  /**
   * Obter URL para download do certificado
   */
  public function getUrlDownload()
  {
    return route('certificados.download', $this->id);
  }

  /**
   * OBTER URL PARA VISUALIZAÇÃO DO CERTIFICADO
   */
  public function getUrlView()
  {
    return route('certificados.view', $this->id);
  }

  /**
   * Formatar CPF para exibição
   */
  public function getCpfFormatadoAttribute()
  {
    $cpf = preg_replace('/[^0-9]/', '', $this->cpf_aluno);
    if (strlen($cpf) === 11) {
      return substr($cpf, 0, 3) .
        '.' .
        substr($cpf, 3, 3) .
        '.' .
        substr($cpf, 6, 3) .
        '-' .
        substr($cpf, 9, 2);
    }
    return $this->cpf_aluno;
  }

  /**
   * OBTER TAMANHO DO ARQUIVO NO MINIO
   */
  public function getTamanhoArquivoAttribute()
  {
    try {
      if (!$this->arquivoExiste()) {
        return 'Arquivo não encontrado';
      }

      $size = StorageHelper::certificados()->size($this->arquivo_path);
      $units = ['B', 'KB', 'MB', 'GB'];

      for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
      }

      return round($size, 2) . ' ' . $units[$i];
    } catch (\Exception $e) {
      \Log::error('Erro ao obter tamanho do certificado no MinIO', [
        'certificado_id' => $this->id,
        'arquivo_path' => $this->arquivo_path,
        'error' => $e->getMessage(),
      ]);
      return 'Erro ao obter tamanho';
    }
  }

  /**
   * OBTER ÚLTIMA MODIFICAÇÃO DO ARQUIVO NO MINIO
   */
  public function getDataModificacaoAttribute()
  {
    try {
      if (!$this->arquivoExiste()) {
        return null;
      }

      $timestamp = StorageHelper::certificados()->lastModified(
        $this->arquivo_path,
      );
      return $timestamp
        ? \Carbon\Carbon::createFromTimestamp($timestamp)
        : null;
    } catch (\Exception $e) {
      \Log::error('Erro ao obter data de modificação do certificado no MinIO', [
        'certificado_id' => $this->id,
        'arquivo_path' => $this->arquivo_path,
        'error' => $e->getMessage(),
      ]);
      return null;
    }
  }

  /**
   * OBTER INFORMAÇÕES COMPLETAS DO ARQUIVO NO MINIO
   */
  public function getInfoArquivo()
  {
    try {
      if (!$this->arquivoExiste()) {
        return [
          'exists' => false,
          'size' => null,
          'size_human' => 'Arquivo não encontrado',
          'last_modified' => null,
        ];
      }

      $size = StorageHelper::certificados()->size($this->arquivo_path);
      $lastModified = StorageHelper::certificados()->lastModified(
        $this->arquivo_path,
      );

      return [
        'exists' => true,
        'path' => $this->arquivo_path,
        'size' => $size,
        'size_human' => $this->tamanho_arquivo,
        'last_modified' => $lastModified
          ? \Carbon\Carbon::createFromTimestamp($lastModified)
          : null,
        'download_url' => $this->getUrlDownload(),
        'view_url' => $this->getUrlView(),
      ];
    } catch (\Exception $e) {
      \Log::error('Erro ao obter informações do certificado no MinIO', [
        'certificado_id' => $this->id,
        'arquivo_path' => $this->arquivo_path,
        'error' => $e->getMessage(),
      ]);

      return [
        'exists' => false,
        'error' => 'Erro ao acessar arquivo',
      ];
    }
  }

  /**
   * Verificar se é certificado de matrícula normal
   */
  public function isMatricula()
  {
    return $this->tipo_origem === self::TIPO_MATRICULA;
  }

  /**
   * Verificar se é certificado de curso do sistema
   */
  public function isCursoSistema()
  {
    return $this->tipo_origem === self::TIPO_CURSO_SISTEMA;
  }

  /**
   * Verificar se é certificado de curso externo
   */
  public function isCursoExterno()
  {
    return $this->tipo_origem === self::TIPO_CURSO_EXTERNO;
  }

  /**
   * Obter descrição do tipo de origem
   */
  public function getTipoOrigemDescricaoAttribute()
  {
    return match ($this->tipo_origem) {
      self::TIPO_MATRICULA => 'Matrícula Regular',
      self::TIPO_CURSO_SISTEMA => 'Curso do Sistema',
      self::TIPO_CURSO_EXTERNO => 'Curso Externo',
      default => 'Não Identificado',
    };
  }

  /**
   * Obter cor para badge do tipo
   */
  public function getTipoOrigemCorAttribute()
  {
    return match ($this->tipo_origem) {
      self::TIPO_MATRICULA => 'success',
      self::TIPO_CURSO_SISTEMA => 'info',
      self::TIPO_CURSO_EXTERNO => 'warning',
      default => 'gray',
    };
  }

  /**
   * OBTER NOME DO BUCKET ONDE ESTÁ ARMAZENADO
   */
  public function getBucketName()
  {
    // Determinar bucket baseado no caminho
    if (str_starts_with($this->arquivo_path, 'certificados-externos/')) {
      return 'certificados (externos)';
    }

    return 'certificados';
  }

  /**
   * Scope para certificados de matrículas
   */
  public function scopeMatriculas($query)
  {
    return $query->where('tipo_origem', self::TIPO_MATRICULA);
  }

  /**
   * Scope para certificados de cursos do sistema
   */
  public function scopeCursosSistema($query)
  {
    return $query->where('tipo_origem', self::TIPO_CURSO_SISTEMA);
  }

  /**
   * Scope para certificados de cursos externos
   */
  public function scopeCursosExternos($query)
  {
    return $query->where('tipo_origem', self::TIPO_CURSO_EXTERNO);
  }

  /**
   * Scope para certificados ativos
   */
  public function scopeAtivos($query)
  {
    return $query->where('ativo', true);
  }

  /**
   * Scope para certificados de um usuário específico
   */
  public function scopeDoUsuario($query, $userId)
  {
    return $query->where('user_id', $userId);
  }
}
