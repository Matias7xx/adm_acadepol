<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Noticia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Mews\Purifier\Facades\Purifier;

class NoticiaController extends Controller
{
    /**
     * Listagem de notícias
     */
    public function index()
    {
        $this->authorize('adminViewAny', Noticia::class);
        
        $noticias = Noticia::query()
            ->when(request('search'), function($query, $search) {
                $query->where('titulo', 'like', "%{$search}%")
                      ->orWhere('descricao_curta', 'like', "%{$search}%");
            })
            ->when(request('status'), function($query, $status) {
                $query->where('status', $status);
            })
            ->when(request('destaque'), function($query, $destaque) {
                $query->where('destaque', $destaque === 'true');
            })
            ->when(request('sort'), function($query, $sortColumn) {
                $direction = 'asc';
                if (strncmp($sortColumn, '-', 1) === 0) {
                    $direction = 'desc';
                    $sortColumn = substr($sortColumn, 1);
                }
                $query->orderBy($sortColumn, $direction);
            }, function($query) {
                $query->latest('data_publicacao');
            })
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

    /**
     * Formulário de criação de notícia
     */
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

    /**
     * Armazenar nova notícia
     */
    public function store(Request $request)
    {
        $this->authorize('adminCreate', Noticia::class);
        
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao_curta' => 'required|string|max:500',
            'conteudo' => 'nullable|string',
            'imagem' => 'nullable|image|max:2048',
            'destaque' => 'boolean',
            'data_publicacao' => 'required|date',
            'status' => 'required|in:rascunho,publicado,arquivado',
        ]);

        // Sanitizar o HTML
        $validated['conteudo'] = Purifier::clean($validated['conteudo'], [
            'HTML.Allowed' => 'p,b,i,strong,em,u,a[href|title|target],ul,ol,li,h1,h2,h3,h4,h5,blockquote,img[src|alt|width|height|class],hr,br,iframe[src|width|height|frameborder|allowfullscreen],div,span[class],video[src|controls|width|height],source[src|type]',
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%',
            'AutoFormat.RemoveEmpty' => true,
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align,width,height,margin,margin-left,margin-right',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.Linkify' => true,
        ]);

        // Processar imagens dentro do conteúdo
        $validated['conteudo'] = $this->processContentImages($validated['conteudo']);
        
        // Processar upload de imagem diretamente para diretório público
        if ($request->hasFile('imagem')) {
            $file = $request->file('imagem');
            $filename = 'noticia_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Criar o diretório se não existir
            $directory = public_path('images/noticias');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $file->move($directory, $filename);
            
            // Armazenar o caminho relativo no banco de dados
            $validated['imagem'] = '/images/noticias/' . $filename;
        }
        
        // Criar notícia
        Noticia::create($validated);
        
        return redirect()->route('admin.noticias.index')
            ->with('message', 'Notícia criada com sucesso.');
    }

    /**
     * Exibir detalhes da notícia
     */
    public function show(Noticia $noticia)
    {
        $this->authorize('adminView', $noticia);
        
        return Inertia::render('Admin/Noticias/Show', [
            'noticia' => [
                'id' => $noticia->id,
                'titulo' => $noticia->titulo,
                'descricao_curta' => $noticia->descricao_curta,
                'conteudo' => $noticia->conteudo,
                'imagem' => $noticia->imagem, // Já contém o caminho completo
                'destaque' => $noticia->destaque,
                'data_publicacao' => $noticia->data_publicacao->format('Y-m-d'),
                'data_formatada' => $noticia->data_formatada,
                'status' => $noticia->status,
                'visualizacoes' => $noticia->visualizacoes,
                'created_at' => $noticia->created_at->format('d/m/Y H:i'),
                'updated_at' => $noticia->updated_at->format('d/m/Y H:i'),
            ],
            'can' => [
                'edit' => Auth::user()->can('adminEdit', Noticia::class),
                'delete' => Auth::user()->can('adminDelete', Noticia::class),
            ],
        ]);
    }

    /**
     * Formulário de edição
     */
    public function edit(Noticia $noticia)
    {
        $this->authorize('adminUpdate', $noticia);
        
        return Inertia::render('Admin/Noticias/Edit', [
            'noticia' => [
                'id' => $noticia->id,
                'titulo' => $noticia->titulo,
                'descricao_curta' => $noticia->descricao_curta,
                'conteudo' => $noticia->conteudo,
                'imagem' => $noticia->imagem, // Já contém o caminho completo
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

    /**
     * Atualizar notícia
     */
    public function update(Request $request, Noticia $noticia)
    {
        $this->authorize('adminUpdate', $noticia);
        
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao_curta' => 'required|string|max:500',
            'conteudo' => 'nullable|string',
            'imagem' => 'nullable|image|max:2048',
            'remover_imagem' => 'nullable|boolean',
            'destaque' => 'boolean',
            'data_publicacao' => 'required|date',
            'status' => 'required|in:rascunho,publicado,arquivado',
        ]);

        // Sanitizar o HTML
        $validated['conteudo'] = Purifier::clean($validated['conteudo'], [
            'HTML.Allowed' => 'p,b,i,strong,em,u,a[href|title|target],ul,ol,li,h1,h2,h3,h4,h5,blockquote,img[src|alt|width|height|class],hr,br,iframe[src|width|height|frameborder|allowfullscreen],div,span[class],video[src|controls|width|height],source[src|type]',
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%',
            'AutoFormat.RemoveEmpty' => true,
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align,width,height,margin,margin-left,margin-right',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.Linkify' => true,
        ]);

        // Processar imagens dentro do conteúdo
        $validated['conteudo'] = $this->processContentImages($validated['conteudo']);
        
        // Remover imagem atual se solicitado
        if ($request->input('remover_imagem') && $noticia->imagem) {
            // Verificar se a imagem está no diretório público
            $imagePath = public_path(ltrim($noticia->imagem, '/'));
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            $validated['imagem'] = null;
        }
        
        // Processar upload de nova imagem
        if ($request->hasFile('imagem')) {
            // Remover imagem anterior se existir
            if ($noticia->imagem) {
                $imagePath = public_path(ltrim($noticia->imagem, '/'));
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $file = $request->file('imagem');
            $filename = 'noticia_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Garantir que o diretório existe
            $directory = public_path('images/noticias');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $file->move($directory, $filename);
            $validated['imagem'] = '/images/noticias/' . $filename;
        } else {
            // Manter imagem atual
            unset($validated['imagem']);
        }
        
        // Remover campo remover_imagem antes de atualizar
        if (isset($validated['remover_imagem'])) {
            unset($validated['remover_imagem']);
        }
        
        // Atualizar notícia
        $noticia->update($validated);
        
        return redirect()->route('admin.noticias.index')
            ->with('message', 'Notícia atualizada com sucesso.');
    }

    /**
     * Remover notícia
     */
    public function destroy(Noticia $noticia)
    {
        $this->authorize('adminDelete', $noticia);
        
        // Remover imagem se existir
        if ($noticia->imagem) {
            $imagePath = public_path(ltrim($noticia->imagem, '/'));
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $noticia->delete();
        
        return redirect()->route('admin.noticias.index')
            ->with('message', 'Notícia removida com sucesso.');
    }
    
    /**
     * Alternar destaque
     */
    public function toggleDestaque(Noticia $noticia)
    {
        $this->authorize('adminUpdate', $noticia);
        
        $noticia->update([
            'destaque' => !$noticia->destaque
        ]);
        
        return redirect()->back()
            ->with('message', $noticia->destaque ? 'Notícia destacada.' : 'Destaque removido.');
    }

    //Processamento de imagens
    private function processContentImages($html)
    {
        // Busca imagens em base64
        preg_match_all('/<img[^>]+src="data:image\/([^;]+);base64,([^"]+)"[^>]*>/i', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $extension = $match[1]; // jpeg, png, etc.
            $base64Image = $match[2];
            
            // Gerar nome único
            $filename = 'content_'.time().'_'.uniqid().'.'.$extension;
            $path = '/images/noticias/content/'.$filename;
            
            // Salvar imagem
            $directory = public_path('images/noticias/content');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            file_put_contents(public_path($path), base64_decode($base64Image));
            
            // Substituir a imagem base64 pela URL da imagem
            $imageUrl = $path; // Use path relativo, não asset()
            $html = str_replace('src="data:image/'.$extension.';base64,'.$base64Image.'"', 'src="'.$imageUrl.'"', $html);
        }
        
        return $html;
    }
}