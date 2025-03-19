<script setup>
import SideCard from './SideCard.vue';
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

// Estado do componente
const noticias = ref([]);
const loading = ref(true);
const error = ref(null);
const retryCount = ref(0);

// Configurações
const MAX_RETRIES = 3;
const RETRY_DELAY = 2000; // ms
const MAX_PREVIEW_ITEMS = 3;

// Buscar notícias da API com mecanismo de retry
const fetchNoticias = async (isRetry = false) => {
  try {
    if (!isRetry) {
      loading.value = true;
    }
    error.value = null;
    
    const response = await fetch('/api/ultimas-noticias', {
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
    
    // Limitamos a apenas MAX_PREVIEW_ITEMS para a visualização da home
    noticias.value = data.slice(0, MAX_PREVIEW_ITEMS).map(noticia => ({
      ...noticia,
      data_publicacao: formatDate(noticia.data_publicacao)
    }));
    
    loading.value = false;
    retryCount.value = 0;
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

// Tratamento de erro de imagem com fallback
const handleImageError = (event) => {
  event.target.src = '/images/placeholder-news.png';
  // Adicionar classe para estilização do placeholder
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

// Verificar se há notícias em destaque
const hasDestaque = () => {
  return noticias.value.some(noticia => noticia.destaque);
};

// Buscar as notícias ao montar o componente
onMounted(() => {
  fetchNoticias();
});
</script>

<template>
  <section class="w-full bg-gray-100 py-8 sm:py-12" aria-labelledby="noticias-titulo">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
      <!-- Conteúdo principal -->
      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Coluna principal de notícias -->
        <div class="w-full lg:w-2/3 space-y-6">
          <!-- Título alinhado com as notícias -->
          <div class="mb-6">
            <h2 id="noticias-titulo" class="text-2xl sm:text-3xl font-bold text-gray-800 relative inline-block">
              Notícias
              <span class="absolute bottom-0 left-0 w-full h-1 bg-[#bea55a]" aria-hidden="true"></span>
            </h2>
          </div>

          <!-- Estado de carregamento -->
          <div v-if="loading" class="flex justify-center items-center py-12" aria-live="polite" aria-busy="true">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-800" role="status"></div>
            <span class="sr-only">Carregando notícias...</span>
          </div>

          <!-- Estado de erro -->
          <div v-else-if="error" class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6" aria-live="assertive">
            <p class="font-medium">Erro ao carregar notícias</p>
            <p>{{ error }}</p>
            <button 
              @click="fetchNoticias" 
              class="mt-2 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none transition-colors"
            >
              Tentar novamente
            </button>
          </div>

          <!-- Sem resultados -->
          <div v-else-if="noticias.length === 0" class="text-center py-8" aria-live="polite">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900">Nenhuma notícia encontrada</h3>
            <p class="mt-1 text-gray-500">
              Não há notícias publicadas no momento.
            </p>
          </div>

          <!-- Lista de notícias -->
          <div v-else class="space-y-6">
            <article 
              v-for="(noticia, index) in noticias" 
              :key="noticia.id" 
              class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1"
              :class="[
                noticia.destaque ? 'border-l-4 border-[#bea55a]' : '',
                index === 0 && hasDestaque() ? 'ring-2 ring-[#bea55a] ring-opacity-50' : ''
              ]"
            >
              <div class="flex flex-col md:flex-row h-full">
                <!-- Container da imagem -->
                <div v-if="noticia.imagem" class="md:w-1/3 h-48 md:h-auto relative">
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
                <div class="p-5 md:w-2/3 flex flex-col h-full">
                  <div class="flex-grow">
                    <!-- Data com ícone -->
                    <div class="text-sm text-gray-500 mb-2 flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <time :datetime="noticia.data_publicacao">{{ noticia.data_publicacao }}</time>
                    </div>
                    
                    <!-- Título da notícia -->
                    <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-3 line-clamp-2">
                      {{ noticia.titulo }}
                    </h3>
                    
                    <!-- Descrição mais legível -->
                    <p class="text-sm sm:text-base text-gray-600 mb-4 leading-relaxed line-clamp-3">
                      {{ truncateText(noticia.descricao_curta, 150) }}
                    </p>
                  </div>
                  
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
                    <div>
                      <Link 
                        :href="`/noticias/${noticia.id}`"
                        class="inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-800 transition-colors focus:outline-none focus:underline"
                        :aria-label="`Leia mais sobre: ${noticia.titulo}`"
                      >
                        Saiba mais
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          </div>

          <!-- Botão "Ver todas as notícias" -->
          <div class="text-center pt-4" v-if="noticias.length > 0">
            <Link 
              href="/noticias"
              class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-700 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200"
            >
              Ver todas as notícias
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </Link>
          </div>
        </div>

        <!-- Sidebar com cards de serviços - 1/3 em desktop -->
        <aside class="w-full lg:w-1/3">
          <SideCard />
        </aside>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Transições suaves */
.transform {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

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