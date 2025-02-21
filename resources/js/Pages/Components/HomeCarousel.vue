<script setup>
import imagem from '@/src/assets/acadepol.jpeg';
import imagem2 from '@/src/assets/goe.jpeg';
import { ref, onMounted, onBeforeUnmount } from 'vue';

const currentIndex = ref(0);
const autoPlayInterval = ref(null);

const newsItems = [
  {
    title: 'PROCESSO SELETIVO DE DOCENTES – 2024',
    excerpt: 'Venha fazer parte do quadro de professores da Academia de Polícia Civil da Paraíba.',
    image: imagem,
    link: '#',
  },
  {
    title: 'IV COTE - Curso de Operações Táticas Especiais',
    excerpt: 'Inscrições: de 04/02 até às 18h do dia 21/02. Início em: 31/03/2025.',
    image: imagem2,
    link: '#',
  },
];

const nextSlide = () => {
  currentIndex.value = (currentIndex.value + 1) % newsItems.length;
};

const prevSlide = () => {
  currentIndex.value = (currentIndex.value - 1 + newsItems.length) % newsItems.length;
};

const startAutoPlay = () => {
  autoPlayInterval.value = setInterval(nextSlide, 5000);
};

const stopAutoPlay = () => {
  if (autoPlayInterval.value) {
    clearInterval(autoPlayInterval.value);
  }
};

onMounted(() => {
  startAutoPlay();
});

onBeforeUnmount(() => {
  stopAutoPlay();
});
</script>

<template>
  <div 
    class="relative shadow-xl overflow-hidden"
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
        :key="index" 
        class="min-w-full relative"
      >
        <!-- Imagem -->
        <div class="relative w-full">
          <img 
            :src="news.image" 
            :alt="news.title" 
            class="w-full h-48 sm:h-64 md:h-80 lg:h-96 object-cover"
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
        @click="currentIndex = index"
        class="w-2 h-2 sm:w-3 sm:h-3 rounded-full transition-all duration-300 focus:outline-none"
        :class="index === currentIndex ? 'bg-white' : 'bg-white/50 hover:bg-white/75'"
        :aria-label="`Ir para slide ${index + 1}`"
      />
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
</style>