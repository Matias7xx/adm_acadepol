<template>
  <div class="container mx-auto px-4 py-8" role="main">
    <!-- Cabeçalho -->
    <header class="mb-8">
      <h1 class="text-3xl font-bold text-gray-800 mb-4">Estrutura Física da ACADEPOL</h1>
      <p class="text-gray-700 text-lg leading-relaxed">
        {{ descricao }}
      </p>
    </header>

    <!-- Barra de busca -->
    <div class="mb-6">
      <div class="relative">
        <input 
          v-model="termoBusca"
          type="text"
          placeholder="Buscar fotos por título ou descrição..."
          class="w-full px-4 py-3 pl-10 pr-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
        />
        <svg class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <button 
          v-if="termoBusca"
          @click="limparBusca"
          class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600"
          aria-label="Limpar busca"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Filtros para categorias -->
    <div class="mb-8">
      <h2 class="text-lg font-semibold text-gray-800 mb-3">Filtrar por categoria</h2>
      <div class="flex flex-wrap gap-2">
        <button 
          @click="filtrarPor('Todos')"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 focus:ring-2 focus:ring-blue-500 focus:outline-none"
          :class="categoriaAtiva === 'Todos' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'"
        >
          Todos ({{ fotos.length }})
        </button>
        <button 
          v-for="categoria in categorias" 
          :key="categoria"
          @click="filtrarPor(categoria)"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 focus:ring-2 focus:ring-blue-500 focus:outline-none"
          :class="categoriaAtiva === categoria ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'"
        >
          {{ categoria }} ({{ contarFotosPorCategoria(categoria) }})
        </button>
      </div>
    </div>

    <!-- Controles de visualização -->
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center gap-4">
        <label class="text-sm font-medium text-gray-700">Visualização:</label>
        <div class="flex bg-gray-100 rounded-lg p-1">
          <button 
            @click="tipoVisualizacao = 'grid'"
            class="px-3 py-1 rounded text-sm transition-all"
            :class="tipoVisualizacao === 'grid' ? 'bg-white shadow-sm' : 'text-gray-600 hover:text-gray-800'"
          >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M3 3h7v7H3V3zm0 11h7v7H3v-7zm11-11h7v7h-7V3zm0 11h7v7h-7v-7z"/>
            </svg>
          </button>
          <button 
            @click="tipoVisualizacao = 'list'"
            class="px-3 py-1 rounded text-sm transition-all"
            :class="tipoVisualizacao === 'list' ? 'bg-white shadow-sm' : 'text-gray-600 hover:text-gray-800'"
          >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/>
            </svg>
          </button>
        </div>
      </div>
      
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700">Tamanho:</label>
        <select 
          v-model="tamanhoGrid" 
          class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="small">Pequeno</option>
          <option value="medium">Médio</option>
          <option value="large">Grande</option>
        </select>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="carregando" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      <p class="mt-4 text-gray-600">Carregando fotos...</p>
    </div>

    <!-- Estado vazio -->
    <div v-else-if="fotosFiltradas.length === 0" class="text-center py-12">
      <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma foto encontrada</h3>
      <p class="text-gray-500 mb-4">
        {{ termoBusca ? 'Tente ajustar sua busca ou escolher outra categoria.' : 'Não há fotos disponíveis nesta categoria.' }}
      </p>
      <button 
        v-if="termoBusca || categoriaAtiva !== 'Todos'"
        @click="resetarFiltros"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Limpar filtros
      </button>
    </div>

    <!-- Galeria - Visualização em Grid -->
    <div 
      v-else-if="tipoVisualizacao === 'grid'"
      class="grid gap-4 transition-all duration-300"
      :class="classesGrid"
    >
      <article 
        v-for="(foto, index) in fotosFiltradas" 
        :key="`${foto.id}-${categoriaAtiva}`"
        class="group relative overflow-hidden rounded-lg shadow-md cursor-pointer transition-all duration-300 hover:shadow-xl"
        :class="alturaCard"
        @click="abrirModal(foto)"
        @keydown.enter="abrirModal(foto)"
        @keydown.space.prevent="abrirModal(foto)"
        tabindex="0"
        role="button"
        :aria-label="`Visualizar foto: ${foto.titulo}`"
      >
        <!-- Skeleton loading -->
        <div 
          v-show="imagensCarregando[foto.id]"
          class="w-full h-full bg-gray-200 animate-pulse"
        ></div>

        <!-- Imagem -->
        <img 
          v-show="!imagensCarregando[foto.id]"
          :src="foto.url" 
          :alt="foto.titulo" 
          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          loading="lazy"
          @load="onImageLoad(foto.id)"
          @error="onImageError(foto.id, $event)"
        />
        
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-300 flex items-center justify-center">
          <div class="text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </div>
        </div>
        
        <!-- Informações -->
        <div class="absolute bottom-0 left-0 right-0 p-4 text-white transform translate-y-full group-hover:translate-y-0 transition-transform duration-300 bg-gradient-to-t from-black/90 to-transparent">
          <h3 class="font-semibold text-lg mb-1">{{ foto.titulo }}</h3>
          <p class="text-sm opacity-90 line-clamp-2">{{ foto.descricao }}</p>
          <span class="inline-block mt-2 px-2 py-1 bg-white/20 rounded-full text-xs">{{ foto.categoria }}</span>
        </div>
      </article>
    </div>

    <!-- Galeria - Visualização em Lista -->
    <div v-else class="space-y-4">
      <article 
        v-for="(foto, index) in fotosFiltradas" 
        :key="`list-${foto.id}-${categoriaAtiva}`"
        class="group bg-white rounded-lg shadow-md overflow-hidden cursor-pointer transition-all duration-300 hover:shadow-xl"
        @click="abrirModal(foto)"
        @keydown.enter="abrirModal(foto)"
        @keydown.space.prevent="abrirModal(foto)"
        tabindex="0"
        role="button"
        :aria-label="`Visualizar foto: ${foto.titulo}`"
      >
        <div class="flex">
          <div class="w-48 h-32 flex-shrink-0 relative overflow-hidden">
            <img 
              :src="foto.url" 
              :alt="foto.titulo" 
              class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
              loading="lazy"
              @error="onImageError(foto.id, $event)"
            />
          </div>
          <div class="flex-1 p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ foto.titulo }}</h3>
                <p class="text-gray-600 mb-3">{{ foto.descricao }}</p>
                <div class="flex items-center gap-2">
                  <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">{{ foto.categoria }}</span>
                </div>
              </div>
              <div class="ml-4 flex-shrink-0">
                <svg class="h-6 w-6 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </article>
    </div>

    <!-- Modal -->
    <div 
      v-if="fotoSelecionada" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-90"
      @click.self="fecharModal"
      @keydown.esc="fecharModal"
      role="dialog"
      aria-modal="true"
    >
      <div class="relative w-full max-w-6xl max-h-screen flex flex-col bg-white rounded-lg shadow-2xl overflow-hidden">
        <!-- Cabeçalho do modal -->
        <div class="flex items-center justify-between p-4 border-b bg-gray-50">
          <h2 class="text-lg font-semibold text-gray-900">{{ fotoSelecionada.titulo }}</h2>
          <div class="flex items-center gap-2">
            
            <!-- Botão de compartilhar -->
            <button 
              @click="compartilharFoto(fotoSelecionada)"
              class="p-2 rounded-full hover:bg-gray-200 transition-colors"
              title="Compartilhar foto"
            >
              <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
              </svg>
            </button>
            
            <!-- Botão de fechar -->
            <button 
              @click="fecharModal" 
              class="p-2 rounded-full hover:bg-gray-200 transition-colors"
              title="Fechar modal"
            >
              <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Imagem ampliada -->
        <div class="relative flex-1 flex items-center justify-center bg-gray-100 min-h-0">
          <img 
            :src="fotoSelecionada.url" 
            :alt="fotoSelecionada.titulo" 
            class="max-w-full max-h-full object-contain"
          />
          
          <!-- Botões de navegação -->
          <button 
            v-if="indiceAtual > 0"
            @click="navegarFoto(-1)" 
            class="absolute left-4 top-1/2 transform -translate-y-1/2 w-12 h-12 bg-white bg-opacity-80 rounded-full flex items-center justify-center shadow-lg hover:bg-opacity-100 transition-all"
            aria-label="Foto anterior"
          >
            <svg class="h-6 w-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          
          <button 
            v-if="indiceAtual < fotosFiltradas.length - 1"
            @click="navegarFoto(1)" 
            class="absolute right-4 top-1/2 transform -translate-y-1/2 w-12 h-12 bg-white bg-opacity-80 rounded-full flex items-center justify-center shadow-lg hover:bg-opacity-100 transition-all"
            aria-label="Próxima foto"
          >
            <svg class="h-6 w-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
        
        <!-- Informações da foto -->
        <div class="p-4 bg-white border-t">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-xl font-bold text-gray-800">{{ fotoSelecionada.titulo }}</h3>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">{{ fotoSelecionada.categoria }}</span>
          </div>
          <p class="text-gray-700 mb-4">{{ fotoSelecionada.descricao }}</p>
          
          <!-- Navegação inferior -->
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">{{ indiceAtual + 1 }} de {{ fotosFiltradas.length }}</span>
            <div class="flex gap-2">
              <button 
                @click="navegarFoto(-1)" 
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="indiceAtual === 0"
              >
                Anterior
              </button>
              <button 
                @click="navegarFoto(1)" 
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="indiceAtual === fotosFiltradas.length - 1"
              >
                Próximo
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

export default {
  name: 'GaleriaFotosAprimorada',
  setup() {
    // ===== DADOS E ESTADOS =====
    const descricao = 'Oferecemos instalações modernas e equipadas para a formação e treinamento dos profissionais de segurança pública.';
    
    const categorias = [
      'Salas de Aula', 
      'Laboratórios', 
      'Área Externa', 
      'Auditório', 
      'Biblioteca', 
      'Instalações Esportivas'
    ];

    const fotos = [
      {
        id: 1,
        titulo: 'Fachada Principal',
        descricao: 'Entrada principal da ACADEPOL com arquitetura moderna e acessibilidade completa',
        categoria: 'Área Externa',
        url: 'https://static.paraiba.pb.gov.br/2015/01/acadepol-foto-francisco-fran%C3%A7a-19.jpg'
      },
      {
        id: 2,
        titulo: 'Laboratório de Informática',
        descricao: 'Laboratório equipado com computadores de última geração para treinamento digital',
        categoria: 'Laboratórios',
        url: 'https://pm.es.gov.br/Media/PMES/_Profiles/c4d8c6e6/9a583751/materia174mes10edit.jpg?v=637972173914939020'
      },
      {
        id: 3,
        titulo: 'Sala de Aula Interativa',
        descricao: 'Sala moderna com recursos multimídia para aulas dinâmicas e interativas',
        categoria: 'Salas de Aula',
        url: 'https://www.es.gov.br/Media/PortalES/_Profiles/c4d8c6e6/d8d1f292/WhatsApp%20Image%202023-11-13%20at%2013.18.54%20(1)-1.jpeg?v=638513060550508360'
      },
      {
        id: 4,
        titulo: 'Biblioteca Central',
        descricao: 'Acervo completo com obras especializadas em segurança pública e direito',
        categoria: 'Biblioteca',
        url: 'https://images.pexels.com/photos/1370296/pexels-photo-1370296.jpeg'
      },
      {
        id: 5,
        titulo: 'Auditório Principal',
        descricao: 'Capacidade para 300 pessoas, equipado com sistema de som e projeção modernos',
        categoria: 'Auditório',
        url: 'https://www.policiacivil.pb.gov.br/noticias/acadepol-conclui-primeira-turma-do-curso-de-formacao-2023-com-palestra-sobre-administracao-publica/22092023-acadepol-conclui-primeira-turma-do-curso-de-formacao-2023-com-palestr-1.jpg/@@images/6e0e85f7-3f3e-4bf8-a70c-3f35c0ab93db.jpeg'
      },
      {
        id: 6,
        titulo: 'Laboratório de Balística',
        descricao: 'Ambiente especializado para análises e estudos balísticos avançados',
        categoria: 'Laboratórios',
        url: 'https://thumbs.dreamstime.com/z/pol%C3%ADcia-cient%C3%ADfica-extrai-vest%C3%ADgios-de-arma-no-laborat%C3%B3rio-bal%C3%ADstica-imagem-conceitual-181491170.jpg?ct=jpeg'
      },
      {
        id: 7,
        titulo: 'Quadra Poliesportiva',
        descricao: 'Espaço amplo para atividades físicas e treinamento tático dos alunos',
        categoria: 'Instalações Esportivas',
        url: 'https://brejo.com/wp-content/uploads/2023/08/acadepol2.jpeg'
      },
      {
        id: 8,
        titulo: 'Estande de Tiro',
        descricao: 'Ambiente seguro e controlado para treinamento de tiro e manuseio de armas',
        categoria: 'Instalações Esportivas',
        url: 'https://www.policiacivil.pb.gov.br/noticias/capacitacao-acadepol-promove-cursos-de-tiro-defensivo-investigacao-de-homicidios-e-extracao-de-dados-para-policiais/capacitacao2.jpeg/@@images/bf056900-b1d1-4dc0-9e44-3a3ad75f6bcb.jpeg'
      },
      {
        id: 9,
        titulo: 'Laboratório de Química Forense',
        descricao: 'Laboratório moderno para análises químicas e perícias forenses',
        categoria: 'Laboratórios',
        url: 'https://images.pexels.com/photos/2280571/pexels-photo-2280571.jpeg'
      },
      {
        id: 10,
        titulo: 'Sala de Simulação',
        descricao: 'Ambiente para simulações práticas de situações reais',
        categoria: 'Salas de Aula',
        url: 'https://images.pexels.com/photos/8199559/pexels-photo-8199559.jpeg'
      }
    ];

    // Estados reativos
    const categoriaAtiva = ref('Todos');
    const fotoSelecionada = ref(null);
    const indiceAtual = ref(0);
    const termoBusca = ref('');
    const tipoVisualizacao = ref('grid');
    const tamanhoGrid = ref('medium');
    const carregando = ref(false);
    const imagensCarregando = ref({});

    // Placeholder para imagens com erro
    const placeholderImage = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMjUgNzVMMTUwIDEwMEwxNzUgNzVMMjAwIDEwMFYxNjBIMTAwVjEwMEwxMjUgNzVaIiBmaWxsPSIjRDFENURCIi8+CjxjaXJjbGUgY3g9IjEzMCIgY3k9IjkwIiByPSIxMCIgZmlsbD0iI0QxRDVEQiIvPgo8L3N2Zz4=';

    // ===== COMPUTED PROPERTIES =====
    const fotosFiltradas = computed(() => {
      let resultado = fotos;
      
      // Filtrar por categoria
      if (categoriaAtiva.value !== 'Todos') {
        resultado = resultado.filter(foto => foto.categoria === categoriaAtiva.value);
      }
      
      // Filtrar por termo de busca
      if (termoBusca.value) {
        const termo = termoBusca.value.toLowerCase();
        resultado = resultado.filter(foto => 
          foto.titulo.toLowerCase().includes(termo) || 
          foto.descricao.toLowerCase().includes(termo) ||
          foto.categoria.toLowerCase().includes(termo)
        );
      }
      
      return resultado;
    });

    const fotosCarregadas = computed(() => {
      return Object.values(imagensCarregando.value).filter(loading => !loading).length;
    });

    const classesGrid = computed(() => {
      const baseClasses = 'grid-cols-1 sm:grid-cols-2';
      
      switch (tamanhoGrid.value) {
        case 'small':
          return `${baseClasses} md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6`;
        case 'medium':
          return `${baseClasses} md:grid-cols-3 lg:grid-cols-4`;
        case 'large':
          return `${baseClasses} md:grid-cols-2 lg:grid-cols-3`;
        default:
          return `${baseClasses} md:grid-cols-3 lg:grid-cols-4`;
      }
    });

    const alturaCard = computed(() => {
      switch (tamanhoGrid.value) {
        case 'small':
          return 'h-48';
        case 'medium':
          return 'h-64';
        case 'large':
          return 'h-80';
        default:
          return 'h-64';
      }
    });

    // ===== MÉTODOS =====
    const contarFotosPorCategoria = (categoria) => {
      return fotos.filter(foto => foto.categoria === categoria).length;
    };

    const filtrarPor = (categoria) => {
      categoriaAtiva.value = categoria;
      fotoSelecionada.value = null;
      termoBusca.value = '';
    };

    const limparBusca = () => {
      termoBusca.value = '';
    };

    const resetarFiltros = () => {
      categoriaAtiva.value = 'Todos';
      termoBusca.value = '';
    };

    const abrirModal = (foto) => {
      fotoSelecionada.value = foto;
      indiceAtual.value = fotosFiltradas.value.findIndex(f => f.id === foto.id);
      document.body.style.overflow = 'hidden';
    };

    const fecharModal = () => {
      fotoSelecionada.value = null;
      document.body.style.overflow = '';
    };

    const navegarFoto = (direcao) => {
      const novoIndice = indiceAtual.value + direcao;
      if (novoIndice >= 0 && novoIndice < fotosFiltradas.value.length) {
        indiceAtual.value = novoIndice;
        fotoSelecionada.value = fotosFiltradas.value[novoIndice];
      }
    };

    const onImageLoad = (fotoId) => {
      imagensCarregando.value[fotoId] = false;
    };

    const onImageError = (fotoId, event) => {
      console.warn(`Erro ao carregar imagem ${fotoId}`);
      event.target.src = placeholderImage;
      imagensCarregando.value[fotoId] = false;
    };

    const compartilharFoto = async (foto) => {
      if (navigator.share) {
        try {
          await navigator.share({
            title: foto.titulo,
            text: foto.descricao,
            url: window.location.href
          });
        } catch (error) {
          console.log('Erro ao compartilhar:', error);
        }
      } else {
        // Fallback para navegadores que não suportam Web Share API
        const url = window.location.href;
        await navigator.clipboard.writeText(`${foto.titulo} - ${url}`);
        alert('Link copiado para a área de transferência!');
      }
    };

    // Navegação por teclado
    const handleKeydown = (event) => {
      if (!fotoSelecionada.value) return;
      
      switch (event.key) {
        case 'ArrowLeft':
          event.preventDefault();
          navegarFoto(-1);
          break;
        case 'ArrowRight':
          event.preventDefault();
          navegarFoto(1);
          break;
        case 'Escape':
          event.preventDefault();
          fecharModal();
          break;
      }
    };

    onMounted(() => {
      // Inicializar estado de carregamento das imagens
      fotos.forEach(foto => {
        imagensCarregando.value[foto.id] = true;
      });
      
      // Adicionar listener para navegação por teclado
      document.addEventListener('keydown', handleKeydown);
      
      // Simular carregamento inicial
      carregando.value = true;
      setTimeout(() => {
        carregando.value = false;
      }, 1000);
    });

    onUnmounted(() => {
      document.removeEventListener('keydown', handleKeydown);
      document.body.style.overflow = '';
    });

    // ===== RETURN =====
    return {
      // Estados
      descricao,
      categorias,
      fotos,
      categoriaAtiva,
      fotoSelecionada,
      indiceAtual,
      termoBusca,
      tipoVisualizacao,
      tamanhoGrid,
      carregando,
      imagensCarregando,
      
      // Computed
      fotosFiltradas,
      fotosCarregadas,
      classesGrid,
      alturaCard,
      
      // Métodos
      contarFotosPorCategoria,
      filtrarPor,
      limparBusca,
      resetarFiltros,
      abrirModal,
      fecharModal,
      navegarFoto,
      onImageLoad,
      onImageError,
      compartilharFoto
    };
  }
};
</script>

<style scoped>
/* ===== CSS ===== */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* ===== ANIMAÇÕES ===== */
@keyframes fadeIn {
  from { 
    opacity: 0; 
    transform: translateY(20px); 
  }
  to { 
    opacity: 1; 
    transform: translateY(0); 
  }
}

.grid > * {
  animation: fadeIn 0.5s ease-out forwards;
}

.grid > *:nth-child(n) {
  animation-delay: calc(var(--index, 0) * 0.1s);
}

/* ===== ACESSIBILIDADE ===== */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}

/* ===== RESPONSIVIDADE ===== */
@media (max-width: 640px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  .grid {
    gap: 0.75rem;
  }
  
  .space-y-4 > * + * {
    margin-top: 0.75rem;
  }
}

/* ===== ESTADOS DE FOCO ===== */
.focus\:ring-2:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

button:focus-visible,
[tabindex]:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* =====  HOVER ===== */
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

.group:hover .group-hover\:bg-opacity-50 {
  background-opacity: 0.5;
}

.group:hover .group-hover\:opacity-100 {
  opacity: 1;
}

.group:hover .group-hover\:translate-y-0 {
  transform: translateY(0);
}

.group:hover .group-hover\:text-gray-600 {
  color: #4b5563;
}

/* ===== LOADING STATES ===== */
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

/* ===== SCROLLBAR PERSONALIZADO ===== */
.overflow-auto::-webkit-scrollbar {
  width: 8px;
}

.overflow-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* ===== MODAL OVERLAY ===== */
.fixed.inset-0 {
  backdrop-filter: blur(4px);
}

/* ===== TRANSIÇÕES SUAVES ===== */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.transition-colors {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.transition-transform {
  transition-property: transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.transition-opacity {
  transition-property: opacity;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== GRADIENTES ===== */
.bg-gradient-to-t {
  background-image: linear-gradient(to top, var(--tw-gradient-stops));
}

.from-black\/90 {
  --tw-gradient-from: rgb(0 0 0 / 0.9);
  --tw-gradient-to: rgb(0 0 0 / 0);
  --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}

.to-transparent {
  --tw-gradient-to: transparent;
}
</style>