<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class DownloadController extends Controller
{
  /**
   * Força o download de um arquivo do storage
   */
  public function downloadFile(Request $request)
  {
    $path = $request->query('path');
    $filename = $request->query('filename', null);

    Log::info('Download solicitado', [
      'path' => $path,
      'filename' => $filename,
      'full_query' => $request->all(),
    ]);

    // Validar se o path existe
    if (!$path) {
      Log::error('Path não fornecido para download');
      abort(400, 'Caminho do arquivo não especificado');
    }

    // Decodificar o path se necessário
    $path = urldecode($path);

    Log::info('Path decodificado', ['path' => $path]);

    // Validar se o arquivo existe
    if (!Storage::disk('public')->exists($path)) {
      Log::error('Arquivo não encontrado', [
        'path' => $path,
        'full_path' => storage_path('app/public/' . $path),
      ]);
      abort(404, 'Arquivo não encontrado');
    }

    // Validar se é um arquivo de notícia (segurança)
    if (!str_starts_with($path, 'noticias/')) {
      Log::warning('Tentativa de acesso não autorizado', ['path' => $path]);
      abort(403, 'Acesso não autorizado');
    }

    // Obter informações do arquivo
    $fullPath = storage_path('app/public/' . $path);
    $originalFilename = $filename ?: basename($path);

    // Determinar o tipo MIME
    try {
      $mimeType = Storage::disk('public')->mimeType($path);
    } catch (\Exception $e) {
      $mimeType = 'application/octet-stream';
    }

    Log::info('Download iniciado', [
      'path' => $path,
      'filename' => $originalFilename,
      'mime_type' => $mimeType,
    ]);

    // Criar resposta de download forçado
    return Response::download($fullPath, $originalFilename, [
      'Content-Type' => $mimeType,
      'Content-Disposition' =>
        'attachment; filename="' . $originalFilename . '"',
      'Cache-Control' => 'no-cache, no-store, must-revalidate',
      'Pragma' => 'no-cache',
      'Expires' => '0',
    ]);
  }

  /**
   * Visualiza um arquivo (abre no navegador)
   */
  public function viewFile(Request $request)
  {
    $path = $request->query('path');

    Log::info('Visualização solicitada', [
      'path' => $path,
      'full_query' => $request->all(),
    ]);

    // Validar se o path existe
    if (!$path) {
      Log::error('Path não fornecido para visualização');
      abort(400, 'Caminho do arquivo não especificado');
    }

    // Decodificar o path se necessário
    $path = urldecode($path);

    Log::info('Path decodificado para visualização', ['path' => $path]);

    // Validar se o arquivo existe
    if (!Storage::disk('public')->exists($path)) {
      Log::error('Arquivo não encontrado para visualização', [
        'path' => $path,
        'full_path' => storage_path('app/public/' . $path),
      ]);
      abort(404, 'Arquivo não encontrado');
    }

    // Validar se é um arquivo de notícia (segurança)
    if (!str_starts_with($path, 'noticias/')) {
      Log::warning('Tentativa de visualização não autorizada', [
        'path' => $path,
      ]);
      abort(403, 'Acesso não autorizado');
    }

    // Obter informações do arquivo
    $fullPath = storage_path('app/public/' . $path);

    // Determinar o tipo MIME
    try {
      $mimeType = Storage::disk('public')->mimeType($path);
    } catch (\Exception $e) {
      $mimeType = 'application/octet-stream';
    }

    Log::info('Visualização iniciada', [
      'path' => $path,
      'mime_type' => $mimeType,
    ]);

    // Retornar arquivo para visualização inline
    return Response::file($fullPath, [
      'Content-Type' => $mimeType,
      'Content-Disposition' => 'inline',
      'Cache-Control' => 'public, max-age=3600',
    ]);
  }
}
