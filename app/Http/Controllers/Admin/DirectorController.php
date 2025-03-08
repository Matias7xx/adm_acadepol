<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Director;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DirectorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('adminCreate', Director::class);

        return Inertia::render('Admin/Diretores/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
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
            $file = $request->file('imagem_file');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/diretores'), $filename);
            $validated['imagem'] = '/images/diretores/' . $filename;
        }
        
        // Se data_fim é null e atual = true, garantir que data_fim seja null
        if ($validated['atual'] && empty($validated['data_fim'])) {
            $validated['data_fim'] = null;
        }
        
        // Converter arrays para JSON
        $validated['realizacoes'] = json_encode($validated['realizacoes'] ?? []);
        
        Director::create($validated);

        return redirect()->route('admin.directors.index')
            ->with('message', 'Diretor cadastrado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Director $director)
    {
        $this->authorize('adminView', $director);

        return Inertia::render('Admin/Diretores/Show', [
            'diretor' => $director,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Director $director)
    {
        $this->authorize('adminUpdate', $director);

        return Inertia::render('Admin/Diretores/Edit', [
            'diretor' => $director,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
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

        // Processar upload de imagem
        if ($request->hasFile('imagem_file')) {
            // Remover imagem antiga se existir
            if ($director->imagem && file_exists(public_path($director->imagem))) {
                unlink(public_path($director->imagem));
            }
            
            $file = $request->file('imagem_file');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/diretores'), $filename);
            $validated['imagem'] = '/images/diretores/' . $filename;
        }
        
        // Se data_fim é null e atual = true, garantir que data_fim seja null
        if ($validated['atual'] && empty($validated['data_fim'])) {
            $validated['data_fim'] = null;
        }
        
        // Converter arrays para JSON
        $validated['realizacoes'] = json_encode($validated['realizacoes'] ?? []);
        
        $director->update($validated);

        return redirect()->route('admin.directors.index')
            ->with('message', 'Diretor atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Director $director)
    {
        $this->authorize('adminDelete', $director);
        
        // Remover imagem se existir
        if ($director->imagem && file_exists(public_path($director->imagem))) {
            unlink(public_path($director->imagem));
        }
        
        $director->delete();
        
        return redirect()->route('admin.directors.index')
            ->with('message', 'Diretor excluído com sucesso!');
    }
    
    /**
     * Fornecer lista de diretores para o componente CardDiretores (frontend)
     */
    public function listarDiretores()
    {
        try {
            $diretores = Director::orderBy('ordem', 'asc')
                         ->orderBy('data_inicio', 'desc')
                         ->orderByRaw('(CASE WHEN atual = true THEN 0 ELSE 1 END)')
                         ->get()
                         ->map(function ($director) {
                             // Formatar o período manualmente em vez de depender do accessor
                             $inicio = $director->data_inicio ? $director->data_inicio->format('d/m/Y') : '';
                             $periodo = $inicio;
                             
                             if ($director->atual || $director->data_fim === null) {
                                 $periodo .= ' - ATUALMENTE';
                             } else if ($director->data_fim) {
                                 $periodo .= ' - ' . $director->data_fim->format('d/m/Y');
                             }
                             
                             // Garantir que realizacoes seja decodificado corretamente
                             $realizacoes = is_string($director->realizacoes) 
                                 ? json_decode($director->realizacoes, true) 
                                 : $director->realizacoes;
                             
                             // Garantir que seja um array
                             $realizacoes = is_array($realizacoes) ? $realizacoes : [];
                             
                             return [
                                 'id' => $director->id,
                                 'nome' => $director->nome,
                                 'periodo' => $periodo,
                                 'historico' => $director->historico,
                                 'imagem' => $director->imagem ?? '/images/placeholder-profile.jpg',
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