<script setup>
import SideCard from './SideCard.vue';
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

// Estado do componente
const noticias = ref([]);
const loading = ref(true);
const error = ref(null);

// Buscar notícias da API
const fetchNoticias = async () => {
  try {
    loading.value = true;
    error.value = null;
    
    const response = await fetch('/api/ultimas-noticias');
    
    if (!response.ok) {
      throw new Error('Não foi possível carregar as notícias');
    }
    
    const data = await response.json();
    
    // Limitar a apenas 3 notícias na home
    noticias.value = data.slice(0, 3);
    
    loading.value = false;
  } catch (err) {
    console.error('Erro ao carregar notícias:', err);
    error.value = err.message;
    loading.value = false;
  }
};

// Função para formatar datas
const formatDate = (dateString) => {
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  } catch (e) {
    return dateString;
  }
};

// Buscar as notícias ao montar o componente
onMounted(() => {
  fetchNoticias();
});
</script>

<template>
  <section class="w-full bg-gray-100 py-8 sm:py-12">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
      <!-- Título com destaque visual -->
      <div class="mb-8 text-center">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 relative inline-block">
          Notícias
          <span class="absolute bottom-0 left-0 w-full h-1 bg-[#bea55a]"></span>
        </h2>
      </div>

      <!-- Estado de carregamento -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-800"></div>
      </div>

      <!-- Estado de erro -->
      <div v-else-if="error" class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
        <p class="font-medium">Erro ao carregar notícias</p>
        <p>{{ error }}</p>
        <button 
          @click="fetchNoticias" 
          class="mt-2 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
        >
          Tentar novamente
        </button>
      </div>

      <!-- Sem resultados -->
      <div v-else-if="noticias.length === 0" class="text-center py-8">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900">Nenhuma notícia encontrada</h3>
        <p class="mt-1 text-gray-500">
          Não há notícias publicadas no momento.
        </p>
      </div>

      <!-- Layout flexível que se adapta melhor em diferentes tamanhos de tela -->
      <div v-else class="flex flex-col lg:flex-row gap-8">
        <!-- Coluna principal de notícias - agora com 2/3 em desktop -->
        <div class="w-full lg:w-2/3 space-y-6">
          <div 
            v-for="noticia in noticias" 
            :key="noticia.id" 
            class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1"
            :class="noticia.destaque ? 'border-l-4 border-[#bea55a]' : ''"
          >
            <div class="flex flex-col md:flex-row">
              <!-- Imagem (se disponível) -->
              <div v-if="noticia.imagem" class="md:w-1/3">
                <img 
                  :src="noticia.imagem" 
                  :alt="noticia.titulo" 
                  class="w-full h-48 md:h-full object-cover"
                  @error="handleImageError"
                >
              </div>
              
              <!-- Conteúdo -->
              <div class="p-5 md:w-2/3">
                <!-- Data com ícone -->
                <div class="text-sm text-gray-500 mb-2 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  {{ noticia.data_publicacao }}
                </div>
                
                <!-- Título com badge de destaque -->
                <div class="flex items-start justify-between">
                  <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-3">
                    {{ noticia.titulo }}
                  </h3>
                  <span 
                    v-if="noticia.destaque" 
                    class="ml-2 flex-shrink-0 bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"
                  >
                    Destaque
                  </span>
                </div>
                
                <!-- Descrição mais legível -->
                <p class="text-sm sm:text-base text-gray-600 mb-4 leading-relaxed">
                  {{ noticia.descricao_curta }}
                </p>
                
                <!-- Visualizações -->
                <div class="flex items-center text-gray-500 text-sm mb-3">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                  </svg>
                  {{ noticia.visualizacoes }} visualizações
                </div>
                
                <!-- Botão de "Saiba mais" mais atraente -->
                <div class="mt-2">
                  <Link 
                    :href="`/noticias/${noticia.id}`"
                    class="inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-800 transition-colors"
                  >
                    Leia mais
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                  </Link>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Paginação, se disponível -->
          <div v-if="pagination && pagination.last_page > 1" class="flex justify-center space-x-1 mt-8">
            <Link
              v-for="page in paginationPages"
              :key="page"
              :href="'/noticias?page=' + page"
              :class="[
                'px-4 py-2 text-sm rounded-md',
                page === pagination.current_page 
                  ? 'bg-[#bea55a] text-white font-medium' 
                  : 'bg-white text-gray-700 hover:bg-gray-100'
              ]"
            >
              {{ page }}
            </Link>
          </div>
          
          <!-- Botão "Ver todas as notícias" melhorado (quando está na Home) -->
          <div class="text-center pt-4" v-if="noticias.length > 0">
          <Link 
            href="/noticias"
            class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-700 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200"
          >
            Ver todas as notícias
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </Link>
        </div>
      </div>

        <!-- Sidebar com cards de serviços - agora com 1/3 em desktop -->
        <div class="w-full lg:w-1/3">
          <SideCard />
        </div>
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
</style>