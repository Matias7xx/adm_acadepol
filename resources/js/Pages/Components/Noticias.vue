<script setup>
import SideCard from './SideCard.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

// Estado do componente
const noticias = ref([]);
const loading = ref(true);
const error = ref(null);
const retryCount = ref(0);
const mounted = ref(false);

// Configurações
const MAX_RETRIES = 1;
const RETRY_DELAY = 2000;
const MAX_PREVIEW_ITEMS = 5;

// Debounce controller para evitar múltiplas requisições
let abortController = null;

// Buscar notícias da API
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
    
    const response = await fetch('/api/noticias-home', {
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
    if (!Array.isArray(data)) {
      throw new Error('Formato de dados inválido recebido do servidor');
    }
    
    // Processar e limitar dados
    noticias.value = data
      .slice(0, MAX_PREVIEW_ITEMS)
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
    
    loading.value = false;
    retryCount.value = 0;
    
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

// Tratamento de erro de imagem com fallback
const handleImageError = (event) => {
  event.target.style.display = 'none';
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

// Truncar texto de forma inteligente para layout compacto
const truncateText = (text, length = 80) => {
  if (!text || typeof text !== 'string') return '';
  if (text.length <= length) return text;
  
  const truncated = text.substring(0, length);
  const lastSpace = truncated.lastIndexOf(' ');
  
  if (lastSpace > length * 0.7) {
    return truncated.substring(0, lastSpace).trim() + '...';
  }
  
  return truncated.trim() + '...';
};

// Verificar se há notícias em destaque
const hasDestaque = () => {
  return noticias.value.some(noticia => noticia.destaque);
};

// Formatar número de visualizações
const formatViews = (views) => {
  if (views >= 1000) {
    return (views / 1000).toFixed(1) + 'k';
  }
  return views.toString();
};

onMounted(() => {
  mounted.value = true;
  fetchNoticias();
});

onUnmounted(() => {
  mounted.value = false;
  if (abortController) {
    abortController.abort();
  }
});
</script>

<template>
  <section 
    class="w-full bg-gradient-to-br bg-gray-100 py-8 sm:py-12 lg:py-16" 
    aria-labelledby="noticias-titulo"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header da seção -->
      <div class="text-center mb-8 lg:mb-12">
        <h2 
          id="noticias-titulo" 
          class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-4 relative inline-block"
        >
          Últimas Notícias
          <span 
            class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-20 h-1 bg-[#bea55a] rounded-full" 
            aria-hidden="true"
          ></span>
        </h2>
      </div>

      <!-- Conteúdo principal -->
      <div class="flex flex-col xl:flex-row gap-8 lg:gap-12">
        <!-- Coluna principal de notícias -->
        <main class="w-full xl:w-2/3">
          <!-- Estado de carregamento -->
          <div 
            v-if="loading" 
            class="flex flex-col justify-center items-center py-16 lg:py-24" 
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
                  <Link 
                    href="/noticias"
                    class="inline-flex items-center px-4 py-2 bg-white text-red-600 text-sm font-medium rounded-lg border border-red-200 hover:bg-red-50 focus:ring-4 focus:ring-red-200 focus:outline-none transition-all duration-200"
                  >
                    Ver página de notícias
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <!-- Sem resultados -->
          <div 
            v-else-if="noticias.length === 0" 
            class="text-center py-16 lg:py-24 bg-white rounded-xl shadow-sm border border-gray-100" 
            aria-live="polite"
          >
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
              <h3 class="text-xl font-semibold text-gray-900 mb-2">Nenhuma notícia encontrada</h3>
              <p class="text-gray-500 mb-6">
                Não há notícias publicadas no momento. Volte em breve para conferir as novidades!
              </p>
              <Link 
                href="/noticias"
                class="inline-flex items-center px-6 py-3 bg-[#bea55a] text-white font-medium rounded-lg hover:bg-yellow-600 focus:ring-4 focus:ring-yellow-200 focus:outline-none transition-all duration-200 shadow-md"
              >
                Explorar arquivo de notícias
              </Link>
            </div>
          </div>

          <!-- Grid de notícias -->
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
                <!-- Container da imagem compacta -->
                <div class="w-32 sm:w-48 relative overflow-hidden flex-shrink-0" v-if="noticia.imagem">
                  <img 
                    :src="noticia.imagem" 
                    :alt="`Imagem ilustrativa da notícia: ${noticia.titulo}`" 
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    loading="lazy"
                    @error="handleImageError"
                  />
                  
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
                    <!-- Metadados compactos -->
                    <div class="flex items-center justify-between mb-2">
                      <div class="flex items-center text-xs text-gray-500">
                        <svg class="h-3 w-3 mr-1 text-[#bea55a]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <time :datetime="noticia.data_publicacao">
                          {{ noticia.data_publicacao }}
                        </time>
                      </div>
                      
                      <!-- <div class="flex items-center text-xs text-gray-500">
                        <svg class="h-3 w-3 mr-1 text-[#bea55a]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                          <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                          <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ formatViews(noticia.visualizacoes) }}</span>
                      </div> -->
                    </div>
                    
                    <!-- Título compacto -->
                    <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-2 leading-tight line-clamp-2 group-hover:text-[#bea55a] transition-colors duration-300">
                      {{ noticia.titulo }}
                    </h3>
                    
                    <!-- Descrição compacta -->
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed line-clamp-2">
                      {{ truncateText(noticia.descricao_curta, 100) }}
                    </p>
                  </div>
                  
                  <!-- Ação compacta -->
                  <div class="">
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

          <div class="text-center pt-6" v-if="noticias.length > 0">
              <Link 
                href="/noticias"
                class="inline-flex items-center px-6 py-2.5 bg-[#1a1a1a] text-white font-medium rounded-lg hover:from-yellow-600 hover:to-[#bea55a] focus:outline-none transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
              >
                Ver todas as notícias
                <svg class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </Link>
            </div>
        </main>

        <!-- Sidebar -->
        <aside class="w-full xl:w-1/3">
          <div class="sticky top-8">
            <SideCard />
          </div>
        </aside>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Animações e transições suaves */
.transform {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Animação de carregamento melhorada */
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

/*responsividade */
@media (max-width: 640px) {
  .aspect-video {
    aspect-ratio: 16 / 9;
  }
}

@media (min-width: 1024px) {
  .aspect-square {
    aspect-ratio: 1 / 1;
  }
}

/* Efeitos de hover */
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

.group:hover .group-hover\:translate-x-1 {
  transform: translateX(0.25rem);
}

.group:hover .group-hover\:gap-3 {
  gap: 0.75rem;
}

/* Estados de foco  */
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
</style>