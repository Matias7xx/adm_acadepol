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
                $query->where(function($q) use ($search) {
                    $q->where('titulo', 'like', "%{$search}%")
                    ->orWhere('descricao_curta', 'like', "%{$search}%");
                });
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
        
        $noticias = \Cache::remember($cacheKey, now()->addMinutes(30), function() {
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
        
        $noticias = \Cache::remember($cacheKey, now()->addMinutes(30), function() {
            return Noticia::where('status', 'publicado')
                ->where('data_publicacao', '<=', now())
                ->whereNull('deleted_at')
                // NÃO FILTRAR POR DESTAQUE - TODAS AS PUBLICADAS
                ->orderBy('data_publicacao', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->take(6) // ou quantas você quiser na home
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
     * API para listar notícias paginadas com suporte a busca
     * Rota: /api/noticias
     */
        public function apiNoticias(Request $request)
    {
        $perPage = $request->input('per_page', 5);
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        
        // Validar e limitar os itens por página
        $perPage = min(max($perPage, 3), 6);
        
        // Criar chave de cache única baseada nos parâmetros
        $cacheKey = 'noticias_api_' . md5($perPage . '_' . $search . '_' . $page);
        
        $result = \Cache::remember($cacheKey, now()->addMinutes(15), function() use ($request, $perPage) {
            $noticias = Noticia::where('status', 'publicado')
                ->where('data_publicacao', '<=', now())
                ->whereNull('deleted_at')
                // NÃO FILTRAR POR DESTAQUE
                ->when($request->search, function($query, $search) {
                    $query->where(function($q) use ($search) {
                        $q->where('titulo', 'like', "%{$search}%")
                        ->orWhere('descricao_curta', 'like', "%{$search}%");
                    });
                })
                ->orderBy('data_publicacao', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            // Transformar os dados para o formato esperado pelo frontend
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
            
            return $noticias;
        });
        
        return response()->json($result);
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

    public static function invalidarTodosOsCaches()
    {
        try {
            \Cache::forget('noticias_destaque_banner');  // Banner
            \Cache::forget('noticias_home_lista');       // Lista da home
            
            // Invalidar caches da API paginada
            for ($page = 1; $page <= 10; $page++) {
                for ($perPage = 3; $perPage <= 6; $perPage++) {
                    $cacheKey = 'noticias_api_' . md5($perPage . '__' . $page);
                    \Cache::forget($cacheKey);
                }
            }
            
            Log::info('Todos os caches de notícias invalidados');
            
        } catch (\Exception $e) {
            Log::warning('Erro ao invalidar caches: ' . $e->getMessage());
        }
    }
}