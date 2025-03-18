<?php

namespace App\Http\Controllers;

use App\Models\Noticia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

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
            ->orderBy('data_publicacao', 'desc')
            ->paginate(6)
            ->withQueryString();
            
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
        $noticia = Noticia::where('id', $id)
            ->where('status', 'publicado')
            ->firstOrFail();
            
        // Incrementa visualizações
        $noticia->incrementarVisualizacoes();
            
        // Próxima e anterior
        $proximaNoticia = Noticia::publicado()
            ->where(function($query) use ($noticia) {
                //Mesma data mas ID maior (foi publicada depois)
                $query->where('data_publicacao', $noticia->data_publicacao)
                      ->where('id', '>', $noticia->id)
                //OU data posterior
                      ->orWhere('data_publicacao', '>', $noticia->data_publicacao);
            })
            ->orderBy('data_publicacao', 'asc')
            ->orderBy('id', 'asc')
            ->first();
            
        $noticiaAnterior = Noticia::publicado()
            ->where(function($query) use ($noticia) {
                //Mesma data mas ID menor (foi publicada antes)
                $query->where('data_publicacao', $noticia->data_publicacao)
                      ->where('id', '<', $noticia->id)
                //OU data anterior
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
                'imagem' => $noticia->imagem,
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
     * Retorna as últimas notícias em formato JSON para componentes / Está retornando para as notícias da HOME
     */
    public function ultimasNoticias()
    {
        $noticias = Noticia::publicado()
            ->orderBy('data_publicacao', 'desc')
            ->take(3)
            ->get()
            ->map(function($noticia) {
                return [
                    'id' => $noticia->id,
                    'titulo' => $noticia->titulo,
                    'descricao_curta' => $noticia->descricao_curta,
                    'imagem' => $noticia->imagem,
                    'data_publicacao' => $noticia->data_formatada,
                    'destaque' => $noticia->destaque,
                    'visualizacoes' => $noticia->visualizacoes
                ];
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
        
        // Validar e limitar os itens por página para evitar sobrecarga em VER TODAS AS NOTÍCIAS
        $perPage = min(max($perPage, 3), 6);
        
        $noticias = Noticia::publicado()
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('titulo', 'like', "%{$search}%")
                      ->orWhere('descricao_curta', 'like', "%{$search}%");
                });
            })
            ->orderBy('data_publicacao', 'desc')
            ->paginate($perPage);

        // Transformar os dados para o formato esperado pelo frontend
        $noticias->getCollection()->transform(function($noticia) {
            return [
                'id' => $noticia->id,
                'titulo' => $noticia->titulo,
                'descricao_curta' => $noticia->descricao_curta,
                'imagem' => $noticia->imagem,
                'data_publicacao' => $noticia->data_formatada,
                'destaque' => $noticia->destaque,
                'visualizacoes' => $noticia->visualizacoes
            ];
        });
        
        return response()->json($noticias);
    }

    public function ListarTodas(Request $request)
    {         
        return Inertia::render('Components/NoticiaListagem', []);
    }
}