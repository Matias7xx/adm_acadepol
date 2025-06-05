<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Noticia;
use App\Helpers\UploadHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Mews\Purifier\Facades\Purifier;

class NoticiaController extends Controller
{
    public function index()
    {
        $this->authorize('adminViewAny', Noticia::class);
        
        $noticias = Noticia::query()
            ->when(request('search'), function($query, $search) {
                $query->where('titulo', 'like', "%{$search}%")
                      ->orWhere('descricao_curta', 'like', "%{$search}%");
            })
            ->when(request('status'), function($query, $status) {
                $query->where('status', $status);
            })
            ->when(request('destaque'), function($query, $destaque) {
                $query->where('destaque', $destaque === 'true');
            })
            ->when(request('sort'), function($query, $sortColumn) {
                $direction = 'asc';
                if (strncmp($sortColumn, '-', 1) === 0) {
                    $direction = 'desc';
                    $sortColumn = substr($sortColumn, 1);
                }
                $query->orderBy($sortColumn, $direction);
            }, function($query) {
                $query->orderBy('data_publicacao', 'desc') // Ordena por data_publicacao (mais novo primeiro)
                  ->orderBy('id', 'desc'); // Critério secundário: dentro da mesma data, ordena por id (mais novo primeiro)
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Noticias/Index', [
            'noticias' => $noticias,
            'filters' => request()->only(['search', 'status', 'destaque', 'sort']),
            'can' => [
                'create' => Auth::user()->can('adminCreate', Noticia::class),
                'edit' => Auth::user()->can('adminEdit', Noticia::class),
                'delete' => Auth::user()->can('adminDelete', Noticia::class),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('adminCreate', Noticia::class);
        
        return Inertia::render('Admin/Noticias/Create', [
            'statusOptions' => [
                ['value' => 'rascunho', 'label' => 'Rascunho'],
                ['value' => 'publicado', 'label' => 'Publicado'],
                ['value' => 'arquivado', 'label' => 'Arquivado'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('adminCreate', Noticia::class);
        
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao_curta' => 'required|string|max:500',
            'conteudo' => 'nullable|string',
            'imagem' => 'nullable|image|max:2048',
            'destaque' => 'boolean',
            'data_publicacao' => 'required|date',
            'status' => 'required|in:rascunho,publicado,arquivado',
        ]);

        // Processar imagens do conteúdo primeiro
        $validated['conteudo'] = $this->processContentImages($validated['conteudo'], $validated['titulo']);

        // Sanitizar o HTML
        $validated['conteudo'] = Purifier::clean($validated['conteudo'], [
            'HTML.Allowed' => 'p,b,i,strong,em,u,a[href|title|target],ul,ol,li,h1,h2,h3,h4,h5,blockquote,img[src|alt|width|height|class],hr,br,iframe[src|width|height|frameborder|allowfullscreen],div,span[class],video[src|controls|width|height],source[src|type]',
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%',
            'AutoFormat.RemoveEmpty' => true,
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align,width,height,margin,margin-left,margin-right',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.Linkify' => true,
        ]);
        
        // Processar upload de imagem de capa
        if ($request->hasFile('imagem')) {
            $imagePath = UploadHelper::uploadImage(
                $request->file('imagem'),
                'noticias',
                $validated['titulo'],
                'capa'
            );
            if ($imagePath) {
                $validated['imagem'] = $imagePath;
            }
        }
        
        Noticia::create($validated);

        \Cache::forget('noticias_destaque_banner');
        
        // Invalidar cache após criar notícia
        $this->invalidateAllNoticiasCache();
        
        return redirect()->route('admin.noticias.index')
            ->with('message', 'Notícia criada com sucesso.');
    }

    public function show(Noticia $noticia)
    {
        $this->authorize('adminView', $noticia);
        
        return Inertia::render('Admin/Noticias/Show', [
            'noticia' => [
                'id' => $noticia->id,
                'titulo' => $noticia->titulo,
                'descricao_curta' => $noticia->descricao_curta,
                'conteudo' => $noticia->conteudo,
                'imagem' => $noticia->imagem ? UploadHelper::getPublicUrl($noticia->imagem) : null,
                'destaque' => $noticia->destaque,
                'data_publicacao' => $noticia->data_publicacao->format('Y-m-d'),
                'data_formatada' => $noticia->data_formatada,
                'status' => $noticia->status,
                'visualizacoes' => $noticia->visualizacoes,
                'created_at' => $noticia->created_at->format('d/m/Y H:i'),
                'updated_at' => $noticia->updated_at->format('d/m/Y H:i'),
            ],
            'can' => [
                'edit' => Auth::user()->can('adminEdit', Noticia::class),
                'delete' => Auth::user()->can('adminDelete', Noticia::class),
            ],
        ]);
    }

    public function edit(Noticia $noticia)
    {
        $this->authorize('adminUpdate', $noticia);
        
        return Inertia::render('Admin/Noticias/Edit', [
            'noticia' => [
                'id' => $noticia->id,
                'titulo' => $noticia->titulo,
                'descricao_curta' => $noticia->descricao_curta,
                'conteudo' => $noticia->conteudo,
                'imagem' => $noticia->imagem ? UploadHelper::getPublicUrl($noticia->imagem) : null,
                'destaque' => $noticia->destaque,
                'data_publicacao' => $noticia->data_publicacao->format('Y-m-d'),
                'status' => $noticia->status,
            ],
            'statusOptions' => [
                ['value' => 'rascunho', 'label' => 'Rascunho'],
                ['value' => 'publicado', 'label' => 'Publicado'],
                ['value' => 'arquivado', 'label' => 'Arquivado'],
            ],
        ]);
    }

     public function update(Request $request, Noticia $noticia)
    {
        $this->authorize('adminUpdate', $noticia);
        
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao_curta' => 'required|string|max:500',
            'conteudo' => 'nullable|string',
            'imagem' => 'nullable|image|max:2048',
            'remover_imagem' => 'nullable|boolean',
            'destaque' => 'boolean',
            'data_publicacao' => 'required|date',
            'status' => 'required|in:rascunho,publicado,arquivado',
        ]);

        // Verificar se o título da notícia mudou
        $tituloAntigo = $noticia->titulo;
        $tituloNovo = $validated['titulo'];
        $tituloMudou = $tituloAntigo !== $tituloNovo;

        //Processar apenas imagens base64 novas, não reprocessar URLs existentes
        $validated['conteudo'] = $this->processOnlyNewContentImages($validated['conteudo'], $validated['titulo']);

        // Sanitizar o HTML
        $validated['conteudo'] = Purifier::clean($validated['conteudo'], [
            'HTML.Allowed' => 'p,b,i,strong,em,u,a[href|title|target],ul,ol,li,h1,h2,h3,h4,h5,blockquote,img[src|alt|width|height|class],hr,br,iframe[src|width|height|frameborder|allowfullscreen],div,span[class],video[src|controls|width|height],source[src|type]',
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%',
            'AutoFormat.RemoveEmpty' => true,
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align,width,height,margin,margin-left,margin-right',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.Linkify' => true,
        ]);
        
        // Remover imagem atual se solicitado
        if ($request->input('remover_imagem') && $noticia->imagem) {
            UploadHelper::deleteImage($noticia->imagem);
            $validated['imagem'] = null;
        }
        
        // Processar upload de nova imagem de capa
        if ($request->hasFile('imagem')) {
            // Remover imagem anterior se existir
            if ($noticia->imagem) {
                UploadHelper::deleteImage($noticia->imagem);
            }
            
            // Upload nova imagem
            $imagePath = UploadHelper::uploadImage(
                $request->file('imagem'),
                'noticias',
                $validated['titulo'],
                'capa'
            );
            if ($imagePath) {
                $validated['imagem'] = $imagePath;
            }
        } else if ($tituloMudou && $noticia->imagem) {
            // Se o título mudou mas não há nova imagem, mover a imagem existente
            $novaImagemPath = UploadHelper::moveImage(
                $noticia->imagem,
                'noticias',
                $validated['titulo'],
                'capa'
            );
            if ($novaImagemPath) {
                $validated['imagem'] = $novaImagemPath;
            }
        }
        
        // Remover campo remover_imagem antes de atualizar
        if (isset($validated['remover_imagem'])) {
            unset($validated['remover_imagem']);
        }
        
        // Atualizar notícia
        $noticia->update($validated);

        \Cache::forget('noticias_destaque_banner');
        $this->invalidateAllNoticiasCache();

        // Se o título mudou, mover todas as imagens de conteúdo para nova pasta
        if ($tituloMudou) {
            $this->moveContentImages($noticia->conteudo, $tituloAntigo, $tituloNovo);
            
            // Limpar pasta antiga
            $pastaAntiga = 'noticias/' . UploadHelper::sanitizeFolderName($tituloAntigo);
            UploadHelper::cleanupEmptyFolder($pastaAntiga);
        }
        
        // Invalidar cache após atualizar notícia
        $this->invalidateNoticiasCache();
        
        return redirect()->route('admin.noticias.index')
            ->with('message', 'Notícia atualizada com sucesso.');
    }

    /**
 * Processa APENAS imagens base64 novas, preservando URLs existentes
 */
private function processOnlyNewContentImages($html, $tituloNoticia)
    {
        if (!$html) return $html;
        
        // Busca APENAS imagens em base64 (imagens realmente novas)
        preg_match_all('/<img[^>]+src="data:image\/([^;]+);base64,([^"]+)"[^>]*>/i', $html, $matches, PREG_SET_ORDER);
        
        // Se não encontrou imagens base64, retorna o HTML inalterado
        if (empty($matches)) {
            return $html;
        }
        
        foreach ($matches as $match) {
            $extension = $match[1]; // jpeg, png, etc.
            $base64Image = $match[2];
            $fullBase64 = 'data:image/' . $extension . ';base64,' . $base64Image;
            
            // Fazer upload usando helper apenas para imagens novas
            $imagePath = UploadHelper::uploadBase64Image(
                $fullBase64,
                'noticias',
                $tituloNoticia,
                'content',
                $extension
            );
            
            if ($imagePath) {
                // Substituir APENAS esta imagem base64 específica pela URL da imagem
                $imageUrl = UploadHelper::getPublicUrl($imagePath);
                $html = str_replace($match[0], str_replace('src="data:image/'.$extension.';base64,'.$base64Image.'"', 'src="'.$imageUrl.'"', $match[0]), $html);
            }
        }
        
        return $html;
    }

    public function destroy(Noticia $noticia)
    {
        $this->authorize('adminDelete', $noticia);
        
        // Remover imagem de capa
        if ($noticia->imagem) {
            UploadHelper::deleteImage($noticia->imagem);
        }
        
        // Remover imagens do conteúdo
        $this->removeContentImages($noticia->conteudo);
        
        $tituloNoticia = $noticia->titulo;
        $noticia->delete();

        // Limpar pasta da notícia
        $pastaNoticia = 'noticias/' . UploadHelper::sanitizeFolderName($tituloNoticia);
        UploadHelper::cleanupEmptyFolder($pastaNoticia);
        
        //Invalidar cache após excluir notícia
        $this->invalidateAllNoticiasCache();
        
        return redirect()->route('admin.noticias.index')
            ->with('message', 'Notícia removida com sucesso.');
    }
    
        public function toggleDestaque(Noticia $noticia)
    {
        $this->authorize('adminUpdate', $noticia);
        
        $noticia->update([
            'destaque' => !$noticia->destaque
        ]);
        
        // INVALIDAR CACHE ESPECÍFICO DO BANNER
        \Cache::forget('noticias_destaque_banner');
        \Cache::forget('ultimas_noticias_geral');
        
        // Invalidar outros caches relacionados (se você tiver)
        $this->invalidateAllNoticiasCache();
        
        return redirect()->back()
            ->with('message', $noticia->destaque ? 'Notícia destacada.' : 'Destaque removido.');
    }

    /**
     * Processa imagens base64 do conteúdo e as salva no storage
     */
    private function processContentImages($html, $tituloNoticia)
    {
        if (!$html) return $html;
        
        // Busca imagens em base64
        preg_match_all('/<img[^>]+src="data:image\/([^;]+);base64,([^"]+)"[^>]*>/i', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $extension = $match[1]; // jpeg, png, etc.
            $base64Image = $match[2];
            $fullBase64 = 'data:image/' . $extension . ';base64,' . $base64Image;
            
            // Fazer upload usando helper
            $imagePath = UploadHelper::uploadBase64Image(
                $fullBase64,
                'noticias',
                $tituloNoticia,
                'content',
                $extension
            );
            
            if ($imagePath) {
                // Substituir a imagem base64 pela URL da imagem
                $imageUrl = UploadHelper::getPublicUrl($imagePath);
                $html = str_replace($match[0], str_replace('src="data:image/'.$extension.';base64,'.$base64Image.'"', 'src="'.$imageUrl.'"', $match[0]), $html);
            }
        }
        
        return $html;
    }

    /**
     * Move imagens do conteúdo quando o título da notícia muda
     */
    private function moveContentImages($html, $tituloAntigo, $tituloNovo)
    {
        if (!$html) return;
        
        // Buscar todas as imagens no conteúdo que são do nosso storage
        preg_match_all('/<img[^>]+src="([^"]*\/storage\/noticias\/[^"]+)"[^>]*>/i', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $imageSrc) {
                // Extrair o caminho relativo do storage
                $relativePath = str_replace('/storage/', '', parse_url($imageSrc, PHP_URL_PATH));
                
                // Mover imagem para nova pasta
                UploadHelper::moveImage(
                    $relativePath,
                    'noticias',
                    $tituloNovo,
                    'content'
                );
            }
        }
    }

    /**
     * Remove imagens do conteúdo de uma notícia
     */
    private function removeContentImages($content)
    {
        if (!$content) return;
        
        // Buscar todas as imagens no conteúdo que são do nosso storage
        preg_match_all('/<img[^>]+src="([^"]*\/storage\/noticias\/[^"]+)"[^>]*>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $imageSrc) {
                // Extrair o caminho relativo do storage
                $relativePath = str_replace('/storage/', '', parse_url($imageSrc, PHP_URL_PATH));
                
                // Remover imagem
                UploadHelper::deleteImage($relativePath);
            }
        }
    }

    private function invalidateAllNoticiasCache()
    {
        try {
            // 1. Cache do banner (só destaques)
            \Cache::forget('noticias_destaque_banner');
            
            // 2. Cache da lista da home (todas as notícias)
            \Cache::forget('noticias_home_lista');
            
            // 3. Cache da API paginada (múltiplas páginas e buscas)
            for ($page = 1; $page <= 20; $page++) {
                for ($perPage = 3; $perPage <= 6; $perPage++) {
                    // Cache sem busca
                    $cacheKey = 'noticias_api_' . md5($perPage . '__' . $page);
                    \Cache::forget($cacheKey);
                    
                    // Caches com buscas comuns
                    $searchTerms = ['', 'curso', 'acadepol', 'policia', 'capacitacao', 'treinamento'];
                    foreach ($searchTerms as $search) {
                        $cacheKey = 'noticias_api_' . md5($perPage . '_' . $search . '_' . $page);
                        \Cache::forget($cacheKey);
                    }
                }
            }
            
            // 4. Se estiver usando Redis, limpar padrões relacionados a notícias
            if (config('cache.default') === 'redis') {
                $redis = \Cache::getRedis();
                $prefix = config('cache.prefix') . ':';
                $patterns = [
                    $prefix . '*noticias*',
                    $prefix . '*ultimas_noticias*',
                    $prefix . '*noticias_home*',
                    $prefix . '*noticias_api*',
                    $prefix . '*noticias_destaque*'
                ];
                
                foreach ($patterns as $pattern) {
                    $keys = $redis->keys($pattern);
                    if (!empty($keys)) {
                        $redis->del($keys);
                    }
                }
            }
            
            // 5. Cache legado (se ainda existir)
            \Cache::forget('ultimas_noticias_' . md5('home_component'));
            
            \Log::info('Todos os caches de notícias invalidados com sucesso');
            
        } catch (\Exception $e) {
            \Log::warning('Erro ao invalidar caches de notícias: ' . $e->getMessage());
            
            // Fallback: limpar todo o cache se houver erro
            try {
                \Artisan::call('cache:clear');
                \Log::info('Cache geral limpo como fallback');
            } catch (\Exception $fallbackError) {
                \Log::error('Erro crítico ao limpar cache: ' . $fallbackError->getMessage());
            }
        }
    }
}