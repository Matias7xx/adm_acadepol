<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Director;
use App\Helpers\UploadHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DirectorController extends Controller
{
  public function index()
  {
    $this->authorize('adminViewAny', Director::class);
    $diretores = (new Director())->newQuery();

    if (request()->has('search')) {
      $diretores->where('nome', 'Like', '%' . request()->input('search') . '%');
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
      // Ordenação: diretor atual primeiro, depois cronológica (mais recente primeiro)
      $diretores
        ->orderByRaw('(CASE WHEN atual = true THEN 0 ELSE 1 END)')
        ->orderBy('data_inicio', 'desc');
    }

    $diretores = $diretores
      ->paginate(config('admin.paginate.per_page'))
      ->onEachSide(config('admin.paginate.each_side'))
      ->appends(request()->query());

    $diretores->getCollection()->transform(function ($director) {
      $director->imagem = $director->imagem
        ? UploadHelper::getPublicUrl($director->imagem)
        : null;
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

    $validated = $request->validate(
      [
        'nome' => 'required|string|max:255',
        'data_inicio' => 'required|date',
        'data_fim' => [
          'nullable',
          'date',
          'after_or_equal:data_inicio',
          // Se atual = false, data_fim é obrigatória
          Rule::requiredIf(!$request->boolean('atual')),
        ],
        'historico' => 'nullable|string|max:5000',
        'realizacoes' => 'nullable|array',
        'realizacoes.*' => 'string|max:500',
        'atual' => 'boolean',
        'imagem_file' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
      ],
      [
        'data_fim.required_if' =>
          'A data de término é obrigatória quando não é o diretor atual.',
        'data_fim.after_or_equal' =>
          'A data de término deve ser posterior ou igual à data de início.',
        'imagem_file.max' => 'A imagem deve ter no máximo 2MB.',
        'realizacoes.*.max' =>
          'Cada realização deve ter no máximo 500 caracteres.',
      ],
    );

    // Verificar se já existe um diretor atual
    if ($validated['atual']) {
      $diretorAtualExistente = Director::where('atual', true)->first();
      if ($diretorAtualExistente) {
        return back()->withErrors([
          'atual' =>
            'Já existe um diretor atual. Você deve primeiro desmarcar o diretor atual antes de definir um novo.',
        ]);
      }
    }

    // Processar upload de imagem
    if ($request->hasFile('imagem_file')) {
      $imagePath = UploadHelper::uploadImage(
        $request->file('imagem_file'),
        'diretores',
        $validated['nome'],
        'diretor',
      );
      $validated['imagem'] = $imagePath;
    }

    // Se atual = true, garantir que data_fim seja null
    if ($validated['atual']) {
      $validated['data_fim'] = null;
    }

    // Converter realizações para JSON
    $validated['realizacoes'] = json_encode($validated['realizacoes'] ?? []);

    // Remover arquivo da validação
    unset($validated['imagem_file']);

    Director::create($validated);

    return redirect()
      ->route('admin.directors.index')
      ->with('message', 'Diretor cadastrado com sucesso!');
  }

  public function show(Director $director)
  {
    $this->authorize('adminView', $director);

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
      'imagem' => $director->imagem
        ? UploadHelper::getPublicUrl($director->imagem)
        : null,
      'created_at' => $director->created_at,
      'updated_at' => $director->updated_at,
      'periodo_formatado' => $director->periodo_formatado,
    ];

    return Inertia::render('Admin/Diretores/Show', [
      'diretor' => $directorData,
    ]);
  }

  public function edit(Director $director)
  {
    $this->authorize('adminUpdate', $director);

    $directorData = [
      'id' => $director->id,
      'nome' => $director->nome,
      'data_inicio' => $director->data_inicio
        ? $director->data_inicio->format('Y-m-d')
        : null,
      'data_fim' => $director->data_fim
        ? $director->data_fim->format('Y-m-d')
        : null,
      'historico' => $director->historico,
      'realizacoes' => is_string($director->realizacoes)
        ? json_decode($director->realizacoes, true)
        : $director->realizacoes,
      'atual' => $director->atual,
      'imagem' => $director->imagem
        ? UploadHelper::getPublicUrl($director->imagem)
        : null,
    ];

    return Inertia::render('Admin/Diretores/Edit', [
      'diretor' => $directorData,
    ]);
  }

  public function update(Request $request, Director $director)
  {
    $this->authorize('adminUpdate', $director);

    $validated = $request->validate(
      [
        'nome' => 'required|string|max:255',
        'data_inicio' => 'required|date',
        'data_fim' => [
          'nullable',
          'date',
          'after_or_equal:data_inicio',
          // Se atual = false, data_fim é obrigatória
          Rule::requiredIf(!$request->boolean('atual')),
        ],
        'historico' => 'nullable|string|max:5000',
        'realizacoes' => 'nullable|array',
        'realizacoes.*' => 'string|max:500',
        'atual' => 'boolean',
        'imagem_file' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
      ],
      [
        'data_fim.required_if' =>
          'A data de término é obrigatória quando não é o diretor atual.',
        'data_fim.after_or_equal' =>
          'A data de término deve ser posterior ou igual à data de início.',
        'imagem_file.max' => 'A imagem deve ter no máximo 2MB.',
        'realizacoes.*.max' =>
          'Cada realização deve ter no máximo 500 caracteres.',
      ],
    );

    // Verificar se já existe outro diretor atual (apenas se este não for o atual)
    if ($validated['atual'] && !$director->atual) {
      $diretorAtualExistente = Director::where('atual', true)
        ->where('id', '!=', $director->id)
        ->first();
      if ($diretorAtualExistente) {
        return back()->withErrors([
          'atual' => "Já existe um diretor atual ({$diretorAtualExistente->nome}). Você deve primeiro desmarcar o diretor atual antes de definir um novo.",
        ]);
      }
    }

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
        'diretor',
      );
      $validated['imagem'] = $imagePath;
    } elseif ($nomeMudou && $director->imagem) {
      // Se o nome mudou mas não há nova imagem, mover a imagem existente
      $novaImagemPath = UploadHelper::moveImage(
        $director->imagem,
        'diretores',
        $validated['nome'],
        'diretor',
      );
      if ($novaImagemPath) {
        $validated['imagem'] = $novaImagemPath;
      }
    }

    // Se atual = true, garantir que data_fim seja null
    if ($validated['atual']) {
      $validated['data_fim'] = null;
    }

    // Converter realizações para JSON
    $validated['realizacoes'] = json_encode($validated['realizacoes'] ?? []);

    // Remover arquivo da validação
    unset($validated['imagem_file']);

    $director->update($validated);

    // Limpar pasta antiga se o nome mudou
    if ($nomeMudou) {
      $pastaAntiga =
        'diretores/' . UploadHelper::sanitizeFolderName($nomeAntigo);
      UploadHelper::cleanupEmptyFolder($pastaAntiga);
    }

    return redirect()
      ->route('admin.directors.index')
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
    $pastaDiretor =
      'diretores/' . UploadHelper::sanitizeFolderName($nomeDiretor);
    UploadHelper::cleanupEmptyFolder($pastaDiretor);

    return redirect()
      ->route('admin.directors.index')
      ->with('message', 'Diretor excluído com sucesso!');
  }

  /**
   * endpoint para listar diretores na galeria pública
   */
  public function listarDiretores()
  {
    try {
      $diretores = Director::orderByRaw(
        '(CASE WHEN atual = true THEN 0 ELSE 1 END)',
      )
        ->orderBy('data_inicio', 'desc')
        ->get()
        ->map(function ($director) {
          // Formatar o período
          $inicio = $director->data_inicio
            ? $director->data_inicio->format('d/m/Y')
            : '';
          $periodo = $inicio;

          if ($director->atual || $director->data_fim === null) {
            $periodo .= ' - ATUALMENTE';
          } elseif ($director->data_fim) {
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
            'imagem' => $director->imagem
              ? UploadHelper::getPublicUrl($director->imagem)
              : '/images/placeholder-profile.jpg',
            'realizacoes' => $realizacoes,
            'atual' => (bool) $director->atual,
            // dados para ordenação cronológica no frontend
            'data_inicio' => $director->data_inicio?->format('Y-m-d'),
            'data_fim' => $director->data_fim?->format('Y-m-d'),
          ];
        });

      return response()->json($diretores);
    } catch (\Exception $e) {
      \Log::error('Erro ao listar diretores: ' . $e->getMessage());
      return response()->json(
        [
          'error' => 'Erro ao carregar diretores: ' . $e->getMessage(),
        ],
        500,
      );
    }
  }

  /**
   * Método para alternar diretor atual
   */
  public function toggleAtual(Director $director)
  {
    $this->authorize('adminUpdate', $director);

    // Se está marcando como atual
    if (!$director->atual) {
      // Desmarcar outros diretores atuais
      Director::where('atual', true)->update([
        'atual' => false,
        'data_fim' => now(),
      ]);

      // Marcar este como atual
      $director->update([
        'atual' => true,
        'data_fim' => null,
      ]);

      $message = "{$director->nome} foi marcado como diretor atual.";
    } else {
      // Desmarcar como atual
      $director->update([
        'atual' => false,
        'data_fim' => now()->format('Y-m-d'),
      ]);

      $message = "{$director->nome} não é mais o diretor atual.";
    }

    return back()->with('message', $message);
  }
}
