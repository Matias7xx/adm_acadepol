<script setup>
import { ref, onMounted, watch } from 'vue';
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

// Configurações
const MAX_RETRIES = 3;
const RETRY_DELAY = 2000; // ms

// Buscar notícias da API com paginação e busca
const fetchNoticias = async (isRetry = false) => {
  try {
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
        'X-Requested-With': 'XMLHttpRequest'
      },
      cache: 'no-cache'
    });
    
    if (!response.ok) {
      throw new Error(`Erro ${response.status}: ${response.statusText || 'Não foi possível carregar as notícias'}`);
    }
    
    const data = await response.json();
    
    // Processar dados da API paginada
    noticias.value = data.data.map(noticia => ({
      ...noticia,
      data_publicacao: formatDate(noticia.data_publicacao)
    }));
    
    // Atualizar informações de paginação
    currentPage.value = data.current_page;
    totalPages.value = data.last_page;
    totalItems.value = data.total;
    itemsPerPage.value = data.per_page;
    
    loading.value = false;
    retryCount.value = 0;
    
    // Atualizar URL com os parâmetros de busca e paginação (opcional)
    updateUrlParams();
    
  } catch (err) {
    console.error('Erro ao carregar notícias:', err);
    
    // Implementar lógica de retry
    if (retryCount.value < MAX_RETRIES) {
      retryCount.value++;
      console.log(`Tentativa ${retryCount.value} de ${MAX_RETRIES}...`);
      setTimeout(() => fetchNoticias(true), RETRY_DELAY);
    } else {
      error.value = err.message;
      loading.value = false;
    }
  }
};

// Atualizar parâmetros da URL sem recarregar a página
const updateUrlParams = () => {
  const params = new URLSearchParams(window.location.search);
  
  if (currentPage.value !== 1) {
    params.set('page', currentPage.value);
  } else {
    params.delete('page');
  }
  
  if (searchQuery.value) {
    params.set('search', searchQuery.value);
  } else {
    params.delete('search');
  }
  
  const url = window.location.pathname + (params.toString() ? `?${params.toString()}` : '');
  window.history.pushState({}, '', url);
};

// Tratamento de erro de imagem com fallback
const handleImageError = (event) => {
  event.target.src = '/images/placeholder-news.png';
  event.target.classList.add('placeholder-image');
};

// Função para formatar datas
const formatDate = (dateString) => {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    
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

// Truncar texto para evitar quebras de layout
const truncateText = (text, length = 120) => {
  if (!text || text.length <= length) return text;
  return text.substring(0, length).trim() + '...';
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
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    fetchNoticias();
    // Scroll para o topo da lista
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

// Inicializar a partir dos parâmetros da URL
const initFromUrl = () => {
  const params = new URLSearchParams(window.location.search);
  
  // Pegar página da URL
  const pageParam = params.get('page');
  if (pageParam && !isNaN(parseInt(pageParam))) {
    currentPage.value = parseInt(pageParam);
  }
  
  // Pegar termo de busca da URL
  const searchParam = params.get('search');
  if (searchParam) {
    searchQuery.value = searchParam;
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

const debouncedSearch = debounce(handleSearch, 400);

// Observar mudanças na query de busca
watch(searchQuery, () => {
  debouncedSearch();
});

// Buscar notícias ao montar o componente
onMounted(() => {
  initFromUrl();
});
</script>

<template>
  <Head title="Todas as Notícias" />
  <SiteNavbar />
  <Header />
    <div class="py-12 bg-gray-100">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <Link 
          href="/" 
          class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800 mb-4"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Voltar
        </Link>

        <!-- Cabeçalho da página -->
        <div class="mb-8 md:flex md:items-end md:justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Notícias</h1>
            <p class="mt-2 text-gray-600">Fique por dentro das últimas informações da ACADEPOL</p>
          </div>
          
          <!-- Barra de pesquisa -->
          <div class="mt-4 md:mt-0 md:ml-4 w-full md:w-auto">
            <div class="relative w-full md:w-64 lg:w-80">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar notícias..."
                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#bea55a] focus:border-[#bea55a] focus:outline-none transition-colors"
              />
              <button 
                v-if="searchQuery"
                @click="clearSearch" 
                class="absolute right-10 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                aria-label="Limpar busca"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
              <button 
                @click="handleSearch"
                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                aria-label="Buscar"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Estado de carregamento -->
        <div v-if="loading" class="flex justify-center items-center py-20" aria-live="polite" aria-busy="true">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#bea55a]" role="status"></div>
          <span class="sr-only">Carregando notícias...</span>
        </div>

        <!-- Estado de erro -->
        <div v-else-if="error" class="bg-red-100 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-6 shadow-sm" aria-live="assertive">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-700">Erro ao carregar notícias</h3>
              <p class="mt-1 text-sm text-red-600">{{ error }}</p>
              <button 
                @click="fetchNoticias" 
                class="mt-2 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none transition-colors"
              >
                Tentar novamente
              </button>
            </div>
          </div>
        </div>

        <!-- Resultados da busca -->
        <div v-else-if="searchQuery" class="mb-4 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg">
          <div class="flex justify-between items-center">
            <p>
              <span v-if="totalItems === 0">Nenhum resultado encontrado para </span>
              <span v-else>{{ totalItems }} resultado(s) para </span>
              <strong>"{{ searchQuery }}"</strong>
            </p>
            <button 
              @click="clearSearch" 
              class="text-[#bea55a] hover:underline focus:outline-none ml-2 flex items-center"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Limpar
            </button>
          </div>
        </div>

        <!-- Sem resultados -->
        <div v-if="!loading && !error && noticias.length === 0" class="bg-white rounded-lg shadow-sm p-8 text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
          </svg>
          <h3 class="text-lg font-medium text-gray-900">Nenhuma notícia encontrada</h3>
          <p class="mt-1 text-gray-500">
            {{ searchQuery ? `Não encontramos resultados para "${searchQuery}".` : 'Não há notícias publicadas no momento.' }}
          </p>
          <button 
            v-if="searchQuery" 
            @click="clearSearch" 
            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#bea55a] hover:bg-[#a69247] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#bea55a]"
          >
            Limpar busca
          </button>
        </div>

        <!-- Grade de notícias -->
        <div v-else-if="!loading && !error" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <article 
            v-for="noticia in noticias" 
            :key="noticia.id"
            class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition-shadow duration-300 flex flex-col h-full"
            :class="{ 'ring-2 ring-[#bea55a] ring-opacity-50': noticia.destaque }"
          >
            <!-- Container da imagem -->
            <div class="relative h-48">
              <img 
                :src="noticia.imagem" 
                :alt="`Imagem ilustrativa: ${noticia.titulo}`" 
                class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                loading="lazy"
                @error="handleImageError"
              />
              <span 
                v-if="noticia.destaque" 
                class="absolute top-2 left-2 bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"
                aria-label="Notícia em destaque"
              >
                Destaque
              </span>
            </div>
            
            <!-- Conteúdo -->
            <div class="p-5 flex flex-col flex-grow">
              <!-- Data com ícone -->
              <div class="text-sm text-gray-500 mb-2 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <time :datetime="noticia.data_publicacao">{{ noticia.data_publicacao }}</time>
              </div>
              
              <!-- Título da notícia -->
              <h2 class="text-lg font-bold text-gray-800 mb-3 line-clamp-2">
                {{ noticia.titulo }}
              </h2>
              
              <!-- Descrição -->
              <p class="text-sm text-gray-600 mb-4 leading-relaxed line-clamp-3 flex-grow">
                {{ truncateText(noticia.descricao_curta, 130) }}
              </p>
              
              <div class="mt-auto">
                <!-- Visualizações -->
                <div class="flex items-center text-gray-500 text-sm mb-3">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                  </svg>
                  <span>{{ noticia.visualizacoes }} visualizações</span>
                </div>
                
                <!-- Botão de "Saiba mais" -->
                <Link 
                  :href="`/noticias/${noticia.id}`"
                  class="inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-800 transition-colors focus:outline-none focus:underline"
                  :aria-label="`Leia mais sobre: ${noticia.titulo}`"
                >
                  Leia mais
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                  </svg>
                </Link>
              </div>
            </div>
          </article>
        </div>
        
        <!-- Paginação -->
        <div v-if="!loading && !error && totalPages > 1" class="flex justify-center items-center mt-10">
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Paginação">
            <!-- Botão anterior -->
            <button
              @click="changePage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium"
              :class="currentPage === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-500 hover:bg-gray-50'"
            >
              <span class="sr-only">Anterior</span>
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            
            <!-- Números de página -->
            <template v-for="page in totalPages" :key="page">
              <!-- Lógica para mostrar apenas algumas páginas em volta da atual -->
              <button
                v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1) || (totalPages <= 7) || (page === 2 && currentPage > 3) || (page === totalPages - 1 && currentPage < totalPages - 2)"
                @click="changePage(page)"
                :aria-current="page === currentPage ? 'page' : undefined"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                :class="page === currentPage ? 'z-10 bg-[#bea55a] border-[#bea55a] text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
              >
                {{ page }}
              </button>
              
              <!-- Separador "..." -->
              <span
                v-else-if="(page === 2 && currentPage > 3) || (page === totalPages - 1 && currentPage < totalPages - 2)"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
              >
                ...
              </span>
            </template>
            
            <!-- Botão próximo -->
            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium"
              :class="currentPage === totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-500 hover:bg-gray-50'"
            >
              <span class="sr-only">Próximo</span>
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
          </nav>
          
          <!-- Informação de paginação -->
          <div class="text-xs text-gray-500 ml-4">
            Mostrando {{ noticias.length }} de {{ totalItems }} resultado(s)
          </div>
        </div>
      </div>
    </div>
  <Footer />
</template>

<style scoped>
/* Animação de carregamento */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Estilos para placeholder de imagem */
.placeholder-image {
  opacity: 0.85;
  background-color: #f3f4f6;
}

/* Limitar linhas de texto */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>