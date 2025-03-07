<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\Curso;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MatriculaController extends Controller
{
    /**
     * Exibe o formulário de inscrição para um curso.
     *
     * @param  int  $cursoId
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
        //Exibir Lista de Matrículas
     public function index(Request $request)
     {
         // Verificar permissão - somente administradores
         $this->authorize('viewAny', Matricula::class);
         
         $matriculas = Matricula::with(['curso', 'aluno'])
             ->when($request->search, function($query, $search) {
                 return $query->whereHas('aluno', function($query) use ($search) {
                     $query->where('name', 'like', "%{$search}%")
                         ->orWhere('matricula', 'like', "%{$search}%");
                 });
             })
             ->when($request->status, function($query, $status) {
                 return $query->where('status', $status);
             })
             ->when($request->curso_id, function($query, $cursoId) {
                 return $query->where('curso_id', $cursoId);
             })
             ->orderBy('created_at', 'desc')
             ->paginate(10)
             ->appends($request->all());
             
         $cursos = Curso::select('id', 'nome')->get();
         
         return Inertia::render('Admin/Matriculas/Index', [
             'matriculas' => $matriculas,
             'cursos' => $cursos,
             'filters' => $request->only(['search', 'status', 'curso_id']),
         ]);
     }

    public function inscricao($cursoId)
    {
        // Se não estiver autenticado, redireciona para login
        if (!Auth::check()) {
        // Salva o curso ID na sessão para recuperar após o login
        session(['intended_curso_id' => $cursoId]);
        
        return back()->withErrors([
                'unauthenticated' => 'Você precisa estar logado para se inscrever.'
            ]);
        }
        
        $cursoId = (int) $cursoId;
        $curso = Curso::findOrFail($cursoId);
        $user = Auth::user();

        // Verifica se o usuário já está matriculado
        $matriculaExistente = Matricula::where('curso_id', $cursoId)
            ->where('user_id', $user->id)
            ->exists();

            if ($matriculaExistente) {
                return back()->withErrors([
                    'enrollment' => 'Você já está matriculado neste curso.'
                ]);
            }

        // Verifica se o curso ainda está com inscrições abertas
        if ($curso->status !== 'aberto') {
            return redirect()->route('cursos')
                ->with('message', 'As inscrições para este curso não estão abertas.');
        }

        // Verifica se o curso atingiu a capacidade máxima
        $matriculasAtivas = Matricula::where('curso_id', $cursoId)
            ->whereIn('status', ['aprovada', 'pendente'])
            ->count();

        if ($matriculasAtivas >= $curso->capacidade_maxima) {
            return redirect()->route('cursos')
                ->with('message', 'O curso atingiu a capacidade máxima.');
        }

        return Inertia::render('CursoDetalhesComponents/Formulario', [
            'curso' => $curso,
            'user' => $user
        ]);
    }

    /**
     * Processa a inscrição em um curso.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'dados_adicionais' => 'required|array',
        ]);
    
        $user = Auth::user();
        $curso = Curso::findOrFail($request->curso_id);
    
        // Verifica se o usuário já está matriculado
        if (Matricula::where('curso_id', $request->curso_id)
            ->where('user_id', $user->id)
            ->exists()) {
            // Use redirect com with para flash message
            return redirect()->route('cursos')
                ->with('message', 'Você já está matriculado neste curso.');
        }
    
        // Verifica a capacidade do curso
        $matriculasAtivas = Matricula::where('curso_id', $request->curso_id)
            ->whereIn('status', ['aprovada', 'pendente'])
            ->count();
    
        if ($matriculasAtivas >= $curso->capacidade_maxima) {
            return redirect()->route('cursos')
                ->with('message', 'O curso atingiu a capacidade máxima.');
        }
    
        /* // Verifica pré-requisitos
        $preRequisitos = is_string($curso->pre_requisitos) 
            ? json_decode($curso->pre_requisitos, true) ?? [] 
            : ($curso->pre_requisitos ?? []);
    
        foreach ($preRequisitos as $preRequisitoId) {
            $concluiu = Matricula::where('curso_id', $preRequisitoId)
                ->where('user_id', $user->id)
                ->where('status', 'aprovada')
                ->exists();
            
            if (!$concluiu) {
                return redirect()->route('cursos')
                    ->with('message', 'Você não atende aos pré-requisitos para este curso.');
            }
        } */
    
        // Criar a matrícula
        $matricula = Matricula::create([
            'curso_id' => $request->curso_id,
            'user_id' => $user->id,
            'dados_adicionais' => $request->dados_adicionais,
            'status' => 'pendente',
        ]);
    
        return redirect()->route('cursos')
            ->with('message', 'Inscrição enviada com sucesso! Aguarde a confirmação.');
    }

    /**
     * Aprovar uma matrícula.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function aprovar($id)
    {
        $matricula = Matricula::findOrFail($id);

        if ($matricula->status !== 'pendente') {
            return response()->json([
                'message' => 'Essa matrícula já foi processada.'
            ], 400);
        }

        // Aprovar a matrícula
        $matricula->update(['status' => 'aprovada']);

        return response()->json([
            'message' => 'Matrícula aprovada com sucesso!'
        ]);
    }

    /**
     * Rejeitar uma matrícula.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejeitar($id)
    {
        $matricula = Matricula::findOrFail($id);

        if ($matricula->status !== 'pendente') {
            return response()->json([
                'message' => 'Essa matrícula já foi processada.'
            ], 400);
        }

        // Atualiza o status para rejeitado
        $matricula->update(['status' => 'rejeitada']);

        return response()->json([
            'message' => 'Matrícula rejeitada.'
        ]);
    }

    public function alterarStatus(Request $request, $id)
{
    $matricula = Matricula::findOrFail($id);
    $this->authorize('update', $matricula);
    
    $request->validate([
        'status' => 'required|in:aprovada,rejeitada,pendente',
    ]);

    $novoStatus = $request->status;
    
    // Verificar se o status é diferente do atual
    if ($matricula->status !== $novoStatus) {
        $matricula->status = $novoStatus;
        $matricula->save();
        
        // Em vez de retornar JSON, redirecione com uma mensagem de sessão
        return redirect()->back()->with('message', 'Status da matrícula alterado com sucesso para ' . $novoStatus);
    }
    
    return redirect()->back()->with('message', 'A matrícula já está com este status');
}
    
    /**
     * Exibe os detalhes de uma matrícula (para administradores).
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id)
    {
        $matricula = Matricula::with(['curso', 'aluno'])->findOrFail($id);
        
        // Verificar permissão - somente administradores ou o próprio usuário
        $this->authorize('view', $matricula);
        
        return Inertia::render('Admin/Matriculas/Show', [
            'matricula' => $matricula
        ]);
    }
}