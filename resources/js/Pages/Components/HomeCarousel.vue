<!-- HomeCarousel.vue -->
<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const currentIndex = ref(0);
const autoPlayInterval = ref(null);
const newsItems = ref([]);
const loading = ref(true);
const error = ref(null);

// Buscar notícias destacadas do backend
const fetchDestacadas = async () => {
  try {
    loading.value = true;
    const response = await fetch('/api/ultimas-noticias');
    if (!response.ok) {
      throw new Error('Falha ao carregar notícias em destaque');
    }
    
    const data = await response.json();
    // Filtrar notícias destacadas ou pegar as primeiras se não houver destacadas
    const destacadas = data.filter(item => item.destaque).length > 0 
      ? data.filter(item => item.destaque) 
      : data;
    
    // Formatar os dados para o formato que o carrossel espera
    newsItems.value = destacadas.slice(0, 3).map(noticia => ({
      title: noticia.titulo,
      excerpt: noticia.descricao_curta,
      image: noticia.imagem,
      link: `/noticias/${noticia.id}`,
      id: noticia.id
    }));
    
    loading.value = false;
  } catch (err) {
    console.error('Erro ao carregar notícias destacadas:', err);
    error.value = err.message;
    loading.value = false;
    
    // Fallback para os dados estáticos originais em caso de erro
    newsItems.value = [
      {
        title: 'PROCESSO SELETIVO DE DOCENTES – 2024',
        excerpt: 'Venha fazer parte do quadro de professores da Academia de Polícia Civil da Paraíba.',
        image: '/images/acadepol.jpeg',
        link: '#',
      },
      {
        title: 'IV COTE - Curso de Operações Táticas Especiais',
        excerpt: 'Inscrições: de 04/02 até às 18h do dia 21/02. Início em: 31/03/2025.',
        image: '/images/goe.jpeg',
        link: '#',
      },
    ];
  }
};

const nextSlide = () => {
  currentIndex.value = (currentIndex.value + 1) % newsItems.value.length;
};

const prevSlide = () => {
  currentIndex.value = (currentIndex.value - 1 + newsItems.value.length) % newsItems.value.length;
};

const goToSlide = (index) => {
  currentIndex.value = index;
};

const startAutoPlay = () => {
  autoPlayInterval.value = setInterval(nextSlide, 5000);
};

const stopAutoPlay = () => {
  if (autoPlayInterval.value) {
    clearInterval(autoPlayInterval.value);
  }
};

// Tratamento de erro de imagem
/* const handleImageError = (event) => {
  event.target.src = '/images/placeholder-news.jpg';
};
 */
onMounted(() => {
  fetchDestacadas();
  startAutoPlay();
});

onBeforeUnmount(() => {
  stopAutoPlay();
});
</script>

<template> 
  <div class="max-w-screen-xl mx-auto px-4"> <!-- Container alinhado com o restante do layout -->
    <!-- Estado de carregamento -->
    <div v-if="loading" class="relative shadow-xl overflow-hidden w-full h-48 sm:h-64 md:h-80 lg:h-96 bg-gray-200 animate-pulse flex items-center justify-center text-gray-400">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
      </svg>
    </div>
    
    <!-- Estado de erro ou sem notícias -->
    <div v-else-if="error || newsItems.length === 0" class="relative shadow-xl overflow-hidden w-full h-48 sm:h-64 bg-gray-100 flex items-center justify-center text-gray-500 p-4 rounded-lg">
      <div class="text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
        </svg>
        <p>{{ error || 'Não há notícias em destaque no momento.' }}</p>
      </div>
    </div>
    
    <!-- Carrossel -->
    <div 
      v-else
      class="relative shadow-xl overflow-hidden w-full"
      @mouseenter="stopAutoPlay"
      @mouseleave="startAutoPlay"
    >
      <!-- Slides Container -->
      <div 
        class="flex transition-transform duration-500 ease-in-out" 
        :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
      >
        <div 
          v-for="(news, index) in newsItems" 
          :key="news.id || index" 
          class="min-w-full relative"
        >
          <!-- Imagem -->
          <div class="relative w-full">
            <img 
              :src="news.image" 
              :alt="news.title" 
              class="w-full h-48 sm:h-64 md:h-80 lg:h-96 object-cover"
              @error="handleImageError"
            >
            <!-- Overlay gradiente -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
          </div>

          <!-- Conteúdo -->
          <div class="absolute bottom-0 left-0 right-0 p-4 sm:p-6 text-white">
            <h2 class="text-lg sm:text-xl md:text-2xl font-bold mb-2 line-clamp-2">
              {{ news.title }}
            </h2>
            <p class="text-sm sm:text-base mb-2 line-clamp-2 text-gray-200">
              {{ news.excerpt }}
            </p>
            <a 
              :href="news.link" 
              class="inline-block text-yellow-400 hover:text-yellow-300 transition-colors text-sm sm:text-base"
            >
              Leia mais »
            </a>
          </div>
        </div>
      </div>

      <!-- Controles de Navegação -->
      <button 
        @click="prevSlide" 
        class="absolute top-1/2 left-2 sm:left-4 -translate-y-1/2 bg-black/50 hover:bg-black/70 
               text-white p-2 rounded-full transition-all duration-300 focus:outline-none
               w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center"
        aria-label="Slide anterior"
      >
        ‹
      </button>
      <button 
        @click="nextSlide" 
        class="absolute top-1/2 right-2 sm:right-4 -translate-y-1/2 bg-black/50 hover:bg-black/70 
               text-white p-2 rounded-full transition-all duration-300 focus:outline-none
               w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center"
        aria-label="Próximo slide"
      >
        ›
      </button>

      <!-- Indicadores -->
      <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2">
        <button 
          v-for="(_, index) in newsItems" 
          :key="index"
          @click="goToSlide(index)"
          class="w-2 h-2 sm:w-3 sm:h-3 rounded-full transition-all duration-300 focus:outline-none"
          :class="index === currentIndex ? 'bg-white' : 'bg-white/50 hover:bg-white/75'"
          :aria-label="`Ir para slide ${index + 1}`"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animações suaves */
.transition-transform {
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Remover outline padrão mas manter para teclado */
button:focus-visible {
  outline: 2px solid white;
  outline-offset: 2px;
}

/* Animação de carregamento */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

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