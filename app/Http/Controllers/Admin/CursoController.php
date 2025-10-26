<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Helpers\UploadHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CursoController extends Controller
{
  public function index()
  {
    $this->authorize('adminViewAny', Curso::class);
    $cursos = (new Curso())->newQuery();

    if (request()->has('search')) {
      $searchTerm = trim(request()->input('search'));
      if (!empty($searchTerm)) {
        $cursos->where(function ($query) use ($searchTerm) {
          $query
            ->whereRaw('LOWER(nome) LIKE ?', [
              '%' . strtolower($searchTerm) . '%',
            ])
            ->orWhereRaw('LOWER(descricao) LIKE ?', [
              '%' . strtolower($searchTerm) . '%',
            ])
            ->orWhereRaw('LOWER(localizacao) LIKE ?', [
              '%' . strtolower($searchTerm) . '%',
            ])
            ->orWhereRaw('LOWER(modalidade) LIKE ?', [
              '%' . strtolower($searchTerm) . '%',
            ]);
        });
      }
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

    $cursos = $cursos
      ->paginate(config('admin.paginate.per_page'))
      ->onEachSide(config('admin.paginate.each_side'))
      ->appends(request()->query());

    $cursos->getCollection()->transform(function ($curso) {
      $curso->imagem = $curso->imagem
        ? UploadHelper::getPublicUrl($curso->imagem)
        : null;
      return $curso;
    });

    return Inertia::render('Admin/Cursos/Index', [
      'cursos' => $cursos,
      'filters' => request()->all('search'),
      'can' => [
        'create' => Auth::user()->can('curso create'),
        'edit' => Auth::user()->can('curso edit'),
        'delete' => Auth::user()->can('curso delete'),
      ],
    ]);
  }

  /**
   * Exibe cursos públicos com busca case-insensitive melhorada
   */
  public function cursosPublicos(Request $request)
  {
    $query = Curso::query();

    // Aplicar busca case-insensitive se fornecida
    if ($request->filled('search')) {
      $searchTerm = trim($request->get('search'));

      if (!empty($searchTerm)) {
        $query->where(function ($q) use ($searchTerm) {
          // Converter tanto o termo de busca quanto os campos para minúsculas
          $searchLower = strtolower($searchTerm);

          $q->whereRaw('LOWER(nome) LIKE ?', ["%{$searchLower}%"])
            ->orWhereRaw('LOWER(descricao) LIKE ?', ["%{$searchLower}%"])
            ->orWhereRaw('LOWER(localizacao) LIKE ?', ["%{$searchLower}%"])
            ->orWhereRaw('LOWER(modalidade) LIKE ?', ["%{$searchLower}%"])
            ->orWhereRaw('LOWER(status) LIKE ?', ["%{$searchLower}%"]);
        });
      }
    }

    // Subquery para encontrar o ID (mais recente) para cada curso
    $latestCourseIds = DB::table('cursos as c1')
      ->select('c1.id')
      ->whereRaw(
        'c1.id = (SELECT MAX(c2.id) FROM cursos c2 WHERE c2.nome = c1.nome)',
      )
      ->pluck('id');

    // Filtrar apenas os cursos mais recentes por nome
    $query->whereIn('id', $latestCourseIds);

    $cursos = $query
      ->orderByRaw(
        "
                CASE
                    WHEN status = 'aberto' THEN 1
                    WHEN status = 'fechado' THEN 2
                    WHEN status = 'suspenso' THEN 3
                    WHEN status = 'concluido' THEN 4
                    ELSE 5
                END
            ",
      )
      ->orderBy('created_at', 'desc')
      ->orderBy('id', 'desc')
      ->paginate(8) // 8 por página
      ->withQueryString(); // Mantém parâmetros de busca na paginação

    // Transformar os dados para incluir URLs corretas das imagens
    $cursos->getCollection()->transform(function ($curso) {
      $curso->imagem = $curso->imagem
        ? UploadHelper::getPublicUrl($curso->imagem)
        : null;

      // Garantir que o status está correto para o frontend
      $curso->is_concluido = in_array($curso->status, ['concluido', 'fechado']);
      $curso->is_aberto = $curso->status === 'aberto';

      return $curso;
    });

    return Inertia::render('Cursos', [
      'cursos' => $cursos,
      'filters' => [
        'search' => $request->get('search'),
      ],
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
      'imagem_file' => 'nullable|image|max:2048',
      'data_inicio' => 'required|date',
      'data_fim' => 'required|date|after_or_equal:data_inicio',
      'carga_horaria' => 'required|integer|min:1',
      'pre_requisitos' => 'nullable|array',
      'enxoval' => 'nullable|array',
      'localizacao' => 'required|string|max:255',
      'capacidade_maxima' => 'required|integer|min:1',
      'modalidade' => 'required|string|max:20',
      'certificacao' => 'boolean',
      'certificacao_modelo' => 'nullable|string',
      'status' => 'required|string|max:20',
    ]);

    // Processar upload da imagem se enviada
    if ($request->hasFile('imagem_file')) {
      $imagePath = UploadHelper::uploadImage(
        $request->file('imagem_file'),
        'cursos',
        $validated['nome'],
        'curso',
      );
      $validated['imagem'] = $imagePath;
    }

    // Converter arrays para JSON
    $validated['pre_requisitos'] = json_encode(
      $validated['pre_requisitos'] ?? [],
    );
    $validated['enxoval'] = json_encode($validated['enxoval'] ?? []);

    // Remover arquivo da validação antes de criar
    unset($validated['imagem_file']);

    Curso::create($validated);

    return redirect()
      ->route('admin.cursos.index')
      ->with('message', 'Curso cadastrado com sucesso!');
  }

  public function show(Curso $curso)
  {
    $this->authorize('adminView', $curso);

    // Formatar os dados do curso incluindo URL correta da imagem
    $cursoData = [
      'id' => $curso->id,
      'nome' => $curso->nome,
      'descricao' => $curso->descricao,
      'imagem' => $curso->imagem
        ? UploadHelper::getPublicUrl($curso->imagem)
        : null,
      'data_inicio' => $curso->data_inicio,
      'data_fim' => $curso->data_fim,
      'carga_horaria' => $curso->carga_horaria,
      'pre_requisitos' => is_string($curso->pre_requisitos)
        ? json_decode($curso->pre_requisitos, true)
        : $curso->pre_requisitos,
      'enxoval' => is_string($curso->enxoval)
        ? json_decode($curso->enxoval, true)
        : $curso->enxoval,
      'localizacao' => $curso->localizacao,
      'capacidade_maxima' => $curso->capacidade_maxima,
      'modalidade' => $curso->modalidade,
      'certificacao' => $curso->certificacao,
      'certificacao_modelo' => $curso->certificacao_modelo,
      'status' => $curso->status,
      'created_at' => $curso->created_at,
      'updated_at' => $curso->updated_at,
    ];

    return Inertia::render('Admin/Cursos/Show', [
      'curso' => $cursoData,
    ]);
  }

  public function showCurso(Curso $curso)
  {
    //Formatar os dados do curso para exibição pública
    $cursoData = [
      'id' => $curso->id,
      'nome' => $curso->nome,
      'descricao' => $curso->descricao,
      'imagem' => $curso->imagem
        ? UploadHelper::getPublicUrl($curso->imagem)
        : null,
      'data_inicio' => $curso->data_inicio,
      'data_fim' => $curso->data_fim,
      'carga_horaria' => $curso->carga_horaria,
      'pre_requisitos' => is_string($curso->pre_requisitos)
        ? json_decode($curso->pre_requisitos, true)
        : $curso->pre_requisitos,
      'enxoval' => is_string($curso->enxoval)
        ? json_decode($curso->enxoval, true)
        : $curso->enxoval,
      'localizacao' => $curso->localizacao,
      'capacidade_maxima' => $curso->capacidade_maxima,
      'modalidade' => $curso->modalidade,
      'certificacao' => $curso->certificacao,
      'certificacao_modelo' => $curso->certificacao_modelo,
      'status' => $curso->status,
    ];

    return Inertia::render('CursoDetalhe', [
      'curso' => $cursoData,
    ]);
  }

  public function exibirAlunos($id)
  {
    $curso = Curso::with('alunos')->findOrFail($id);

    return response()->json([
      'curso' => $curso,
      'quantidade_inscritos' => $curso->alunos->count(),
      'inscritos' => $curso->alunos,
    ]);
  }

  public function edit(Curso $curso)
  {
    $this->authorize('adminUpdate', $curso);

    // Formatar os dados do curso para edição
    $cursoData = [
      'id' => $curso->id,
      'nome' => $curso->nome,
      'descricao' => $curso->descricao,
      'imagem' => $curso->imagem
        ? UploadHelper::getPublicUrl($curso->imagem)
        : null,
      'data_inicio' => $curso->data_inicio
        ? $curso->data_inicio->format('Y-m-d')
        : null,
      'data_fim' => $curso->data_fim ? $curso->data_fim->format('Y-m-d') : null,
      'carga_horaria' => $curso->carga_horaria,
      'pre_requisitos' => is_string($curso->pre_requisitos)
        ? json_decode($curso->pre_requisitos, true)
        : $curso->pre_requisitos,
      'enxoval' => is_string($curso->enxoval)
        ? json_decode($curso->enxoval, true)
        : $curso->enxoval,
      'localizacao' => $curso->localizacao,
      'capacidade_maxima' => $curso->capacidade_maxima,
      'modalidade' => $curso->modalidade,
      'certificacao' => $curso->certificacao,
      'certificacao_modelo' => $curso->certificacao_modelo,
      'status' => $curso->status,
    ];

    return Inertia::render('Admin/Cursos/Edit', [
      'curso' => $cursoData,
    ]);
  }

  public function update(Request $request, Curso $curso)
  {
    \Log::info('Request recebida:', $request->all());
    $this->authorize('adminUpdate', $curso);

    $validated = $request->validate([
      'nome' => 'required|string|max:255',
      'descricao' => 'nullable|string',
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

    // Verificar se o nome do curso mudou (para mover imagens)
    $nomeAntigo = $curso->nome;
    $nomeNovo = $validated['nome'];
    $nomeMudou = $nomeAntigo !== $nomeNovo;

    // Processar upload de nova imagem
    if ($request->hasFile('imagem_file')) {
      // Remover imagem antiga
      if ($curso->imagem) {
        UploadHelper::deleteImage($curso->imagem);
      }

      // Upload nova imagem
      $imagePath = UploadHelper::uploadImage(
        $request->file('imagem_file'),
        'cursos',
        $validated['nome'],
        'curso',
      );
      $validated['imagem'] = $imagePath;
    } elseif ($nomeMudou && $curso->imagem) {
      // Se o nome mudou mas não há nova imagem, mover a imagem existente
      $novaImagemPath = UploadHelper::moveImage(
        $curso->imagem,
        'cursos',
        $validated['nome'],
        'curso',
      );
      if ($novaImagemPath) {
        $validated['imagem'] = $novaImagemPath;
      }
    }

    // Converter arrays para JSON
    $validated['pre_requisitos'] = json_encode(
      $validated['pre_requisitos'] ?? [],
    );
    $validated['enxoval'] = json_encode($validated['enxoval'] ?? []);

    // Remover arquivo da validação antes de atualizar
    unset($validated['imagem_file']);

    // Atualizar o curso
    $curso->update($validated);

    // Limpar pasta antiga se o nome mudou
    if ($nomeMudou) {
      $pastaAntiga = 'cursos/' . UploadHelper::sanitizeFolderName($nomeAntigo);
      UploadHelper::cleanupEmptyFolder($pastaAntiga);
    }

    return redirect()
      ->route('admin.cursos.index')
      ->with('message', 'Curso atualizado com sucesso!');
  }

  public function destroy(Curso $curso)
  {
    $this->authorize('adminDelete', $curso);

    // Verificar se há matrículas associadas
    $matriculasAtivas = $curso
      ->alunos()
      ->whereIn('status', ['aprovada', 'pendente'])
      ->count();

    if ($matriculasAtivas > 0) {
      return redirect()
        ->route('admin.cursos.index')
        ->with(
          'message',
          'Não é possível excluir um curso com matrículas ativas.',
        );
    }

    // Remover a imagem do curso
    if ($curso->imagem) {
      UploadHelper::deleteImage($curso->imagem);
    }

    $curso->delete();

    // Limpar pasta do curso
    $pastaCurso = 'cursos/' . UploadHelper::sanitizeFolderName($curso->nome);
    UploadHelper::cleanupEmptyFolder($pastaCurso);

    return redirect()
      ->route('admin.cursos.index')
      ->with('message', 'Curso excluído com sucesso!');
  }
}
