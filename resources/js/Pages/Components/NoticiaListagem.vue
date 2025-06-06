<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import Header from './Header.vue';
import SiteNavbar from './SiteNavbar.vue';
import Footer from './Footer.vue';

// Estado do componente
const noticias = ref([]);
const loading = ref(true);
const error = ref(null);
const searchQuery = ref('');
const currentPage = ref(1);
const totalPages = ref(1);
const totalItems = ref(0);
const itemsPerPage = ref(6);
const retryCount = ref(0);
const mounted = ref(false);

// Configurações
const MAX_RETRIES = 3;
const RETRY_DELAY = 2000;

// Debounce controller para evitar múltiplas requisições
let abortController = null;

// Buscar notícias da API com paginação e busca
const fetchNoticias = async (isRetry = false) => {
  try {
    // Cancelar requisição anterior se existir
    if (abortController) {
      abortController.abort();
    }
    
    abortController = new AbortController();
    
    if (!isRetry) {
      loading.value = true;
    }
    error.value = null;
    
    // Construir URL com parâmetros
    let url = `/api/noticias?page=${currentPage.value}`;
    
    if (searchQuery.value) {
      url += `&search=${encodeURIComponent(searchQuery.value)}`;
    }
    
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      signal: abortController.signal,
      cache: 'no-cache'
    });
    
    if (!response.ok) {
      const errorMessage = response.status === 404 
        ? 'Serviço de notícias indisponível' 
        : `Erro ${response.status}: ${response.statusText || 'Não foi possível carregar as notícias'}`;
      throw new Error(errorMessage);
    }
    
    const data = await response.json();
    
    // Validar estrutura dos dados
    if (!data || !Array.isArray(data.data)) {
      throw new Error('Formato de dados inválido recebido do servidor');
    }
    
    // Processar dados da API paginada
    noticias.value = data.data
      .map(noticia => ({
        ...noticia,
        id: noticia.id || Math.random().toString(36).substr(2, 9),
        titulo: noticia.titulo || 'Título não disponível',
        descricao_curta: noticia.descricao_curta || 'Descrição não disponível',
        data_publicacao: formatDate(noticia.data_publicacao),
        visualizacoes: parseInt(noticia.visualizacoes) || 0,
        destaque: Boolean(noticia.destaque)
      }))
      .filter(noticia => noticia.titulo !== 'Título não disponível');
    
    // Atualizar informações de paginação
    currentPage.value = data.current_page || 1;
    totalPages.value = data.last_page || 1;
    totalItems.value = data.total || 0;
    itemsPerPage.value = data.per_page || 6;
    
    loading.value = false;
    retryCount.value = 0;
    
    // Atualizar URL com os parâmetros de busca e paginação
    updateUrlParams();
    
  } catch (err) {
    // Ignorar erros de abort (cancelamento de requisição)
    if (err.name === 'AbortError') {
      return;
    }
    
    console.error('Erro ao carregar notícias:', err);
    
    if (retryCount.value < MAX_RETRIES && mounted.value) {
      retryCount.value++;
      console.log(`Tentativa ${retryCount.value} de ${MAX_RETRIES}...`);
      setTimeout(() => {
        if (mounted.value) fetchNoticias(true);
      }, RETRY_DELAY);
    } else {
      error.value = err.message;
      loading.value = false;
    }
  }
};

// Atualizar parâmetros da URL sem recarregar a página
const updateUrlParams = () => {
  if (typeof window === 'undefined') return;
  
  const params = new URLSearchParams(window.location.search);
  
  if (currentPage.value !== 1) {
    params.set('page', currentPage.value);
  } else {
    params.delete('page');
  }
  
  if (searchQuery.value.trim()) {
    params.set('search', searchQuery.value.trim());
  } else {
    params.delete('search');
  }
  
  const url = window.location.pathname + (params.toString() ? `?${params.toString()}` : '');
  window.history.pushState({}, '', url);
};

// Tratamento de erro de imagem com fallback
const handleImageError = (event, noticia) => {
  const img = event.target;
  
  // Evitar loop infinito de erro
  if (img.dataset.fallbackAttempted) {
    img.style.display = 'none';
    return;
  }
  
  img.dataset.fallbackAttempted = 'true';
  img.src = '/images/placeholder-news.png';
  img.alt = `Imagem não disponível para: ${noticia.titulo}`;
  img.classList.add('placeholder-image');
};

// Função para formatar datas com fallback
const formatDate = (dateString) => {
  if (!dateString) return 'Data não informada';
  
  try {
    // Tentar diferentes formatos de data
    let date;
    if (dateString.includes('/')) {
      const [day, month, year] = dateString.split('/');
      date = new Date(year, month - 1, day);
    } else {
      date = new Date(dateString);
    }
    
    if (isNaN(date.getTime())) {
      return dateString; // Retorna o valor original se não conseguir converter
    }
    
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  } catch (e) {
    console.warn('Erro ao formatar data:', e);
    return dateString;
  }
};

// Truncar texto
const truncateText = (text, length = 100) => {
  if (!text || typeof text !== 'string') return '';
  if (text.length <= length) return text;
  
  const truncated = text.substring(0, length);
  const lastSpace = truncated.lastIndexOf(' ');
  
  if (lastSpace > length * 0.7) {
    return truncated.substring(0, lastSpace).trim() + '...';
  }
  
  return truncated.trim() + '...';
};

// Formatar número de visualizações
const formatViews = (views) => {
  if (views >= 1000) {
    return (views / 1000).toFixed(1) + 'k';
  }
  return views.toString();
};

// Realizar busca
const handleSearch = () => {
  currentPage.value = 1; // Resetar para página 1 ao buscar
  fetchNoticias();
};

// Limpar busca
const clearSearch = () => {
  searchQuery.value = '';
  handleSearch();
};

// Mudar página
const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
    currentPage.value = page;
    fetchNoticias();
    // Scroll suave para o topo da lista
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

// Inicializar a partir dos parâmetros da URL
const initFromUrl = () => {
  if (typeof window === 'undefined') return;
  
  const params = new URLSearchParams(window.location.search);
  
  // Pegar página da URL
  const pageParam = params.get('page');
  if (pageParam && !isNaN(parseInt(pageParam))) {
    currentPage.value = Math.max(1, parseInt(pageParam));
  }
  
  // Pegar termo de busca da URL
  const searchParam = params.get('search');
  if (searchParam) {
    searchQuery.value = searchParam.trim();
  }
  
  // Buscar notícias com esses parâmetros
  fetchNoticias();
};

// Configurar debounce para busca em tempo real
const debounce = (fn, delay) => {
  let timeoutId;
  return function(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn.apply(this, args), delay);
  };
};

const debouncedSearch = debounce(() => {
  if (mounted.value) {
    handleSearch();
  }
}, 500);

// Observar mudanças na query de busca
watch(searchQuery, (newValue, oldValue) => {
  // Só executar se o componente estiver montado e o valor realmente mudou
  if (mounted.value && newValue !== oldValue) {
    debouncedSearch();
  }
});

// Gerar array de páginas para paginação
const getPaginationPages = () => {
  const pages = [];
  const current = currentPage.value;
  const total = totalPages.value;
  
  if (total <= 7) {
    // Se temos 7 ou menos páginas, mostrar todas
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    // Sempre mostrar primeira página
    pages.push(1);
    
    if (current > 3) {
      pages.push('...');
    }
    
    // Mostrar páginas ao redor da atual
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    
    if (current < total - 2) {
      pages.push('...');
    }
    
    // Sempre mostrar última página
    if (total > 1) {
      pages.push(total);
    }
  }
  
  return pages;
};

onMounted(() => {
  mounted.value = true;
  initFromUrl();
});

onUnmounted(() => {
  mounted.value = false;
  if (abortController) {
    abortController.abort();
  }
});
</script>

<template>
  <Head title="Todas as Notícias - ACADEPOL" />
  <SiteNavbar />
  <Header />
  
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Breadcrumb/Voltar -->
      <Link 
        href="/" 
        class="inline-flex items-center text-sm text-gray-600 hover:text-[#bea55a] mb-6 transition-colors focus:outline-none focus:ring-2 focus:ring-[#bea55a] focus:ring-offset-2 rounded-md"
      >
        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Voltar para Home
      </Link>

      <!-- Cabeçalho da página -->
      <div class="mb-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:p-8">
        <div class="lg:flex lg:items-center lg:justify-between">
          <div class="flex-1 min-w-0">
            <h1 class="text-2xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
              Todas as Notícias
            </h1>
            <p class="text-gray-600 text-md">
              Explore nosso arquivo completo de notícias e fique sempre atualizado sobre as atividades da ACADEPOL
            </p>
          </div>
          
          <!-- Barra de pesquisa -->
          <div class="mt-6 lg:mt-0 lg:ml-8 w-full lg:w-auto">
            <div class="relative w-full lg:w-80">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar notícias..."
                class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-[#bea55a] focus:border-[#bea55a] text-gray-900 transition-colors"
              />
              <div class="absolute inset-y-0 right-0 flex items-center">
                <button 
                  v-if="searchQuery"
                  @click="clearSearch" 
                  class="p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#bea55a] focus:ring-offset-2 rounded-md transition-colors"
                  aria-label="Limpar busca"
                >
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Estado de carregamento -->
      <div 
        v-if="loading" 
        class="flex flex-col justify-center items-center py-20 lg:py-32" 
        aria-live="polite" 
        aria-busy="true"
      >
        <div class="relative">
          <div class="animate-spin rounded-full h-16 w-16 border-4 border-gray-200 border-t-[#bea55a]" role="status"></div>
          <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-yellow-400 animate-pulse"></div>
        </div>
        <p class="mt-4 text-gray-600 font-medium">Carregando notícias...</p>
        <span class="sr-only">Carregando notícias...</span>
      </div>

      <!-- Estado de erro -->
      <div 
        v-else-if="error" 
        class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-400 rounded-lg p-6 mb-8 shadow-sm" 
        aria-live="assertive"
      >
        <div class="flex items-start">
          <svg class="h-6 w-6 text-red-400 mt-1 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <h3 class="text-red-800 font-semibold text-lg">Erro ao carregar notícias</h3>
            <p class="text-red-700 mt-2">{{ error }}</p>
            <div class="mt-4 flex flex-wrap gap-3">
              <button 
                @click="fetchNoticias" 
                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-200 focus:outline-none transition-all duration-200 shadow-sm"
              >
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Tentar novamente
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Template de resultados -->
      <template v-else>
        <!-- Resultados da busca -->
        <div v-if="searchQuery" class="mb-6 bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4">
          <div class="flex justify-between items-center">
            <p class="text-blue-800">
              <span v-if="totalItems === 0" class="font-medium">Nenhum resultado encontrado para </span>
              <span v-else class="font-medium">{{ totalItems }} resultado{{ totalItems !== 1 ? 's' : '' }} para </span>
              <strong class="text-blue-900">"{{ searchQuery }}"</strong>
            </p>
            <button 
              @click="clearSearch" 
              class="text-[#bea55a] hover:text-yellow-600 focus:outline-none focus:ring-2 focus:ring-[#bea55a] focus:ring-offset-2 rounded-md flex items-center font-medium transition-colors"
            >
              <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Limpar
            </button>
          </div>
        </div>

        <!-- Sem resultados -->
        <div v-if="noticias.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
          <div class="mx-auto max-w-md">
            <svg 
              class="h-20 w-20 text-gray-300 mx-auto mb-6" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke="currentColor" 
              aria-hidden="true"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Nenhuma notícia encontrada</h3>
            <p class="text-gray-500 mb-6">
              {{ searchQuery ? `Não encontramos resultados para "${searchQuery}". Tente uma busca diferente.` : 'Não há notícias publicadas no momento.' }}
            </p>
            <button 
              v-if="searchQuery" 
              @click="clearSearch" 
              class="inline-flex items-center px-6 py-3 bg-[#bea55a] text-white font-medium rounded-lg hover:bg-yellow-600 focus:ring-4 focus:ring-yellow-200 focus:outline-none transition-all duration-200 shadow-md"
            >
              Limpar busca
            </button>
          </div>
        </div>

        <!-- Lista compacta de notícias -->
        <div v-else class="space-y-4">
          <article 
            v-for="(noticia, index) in noticias" 
            :key="noticia.id"
            class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden border border-gray-100 hover:border-[#bea55a]/30"
            :class="[
              noticia.destaque ? 'ring-1 ring-[#bea55a]/30 border-[#bea55a]/30' : '',
              'transform hover:-translate-y-0.5'
            ]"
          >
            <div class="flex h-32 sm:h-36">
              <!-- Imagem compacta -->
              <div class="w-32 sm:w-48 relative overflow-hidden flex-shrink-0">
                <img 
                  v-if="noticia.imagem"
                  :src="noticia.imagem" 
                  :alt="`Imagem ilustrativa da notícia: ${noticia.titulo}`" 
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  loading="lazy"
                  @error="(e) => handleImageError(e, noticia)"
                />
                <div 
                  v-else 
                  class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center"
                >
                  <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                
                <!-- Badge de destaque -->
                <div 
                  v-if="noticia.destaque" 
                  class="absolute top-2 left-2 bg-gradient-to-r from-yellow-400 to-[#bea55a] text-white text-xs font-bold px-2 py-1 rounded-full shadow-sm"
                  aria-label="Notícia em destaque"
                >
                  Destaque
                </div>
              </div>
              
              <!-- Conteúdo compacto -->
              <div class="flex-1 p-4 flex flex-col justify-between min-w-0">
                <div>
                  <!-- Metadados -->
                  <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center text-xs text-gray-500">
                      <svg class="h-3 w-3 mr-1 text-[#bea55a]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <time :datetime="noticia.data_publicacao">
                        {{ noticia.data_publicacao }}
                      </time>
                    </div>
                    
                    <div class="flex items-center text-xs text-gray-500">
                      <svg class="h-3 w-3 mr-1 text-[#bea55a]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                      </svg>
                      <span>{{ formatViews(noticia.visualizacoes) }}</span>
                    </div>
                  </div>
                  
                  <!-- Título -->
                  <h2 class="text-sm sm:text-base font-bold text-gray-900 mb-2 leading-tight line-clamp-2 group-hover:text-[#bea55a] transition-colors duration-300">
                    {{ noticia.titulo }}
                  </h2>
                  
                  <!-- Descrição -->
                  <p class="text-xs sm:text-sm text-gray-600 leading-relaxed line-clamp-2">
                    {{ truncateText(noticia.descricao_curta, 100) }}
                  </p>
                </div>
                
                <!-- Ação -->
                <div class="mt-3">
                  <Link 
                    :href="`/noticias/${noticia.id}`"
                    class="inline-flex items-center text-[#bea55a] hover:text-yellow-600 font-medium text-sm group-hover:gap-2 gap-1 transition-all duration-300"
                    :aria-label="`Leia a notícia completa: ${noticia.titulo}`"
                  >
                    Leia mais
                    <svg class="h-3 w-3 transform group-hover:translate-x-0.5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                  </Link>
                </div>
              </div>
            </div>
          </article>
        </div>
        
        <!-- Paginação aprimorada -->
        <div v-if="totalPages > 1" class="flex flex-col sm:flex-row justify-between items-center mt-8 gap-4">
          <!-- Informação de paginação -->
          <div class="text-sm text-gray-600 order-2 sm:order-1">
            Mostrando <span class="font-medium">{{ ((currentPage - 1) * itemsPerPage) + 1 }}</span> 
            a <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, totalItems) }}</span> 
            de <span class="font-medium">{{ totalItems }}</span> 
            resultado{{ totalItems !== 1 ? 's' : '' }}
          </div>
          
          <!-- Controles de paginação -->
          <nav class="relative z-0 inline-flex rounded-lg shadow-sm -space-x-px order-1 sm:order-2" aria-label="Paginação">
            <!-- Botão anterior -->
            <button
              @click="changePage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="relative inline-flex items-center px-3 py-2 rounded-l-lg border border-gray-300 bg-white text-sm font-medium transition-colors"
              :class="currentPage === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-[#bea55a] focus:ring-offset-2'"
            >
              <span class="sr-only">Anterior</span>
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            
            <!-- Números de página -->
            <template v-for="(page, index) in getPaginationPages()" :key="`page-${index}`">
              <button
                v-if="typeof page === 'number'"
                @click="changePage(page)"
                :aria-current="page === currentPage ? 'page' : undefined"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-[#bea55a] focus:ring-offset-2"
                :class="page === currentPage 
                  ? 'z-10 bg-[#bea55a] border-[#bea55a] text-white' 
                  : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
              >
                {{ page }}
              </button>
              
              <!-- Separador "..." -->
              <span
                v-else
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
              >
                ...
              </span>
            </template>
            
            <!-- Botão próximo -->
            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="relative inline-flex items-center px-3 py-2 rounded-r-lg border border-gray-300 bg-white text-sm font-medium transition-colors"
              :class="currentPage === totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-[#bea55a] focus:ring-offset-2'"
            >
              <span class="sr-only">Próximo</span>
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </nav>
        </div>
      </template>
    </div>
  </div>
  
  <Footer />
</template>

<style scoped>
/* Animações e transições */
.transform {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Animação de carregamento */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Estilos para placeholder de imagem */
.placeholder-image {
  opacity: 0.8;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  filter: grayscale(100%);
}

/* Limitar linhas de texto para layout compacto */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

/* responsividade */
@media (max-width: 640px) {
  .aspect-video {
    aspect-ratio: 16 / 9;
  }
}

/* Efeitos de hover */
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

.group:hover .group-hover\:translate-x-0.5 {
  transform: translateX(0.125rem);
}

.group:hover .group-hover\:gap-2 {
  gap: 0.5rem;
}

/* Estados de foco */
a:focus-visible,
button:focus-visible {
  outline: 2px solid #bea55a;
  outline-offset: 2px;
}

/* Gradientes */
.bg-gradient-to-br {
  background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

.bg-gradient-to-r {
  background-image: linear-gradient(to right, var(--tw-gradient-stops));
}

/* Transições para inputs */
input[type="text"] {
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

input[type="text"]:focus {
  border-color: #bea55a;
  box-shadow: 0 0 0 3px rgba(190, 165, 90, 0.1);
}

/* Estados de loading para botões */
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Melhorar scrollbar em navegadores WebKit */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #bea55a;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a69247;
}
</style>