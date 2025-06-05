<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Director;
use App\Helpers\UploadHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DirectorController extends Controller
{
        public function index()
    {
        $this->authorize('adminViewAny', Director::class);
        $diretores = (new Director)->newQuery();

        if (request()->has('search')) {
            $diretores->where('nome', 'Like', '%'.request()->input('search').'%');
        }

        if (request()->query('sort')) {
            $attribute = request()->query('sort');
            $sort_order = 'ASC';
            if (strncmp($attribute, '-', 1) === 0) {
                $sort_order = 'DESC';
                $attribute = substr($attribute, 1);
            }
            $diretores->orderBy($attribute, $sort_order);
        } else {
            $diretores->orderBy('ordem', 'asc')
                    ->orderBy('data_inicio', 'desc');
        }

        $diretores = $diretores->paginate(config('admin.paginate.per_page'))
                    ->onEachSide(config('admin.paginate.each_side'))
                    ->appends(request()->query());

        $diretores->getCollection()->transform(function($director) {
            // Formatar URL da imagem usando UploadHelper
            $director->imagem = $director->imagem ? UploadHelper::getPublicUrl($director->imagem) : null;
            return $director;
        });

        return Inertia::render('Admin/Diretores/Index', [
            'diretores' => $diretores,
            'filters' => request()->all('search'),
            'can' => [
                'create' => Auth::user()->can('director create'),
                'edit' => Auth::user()->can('director edit'),
                'delete' => Auth::user()->can('director delete'),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('adminCreate', Director::class);

        return Inertia::render('Admin/Diretores/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('adminCreate', Director::class);
        
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'historico' => 'nullable|string',
            'realizacoes' => 'nullable|array',
            'atual' => 'boolean',
            'ordem' => 'integer',
            'imagem_file' => 'nullable|image|max:2048',
        ]);

        // Processar upload de imagem
        if ($request->hasFile('imagem_file')) {
            $imagePath = UploadHelper::uploadImage(
                $request->file('imagem_file'),
                'diretores',
                $validated['nome'],
                'diretor'
            );
            $validated['imagem'] = $imagePath;
        }
        
        // Se data_fim é null e atual = true, garantir que data_fim seja null
        if ($validated['atual'] && empty($validated['data_fim'])) {
            $validated['data_fim'] = null;
        }
        
        // Converter arrays para JSON
        $validated['realizacoes'] = json_encode($validated['realizacoes'] ?? []);
        
        // Remover arquivo da validação
        unset($validated['imagem_file']);
        
        Director::create($validated);

        return redirect()->route('admin.directors.index')
            ->with('message', 'Diretor cadastrado com sucesso!');
    }

        public function show(Director $director)
    {
        $this->authorize('adminView', $director);

        //Formatar os dados do diretor incluindo URL correta da imagem
        $directorData = [
            'id' => $director->id,
            'nome' => $director->nome,
            'data_inicio' => $director->data_inicio,
            'data_fim' => $director->data_fim,
            'historico' => $director->historico,
            'realizacoes' => is_string($director->realizacoes) 
                ? json_decode($director->realizacoes, true) 
                : $director->realizacoes,
            'atual' => $director->atual,
            'ordem' => $director->ordem,
            'imagem' => $director->imagem ? UploadHelper::getPublicUrl($director->imagem) : null,
            'created_at' => $director->created_at,
            'updated_at' => $director->updated_at,
            // Adicionar período formatado para exibição
            'periodo_formatado' => $director->periodo_formatado,
        ];

        return Inertia::render('Admin/Diretores/Show', [
            'diretor' => $directorData,
        ]);
    }

        public function edit(Director $director)
    {
        $this->authorize('adminUpdate', $director);

        // Formatar os dados do diretor incluindo URL correta da imagem
        $directorData = [
            'id' => $director->id,
            'nome' => $director->nome,
            'data_inicio' => $director->data_inicio ? $director->data_inicio->format('Y-m-d') : null,
            'data_fim' => $director->data_fim ? $director->data_fim->format('Y-m-d') : null,
            'historico' => $director->historico,
            'realizacoes' => is_string($director->realizacoes) 
                ? json_decode($director->realizacoes, true) 
                : $director->realizacoes,
            'atual' => $director->atual,
            'ordem' => $director->ordem,
            'imagem' => $director->imagem ? UploadHelper::getPublicUrl($director->imagem) : null,
        ];

        return Inertia::render('Admin/Diretores/Edit', [
            'diretor' => $directorData,
        ]);
    }

    public function update(Request $request, Director $director)
    {
        $this->authorize('adminUpdate', $director);
        
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'historico' => 'nullable|string',
            'realizacoes' => 'nullable|array',
            'atual' => 'boolean',
            'ordem' => 'integer',
            'imagem_file' => 'nullable|image|max:2048',
        ]);

        // Verificar se o nome do diretor mudou
        $nomeAntigo = $director->nome;
        $nomeNovo = $validated['nome'];
        $nomeMudou = $nomeAntigo !== $nomeNovo;

        // Processar upload de nova imagem
        if ($request->hasFile('imagem_file')) {
            // Remover imagem antiga
            if ($director->imagem) {
                UploadHelper::deleteImage($director->imagem);
            }
            
            // Upload nova imagem
            $imagePath = UploadHelper::uploadImage(
                $request->file('imagem_file'),
                'diretores',
                $validated['nome'],
                'diretor'
            );
            $validated['imagem'] = $imagePath;
        } else if ($nomeMudou && $director->imagem) {
            // Se o nome mudou mas não há nova imagem, mover a imagem existente
            $novaImagemPath = UploadHelper::moveImage(
                $director->imagem,
                'diretores',
                $validated['nome'],
                'diretor'
            );
            if ($novaImagemPath) {
                $validated['imagem'] = $novaImagemPath;
            }
        }
        
        // Se data_fim é null e atual = true, garantir que data_fim seja null
        if ($validated['atual'] && empty($validated['data_fim'])) {
            $validated['data_fim'] = null;
        }
        
        // Converter arrays para JSON
        $validated['realizacoes'] = json_encode($validated['realizacoes'] ?? []);
        
        // Remover arquivo da validação
        unset($validated['imagem_file']);
        
        $director->update($validated);

        // Limpar pasta antiga se o nome mudou
        if ($nomeMudou) {
            $pastaAntiga = 'diretores/' . UploadHelper::sanitizeFolderName($nomeAntigo);
            UploadHelper::cleanupEmptyFolder($pastaAntiga);
        }

        return redirect()->route('admin.directors.index')
            ->with('message', 'Diretor atualizado com sucesso!');
    }

    public function destroy(Director $director)
    {
        $this->authorize('adminDelete', $director);
        
        // Remover imagem
        if ($director->imagem) {
            UploadHelper::deleteImage($director->imagem);
        }
        
        $nomeDiretor = $director->nome;
        $director->delete();

        // Limpar pasta do diretor
        $pastaDiretor = 'diretores/' . UploadHelper::sanitizeFolderName($nomeDiretor);
        UploadHelper::cleanupEmptyFolder($pastaDiretor);
        
        return redirect()->route('admin.directors.index')
            ->with('message', 'Diretor excluído com sucesso!');
    }
    
    public function listarDiretores()
    {
        try {
            $diretores = Director::orderBy('ordem', 'asc')
                         ->orderBy('data_inicio', 'desc')
                         ->orderByRaw('(CASE WHEN atual = true THEN 0 ELSE 1 END)')
                         ->get()
                         ->map(function ($director) {
                             // Formatar o período
                             $inicio = $director->data_inicio ? $director->data_inicio->format('d/m/Y') : '';
                             $periodo = $inicio;
                             
                             if ($director->atual || $director->data_fim === null) {
                                 $periodo .= ' - ATUALMENTE';
                             } else if ($director->data_fim) {
                                 $periodo .= ' - ' . $director->data_fim->format('d/m/Y');
                             }
                             
                             // Garantir que realizacoes seja um array
                             $realizacoes = is_string($director->realizacoes) 
                                 ? json_decode($director->realizacoes, true) 
                                 : $director->realizacoes;
                             $realizacoes = is_array($realizacoes) ? $realizacoes : [];
                             
                             return [
                                 'id' => $director->id,
                                 'nome' => $director->nome,
                                 'periodo' => $periodo,
                                 'historico' => $director->historico,
                                 'imagem' => $director->imagem ? UploadHelper::getPublicUrl($director->imagem) : '/images/placeholder-profile.jpg',
                                 'realizacoes' => $realizacoes,
                                 'atual' => (bool)$director->atual
                             ];
                         });
        
            return response()->json($diretores);
        } catch (\Exception $e) {
            \Log::error('Erro ao listar diretores: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao carregar diretores: ' . $e->getMessage()], 500);
        }
    }
}