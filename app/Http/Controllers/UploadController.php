<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Helpers\UploadHelper;

class UploadController extends Controller
{
    /**
     * Constructor para aplicar middleware de autenticação
     */
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

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
                'session_token' => session()->token()
            ]);

            // Validar a requisição
            $request->validate([
                'upload' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
            ]);

            $file = $request->file('upload');
            
            if (!$file || !$file->isValid()) {
                Log::error('Arquivo inválido ou não enviado');
                return response()->json([
                    'uploaded' => false,
                    'error' => [
                        'message' => 'Arquivo inválido ou não enviado.'
                    ]
                ], 400);
            }

            // Usar o UploadHelper para fazer upload temporário
            $imagePath = UploadHelper::uploadImage(
                $file,
                'noticias',
                'temp',
                'ckeditor'
            );
            
            if (!$imagePath) {
                Log::error('Falha ao salvar arquivo com UploadHelper');
                return response()->json([
                    'uploaded' => false,
                    'error' => [
                        'message' => 'Falha ao salvar arquivo.'
                    ]
                ], 500);
            }

            // Gerar URL da imagem
            $url = UploadHelper::getPublicUrl($imagePath);
            
            Log::info('Upload realizado com sucesso', [
                'path' => $imagePath,
                'url' => $url,
                'filename' => $file->getClientOriginalName()
            ]);

            return response()->json([
                'uploaded' => true,
                'url' => $url,
                'fileName' => $file->getClientOriginalName()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Erro de validação no upload', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'uploaded' => false,
                'error' => [
                    'message' => 'Arquivo inválido: ' . implode(', ', $e->validator->errors()->all())
                ]
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Erro geral no upload', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'uploaded' => false,
                'error' => [
                    'message' => 'Erro interno do servidor: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Upload de arquivos (PDF, DOC, etc.) para o CKEditor
     */
    public function uploadCKEditorFile(Request $request)
    {
        try {
            Log::info('Upload de arquivo CKEditor iniciado');

            // Validar a requisição
            $request->validate([
                'upload' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar|max:10240', // 10MB max
            ]);

            $file = $request->file('upload');
            
            if (!$file || !$file->isValid()) {
                return response()->json([
                    'uploaded' => false,
                    'error' => [
                        'message' => 'Arquivo inválido ou não enviado.'
                    ]
                ], 400);
            }

            // Usar UploadHelper para upload de arquivo
            $filePath = UploadHelper::uploadImage( // Reutilizar método para files também
                $file,
                'noticias',
                'temp',
                'files'
            );
            
            if (!$filePath) {
                return response()->json([
                    'uploaded' => false,
                    'error' => [
                        'message' => 'Falha ao salvar arquivo.'
                    ]
                ], 500);
            }

            // Gerar URL do arquivo
            $url = UploadHelper::getPublicUrl($filePath);
            
            // Calcular tamanho do arquivo
            $fileSize = $this->formatFileSize($file->getSize());
            
            Log::info('Upload de arquivo realizado com sucesso', [
                'path' => $filePath,
                'url' => $url,
                'size' => $fileSize
            ]);

            return response()->json([
                'uploaded' => true,
                'url' => $url,
                'fileName' => $file->getClientOriginalName(),
                'fileSize' => $fileSize
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no upload de arquivo', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'uploaded' => false,
                'error' => [
                    'message' => 'Erro no upload: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Formatar tamanho do arquivo
     */
    private function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}