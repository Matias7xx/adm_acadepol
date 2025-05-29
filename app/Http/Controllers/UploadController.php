<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Upload de imagens para o CKEditor
     */
    public function uploadCKEditorImage(Request $request)
    {
        try {
            // Validar a requisição
            $request->validate([
                'upload' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $file = $request->file('upload');
            
            if (!$file) {
                return response()->json([
                    'uploaded' => false,
                    'error' => [
                        'message' => 'Nenhum arquivo foi enviado.'
                    ]
                ], 400);
            }

            // Gerar nome único para o arquivo
            $filename = 'ck_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            // Salvar diretamente no diretório público
            $directory = public_path('images/noticias/content');
            
            // Criar o diretório se não existir
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Mover o arquivo
            $file->move($directory, $filename);
            
            // Gerar URL da imagem
            $url = asset('images/noticias/content/' . $filename);
            
            return response()->json([
                'uploaded' => true,
                'url' => $url
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'uploaded' => false,
                'error' => [
                    'message' => 'Erro no upload: ' . $e->getMessage()
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
            // Validar a requisição
            $request->validate([
                'upload' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar|max:10240' // 10MB max
            ]);

            $file = $request->file('upload');
            
            if (!$file) {
                return response()->json([
                    'uploaded' => false,
                    'error' => [
                        'message' => 'Nenhum arquivo foi enviado.'
                    ]
                ], 400);
            }

            // Sanitizar o nome do arquivo original
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            
            // Limpar o nome do arquivo
            $baseName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $baseName);
            $baseName = preg_replace('/_+/', '_', $baseName); // Remove underscores múltiplos
            
            // Gerar nome único para o arquivo
            $filename = $baseName . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Salvar diretamente no diretório público
            $directory = public_path('files/noticias');
            
            // Criar o diretório se não existir
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Mover o arquivo
            $file->move($directory, $filename);
            
            // Gerar URL do arquivo
            $url = asset('files/noticias/' . $filename);
            
            // Calcular tamanho do arquivo
            $fileSize = $this->formatFileSize($file->getSize());
            
            return response()->json([
                'uploaded' => true,
                'url' => $url,
                'fileName' => $originalName,
                'fileSize' => $fileSize
            ]);

        } catch (\Exception $e) {
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