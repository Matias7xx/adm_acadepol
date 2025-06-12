<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\Curso;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Mail\NovaMatricula;
use App\Mail\MatriculaAprovada;
use App\Mail\MatriculaRejeitada;

class MatriculaController extends Controller
{
    /**
     * Exibe o formulário de inscrição para um curso.
     *
     * @param  int  $cursoId
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request, Curso $curso)
{
    // Verificar permissão - somente administradores
    $this->authorize('viewAny', Matricula::class);
    
    $query = Matricula::with(['curso', 'aluno']);
    
    // Se recebeu um objeto curso, filtra pelo ID do curso
    if ($curso && $curso->exists) {
        $query->where('curso_id', $curso->id);
    }
    // Se não recebeu um objeto curso, mas recebeu um curso_id no request, também filtra
    else if ($request->has('curso_id')) {
        $query->where('curso_id', $request->curso_id);
    }
    
    // Filtros adicionais
    $matriculas = $query->when($request->search, function($query, $search) {
            $search = trim(htmlspecialchars($search));
            return $query->whereHas('aluno', function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('matricula', 'like', "%{$search}%");
            });
        })
        ->when($request->status, function($query, $status) {
            return $query->where('status', $status);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(10)
        ->appends($request->only(['search', 'status', 'curso_id']));
    
    $cursos = Curso::select('id', 'nome')->get();
    
    return Inertia::render('Admin/Matriculas/Index', [
        'matriculas' => $matriculas,
        'cursos' => $cursos,
        'cursoAtual' => $curso ?? null,
        'filters' => $request->only(['search', 'status', 'curso_id']),
    ]);
}

    public function inscricao($cursoId)
    {
        // Validação de ID
        $cursoId = filter_var($cursoId, FILTER_VALIDATE_INT);
        if (!$cursoId) {
            abort(404, 'Curso não encontrado');
        }
        
        // Se não estiver autenticado, redireciona para login com parâmetros
        if (!Auth::check()) {
            // Salva o curso ID na sessão para recuperar após o login
            session(['intended_curso_id' => $cursoId]);
            session(['intended_route' => 'matricula']);
            session(['intended_acao' => 'matricula_curso']);
            
            // Redirecionar para login com os parâmetros na URL
            return redirect()->route('login', [
                'intended_route' => 'matricula',
                'curso_id' => $cursoId,
                'acao' => 'matricula_curso'
            ])->withErrors([
                'unauthenticated' => 'Você precisa estar logado para se inscrever.'
            ]);
        }
        
        $curso = Curso::findOrFail($cursoId);
        $user = Auth::user();

        // Verifica se o usuário já está matriculado
        $matriculaExistente = Matricula::where('curso_id', $cursoId)
            ->where('user_id', $user->id)
            ->exists();

        if ($matriculaExistente) {
            return back()->withErrors([
                'enrollment' => 'Você já realizou sua inscrição neste curso.'
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
     * Exibe a página de confirmação após o envio da matrícula
     */
    public function confirmacao(Request $request)
    {
        // Recuperar dados da matrícula da sessão
        return Inertia::render('Components/Confirmacao', [
            'user' => Auth::user(),
            'mensagem' => 'Sua inscrição foi enviada com sucesso e está aguardando análise.',
            'detalhes' => session('detalhes_matricula'),
            'tipo' => 'matricula'
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
        //Validação dos dados
        $validator = Validator::make($request->all(), [
            'curso_id' => ['required', 'exists:cursos,id'],
            'dados_adicionais' => ['required', 'array'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $validated = $validator->validated();
    
        $user = Auth::user();
        $curso = Curso::findOrFail($validated['curso_id']);
    
        // Logs para auditoria
        Log::info('Tentativa de matrícula', [
            'user_id' => $user->id,
            'curso_id' => $curso->id, 
            'ip' => $request->ip()
        ]);
    
        // Verifica se o usuário já está matriculado
        if (Matricula::where('curso_id', $validated['curso_id'])
            ->where('user_id', $user->id)
            ->exists()) {
            return redirect()->route('cursos')
                ->with('message', 'Você já realizou sua inscrição neste curso.');
        }
    
        // Verifica a capacidade do curso
        $matriculasAtivas = Matricula::where('curso_id', $validated['curso_id'])
            ->whereIn('status', ['aprovada', 'pendente'])
            ->count();
    
        if ($matriculasAtivas >= $curso->capacidade_maxima) {
            return redirect()->route('cursos')
                ->with('message', 'O curso atingiu a capacidade máxima.');
        }
    
        try {
            // Criar a matrícula
            $matricula = Matricula::create([
                'curso_id' => $validated['curso_id'],
                'user_id' => $user->id,
                'dados_adicionais' => $validated['dados_adicionais'],
                'status' => 'pendente',
            ]);
    
            session(['detalhes_matricula' => [
                'nome' => $user->name,
                'curso' => $curso->nome,
                'data_inicio' => (new \DateTime($curso->data_inicio))->format('d/m/Y'),
                'data_fim' => (new \DateTime($curso->data_fim))->format('d/m/Y'),
                'id' => $matricula->id,
                'created_at' => now()->format('d/m/Y H:i')
            ]]);
            
            return redirect()->route('confirmacao');
        } catch (\Exception $e) {
            Log::error('Erro ao realizar matrícula', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'curso_id' => $curso->id
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocorreu um erro ao processar sua inscrição. Por favor, tente novamente.')
                ->withInput();
        }
    }

    /**
     * Aprovar uma matrícula.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function aprovar($id)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            return response()->json([
                'message' => 'ID de matrícula inválido'
            ], 400);
        }
        
        try {
            $matricula = Matricula::with(['curso', 'aluno'])->findOrFail($id);
            $this->authorize('update', $matricula);
    
            if ($matricula->status !== 'pendente') {
                return response()->json([
                    'message' => 'Essa matrícula já foi processada.'
                ], 400);
            }
    
            // Aprovar a matrícula
            $matricula->update(['status' => 'aprovada']);
            
            // Enviar email de aprovação para o aluno
            if ($matricula->aluno && $matricula->aluno->email) {
                Mail::to($matricula->aluno->email)->send(new MatriculaAprovada($matricula));
            }
            
            Log::info('Matrícula aprovada', [
                'matricula_id' => $id,
                'admin_id' => Auth::id()
            ]);
    
            return response()->json([
                'message' => 'Matrícula aprovada com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao aprovar matrícula', [
                'error' => $e->getMessage(),
                'matricula_id' => $id
            ]);
            
            return response()->json([
                'message' => 'Erro ao processar a solicitação'
            ], 500);
        }
    }

    /**
     * Rejeitar uma matrícula.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejeitar(Request $request, $id)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            return response()->json([
                'message' => 'ID de matrícula inválido'
            ], 400);
        }
        
        // Validar o motivo da rejeição
        $validator = Validator::make($request->all(), [
            'motivo_rejeicao' => 'required|string|min:5',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'É necessário informar um motivo válido para rejeitar a matrícula.',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $matricula = Matricula::with(['curso', 'aluno'])->findOrFail($id);
            $this->authorize('update', $matricula);
    
            if ($matricula->status !== 'pendente') {
                return response()->json([
                    'message' => 'Essa matrícula já foi processada.'
                ], 400);
            }
    
            // Atualiza o status para rejeitado e salva o motivo
            $motivo = $request->input('motivo_rejeicao');
            $matricula->update([
                'status' => 'rejeitada',
                'motivo_rejeicao' => $motivo
            ]);
            
            // Enviar email de rejeição para o aluno
            if ($matricula->aluno && $matricula->aluno->email) {
                Mail::to($matricula->aluno->email)->send(new MatriculaRejeitada($matricula, $motivo));
            }
            
            Log::info('Matrícula rejeitada', [
                'matricula_id' => $id,
                'admin_id' => Auth::id(),
                'motivo' => $motivo
            ]);
    
            return response()->json([
                'message' => 'Matrícula rejeitada com sucesso.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao rejeitar matrícula', [
                'error' => $e->getMessage(),
                'matricula_id' => $id
            ]);
            
            return response()->json([
                'message' => 'Erro ao processar a solicitação'
            ], 500);
        }
    }

    /**
     * Alterar o status de uma matrícula
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function alterarStatus(Request $request, $id)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            return redirect()->back()->with('error', 'ID de matrícula inválido');
        }
        
        try {
            $matricula = Matricula::with(['curso', 'aluno'])->findOrFail($id);
            $this->authorize('update', $matricula);
            
            $validated = $request->validate([
                'status' => ['required', Rule::in(['aprovada', 'rejeitada', 'pendente'])],
                'motivo_rejeicao' => 'required_if:status,rejeitada|nullable|string|min:5',
            ]);
        
            $novoStatus = $validated['status'];
            
            // Verificar se o status é diferente do atual
            if ($matricula->status === $novoStatus) {
                return redirect()->back()->with('message', 'A matrícula já está com este status');
            }
            
            // Preparar dados para atualização
            $dados = ['status' => $novoStatus];
            
            // Se for rejeição, incluir o motivo
            if ($novoStatus === 'rejeitada') {
                if (!isset($validated['motivo_rejeicao']) || empty($validated['motivo_rejeicao'])) {
                    return redirect()->back()->with('error', 'É necessário informar um motivo para rejeitar a matrícula');
                }
                $dados['motivo_rejeicao'] = $validated['motivo_rejeicao'];
            }
            
            // Atualizar a matrícula
            $matricula->update($dados);
            
            // Enviar notificação por email conforme o status
            if ($matricula->aluno && $matricula->aluno->email) {
                if ($novoStatus === 'aprovada') {
                    Mail::to($matricula->aluno->email)->send(new MatriculaAprovada($matricula));
                } elseif ($novoStatus === 'rejeitada') {
                    Mail::to($matricula->aluno->email)->send(new MatriculaRejeitada($matricula, $dados['motivo_rejeicao']));
                }
            }
            
            Log::info('Status da matrícula alterado', [
                'matricula_id' => $id,
                'admin_id' => Auth::id(),
                'status_antigo' => $matricula->getOriginal('status'),
                'status_novo' => $novoStatus,
                'motivo_rejeicao' => $novoStatus === 'rejeitada' ? $dados['motivo_rejeicao'] : null
            ]);
            
            return redirect()->back()->with('message', 'Status da matrícula alterado com sucesso para ' . $novoStatus);
        } catch (\Exception $e) {
            Log::error('Erro ao alterar status da matrícula', [
                'error' => $e->getMessage(),
                'matricula_id' => $id
            ]);
            
            return redirect()->back()->with('error', 'Ocorreu um erro ao alterar o status da matrícula: ' . $e->getMessage());
        }
    }
    
    /**
     * Exibe os detalhes de uma matrícula (para administradores).
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            abort(404, 'Matrícula não encontrada');
        }
        
        $matricula = Matricula::with(['curso', 'aluno'])->findOrFail($id);
        
        // Verificar permissão - somente administradores
        $this->authorize('view', $matricula);
        
        return Inertia::render('Admin/Matriculas/Show', [
            'matricula' => $matricula
        ]);
    }
}