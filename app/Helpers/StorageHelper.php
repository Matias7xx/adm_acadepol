<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class StorageHelper
{
  /**
   * Obter instância do Storage com bucket específico
   */
  public static function bucket($bucketName)
  {
    // cópia da configuração do s3
    $config = config('filesystems.disks.s3');

    // Alterar apenas o bucket
    $config['bucket'] = $bucketName;

    // Criar configuração temporária
    Config::set('filesystems.disks.s3_temp', $config);

    // Retorna instância do Storage com o bucket específico
    return Storage::disk('s3_temp');
  }

  /**
   * Bucket de fotos/funcionais
   */
  public static function fotos()
  {
    return self::bucket(env('AWS_BUCKET_FOTOS', 'funcionais'));
  }

  /**
   * BUCKET DE CERTIFICADOS
   */
  public static function certificados()
  {
    return self::bucket(env('AWS_BUCKET_CERTIFICADOS', 'certificados'));
  }

  /**
   * MÉTODO AUXILIAR PARA LISTAR TODOS OS BUCKETS CONFIGURADOS
   */
  public static function listarBuckets()
  {
    return [
      'fotos' => env('AWS_BUCKET_FOTOS', 'funcionais'),
      'certificados' => env('AWS_BUCKET_CERTIFICADOS', 'certificados'),
    ];
  }

  /**
   * VERIFICAR CONECTIVIDADE COM UM BUCKET ESPECÍFICO
   */
  public static function testarConectividade($bucketName)
  {
    try {
      $disk = self::bucket($bucketName);

      // Tentar listar arquivos para verificar conectividade
      $files = $disk->files('', true); // Listar recursivamente

      return [
        'sucesso' => true,
        'bucket' => $bucketName,
        'total_arquivos' => count($files),
        'exemplo_arquivos' => array_slice($files, 0, 5), // Primeiros 5 arquivos
      ];
    } catch (\Exception $e) {
      return [
        'sucesso' => false,
        'bucket' => $bucketName,
        'erro' => $e->getMessage(),
      ];
    }
  }

  /**
   * OBTER ESTATÍSTICAS DE UM BUCKET
   */
  public static function estatisticasBucket($bucketName)
  {
    try {
      $disk = self::bucket($bucketName);
      $files = $disk->allFiles();

      $totalSize = 0;
      $tipos = [];

      foreach ($files as $file) {
        try {
          $size = $disk->size($file);
          $totalSize += $size;

          $extensao = pathinfo($file, PATHINFO_EXTENSION);
          $tipos[$extensao] = ($tipos[$extensao] ?? 0) + 1;
        } catch (\Exception $e) {
          // Ignorar arquivos com erro
          continue;
        }
      }

      return [
        'bucket' => $bucketName,
        'total_arquivos' => count($files),
        'tamanho_total' => $totalSize,
        'tamanho_formatado' => self::formatarTamanho($totalSize),
        'tipos_arquivo' => $tipos,
        'sucesso' => true,
      ];
    } catch (\Exception $e) {
      return [
        'bucket' => $bucketName,
        'erro' => $e->getMessage(),
        'sucesso' => false,
      ];
    }
  }

  /**
   * FORMATAR TAMANHO EM BYTES PARA FORMATO LEGÍVEL
   */
  private static function formatarTamanho($bytes)
  {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
      $bytes /= 1024;
    }

    return round($bytes, 2) . ' ' . $units[$i];
  }
}
