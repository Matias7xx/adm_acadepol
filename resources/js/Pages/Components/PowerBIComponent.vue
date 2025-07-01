<script setup>
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Relatório Power BI'
  },
  src: {
    type: String,
    required: true
  },
  height: {
    type: String,
    default: '600px'
  }
});

// Estado
const isMobile = ref(false);
const isLoading = ref(true);
const loadingProgress = ref(0);
const isFullscreen = ref(false);

// Verificar se está em dispositivo móvel
onMounted(() => {
  isMobile.value = 
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
    window.innerWidth < 768;
    
  // Simular carregamento
  const interval = setInterval(() => {
    loadingProgress.value += 10;
    if (loadingProgress.value >= 100) {
      clearInterval(interval);
      isLoading.value = false;
    }
  }, 200);
});

// Altura responsiva
const responsiveHeight = computed(() => {
  if (isMobile.value) {
    return '400px';
  }
  return props.height;
});

// Alternar tela cheia
const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value;
};

// Abrir em nova aba
const openInNewTab = () => {
  window.open(props.src, '_blank');
};
</script>

<template>
  <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
    <!-- Cabeçalho do componente -->
    <div class="bg-gray-100 border p-4">
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800">{{ title }}</h2>
        <div class="flex space-x-2">
          <!-- Botão de tela cheia -->
          <button 
            @click="toggleFullscreen"
            class="bg-white/20 hover:bg-white/30 text-gray-800 p-2 rounded-lg transition-colors"
            :title="isFullscreen ? 'Sair da tela cheia' : 'Tela cheia'"
          >
            <svg v-if="!isFullscreen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.5 3.5M15 9v-4.5M15 9h4.5M15 9l5.5-5.5M9 15v4.5M9 15H4.5M9 15l-5.5 5.5M15 15v4.5M15 15h4.5m0 0l5.5 5.5" />
            </svg>
          </button>
          
          <!-- Botão para abrir em nova aba -->
          <button 
            @click="openInNewTab"
            class="bg-white/20 hover:bg-white/30 text-gray-800 p-2 rounded-lg transition-colors"
            title="Abrir em nova aba"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Container do iframe -->
    <div 
      class="relative"
      :class="isFullscreen ? 'fixed inset-0 z-50 bg-white' : ''"
    >
      <!-- Estado de carregamento -->
      <div v-if="isLoading" class="flex flex-col justify-center items-center p-8" :style="{ height: responsiveHeight }">
        <div class="w-64 h-2 bg-gray-200 rounded-full overflow-hidden mb-4">
          <div 
            class="h-full bg-gray-300 transition-all duration-200 ease-out" 
            :style="{ width: `${loadingProgress}%` }"
          ></div>
        </div>
        <p class="text-center text-gray-600">
          Carregando relatório... {{ loadingProgress }}%
        </p>
      </div>
      
      <!-- Iframe do Power BI -->
      <div v-else class="relative">
        <!-- Botão de fechar tela cheia (quando em fullscreen) -->
        <button 
          v-if="isFullscreen"
          @click="toggleFullscreen"
          class="absolute top-4 right-4 z-10 bg-black/50 hover:bg-black/70 text-white p-2 rounded-lg transition-colors"
          title="Fechar tela cheia"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        
        <!-- Container responsivo do iframe -->
        <div 
          class="w-full bg-gray-50 overflow-hidden"
          :class="isFullscreen ? 'h-screen' : 'rounded-b-lg'"
          :style="!isFullscreen ? { height: responsiveHeight } : {}"
        >
          <iframe 
            :src="props.src"
            :title="props.title"
            class="w-full h-full border-0"
            frameborder="0"
            allowfullscreen="true"
            loading="lazy"
          ></iframe>
        </div>
      </div>
      
      <!-- Aviso para dispositivos móveis -->
      <div v-if="isMobile && !isLoading" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 m-4" role="alert">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-yellow-700">
              <strong>Dica:</strong> Para melhor visualização em dispositivos móveis, use a opção "Abrir em nova aba" ou "Tela cheia".
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animações e estilos */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Responsividade para telas muito pequenas */
@media (max-width: 480px) {
  .bg-gray-50 {
    background-color: #f9fafb;
  }
}

/* visualização em tablets */
@media (min-width: 768px) and (max-width: 1024px) {
  iframe {
    min-height: 500px;
  }
}

/* Estilo para fullscreen */
.fixed.inset-0 {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}
</style>