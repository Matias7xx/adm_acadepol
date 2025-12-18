<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Noticia;
use App\Helpers\UploadHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Mews\Purifier\Facades\Purifier;

class NoticiaController extends Controller
{
  public function index()
  {
    $this->authorize('adminViewAny', Noticia::class);

    $noticias = Noticia::query()
      ->when(request('search'), function ($query, $search) {
        $query
          ->where('titulo', 'like', "%{$search}%")
          ->orWhere('descricao_curta', 'like', "%{$search}%");
      })
      ->when(request('status'), function ($query, $status) {
        $query->where('status', $status);
      })
      ->when(request('destaque'), function ($query, $destaque) {
        $query->where('destaque', $destaque === 'true');
      })
      ->when(
        request('sort'),
        function ($query, $sortColumn) {
          $direction = 'asc';
          if (strncmp($sortColumn, '-', 1) === 0) {
            $direction = 'desc';
            $sortColumn = substr($sortColumn, 1);
          }
          $query->orderBy($sortColumn, $direction);
        },
        function ($query) {
          $query
            ->orderBy('data_publicacao', 'desc') // Ordena por data_publicacao (mais novo primeiro)
            ->orderBy('id', 'desc'); // Critério secundário: dentro da mesma data, ordena por id (mais novo primeiro)
        },
      )
      ->paginate(10)
      ->withQueryString();

    return Inertia::render('Admin/Noticias/Index', [
      'noticias' => $noticias,
      'filters' => request()->only(['search', 'status', 'destaque', 'sort']),
      'can' => [
        'create' => Auth::user()->can('adminCreate', Noticia::class),
        'edit' => Auth::user()->can('adminEdit', Noticia::class),
        'delete' => Auth::user()->can('adminDelete', Noticia::class),
      ],
    ]);
  }

  public function create()
  {
    $this->authorize('adminCreate', Noticia::class);

    return Inertia::render('Admin/Noticias/Create', [
      'statusOptions' => [
        ['value' => 'rascunho', 'label' => 'Rascunho'],
        ['value' => 'publicado', 'label' => 'Publicado'],
        ['value' => 'arquivado', 'label' => 'Arquivado'],
      ],
    ]);
  }

  public function store(Request $request)
  {
    $this->authorize('adminCreate', Noticia::class);

    $validated = $request->validate([
      'titulo' => 'required|string|max:255',
      'descricao_curta' => 'required|string|max:500',
      'conteudo' => 'nullable|string',
      'imagem' => 'nullable|image|max:2048',
      'carousel_images' => 'nullable|array',
      'carousel_images.*.url' => 'nullable|string',
      'destaque' => 'boolean',
      'data_publicacao' => 'required|date',
      'status' => 'required|in:rascunho,publicado,arquivado',
    ]);

    // Processar imagens e documentos do conteúdo primeiro
    $validated['conteudo'] = $this->processContentMedia(
      $validated['conteudo'],
      $validated['titulo'],
    );

    // Sanitizar o HTML para suportar documentos
    $validated['conteudo'] = Purifier::clean($validated['conteudo'], [
      'HTML.Allowed' =>
        'p,b,i,strong,em,u,a[href|title|target|download|rel],ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,img[src|alt|width|height|class],hr,br,iframe[src|width|height|frameborder|allowfullscreen],div[class|style],span[class|style],video[src|controls|width|height],source[src|type],table,tr,td[colspan|rowspan],th[colspan|rowspan]',
      'HTML.SafeIframe' => true,
      'URI.SafeIframeRegexp' =>
        '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%',
      'AutoFormat.RemoveEmpty' => false, // Mudou para false para preservar estrutura dos documentos
      'CSS.AllowedProperties' =>
        'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align,width,height,margin,margin-left,margin-right,border,border-radius,padding,display,flex-direction,align-items,justify-content,flex,gap,transition,box-sizing,transform',
      'AutoFormat.AutoParagraph' => true,
      'AutoFormat.Linkify' => true,
      'Attr.AllowedClasses' => 'document-download', // Permitir classe específica dos documentos
      'HTML.TidyLevel' => 'none',
      'HTML.TargetBlank' => true, // Permite target="_blank"
    ]);

    // Processar upload de imagem de capa
    if ($request->hasFile('imagem')) {
      $imagePath = UploadHelper::uploadImage(
        $request->file('imagem'),
        'noticias',
        $validated['titulo'],
        'capa',
      );
      if ($imagePath) {
        $validated['imagem'] = $imagePath;
      }
    }

    // Processar imagens do carrossel (VISUALIZAÇÃO DA NOTÍCIA)
    if (
      isset($validated['carousel_images']) &&
      is_array($validated['carousel_images'])
    ) {
      $validated['carousel_images'] = array_values(
        $validated['carousel_images'],
      );
    }

    Noticia::create($validated);

    \Cache::forget('noticias_destaque_banner');

    // Invalidar cache após criar notícia
    $this->invalidateAllNoticiasCache();

    return redirect()
      ->route('admin.noticias.index')
      ->with('message', 'Notícia criada com sucesso.');
  }

  public function show(Noticia $noticia)
  {
    $this->authorize('adminView', $noticia);

    return Inertia::render('Admin/Noticias/Show', [
      'noticia' => [
        'id' => $noticia->id,
        'titulo' => $noticia->titulo,
        'descricao_curta' => $noticia->descricao_curta,
        'conteudo' => $noticia->conteudo,
        'imagem' => $noticia->imagem
          ? UploadHelper::getPublicUrl($noticia->imagem)
          : null,
        'carousel_images' => $noticia->carousel_images ?? [],
        'destaque' => $noticia->destaque,
        'data_publicacao' => $noticia->data_publicacao->format('Y-m-d'),
        'data_formatada' => $noticia->data_formatada,
        'status' => $noticia->status,
        'visualizacoes' => $noticia->visualizacoes,
        'created_at' => $noticia->created_at->toISOString(),
        'updated_at' => $noticia->updated_at->toISOString(),
      ],
      'can' => [
        'edit' => Auth::user()->can('adminEdit', Noticia::class),
        'delete' => Auth::user()->can('adminDelete', Noticia::class),
      ],
    ]);
  }

  public function edit(Noticia $noticia)
  {
    $this->authorize('adminUpdate', $noticia);

    return Inertia::render('Admin/Noticias/Edit', [
      'noticia' => [
        'id' => $noticia->id,
        'titulo' => $noticia->titulo,
        'descricao_curta' => $noticia->descricao_curta,
        'conteudo' => $noticia->conteudo,
        'imagem' => $noticia->imagem
          ? UploadHelper::getPublicUrl($noticia->imagem)
          : null,
        'carousel_images' => $noticia->carousel_images ?? [],
        'destaque' => $noticia->destaque,
        'data_publicacao' => $noticia->data_publicacao->format('Y-m-d'),
        'status' => $noticia->status,
      ],
      'statusOptions' => [
        ['value' => 'rascunho', 'label' => 'Rascunho'],
        ['value' => 'publicado', 'label' => 'Publicado'],
        ['value' => 'arquivado', 'label' => 'Arquivado'],
      ],
    ]);
  }

  public function update(Request $request, Noticia $noticia)
  {
    $this->authorize('adminUpdate', $noticia);

    $validated = $request->validate([
      'titulo' => 'required|string|max:255',
      'descricao_curta' => 'required|string|max:500',
      'conteudo' => 'nullable|string',
      'imagem' => 'nullable|image|max:2048',
      'carousel_images' => 'nullable|array',
      'carousel_images.*.url' => 'nullable|string',
      'remover_imagem' => 'nullable|boolean',
      'destaque' => 'boolean',
      'data_publicacao' => 'required|date',
      'status' => 'required|in:rascunho,publicado,arquivado',
    ]);

    // Verificar se o título da notícia mudou
    $tituloAntigo = $noticia->titulo;
    $tituloNovo = $validated['titulo'];
    $tituloMudou = $tituloAntigo !== $tituloNovo;

    // Processar apenas imagens base64 novas e documentos temporários, não reprocessar URLs existentes
    $validated['conteudo'] = $this->processOnlyNewContentMedia(
      $validated['conteudo'],
      $validated['titulo'],
    );

    // Sanitizar o HTML para suportar documentos
    $validated['conteudo'] = Purifier::clean($validated['conteudo'], [
      'HTML.Allowed' =>
        'p,b,i,strong,em,u,a[href|title|target|download|rel],ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,img[src|alt|width|height|class],hr,br,iframe[src|width|height|frameborder|allowfullscreen],div[class|style],span[class|style],video[src|controls|width|height],source[src|type],table,tr,td[colspan|rowspan],th[colspan|rowspan]',
      'HTML.SafeIframe' => true,
      'URI.SafeIframeRegexp' =>
        '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%',
      'AutoFormat.RemoveEmpty' => false, // Mudou para false para preservar estrutura dos documentos
      'CSS.AllowedProperties' =>
        'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align,width,height,margin,margin-left,margin-right,border,border-radius,padding,display,flex-direction,align-items,justify-content,flex,gap,transition,box-sizing,transform',
      'AutoFormat.AutoParagraph' => true,
      'AutoFormat.Linkify' => true,
      'Attr.AllowedClasses' => 'document-download', // Permitir classe específica dos documentos
      'HTML.TidyLevel' => 'none',
      'HTML.TargetBlank' => true, // Permite target="_blank"
    ]);

    // Remover imagem atual se solicitado
    if ($request->input('remover_imagem') && $noticia->imagem) {
      UploadHelper::deleteImage($noticia->imagem);
      $validated['imagem'] = null;
    }

    // Processar upload de nova imagem de capa
    if ($request->hasFile('imagem')) {
      // Remover imagem anterior se existir
      if ($noticia->imagem) {
        UploadHelper::deleteImage($noticia->imagem);
      }

      // Upload nova imagem
      $imagePath = UploadHelper::uploadImage(
        $request->file('imagem'),
        'noticias',
        $validated['titulo'],
        'capa',
      );
      if ($imagePath) {
        $validated['imagem'] = $imagePath;
      }
    } else {
      // Se não há novo upload E não mudou título E não é para remover
      // então mantém a imagem atual removendo o campo do $validated
      if (!$request->input('remover_imagem')) {
        unset($validated['imagem']);
      }
    }

    // Remover campo remover_imagem antes de atualizar
    if (isset($validated['remover_imagem'])) {
      unset($validated['remover_imagem']);
    }

    // Processar imagens do carrossel
    if (
      isset($validated['carousel_images']) &&
      is_array($validated['carousel_images'])
    ) {
      $validated['carousel_images'] = array_values(
        $validated['carousel_images'],
      );
    }

    // Atualizar notícia
    $noticia->update($validated);

    \Cache::forget('noticias_destaque_banner');
    $this->invalidateAllNoticiasCache();

    // Se o título mudou, mover todas as imagens e documentos de conteúdo para nova pasta
    if ($tituloMudou) {
      $this->moveContentMedia($noticia->conteudo, $tituloAntigo, $tituloNovo);

      // Limpar pasta antiga
      $pastaAntiga =
        'noticias/' . UploadHelper::sanitizeFolderName($tituloAntigo);
      UploadHelper::cleanupEmptyFolder($pastaAntiga);
    }

    // Invalidar cache após atualizar notícia
    $this->invalidateAllNoticiasCache();

    return redirect()
      ->route('admin.noticias.index')
      ->with('message', 'Notícia atualizada com sucesso.');
  }

  /**
   * Processa APENAS imagens base64 novas e documentos temporários, preservando URLs existentes
   */
  private function processOnlyNewContentMedia($html, $tituloNoticia)
  {
    if (!$html) {
      return $html;
    }

    // Processar imagens base64 (imagens realmente novas)
    $html = $this->processOnlyNewContentImages($html, $tituloNoticia);

    // Processar documentos temporários (que estão na pasta temp)
    $html = $this->processTemporaryDocuments($html, $tituloNoticia);

    return $html;
  }

  /**
   * Processa APENAS imagens base64 novas, preservando URLs existentes
   */
  private function processOnlyNewContentImages($html, $tituloNoticia)
  {
    if (!$html) {
      return $html;
    }

    // Busca APENAS imagens em base64 (imagens realmente novas)
    preg_match_all(
      '/<img[^>]+src="data:image\/([^;]+);base64,([^"]+)"[^>]*>/i',
      $html,
      $matches,
      PREG_SET_ORDER,
    );

    // Se não encontrou imagens base64, retorna o HTML inalterado
    if (empty($matches)) {
      return $html;
    }

    foreach ($matches as $match) {
      $extension = $match[1]; // jpeg, png, etc.
      $base64Image = $match[2];
      $fullBase64 = 'data:image/' . $extension . ';base64,' . $base64Image;

      // Fazer upload usando helper apenas para imagens novas
      $imagePath = UploadHelper::uploadBase64Image(
        $fullBase64,
        'noticias',
        $tituloNoticia,
        'content',
        $extension,
      );

      if ($imagePath) {
        // Substituir APENAS esta imagem base64 específica pela URL da imagem
        $imageUrl = UploadHelper::getPublicUrl($imagePath);
        $html = str_replace(
          $match[0],
          str_replace(
            'src="data:image/' . $extension . ';base64,' . $base64Image . '"',
            'src="' . $imageUrl . '"',
            $match[0],
          ),
          $html,
        );
      }
    }

    return $html;
  }

  /**
   * Processa documentos temporários (que estão na pasta temp) movendo para pasta definitiva
   */
  private function processTemporaryDocuments($html, $tituloNoticia)
  {
    if (!$html) {
      return $html;
    }

    // Busca documentos que estão na pasta temp
    preg_match_all(
      '/<a[^>]+href="([^"]*\/storage\/noticias\/temp\/[^"]+)"[^>]*>/i',
      $html,
      $matches,
      PREG_SET_ORDER,
    );

    foreach ($matches as $match) {
      $tempUrl = $match[1];

      // Extrair o caminho relativo do storage
      $relativePath = str_replace(
        '/storage/',
        '',
        parse_url($tempUrl, PHP_URL_PATH),
      );

      // Verificar se o arquivo realmente existe na pasta temp
      if (Storage::exists($relativePath)) {
        // Mover arquivo da pasta temp para pasta definitiva da notícia
        $newPath = UploadHelper::moveImage(
          $relativePath,
          'noticias',
          $tituloNoticia,
          'files',
        );

        if ($newPath) {
          // Substituir a URL temporária pela URL definitiva
          $newUrl = UploadHelper::getPublicUrl($newPath);
          $html = str_replace($tempUrl, $newUrl, $html);
        }
      }
    }

    return $html;
  }

  public function destroy(Noticia $noticia)
  {
    $this->authorize('adminDelete', $noticia);

    // Remover imagem de capa
    if ($noticia->imagem) {
      UploadHelper::deleteImage($noticia->imagem);
    }

    // Remover imagens e documentos do conteúdo
    $this->removeContentMedia($noticia->conteudo);

    $tituloNoticia = $noticia->titulo;
    $noticia->delete();

    // Limpar pasta da notícia
    $pastaNoticia =
      'noticias/' . UploadHelper::sanitizeFolderName($tituloNoticia);
    UploadHelper::cleanupEmptyFolder($pastaNoticia);

    //Invalidar cache após excluir notícia
    $this->invalidateAllNoticiasCache();

    return redirect()
      ->route('admin.noticias.index')
      ->with('message', 'Notícia removida com sucesso.');
  }

  public function toggleDestaque(Noticia $noticia)
  {
    $this->authorize('adminUpdate', $noticia);

    $noticia->update([
      'destaque' => !$noticia->destaque,
    ]);

    // INVALIDAR CACHE ESPECÍFICO DO BANNER
    \Cache::forget('noticias_destaque_banner');
    \Cache::forget('ultimas_noticias_geral');

    // Invalidar outros caches relacionados (se você tiver)
    $this->invalidateAllNoticiasCache();

    return redirect()
      ->back()
      ->with(
        'message',
        $noticia->destaque ? 'Notícia destacada.' : 'Destaque removido.',
      );
  }

  /**
   * Processa imagens base64 e documentos temporários do conteúdo e os salva no storage
   */
  private function processContentMedia($html, $tituloNoticia)
  {
    if (!$html) {
      return $html;
    }

    // Processar imagens base64
    $html = $this->processContentImages($html, $tituloNoticia);

    // Processar documentos temporários
    $html = $this->processTemporaryDocuments($html, $tituloNoticia);

    return $html;
  }

  /**
   * Processa imagens base64 do conteúdo e as salva no storage
   */
  private function processContentImages($html, $tituloNoticia)
  {
    if (!$html) {
      return $html;
    }

    // Busca imagens em base64
    preg_match_all(
      '/<img[^>]+src="data:image\/([^;]+);base64,([^"]+)"[^>]*>/i',
      $html,
      $matches,
      PREG_SET_ORDER,
    );

    foreach ($matches as $match) {
      $extension = $match[1]; // jpeg, png, etc.
      $base64Image = $match[2];
      $fullBase64 = 'data:image/' . $extension . ';base64,' . $base64Image;

      // Fazer upload usando helper
      $imagePath = UploadHelper::uploadBase64Image(
        $fullBase64,
        'noticias',
        $tituloNoticia,
        'content',
        $extension,
      );

      if ($imagePath) {
        // Substituir a imagem base64 pela URL da imagem
        $imageUrl = UploadHelper::getPublicUrl($imagePath);
        $html = str_replace(
          $match[0],
          str_replace(
            'src="data:image/' . $extension . ';base64,' . $base64Image . '"',
            'src="' . $imageUrl . '"',
            $match[0],
          ),
          $html,
        );
      }
    }

    return $html;
  }

  /**
   * Move imagens e documentos do conteúdo quando o título da notícia muda
   */
  private function moveContentMedia($html, $tituloAntigo, $tituloNovo)
  {
    if (!$html) {
      return;
    }

    // Mover imagens
    $this->moveContentImages($html, $tituloAntigo, $tituloNovo);

    // Mover documentos
    $this->moveContentDocuments($html, $tituloAntigo, $tituloNovo);
  }

  /**
   * Move imagens do conteúdo quando o título da notícia muda
   */
  private function moveContentImages($html, $tituloAntigo, $tituloNovo)
  {
    if (!$html) {
      return;
    }

    // Buscar todas as imagens no conteúdo que são do nosso storage
    preg_match_all(
      '/<img[^>]+src="([^"]*\/storage\/noticias\/[^"]+)"[^>]*>/i',
      $html,
      $matches,
    );

    if (!empty($matches[1])) {
      foreach ($matches[1] as $imageSrc) {
        // Extrair o caminho relativo do storage
        $relativePath = str_replace(
          '/storage/',
          '',
          parse_url($imageSrc, PHP_URL_PATH),
        );

        // Mover imagem para nova pasta
        UploadHelper::moveImage(
          $relativePath,
          'noticias',
          $tituloNovo,
          'content',
        );
      }
    }
  }

  /**
   * Move documentos do conteúdo quando o título da notícia muda
   */
  private function moveContentDocuments($html, $tituloAntigo, $tituloNovo)
  {
    if (!$html) {
      return;
    }

    // Buscar todos os documentos no conteúdo que são do nosso storage
    preg_match_all(
      '/<a[^>]+href="([^"]*\/storage\/noticias\/[^"]+)"[^>]*>/i',
      $html,
      $matches,
    );

    if (!empty($matches[1])) {
      foreach ($matches[1] as $docSrc) {
        // Extrair o caminho relativo do storage
        $relativePath = str_replace(
          '/storage/',
          '',
          parse_url($docSrc, PHP_URL_PATH),
        );

        // Mover documento para nova pasta
        UploadHelper::moveImage(
          $relativePath,
          'noticias',
          $tituloNovo,
          'files',
        );
      }
    }
  }

  /**
   * Remove imagens e documentos do conteúdo de uma notícia
   */
  private function removeContentMedia($content)
  {
    if (!$content) {
      return;
    }

    // Remover imagens
    $this->removeContentImages($content);

    // Remover documentos
    $this->removeContentDocuments($content);
  }

  /**
   * Remove imagens do conteúdo de uma notícia
   */
  private function removeContentImages($content)
  {
    if (!$content) {
      return;
    }

    // Buscar todas as imagens no conteúdo que são do nosso storage
    preg_match_all(
      '/<img[^>]+src="([^"]*\/storage\/noticias\/[^"]+)"[^>]*>/i',
      $content,
      $matches,
    );

    if (!empty($matches[1])) {
      foreach ($matches[1] as $imageSrc) {
        // Extrair o caminho relativo do storage
        $relativePath = str_replace(
          '/storage/',
          '',
          parse_url($imageSrc, PHP_URL_PATH),
        );

        // Remover imagem
        UploadHelper::deleteImage($relativePath);
      }
    }
  }

  /**
   * Remove documentos do conteúdo de uma notícia
   */
  private function removeContentDocuments($content)
  {
    if (!$content) {
      return;
    }

    // Buscar todos os documentos no conteúdo que são do nosso storage
    preg_match_all(
      '/<a[^>]+href="([^"]*\/storage\/noticias\/[^"]+)"[^>]*>/i',
      $content,
      $matches,
    );

    if (!empty($matches[1])) {
      foreach ($matches[1] as $docSrc) {
        // Extrair o caminho relativo do storage
        $relativePath = str_replace(
          '/storage/',
          '',
          parse_url($docSrc, PHP_URL_PATH),
        );

        // Remover documento
        UploadHelper::deleteImage($relativePath);
      }
    }
  }

  private function invalidateAllNoticiasCache()
  {
    try {
      // 1. Cache do banner (só destaques)
      \Cache::forget('noticias_destaque_banner');

      // 2. Cache da lista da home (todas as notícias)
      \Cache::forget('noticias_home_lista');

      // 3. Cache da API paginada (múltiplas páginas e buscas)
      for ($page = 1; $page <= 20; $page++) {
        for ($perPage = 3; $perPage <= 6; $perPage++) {
          // Cache sem busca
          $cacheKey = 'noticias_api_' . md5($perPage . '__' . $page);
          \Cache::forget($cacheKey);

          // Caches com buscas comuns
          $searchTerms = [
            '',
            'curso',
            'acadepol',
            'policia',
            'capacitacao',
            'treinamento',
          ];
          foreach ($searchTerms as $search) {
            $cacheKey =
              'noticias_api_' . md5($perPage . '_' . $search . '_' . $page);
            \Cache::forget($cacheKey);
          }
        }
      }

      // 4. Se estiver usando Redis, limpar padrões relacionados a notícias
      if (config('cache.default') === 'redis') {
        $redis = \Cache::getRedis();
        $prefix = config('cache.prefix') . ':';
        $patterns = [
          $prefix . '*noticias*',
          $prefix . '*ultimas_noticias*',
          $prefix . '*noticias_home*',
          $prefix . '*noticias_api*',
          $prefix . '*noticias_destaque*',
        ];

        foreach ($patterns as $pattern) {
          $keys = $redis->keys($pattern);
          if (!empty($keys)) {
            $redis->del($keys);
          }
        }
      }

      // 5. Cache legado (se ainda existir)
      \Cache::forget('ultimas_noticias_' . md5('home_component'));

      \Log::info('Todos os caches de notícias invalidados com sucesso');
    } catch (\Exception $e) {
      \Log::warning(
        'Erro ao invalidar caches de notícias: ' . $e->getMessage(),
      );

      // Fallback: limpar todo o cache se houver erro
      try {
        \Artisan::call('cache:clear');
        \Log::info('Cache geral limpo como fallback');
      } catch (\Exception $fallbackError) {
        \Log::error(
          'Erro crítico ao limpar cache: ' . $fallbackError->getMessage(),
        );
      }
    }
  }
}
