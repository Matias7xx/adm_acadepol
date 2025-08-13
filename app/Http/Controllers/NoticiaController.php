<?php

namespace App\Http\Controllers;

use App\Models\Noticia;
use App\Helpers\UploadHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NoticiaController extends Controller
{
    /**
     * Lista todas as notícias publicadas no site PÚBLICO
     */
    public function index(Request $request)
    {
        $noticias = Noticia::publicado()
            ->when($request->search, function($query, $search) {
                $searchTerm = trim($search);
                if (!empty($searchTerm)) {
                    $query->where(function($q) use ($searchTerm) {
                        // Converter tanto o termo de busca quanto os campos para minúsculas
                        $searchLower = strtolower($searchTerm);
                        
                        $q->whereRaw('LOWER(titulo) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(descricao_curta) LIKE ?', ["%{$searchLower}%"]);
                    });
                }
            })
            // NÃO FILTRAR POR DESTAQUE
            ->orderBy('data_publicacao', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(6)
            ->withQueryString();

        // Transformar dados para garantir URLs corretas das imagens
        $noticias->getCollection()->transform(function($noticia) {
            $noticia->imagem = $noticia->imagem ? UploadHelper::getPublicUrl($noticia->imagem) : null;
            return $noticia;
        });
            
        return Inertia::render('Components/Noticias', [
            'noticias' => $noticias,
            'filtros' => $request->only(['search']),
        ]);
    }

    /**
     * Exibe uma notícia específica
     */
    public function exibir($id)
    {
        if (!is_numeric($id)) {
            abort(404, 'Notícia não encontrada');
        }

        $noticia = Noticia::where('id', $id)
            ->where('status', 'publicado')
            ->firstOrFail();
            
        $this->incrementVisualizacoes($noticia);
            
        $proximaNoticia = Noticia::publicado()
            ->where(function($query) use ($noticia) {
                $query->where('data_publicacao', $noticia->data_publicacao)
                      ->where('id', '>', $noticia->id)
                      ->orWhere('data_publicacao', '>', $noticia->data_publicacao);
            })
            ->orderBy('data_publicacao', 'asc')
            ->orderBy('id', 'asc')
            ->first();
            
        $noticiaAnterior = Noticia::publicado()
            ->where(function($query) use ($noticia) {
                $query->where('data_publicacao', $noticia->data_publicacao)
                      ->where('id', '<', $noticia->id)
                      ->orWhere('data_publicacao', '<', $noticia->data_publicacao);
            })
            ->orderBy('data_publicacao', 'desc')
            ->orderBy('id', 'desc')
            ->first();
            
        return Inertia::render('Components/ExibirNoticia', [
            'noticia' => [
                'id' => $noticia->id,
                'titulo' => $noticia->titulo,
                'descricao_curta' => $noticia->descricao_curta,
                'conteudo' => $noticia->conteudo,
                'imagem' => $noticia->imagem ? UploadHelper::getPublicUrl($noticia->imagem) : null,
                'destaque' => $noticia->destaque,
                'data_publicacao' => $noticia->data_formatada,
                'data_publicacao_iso' => $noticia->data_publicacao->toIso8601String(),
                'updated_at_iso' => $noticia->updated_at->toIso8601String(),
                'visualizacoes' => $noticia->visualizacoes,
            ],
            'proximaNoticia' => $proximaNoticia ? [
                'id' => $proximaNoticia->id,
                'titulo' => $proximaNoticia->titulo,
            ] : null,
            'noticiaAnterior' => $noticiaAnterior ? [
                'id' => $noticiaAnterior->id,
                'titulo' => $noticiaAnterior->titulo,
            ] : null,
        ]);
    }

    /**
     * Retorna as últimas notícias em formato JSON para componentes
     * Usado na HOME para exibir as 3 últimas notícias
     */
    public function ultimasNoticias()
    {
        $cacheKey = 'noticias_destaque_banner';
        
        $noticias = \Cache::remember($cacheKey, now()->addMinutes(10), function() {
            return Noticia::where('status', 'publicado')
                ->where('data_publicacao', '<=', now())
                ->where('destaque', true) // <<<< SÓ DESTAQUES
                ->whereNull('deleted_at')
                ->orderBy('data_publicacao', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->take(3)
                ->get()
                ->map(function($noticia) {
                    return [
                        'id' => $noticia->id,
                        'titulo' => $noticia->titulo,
                        'descricao_curta' => $noticia->descricao_curta,
                        'imagem' => $noticia->imagem ? UploadHelper::getPublicUrl($noticia->imagem) : null,
                        'data_publicacao' => $noticia->data_formatada,
                        'destaque' => $noticia->destaque,
                        'visualizacoes' => $noticia->visualizacoes
                    ];
                });
        });
            
        return response()->json($noticias);
    }

    public function noticiasHome()
    {
        $cacheKey = 'noticias_home_lista';
        
        $noticias = \Cache::remember($cacheKey, now()->addMinutes(10), function() {
            return Noticia::where('status', 'publicado')
                ->where('data_publicacao', '<=', now())
                ->whereNull('deleted_at')
                ->orderBy('data_publicacao', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->take(6)
                ->get()
                ->map(function($noticia) {
                    return [
                        'id' => $noticia->id,
                        'titulo' => $noticia->titulo,
                        'descricao_curta' => $noticia->descricao_curta,
                        'imagem' => $noticia->imagem ? UploadHelper::getPublicUrl($noticia->imagem) : null,
                        'data_publicacao' => $noticia->data_formatada,
                        'destaque' => $noticia->destaque,
                        'visualizacoes' => $noticia->visualizacoes
                    ];
                });
        });
            
        return response()->json($noticias);
    }

    /**
     * notícias com paginação
     */
    public function apiNoticias(Request $request)
    {
        $perPage = $request->input('per_page', 6); // 6 como padrão
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        
        // Validar e limitar os itens por página
        $perPage = min(max($perPage, 3), 10);
        
        $noticias = Noticia::where('status', 'publicado')
            ->where('data_publicacao', '<=', now())
            ->whereNull('deleted_at')
            ->when($search, function($query, $search) {
                $searchTerm = trim($search);
                if (!empty($searchTerm)) {
                    $query->where(function($q) use ($searchTerm) {
                        // Converter tanto o termo de busca quanto os campos para minúsculas
                        $searchLower = strtolower($searchTerm);
                        
                        $q->whereRaw('LOWER(titulo) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(descricao_curta) LIKE ?', ["%{$searchLower}%"]);
                    });
                }
            })
            ->orderBy('data_publicacao', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $noticias->getCollection()->transform(function($noticia) {
            return [
                'id' => $noticia->id,
                'titulo' => $noticia->titulo,
                'descricao_curta' => $noticia->descricao_curta,
                'imagem' => $noticia->imagem ? UploadHelper::getPublicUrl($noticia->imagem) : null,
                'data_publicacao' => $noticia->data_formatada,
                'destaque' => $noticia->destaque,
                'visualizacoes' => $noticia->visualizacoes
            ];
        });
        
        return response()->json($noticias);
    }

    /**
     * Página para listar todas as notícias
     */
    public function listarTodas(Request $request)
    {         
        return Inertia::render('Components/NoticiaListagem', [
            'filtros' => $request->only(['search']),
        ]);
    }

    /**
     * Incrementa visualizações de forma otimizada
     */
    private function incrementVisualizacoes($noticia)
    {
        try {
            $sessionKey = 'viewed_noticia_' . $noticia->id;
            
            if (!session()->has($sessionKey)) {
                $noticia->incrementarVisualizacoes();
                session()->put($sessionKey, true);
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao incrementar visualizações da notícia ' . $noticia->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Método para invalidar caches
     */
    public static function invalidarTodosOsCaches()
    {
        try {
            \Cache::forget('noticias_destaque_banner');  // Banner
            \Cache::forget('noticias_home_lista');       // Lista da home
            
            // Invalidar caches da API paginada
            for ($page = 1; $page <= 20; $page++) {
                for ($perPage = 3; $perPage <= 10; $perPage++) {
                    // Diferentes combinações de chave de cache
                    $patterns = [
                        'noticias_api_' . md5($perPage . '__' . $page),
                        'noticias_api_' . md5($perPage . '_' . '' . '_' . $page),
                        'noticias_api_' . md5($perPage . '_' . $page),
                    ];
                    
                    foreach ($patterns as $pattern) {
                        \Cache::forget($pattern);
                    }
                }
            }
            
            // Invalidar caches com termos de busca comuns
            $searchTerms = ['', 'noticia', 'novo', 'curso', 'treinamento', 'edital'];
            foreach ($searchTerms as $term) {
                for ($page = 1; $page <= 10; $page++) {
                    for ($perPage = 3; $perPage <= 10; $perPage++) {
                        $cacheKey = 'noticias_api_' . md5($perPage . '_' . $term . '_' . $page);
                        \Cache::forget($cacheKey);
                    }
                }
            }
            
            Log::info('Todos os caches de notícias invalidados');
            
        } catch (\Exception $e) {
            Log::warning('Erro ao invalidar caches: ' . $e->getMessage());
        }
    }
}