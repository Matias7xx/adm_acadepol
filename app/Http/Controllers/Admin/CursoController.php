<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class CursoController extends Controller
{
    public function index()
    {
        $this->authorize('adminViewAny', Curso::class);
        $cursos = (new Curso)->newQuery();

        if (request()->has('search')) {
            $cursos->where('nome', 'Like', '%'.request()->input('search').'%');
        }

        if (request()->query('sort')) {
            $attribute = request()->query('sort');
            $sort_order = 'ASC';
            if (strncmp($attribute, '-', 1) === 0) {
                $sort_order = 'DESC';
                $attribute = substr($attribute, 1);
            }
            $cursos->orderBy($attribute, $sort_order);
        } else {
            $cursos->latest();
        }

        $cursos = $cursos->paginate(config('admin.paginate.per_page'))
                    ->onEachSide(config('admin.paginate.each_side'))
                    ->appends(request()->query());

        /* $cursos = Curso::all(); */
        return Inertia::render('Admin/Cursos/Index', ['cursos' => $cursos,
            'filters' => request()->all('search'),
            'can' => [
                'create' => Auth::user()->can('curso create'),
                'edit' => Auth::user()->can('curso edit'),
                'delete' => Auth::user()->can('curso delete'),
            ],
        ]);
    }

    public function cursosPublicos() //Exibe os cursos na aba "Cursos" do layout principal
    {
    $cursos = Curso::where('status', 'aberto')->paginate(3);


    return Inertia::render('Cursos', [
        'cursos' => $cursos,
    ]);
    }

    public function create()
    {
        $this->authorize('adminCreate', Curso::class);

        return Inertia::render('Admin/Cursos/Create');
    }

    public function store(Request $request)
    {
    $validated = $request->validate([
        'nome' => 'required|string|max:255',
        'descricao' => 'nullable|string',
        'imagem' => 'nullable|string',
        'imagem_file' => 'nullable|image|max:2048', // Aceita imagens até 2MB
        'data_inicio' => 'required|date',
        'data_fim' => 'required|date|after_or_equal:data_inicio',
        'carga_horaria' => 'required|integer|min:1',
        'pre_requisitos' => 'nullable|array',
        'enxoval' => 'nullable|array',
        'localizacao' => 'required|string|max:255',
        'capacidade_maxima' => 'required|integer|min:1',
        'modalidade' => 'required|string|max:20',
        /* 'material_complementar' => 'nullable|array', */
        'certificacao' => 'boolean',
        'certificacao_modelo' => 'nullable|string',
        'status' => 'required|string|max:20',
    ]);

   /*  // Processa o upload da imagem se enviada
    if ($request->hasFile('imagem_file')) {
        $path = $request->file('imagem_file')->store('cursos', 'public'); // Salva em storage/app/public/cursos
        $validated['imagem'] = Storage::url($path); // Armazena o URL do arquivo
    }
 */
    if ($request->hasFile('imagem_file')) {  //Salva em C:\Code\adm-acadepol\public\images
        $file = $request->file('imagem_file');
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images/cursos'), $filename);
        $validated['imagem'] = '/images/cursos/' . $filename;
    } 

    // Converte arrays para JSON (caso precise armazenar em colunas do tipo JSON)
    $validated['pre_requisitos'] = json_encode($validated['pre_requisitos'] ?? []);
    $validated['enxoval'] = json_encode($validated['enxoval'] ?? []);
    /* $validated['material_complementar'] = json_encode($validated['material_complementar'] ?? []); */

    Curso::create($validated);

    return redirect()->route('admin.cursos.index')->with('message', 'Curso cadastrado com sucesso!');
    }

    public function show(Curso $curso)
    {
        $this->authorize('adminView', $curso);

        return Inertia::render('Admin/Cursos/Show', [
            'curso' => $curso,
        ]);
    }

    public function showCurso(Curso $curso)//Exibe os detalhes do curso para a o usuário fazer matrícula
    {
        return Inertia::render('CursoDetalhe', [
            'curso' => $curso,
        ]);
    }

    public function exibirAlunos($id)//ID DO CURSO. Exibir quantidade e dados dos alunos inscritos
    {
    $curso = Curso::with('alunos')->findOrFail($id);

    return response()->json([
        'curso' => $curso,
        'quantidade_inscritos' => $curso->alunos->count(),
        'inscritos' => $curso->alunos
    ]);
    }

    public function edit(Curso $curso)
    {
    $this->authorize('adminUpdate', $curso);

    return Inertia::render('Admin/Cursos/Edit', [
        'curso' => $curso,
    ]);
    }

    /**
 * Update the specified resource in storage.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  \App\Models\Curso  $curso
 * @return \Illuminate\Http\RedirectResponse
 */
    public function update(Request $request, Curso $curso)
    {

        \Log::info('Request recebida:', $request->all());
    $this->authorize('adminUpdate', $curso);

    // Valide os dados do formulário
    $validated = $request->validate([
        'nome' => 'required|string|max:255',
        'descricao' => 'nullable|string',
        'imagem' => 'nullable|string',
        'imagem_file' => 'nullable|image|max:2048',
        'data_inicio' => 'required|date',
        'data_fim' => 'required|date|after_or_equal:data_inicio',
        'carga_horaria' => 'required|integer|min:1',
        'pre_requisitos' => 'nullable|array',
        'enxoval' => 'nullable|array',
        'localizacao' => 'required|string|max:255',
        'capacidade_maxima' => 'required|integer|min:1',
        'modalidade' => 'required|string',
        'certificacao' => 'boolean',
        'certificacao_modelo' => 'nullable|string',
        'status' => 'required|string',
    ]);

    // Processar upload de imagem, se fornecido
    if ($request->hasFile('imagem_file')) {
        // Remover imagem antiga se existir
        if ($curso->imagem && file_exists(public_path($curso->imagem))) {
            unlink(public_path($curso->imagem));
        }
        
        $file = $request->file('imagem_file');
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images/cursos'), $filename);
        $validated['imagem'] = '/images/cursos/' . $filename;
    }

    // Converter arrays para JSON antes de salvar
    $validated['pre_requisitos'] = json_encode($validated['pre_requisitos'] ?? []);
    $validated['enxoval'] = json_encode($validated['enxoval'] ?? []);

    // Atualizar o curso
    $curso->update($validated);

    return redirect()->route('admin.cursos.index')
        ->with('message', 'Curso atualizado com sucesso!');
    }

    public function destroy(Curso $curso)
    {
    $this->authorize('adminDelete', $curso);

    // Verificar se há matrículas associadas antes de excluir. Se tiver algum usuário com matrícula APROVADA ou PENDENTE ele não remove.
    $matriculasAtivas = $curso->alunos()->whereIn('status', ['aprovada', 'pendente'])->count();
    
    if ($matriculasAtivas > 0) {
        return redirect()->route('admin.cursos.index')
            ->with('message', 'Não é possível excluir um curso com matrículas ativas.');
    }
    
    // Remover a imagem do curso, se existir
    if ($curso->imagem && file_exists(public_path($curso->imagem))) {
        unlink(public_path($curso->imagem));
    }
    
    $curso->delete();
    
    return redirect()->route('admin.cursos.index')
        ->with('message', 'Curso excluído com sucesso!');
    }
    /* public function matricularAluno(Request $request, Curso $curso) {
        //$aluno = \Auth::user(); // Obtém o usuário logado
        $aluno = auth()->user();
    
        // Verifica se o aluno já está matriculado
        if ($curso->alunos()->where('user_id', $aluno->id)->exists()) {
            return response()->json(['message' => 'Você já está matriculado neste curso.'], 400);
        }
    
        // Verifica a capacidade do curso
        if ($curso->alunos()->count() >= $curso->capacidade_maxima) {
            return response()->json(['message' => 'O curso atingiu a capacidade máxima.'], 400);
        }
    
        // Verifica pré-requisitos
        $preRequisitos = is_string($curso->pre_requisitos) 
            ? json_decode($curso->pre_requisitos, true) ?? [] 
            : [];

        foreach ($preRequisitos as $preRequisitoId) {
    if (!$aluno->cursos()->where('curso_id', $preRequisitoId)->exists()) {
        return response()->json([
            'message' => 'Você não atende aos pré-requisitos.'
        ], 400);
    }
}
    
        // Matricula o aluno
        $curso->alunos()->attach($aluno->id);
        return response()->json(['message' => 'Matrícula realizada com sucesso!']);
    } */
}