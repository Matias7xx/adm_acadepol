<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class UploadHelper
{
    /**
     * Faz upload de uma imagem e retorna o caminho relativo
     */
    public static function uploadImage(UploadedFile $file, string $categoria, string $identificador = null, string $tipo = 'default'): ?string
    {
        try {
            // Sanitizar o identificador para nome de pasta
            $folderName = $identificador ? self::sanitizeFolderName($identificador) : 'geral';
            
            // Criar estrutura de pastas: categoria/identificador/tipo
            $directory = $categoria . '/' . $folderName . ($tipo !== 'default' ? '/' . $tipo : '');
            
            // Gerar nome único do arquivo
            $extension = $file->getClientOriginalExtension();
            $filename = $tipo . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Fazer upload
            $path = $file->storeAs($directory, $filename, 'public');
            
            return $path; // Retorna: categoria/identificador/tipo/arquivo.ext
            
        } catch (\Exception $e) {
            \Log::error('Erro no upload da imagem: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Faz upload de uma imagem base64 e retorna o caminho relativo
     */
    public static function uploadBase64Image(string $base64Data, string $categoria, string $identificador = null, string $tipo = 'default', string $extension = 'png'): ?string
    {
        try {
            // Extrair dados da string base64
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $base64Data, $matches)) {
                $extension = $matches[1];
                $data = base64_decode($matches[2]);
            } else {
                return null;
            }
            
            // Sanitizar o identificador para nome de pasta
            $folderName = $identificador ? self::sanitizeFolderName($identificador) : 'geral';
            
            // Criar estrutura de pastas
            $directory = $categoria . '/' . $folderName . ($tipo !== 'default' ? '/' . $tipo : '');
            
            // Gerar nome único do arquivo
            $filename = $tipo . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Caminho completo
            $path = $directory . '/' . $filename;
            
            // Salvar arquivo
            if (Storage::disk('public')->put($path, $data)) {
                return $path;
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::error('Erro no upload da imagem base64: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Move uma imagem de um local para outro
     */
    public static function moveImage(string $oldPath, string $categoria, string $novoIdentificador, string $tipo = 'default'): ?string
    {
        try {
            if (!Storage::disk('public')->exists($oldPath)) {
                return null;
            }
            
            // Sanitizar o novo identificador
            $folderName = self::sanitizeFolderName($novoIdentificador);
            
            // Novo diretório
            $newDirectory = $categoria . '/' . $folderName . ($tipo !== 'default' ? '/' . $tipo : '');
            
            // Manter o nome do arquivo original
            $filename = basename($oldPath);
            $newPath = $newDirectory . '/' . $filename;
            
            // Criar diretório se não existir
            $fullDir = storage_path('app/public/' . $newDirectory);
            if (!file_exists($fullDir)) {
                mkdir($fullDir, 0755, true);
            }
            
            // Mover arquivo
            if (Storage::disk('public')->move($oldPath, $newPath)) {
                return $newPath;
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::error('Erro ao mover imagem: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Deleta uma imagem
     */
    public static function deleteImage(string $path): bool
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->delete($path);
            }
            return true; // Se não existe, considera como "deletado"
            
        } catch (\Exception $e) {
            \Log::error('Erro ao deletar imagem: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna a URL pública de uma imagem
     */
    public static function getPublicUrl(string $path): string
    {
        // Se já é uma URL completa, retorna como está
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        
        // Se já tem o prefixo /storage/, retorna com asset
        if (str_starts_with($path, '/storage/')) {
            return asset($path);
        }
        
        // Se é um caminho do storage sem /storage/, adiciona
        return asset('storage/' . $path);
    }

    /**
     * Sanitiza nome para ser usado como nome de pasta
     */
    public static function sanitizeFolderName(string $name): string
    {
        // Remove acentos e caracteres especiais
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        
        // Remove caracteres não alfanuméricos exceto espaços e hífens
        $name = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $name);
        
        // Substitui espaços por hífens
        $name = preg_replace('/\s+/', '-', $name);
        
        // Remove hífens múltiplos
        $name = preg_replace('/-+/', '-', $name);
        
        // Remove hífens do início e fim
        $name = trim($name, '-');
        
        // Se ficou vazio, usar um nome padrão
        if (empty($name)) {
            $name = 'sem-titulo';
        }
        
        return strtolower($name);
    }

    /**
     * Limpa pasta vazia
     */
    public static function cleanupEmptyFolder(string $folderPath): bool
    {
        try {
            $fullPath = storage_path('app/public/' . $folderPath);
            
            if (is_dir($fullPath)) {
                $files = array_diff(scandir($fullPath), ['.', '..']);
                
                if (empty($files)) {
                    rmdir($fullPath);
                    
                    // Tentar limpar pasta pai se também estiver vazia
                    $parentPath = dirname($folderPath);
                    if ($parentPath !== '.' && $parentPath !== '/') {
                        self::cleanupEmptyFolder($parentPath);
                    }
                    
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            \Log::error('Erro ao limpar pasta: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Migra imagens antigas para o novo sistema
     */
    public static function migrateOldImages(): void
    {
        // Esta função pode ser usada para migrar imagens antigas se necessário
        \Log::info('Iniciando migração de imagens antigas...');
        
        // Implementar lógica de migração se necessário
        
        \Log::info('Migração de imagens concluída.');
    }
}