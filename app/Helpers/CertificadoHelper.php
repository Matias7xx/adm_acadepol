<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;

class CertificadoHelper
{
  /**
   * para certificado de usuário
   */
  public static function gerarCaminhoParaUsuario(
    User $user,
    string $nomeCurso,
    string $numeroCertificado,
  ): string {
    $nomeUsuario = self::sanitizeFolderName($user->name);
    $nomeCursoSanitizado = self::sanitizeFolderName($nomeCurso);

    $diretorio = 'certificados/' . $nomeUsuario . '/' . $nomeCursoSanitizado;
    $nomeArquivo = 'certificado_' . $numeroCertificado . '.pdf';

    $caminhoCompleto = $diretorio . '/' . $nomeArquivo;

    // Se já existe, adiciona timestamp
    if (Storage::disk('local')->exists($caminhoCompleto)) {
      $timestamp = now()->format('Y-m-d_H-i-s');
      $nomeArquivo =
        'certificado_' . $numeroCertificado . '_' . $timestamp . '.pdf';
      $caminhoCompleto = $diretorio . '/' . $nomeArquivo;
    }

    return $caminhoCompleto;
  }

  public static function gerarCaminhoUnico(
    $matricula,
    string $numeroCertificado = null,
  ): string {
    // Se é um usuário direto
    if ($matricula instanceof User) {
      throw new \InvalidArgumentException(
        'Use gerarCaminhoParaUsuario() para usuários diretos',
      );
    }

    // Mantém lógica para matrículas reais
    $nomeUsuario = self::sanitizeFolderName($matricula->aluno->name);
    $nomeCurso = self::sanitizeFolderName($matricula->curso->nome);

    $diretorio = 'certificados/' . $nomeUsuario . '/' . $nomeCurso;

    if ($numeroCertificado) {
      $nomeArquivo = 'certificado_' . $numeroCertificado . '.pdf';
    } else {
      $nomeArquivo = 'certificado.pdf';
    }

    $caminhoCompleto = $diretorio . '/' . $nomeArquivo;

    // Se já existe, adiciona timestamp
    if (Storage::disk('local')->exists($caminhoCompleto)) {
      $timestamp = now()->format('Y-m-d_H-i-s');
      if ($numeroCertificado) {
        $nomeArquivo =
          'certificado_' . $numeroCertificado . '_' . $timestamp . '.pdf';
      } else {
        $nomeArquivo = 'certificado_' . $timestamp . '.pdf';
      }
      $caminhoCompleto = $diretorio . '/' . $nomeArquivo;
    }

    return $caminhoCompleto;
  }

  /**
   * Cria a estrutura de diretórios se não existir
   */
  public static function criarEstruturaDiretorios(string $caminhoCompleto): bool
  {
    $diretorio = dirname($caminhoCompleto);

    try {
      if (!Storage::disk('local')->exists($diretorio)) {
        Storage::disk('local')->makeDirectory($diretorio);
        return true;
      }
      return true;
    } catch (\Exception $e) {
      \Log::error('Erro ao criar diretório para certificado', [
        'diretorio' => $diretorio,
        'erro' => $e->getMessage(),
      ]);
      return false;
    }
  }

  /**
   * Limpa diretórios vazios após remoção de certificados
   */
  public static function limparDiretoriosVazios(string $caminhoArquivo): void
  {
    try {
      $diretorio = dirname($caminhoArquivo);

      // Remove o diretório do curso se estiver vazio
      if (Storage::disk('local')->exists($diretorio)) {
        $arquivos = Storage::disk('local')->files($diretorio);
        if (empty($arquivos)) {
          Storage::disk('local')->deleteDirectory($diretorio);

          // Verifica se o diretório do usuário também ficou vazio
          $diretorioUsuario = dirname($diretorio);
          $subDiretorios = Storage::disk('local')->directories(
            $diretorioUsuario,
          );
          if (
            empty($subDiretorios) &&
            empty(Storage::disk('local')->files($diretorioUsuario))
          ) {
            Storage::disk('local')->deleteDirectory($diretorioUsuario);
          }
        }
      }
    } catch (\Exception $e) {
      \Log::error('Erro ao limpar diretórios vazios', [
        'caminho' => $caminhoArquivo,
        'erro' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Sanitizar nome para usar como nome de pasta
   */
  public static function sanitizeFolderName(string $name): string
  {
    // Remove acentos e caracteres especiais
    $name = self::removeAccents($name);

    // Remove caracteres não alfanuméricos exceto espaços e hífens
    $name = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $name);

    // Substitui espaços por hífens
    $name = preg_replace('/\s+/', '-', $name);

    // Remove hífens múltiplos
    $name = preg_replace('/-+/', '-', $name);

    // Remove hífens do início e fim
    $name = trim($name, '-');

    // Limitar tamanho (para sistemas de arquivo)
    $name = Str::limit($name, 50, '');

    // Se ficou vazio, usar um nome padrão
    if (empty($name)) {
      $name = 'sem-nome';
    }

    return strtolower($name);
  }

  /**
   * Remove acentos de uma string
   */
  private static function removeAccents(string $string): string
  {
    $unwanted_array = [
      'Š' => 'S',
      'š' => 's',
      'Ž' => 'Z',
      'ž' => 'z',
      'À' => 'A',
      'Á' => 'A',
      'Â' => 'A',
      'Ã' => 'A',
      'Ä' => 'A',
      'Å' => 'A',
      'Æ' => 'A',
      'Ç' => 'C',
      'È' => 'E',
      'É' => 'E',
      'Ê' => 'E',
      'Ë' => 'E',
      'Ì' => 'I',
      'Í' => 'I',
      'Î' => 'I',
      'Ï' => 'I',
      'Ñ' => 'N',
      'Ò' => 'O',
      'Ó' => 'O',
      'Ô' => 'O',
      'Õ' => 'O',
      'Ö' => 'O',
      'Ø' => 'O',
      'Ù' => 'U',
      'Ú' => 'U',
      'Û' => 'U',
      'Ü' => 'U',
      'Ý' => 'Y',
      'Þ' => 'B',
      'ß' => 'Ss',
      'à' => 'a',
      'á' => 'a',
      'â' => 'a',
      'ã' => 'a',
      'ä' => 'a',
      'å' => 'a',
      'æ' => 'a',
      'ç' => 'c',
      'è' => 'e',
      'é' => 'e',
      'ê' => 'e',
      'ë' => 'e',
      'ì' => 'i',
      'í' => 'i',
      'î' => 'i',
      'ï' => 'i',
      'ð' => 'o',
      'ñ' => 'n',
      'ò' => 'o',
      'ó' => 'o',
      'ô' => 'o',
      'õ' => 'o',
      'ö' => 'o',
      'ø' => 'o',
      'ù' => 'u',
      'ú' => 'u',
      'û' => 'u',
      'ý' => 'y',
      'þ' => 'b',
      'ÿ' => 'y',
    ];

    return strtr($string, $unwanted_array);
  }
}
