<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use BalajiDharma\LaravelAdminCore\Actions\User\UserCreateAction;
use BalajiDharma\LaravelAdminCore\Actions\User\UserUpdateAction;
use BalajiDharma\LaravelAdminCore\Data\User\UserCreateData;
use BalajiDharma\LaravelAdminCore\Data\User\UserUpdateData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

use App\Models\Certificado;
use App\Models\Curso;
use App\Helpers\CertificadoHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $this->authorize('adminViewAny', User::class);
        $users = (new User)->newQuery();

        if (request()->has('search')) {
            $users->where('name', 'Like', '%'.request()->input('search').'%')
            ->orWhere('matricula', 'Like', '%'.request()->input('search').'%')
            ->orWhere('email', 'Like', '%'.request()->input('search').'%');
        }

        if (request()->query('sort')) {
            $attribute = request()->query('sort');
            $sort_order = 'ASC';
            if (strncmp($attribute, '-', 1) === 0) {
                $sort_order = 'DESC';
                $attribute = substr($attribute, 1);
            }
            $users->orderBy($attribute, $sort_order);
        } else {
            $users->latest();
        }

        $users = $users->paginate(config('admin.paginate.per_page'))
                    ->onEachSide(config('admin.paginate.each_side'))
                    ->appends(request()->query());

        return Inertia::render('Admin/User/Index', [
            'users' => $users,
            'filters' => request()->all('search'),
            'can' => [
                'create' => Auth::user()->can('user create'),
                'edit' => Auth::user()->can('user edit'),
                'delete' => Auth::user()->can('user delete'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $this->authorize('adminCreate', User::class);
        $roles = Role::all()->pluck('name', 'name');

        return Inertia::render('Admin/User/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(UserCreateData $data, UserCreateAction $userCreateAction)
    {
        $this->authorize('adminCreate', User::class);
        $userCreateAction->handle($data);

        return redirect()->route('admin.user.index')
            ->with('message', __('Usuário criado com sucesso.'));
    }

    /**
     * Display the specified resource.
     *
     * @return \Inertia\Response
     */
    public function show(User $user)
    {
        // Carregar certificados e cursos
        $certificados = Certificado::where('user_id', $user->id)
            ->orderBy('data_emissao', 'desc')
            ->get();
        
        $cursos = Curso::where('certificacao', true)
            ->where('status', 'concluído')
            ->orderBy('nome')
            ->get(['id', 'nome', 'carga_horaria']);
        
        $roles = Role::all();
        $userHasRoles = array_column(json_decode($user->roles, true) ?: [], 'name');

        return Inertia::render('Admin/User/Show', [
            'user' => $user,
            'roles' => $roles,
            'userHasRoles' => $userHasRoles,
            'certificados' => $certificados,
            'cursos' => $cursos
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Inertia\Response
     */
    public function edit(User $user)
    {
        $this->authorize('adminUpdate', $user);
        $roles = Role::all()->pluck('name', 'name');
        $userHasRoles = array_column(json_decode($user->roles, true), 'name');

        return Inertia::render('Admin/User/Edit', [
            'user' => $user,
            'roles' => $roles,
            'userHasRoles' => $userHasRoles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UserUpdateData $data, User $user, UserUpdateAction $userUpdateAction)
    {
        $this->authorize('adminUpdate', $user);
        $userUpdateAction->handle($data, $user);

        return redirect()->route('admin.user.index')
            ->with('message', __('Usuário atualizado com sucesso.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        $this->authorize('adminDelete', $user);
        $user->delete();

        return redirect()->route('admin.user.index')
            ->with('message', __('Usuário apagado com sucesso.'));
    }

    /**
     * Show the user a form to change their personal information & password.
     *
     * @return \Inertia\Response
     */
    public function accountInfo()
    {
        $user = \Auth::user();

        return Inertia::render('Admin/User/AccountInfo', [
            'user' => $user,
        ]);
    }

    /**
     * Save the modified personal information for a user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accountInfoStore(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'matricula' => ['required', 'string', 'min:7', 'unique:users,matricula,'.\Auth::user()->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.\Auth::user()->id],
            'cpf' => ['nullable', 'string', 'max:20'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'orgao' => ['nullable', 'string', 'max:255'],
            'lotacao' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
        ]);

        $user = \Auth::user()->update($request->except(['_token']));

        if ($user) {
            $message = 'Conta atualizada com sucesso.';
        } else {
            $message = 'Erro no salvamento. Por favor, tente novamente.';
        }

        return redirect()->route('admin.account.info')->with('message', __($message));
    }

    /**
     * Save the new password for a user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePasswordStore(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'old_password' => ['required'],
            'new_password' => ['required', Rules\Password::defaults()],
            'confirm_password' => ['required', 'same:new_password', Rules\Password::defaults()],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($validator->failed()) {
                return;
            }
            if (! Hash::check($request->input('old_password'), \Auth::user()->password)) {
                $validator->errors()->add(
                    'old_password', __('A senha antiga está incorreta.')
                );
            }
        });

        $validator->validate();

        $user = \Auth::user()->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        if ($user) {
            $message = 'Senha atualizada com sucesso.';
        } else {
            $message = 'Erro no salvamento. Por favor, tente novamente.';
        }

        return redirect()->route('admin.account.info')->with('message', __($message));
    }

    public function adicionarCertificado(Request $request, User $user)
    {
        try {
            // Verificar permissão
            $this->authorize('update', $user);
            
            // Validar dados incluindo campos para certificados livres
            $validator = Validator::make($request->all(), [
                'tipo_certificado' => 'required|in:curso_sistema,curso_externo',
                'curso_id' => 'required_if:tipo_certificado,curso_sistema|nullable|exists:cursos,id',
                'nome_curso_externo' => 'required_if:tipo_certificado,curso_externo|nullable|string|max:255',
                'carga_horaria' => 'required|integer|min:1|max:2000',
                'data_conclusao' => 'required|date|before_or_equal:today',
                'certificado_pdf' => [
                    'required',
                    'file',
                    'mimes:pdf',
                    'max:10240' // 10MB máximo
                ]
            ], [
                'tipo_certificado.required' => 'Selecione o tipo de certificado.',
                'curso_id.required_if' => 'Selecione um curso do sistema.',
                'curso_id.exists' => 'Curso selecionado não é válido.',
                'nome_curso_externo.required_if' => 'Digite o nome do curso externo.',
                'carga_horaria.required' => 'Informe a carga horária.',
                'carga_horaria.integer' => 'A carga horária deve ser um número.',
                'carga_horaria.min' => 'A carga horária deve ser no mínimo 1 hora.',
                'carga_horaria.max' => 'A carga horária não pode ser maior que 2000 horas.',
                'data_conclusao.required' => 'Informe a data de conclusão.',
                'data_conclusao.date' => 'Data de conclusão inválida.',
                'data_conclusao.before_or_equal' => 'A data de conclusão não pode ser no futuro.',
                'certificado_pdf.required' => 'O arquivo PDF do certificado é obrigatório.',
                'certificado_pdf.mimes' => 'O arquivo deve ser um PDF.',
                'certificado_pdf.max' => 'O arquivo não pode ser maior que 10MB.'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->with('error', 'Erro na validação: ' . implode(', ', $validator->errors()->all()));
            }
            
            $validated = $validator->validated();
            
            // Validação específica por tipo de certificado
            if ($validated['tipo_certificado'] === 'curso_sistema') {
                if (empty($validated['curso_id'])) {
                    return redirect()->back()->with('error', 'Selecione um curso do sistema.');
                }
                
                $curso = Curso::findOrFail($validated['curso_id']);
                $nomeCurso = $curso->nome;
                $tipoOrigem = Certificado::TIPO_CURSO_SISTEMA;
                
                // Verificar se já existe certificado para este usuário e curso
                $certificadoExistente = Certificado::where('user_id', $user->id)
                    ->where('curso_id', $curso->id)
                    ->first();
                    
                if ($certificadoExistente) {
                    return redirect()->back()->with('error', 'Este usuário já possui certificado para este curso');
                }
            } else { // curso_externo
                if (empty($validated['nome_curso_externo'])) {
                    return redirect()->back()->with('error', 'Digite o nome do curso externo.');
                }
                
                $nomeCurso = $validated['nome_curso_externo'];
                $tipoOrigem = Certificado::TIPO_CURSO_EXTERNO;
                $curso = null; // Para cursos externos não há registro na tabela cursos
                
                // Para cursos externos, verificar duplicação pelo nome
                $certificadoExistente = Certificado::where('user_id', $user->id)
                    ->where('nome_curso', $nomeCurso)
                    ->where('tipo_origem', $tipoOrigem)
                    ->first();
                    
                if ($certificadoExistente) {
                    return redirect()->back()->with('error', 'Este usuário já possui certificado para este curso externo');
                }
            }
            
            // Gerar número único do certificado
            $numeroCertificado = Certificado::gerarNumeroCertificado();
            
            // Usar helper para gerar caminho estruturado
            $caminhoCompleto = CertificadoHelper::gerarCaminhoParaUsuario($user, $nomeCurso, $numeroCertificado);
            
            // Garantir que o diretório existe
            if (!CertificadoHelper::criarEstruturaDiretorios($caminhoCompleto)) {
                throw new \Exception('Não foi possível criar a estrutura de diretórios');
            }
            
            // Extrair diretório e nome do arquivo
            $diretorio = dirname($caminhoCompleto);
            $nomeArquivo = basename($caminhoCompleto);
            
            // Salvar arquivo no storage
            $arquivoSalvo = $request->file('certificado_pdf')->storeAs($diretorio, $nomeArquivo, 'local');
            
            if (!$arquivoSalvo) {
                throw new \Exception('Falha ao salvar o arquivo do certificado');
            }
            
            // Salvar registro no banco
            $certificado = Certificado::create([
                'matricula_id' => null,
                'user_id' => $user->id,
                'curso_id' => $curso ? $curso->id : null,
                'numero_certificado' => $numeroCertificado,
                'arquivo_path' => $caminhoCompleto,
                'data_emissao' => now(),
                'data_conclusao_curso' => $validated['data_conclusao'],
                'carga_horaria' => $validated['carga_horaria'],
                'nome_aluno' => $user->name,
                'cpf_aluno' => $user->cpf,
                'nome_curso' => $nomeCurso,
                'tipo_origem' => $tipoOrigem,
                'ativo' => true
            ]);
            
            Log::info('Certificado adicionado manualmente pelo admin', [
                'certificado_id' => $certificado->id,
                'user_id' => $user->id,
                'curso_id' => $curso ? $curso->id : null,
                'tipo_origem' => $tipoOrigem,
                'admin_id' => Auth::id(),
                'arquivo_original' => $request->file('certificado_pdf')->getClientOriginalName(),
                'caminho_final' => $caminhoCompleto
            ]);
            
            return redirect()->back()->with('success', 'Certificado adicionado com sucesso!');
            
        } catch (\Exception $e) {
            Log::error('Erro ao adicionar certificado para usuário', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'admin_id' => Auth::id()
            ]);
            
            return redirect()->back()->with('error', 'Erro ao processar certificado: ' . $e->getMessage());
        }
    }

    /**
     * Remover certificado de um usuário
     */
    public function removerCertificado(User $user, Certificado $certificado)
    {
        try {
            // Verificar permissão
            $this->authorize('update', $user);
            
            // Verificar se o certificado pertence ao usuário
            if ($certificado->user_id !== $user->id) {
                return redirect()->back()->with('error', 'Este certificado não pertence ao usuário selecionado');
            }
            
            // Remover arquivo do storage
            if ($certificado->arquivoExiste()) {
                Storage::disk('local')->delete($certificado->arquivo_path);
                
                // Limpar diretórios vazios com helper
                CertificadoHelper::limparDiretoriosVazios($certificado->arquivo_path);
            }
            
            // Remover registro do banco
            $certificadoInfo = [
                'id' => $certificado->id,
                'numero' => $certificado->numero_certificado,
                'curso' => $certificado->nome_curso,
                'arquivo_path' => $certificado->arquivo_path
            ];
            
            $certificado->delete();
            
            Log::info('Certificado removido pelo admin', [
                'certificado_info' => $certificadoInfo,
                'user_id' => $user->id,
                'admin_id' => Auth::id()
            ]);
            
            return redirect()->back()->with('success', 'Certificado removido com sucesso!');
            
        } catch (\Exception $e) {
            Log::error('Erro ao remover certificado do usuário', [
                'error' => $e->getMessage(),
                'certificado_id' => $certificado->id ?? null,
                'user_id' => $user->id,
                'admin_id' => Auth::id()
            ]);
            
            return redirect()->back()->with('error', 'Erro ao remover certificado: ' . $e->getMessage());
        }
    }
}