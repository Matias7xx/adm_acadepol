<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Helpers\UploadHelper;
use Carbon\Carbon;

class UploadController extends Controller
{
  /**
   * Upload de imagens para o CKEditor
   */
  public function uploadCKEditorImage(Request $request)
  {
    try {
      Log::info('Upload CKEditor iniciado', [
        'user_id' => auth()->id(),
        'files' => $request->allFiles(),
        'method' => $request->method(),
        'csrf_token' => $request->header('X-CSRF-TOKEN'),
        'session_token' => session()->token(),
      ]);

      // Validar a requisição
      $request->validate([
        'upload' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
      ]);

      $file = $request->file('upload');

      if (!$file || !$file->isValid()) {
        Log::error('Arquivo inválido ou não enviado');
        return response()->json(
          [
            'uploaded' => false,
            'error' => [
              'message' => 'Arquivo inválido ou não enviado.',
            ],
          ],
          400,
        );
      }

      // Usar o UploadHelper para fazer upload temporário
      $imagePath = UploadHelper::uploadImage(
        $file,
        'noticias',
        'temp',
        'ckeditor',
      );

      if (!$imagePath) {
        Log::error('Falha ao salvar arquivo com UploadHelper');
        return response()->json(
          [
            'uploaded' => false,
            'error' => [
              'message' => 'Falha ao salvar arquivo.',
            ],
          ],
          500,
        );
      }

      // Gerar URL da imagem
      $url = UploadHelper::getPublicUrl($imagePath);

      Log::info('Upload realizado com sucesso', [
        'path' => $imagePath,
        'url' => $url,
        'filename' => $file->getClientOriginalName(),
      ]);

      return response()->json([
        'uploaded' => true,
        'url' => $url,
        'fileName' => $file->getClientOriginalName(),
      ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
      Log::error('Erro de validação no upload', [
        'errors' => $e->errors(),
        'message' => $e->getMessage(),
      ]);

      return response()->json(
        [
          'uploaded' => false,
          'error' => [
            'message' =>
              'Arquivo inválido: ' .
              implode(', ', $e->validator->errors()->all()),
          ],
        ],
        422,
      );
    } catch (\Exception $e) {
      Log::error('Erro geral no upload', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
      ]);

      return response()->json(
        [
          'uploaded' => false,
          'error' => [
            'message' => 'Erro interno do servidor: ' . $e->getMessage(),
          ],
        ],
        500,
      );
    }
  }

  /**
   * Upload de arquivos (PDF, DOC, etc.) para o CKEditor
   */
  public function uploadCKEditorFile(Request $request)
  {
    try {
      Log::info('Upload de arquivo CKEditor iniciado', [
        'user_id' => auth()->id(),
        'files' => $request->allFiles(),
        'method' => $request->method(),
      ]);

      // Validar a requisição
      $request->validate([
        'upload' =>
          'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar|max:10240', // 10MB max
      ]);

      $file = $request->file('upload');

      if (!$file || !$file->isValid()) {
        Log::error('Arquivo inválido ou não enviado');
        return response()->json(
          [
            'uploaded' => false,
            'error' => [
              'message' => 'Arquivo inválido ou não enviado.',
            ],
          ],
          400,
        );
      }

      // Usar UploadHelper para upload de arquivo temporário
      $filePath = UploadHelper::uploadImage(
        // Reutilizar método para files também
        $file,
        'noticias',
        'temp',
        'files',
      );

      if (!$filePath) {
        Log::error('Falha ao salvar arquivo com UploadHelper');
        return response()->json(
          [
            'uploaded' => false,
            'error' => [
              'message' => 'Falha ao salvar arquivo.',
            ],
          ],
          500,
        );
      }

      // Gerar URL do arquivo
      $url = UploadHelper::getPublicUrl($filePath);

      // Calcular tamanho do arquivo
      $fileSize = $this->formatFileSize($file->getSize());

      Log::info('Upload de arquivo realizado com sucesso', [
        'path' => $filePath,
        'url' => $url,
        'size' => $fileSize,
        'filename' => $file->getClientOriginalName(),
        'mime_type' => $file->getMimeType(),
      ]);

      return response()->json([
        'uploaded' => true,
        'url' => $url,
        'fileName' => $file->getClientOriginalName(),
        'fileSize' => $fileSize,
        'mimeType' => $file->getMimeType(),
      ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
      Log::error('Erro de validação no upload de arquivo', [
        'errors' => $e->errors(),
        'message' => $e->getMessage(),
      ]);

      return response()->json(
        [
          'uploaded' => false,
          'error' => [
            'message' =>
              'Arquivo inválido: ' .
              implode(', ', $e->validator->errors()->all()),
          ],
        ],
        422,
      );
    } catch (\Exception $e) {
      Log::error('Erro no upload de arquivo', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
      ]);

      return response()->json(
        [
          'uploaded' => false,
          'error' => [
            'message' => 'Erro no upload: ' . $e->getMessage(),
          ],
        ],
        500,
      );
    }
  }

  /**
   * Formatar tamanho do arquivo
   */
  private function formatFileSize($bytes)
  {
    if ($bytes == 0) {
      return '0 Bytes';
    }

    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));

    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
  }

  /**
   * Obter ícone baseado no tipo de arquivo
   */
  private function getFileIcon($mimeType, $extension = null)
  {
    // Mapear tipos MIME para ícones
    $iconMap = [
      'application/pdf' => '📄',
      'application/msword' => '📝',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' =>
        '📝',
      'application/vnd.ms-excel' => '📊',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' =>
        '📊',
      'application/vnd.ms-powerpoint' => '📎',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation' =>
        '📎',
      'text/plain' => '📃',
      'application/zip' => '🗜️',
      'application/x-rar-compressed' => '🗜️',
    ];

    // Tentar pelo tipo MIME primeiro
    if (isset($iconMap[$mimeType])) {
      return $iconMap[$mimeType];
    }

    // Fallback por extensão se fornecida
    if ($extension) {
      $extensionMap = [
        'pdf' => '📄',
        'doc' => '📝',
        'docx' => '📝',
        'xls' => '📊',
        'xlsx' => '📊',
        'ppt' => '📎',
        'pptx' => '📎',
        'txt' => '📃',
        'zip' => '🗜️',
        'rar' => '🗜️',
      ];

      $ext = strtolower($extension);
      if (isset($extensionMap[$ext])) {
        return $extensionMap[$ext];
      }
    }

    // Ícone padrão
    return '📄';
  }

  /**
   * Upload de múltiplos arquivos (se necessário no futuro)
   */
  public function uploadMultipleFiles(Request $request)
  {
    try {
      Log::info('Upload de múltiplos arquivos iniciado', [
        'user_id' => auth()->id(),
        'file_count' => count($request->allFiles()),
      ]);

      $request->validate([
        'files' => 'required|array|max:5', // Máximo 5 arquivos
        'files.*' =>
          'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar|max:10240',
      ]);

      $uploadedFiles = [];
      $errors = [];

      foreach ($request->file('files') as $index => $file) {
        try {
          if (!$file->isValid()) {
            $errors[] = "Arquivo {$index}: Arquivo inválido";
            continue;
          }

          $filePath = UploadHelper::uploadImage(
            $file,
            'noticias',
            'temp',
            'files',
          );

          if ($filePath) {
            $uploadedFiles[] = [
              'url' => UploadHelper::getPublicUrl($filePath),
              'fileName' => $file->getClientOriginalName(),
              'fileSize' => $this->formatFileSize($file->getSize()),
              'mimeType' => $file->getMimeType(),
              'icon' => $this->getFileIcon(
                $file->getMimeType(),
                $file->getClientOriginalExtension(),
              ),
            ];
          } else {
            $errors[] = "Arquivo {$index}: Falha no upload";
          }
        } catch (\Exception $e) {
          $errors[] = "Arquivo {$index}: " . $e->getMessage();
        }
      }

      Log::info('Upload múltiplo concluído', [
        'uploaded_count' => count($uploadedFiles),
        'error_count' => count($errors),
      ]);

      return response()->json([
        'success' => count($uploadedFiles) > 0,
        'uploaded_files' => $uploadedFiles,
        'errors' => $errors,
        'summary' => [
          'total' => count($request->file('files')),
          'uploaded' => count($uploadedFiles),
          'failed' => count($errors),
        ],
      ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
      Log::error('Erro de validação no upload múltiplo', [
        'errors' => $e->errors(),
        'message' => $e->getMessage(),
      ]);

      return response()->json(
        [
          'success' => false,
          'error' =>
            'Erro de validação: ' .
            implode(', ', $e->validator->errors()->all()),
        ],
        422,
      );
    } catch (\Exception $e) {
      Log::error('Erro no upload múltiplo', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json(
        [
          'success' => false,
          'error' => 'Erro interno: ' . $e->getMessage(),
        ],
        500,
      );
    }
  }

  /**
   * Limpar arquivos temporários antigos (pode ser executado via cron)
   */
  public function cleanupTempFiles()
  {
    try {
      $tempPath = 'noticias/temp';
      $files = Storage::allFiles($tempPath);
      $deletedCount = 0;
      $now = now();

      foreach ($files as $file) {
        $lastModified = Storage::lastModified($file);
        $fileAge = $now->diffInHours(
          Carbon::createFromTimestamp($lastModified),
        );

        // Remover arquivos temporários com mais de 24 horas
        if ($fileAge > 24) {
          Storage::delete($file);
          $deletedCount++;
        }
      }

      Log::info('Limpeza de arquivos temporários concluída', [
        'deleted_files' => $deletedCount,
        'total_files_checked' => count($files),
      ]);

      return response()->json([
        'success' => true,
        'deleted_files' => $deletedCount,
        'message' => "Limpeza concluída. {$deletedCount} arquivo(s) temporário(s) removido(s).",
      ]);
    } catch (\Exception $e) {
      Log::error('Erro na limpeza de arquivos temporários', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json(
        [
          'success' => false,
          'error' => 'Erro na limpeza: ' . $e->getMessage(),
        ],
        500,
      );
    }
  }

  /**
   * Verificar status dos uploads temporários
   */
  public function getTempFilesStatus()
  {
    try {
      $tempPath = 'noticias/temp';
      $files = Storage::allFiles($tempPath);
      $now = now();
      $fileInfo = [];

      foreach ($files as $file) {
        $lastModified = Storage::lastModified($file);
        $fileAge = $now->diffInHours(
          Carbon::createFromTimestamp($lastModified),
        );
        $fileSize = Storage::size($file);

        $fileInfo[] = [
          'path' => $file,
          'name' => basename($file),
          'size' => $this->formatFileSize($fileSize),
          'age_hours' => round($fileAge, 1),
          'last_modified' => Carbon::createFromTimestamp($lastModified)->format(
            'd/m/Y H:i:s',
          ),
          'expires_in' => max(0, 24 - $fileAge) . ' horas',
          'url' => Storage::url($file),
        ];
      }

      // Ordenar por idade (mais antigos primeiro)
      usort($fileInfo, function ($a, $b) {
        return $b['age_hours'] <=> $a['age_hours'];
      });

      return response()->json([
        'success' => true,
        'total_files' => count($files),
        'total_size' => $this->formatFileSize(
          array_sum(
            array_map(function ($file) {
              return Storage::size($file);
            }, $files),
          ),
        ),
        'files' => $fileInfo,
      ]);
    } catch (\Exception $e) {
      Log::error('Erro ao verificar status dos arquivos temporários', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json(
        [
          'success' => false,
          'error' => 'Erro ao verificar status: ' . $e->getMessage(),
        ],
        500,
      );
    }
  }

  /**
   * Forçar limpeza de arquivo específico
   */
  public function deleteTempFile(Request $request)
  {
    try {
      $request->validate([
        'file_path' => 'required|string',
      ]);

      $filePath = $request->input('file_path');

      // Verificar se o arquivo está na pasta temp
      if (!str_starts_with($filePath, 'noticias/temp/')) {
        return response()->json(
          [
            'success' => false,
            'error' =>
              'Apenas arquivos temporários podem ser removidos por esta rota.',
          ],
          400,
        );
      }

      if (Storage::exists($filePath)) {
        Storage::delete($filePath);

        Log::info('Arquivo temporário removido manualmente', [
          'file_path' => $filePath,
          'user_id' => auth()->id(),
        ]);

        return response()->json([
          'success' => true,
          'message' => 'Arquivo removido com sucesso.',
        ]);
      } else {
        return response()->json(
          [
            'success' => false,
            'error' => 'Arquivo não encontrado.',
          ],
          404,
        );
      }
    } catch (\Illuminate\Validation\ValidationException $e) {
      return response()->json(
        [
          'success' => false,
          'error' =>
            'Dados inválidos: ' . implode(', ', $e->validator->errors()->all()),
        ],
        422,
      );
    } catch (\Exception $e) {
      Log::error('Erro ao remover arquivo temporário', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json(
        [
          'success' => false,
          'error' => 'Erro interno: ' . $e->getMessage(),
        ],
        500,
      );
    }
  }

  /**
   * Validar integridade de arquivo
   */
  public function validateFile(Request $request)
  {
    try {
      $request->validate([
        'file_path' => 'required|string',
      ]);

      $filePath = $request->input('file_path');

      if (!Storage::exists($filePath)) {
        return response()->json(
          [
            'valid' => false,
            'error' => 'Arquivo não encontrado.',
          ],
          404,
        );
      }

      $fileSize = Storage::size($filePath);
      $mimeType = Storage::mimeType($filePath);
      $lastModified = Storage::lastModified($filePath);

      // Verificações básicas de integridade
      $validations = [
        'exists' => Storage::exists($filePath),
        'readable' => is_readable(Storage::path($filePath)),
        'size_valid' => $fileSize > 0 && $fileSize <= 10 * 1024 * 1024, // Máx 10MB
        'mime_valid' => in_array($mimeType, [
          'application/pdf',
          'application/msword',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'application/vnd.ms-excel',
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          'application/vnd.ms-powerpoint',
          'application/vnd.openxmlformats-officedocument.presentationml.presentation',
          'text/plain',
          'application/zip',
          'application/x-rar-compressed',
        ]),
      ];

      $isValid = array_reduce(
        $validations,
        function ($carry, $item) {
          return $carry && $item;
        },
        true,
      );

      return response()->json([
        'valid' => $isValid,
        'validations' => $validations,
        'file_info' => [
          'path' => $filePath,
          'size' => $this->formatFileSize($fileSize),
          'mime_type' => $mimeType,
          'last_modified' => Carbon::createFromTimestamp($lastModified)->format(
            'd/m/Y H:i:s',
          ),
          'icon' => $this->getFileIcon($mimeType),
        ],
      ]);
    } catch (\Exception $e) {
      Log::error('Erro na validação de arquivo', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json(
        [
          'valid' => false,
          'error' => 'Erro na validação: ' . $e->getMessage(),
        ],
        500,
      );
    }
  }
}
