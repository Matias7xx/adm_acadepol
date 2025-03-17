<?php

namespace App\Http\Controllers;

use App\Models\Noticia;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            ->where('data_publicacao', '>', $noticia->data_publicacao)
            ->orderBy('data_publicacao', 'asc')
            ->first();
            
        $noticiaAnterior = Noticia::publicado()
            ->where('data_publicacao', '<', $noticia->data_publicacao)
            ->orderBy('data_publicacao', 'desc')
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
     */
    public function ultimasNoticias()
    {
        $noticias = Noticia::publicado()
            ->orderBy('data_publicacao', 'desc')
            ->take(6)
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
}