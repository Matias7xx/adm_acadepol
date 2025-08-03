<script setup>
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
  documentUrl: {
    type: String,
    required: true
  },
  documentTitle: {
    type: String,
    required: true
  },
  fileName: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  additionalInfo: {
    type: Array,
    default: () => []
  }
});

// Estado do componente
const isMobile = ref(false);
const isLoading = ref(true);
const loadingProgress = ref(0);
const zoomLevel = ref(100);

// Verificar se está em dispositivo móvel
onMounted(() => {
  const checkDevice = () => {
    isMobile.value = 
      /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
      window.innerWidth < 768;
  };
  
  checkDevice();
  window.addEventListener('resize', checkDevice);
  
  // Simular carregamento
  const interval = setInterval(() => {
    loadingProgress.value += 10;
    if (loadingProgress.value >= 100) {
      clearInterval(interval);
      isLoading.value = false;
    }
  }, 200);
});

// Arquivo para download
const downloadFileName = computed(() => {
  return props.fileName.replace(/\s+/g, '_') + '.pdf';
});

// Controles de zoom
const zoomIn = () => {
  if (zoomLevel.value < 200) {
    zoomLevel.value += 25;
  }
};

const zoomOut = () => {
  if (zoomLevel.value > 50) {
    zoomLevel.value -= 25;
  }
};

// Estilo do iframe baseado no zoom
const iframeStyle = computed(() => ({
  transform: `scale(${zoomLevel.value / 100})`,
  transformOrigin: 'top left',
  width: '100%',
  height: '100%',
  transition: 'transform 0.2s ease'
}));
</script>

<template>
  <div class="bg-white shadow-md rounded-lg overflow-hidden">
    <!-- Cabeçalho do documento -->
    <div class="p-4 sm:p-6 border-b border-gray-200">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">{{ documentTitle }}</h1>
      <p v-if="description" class="mt-2 text-sm sm:text-base text-gray-600">{{ description }}</p>
    </div>
    
    <!-- Visualizador de documento -->
    <div class="p-4 sm:p-6">
      <!-- Estado de carregamento -->
      <div v-if="isLoading" class="flex flex-col justify-center items-center h-48 sm:h-64">
        <div class="w-48 sm:w-64 h-2 bg-gray-200 rounded-full overflow-hidden mb-4">
          <div 
            class="h-full bg-blue-500 transition-all duration-200 ease-out" 
            :style="{ width: `${loadingProgress}%` }"
          ></div>
        </div>
        <p class="text-center text-gray-600 text-sm sm:text-base">
          Carregando documento... {{ loadingProgress }}%
        </p>
      </div>
      
      <!-- Visualizador para desktop -->
      <div v-else-if="!isMobile" class="document-viewer">
        <!-- Controles de zoom -->
        <div class="zoom-controls mb-4 flex justify-center items-center space-x-4">
          <button 
            @click="zoomOut" 
            class="bg-gray-200 hover:bg-gray-300 p-2 rounded transition-colors"
            :disabled="zoomLevel <= 50"
          >
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
            </svg>
          </button>
          <span class="text-sm sm:text-base font-medium">{{ zoomLevel }}%</span>
          <button 
            @click="zoomIn" 
            class="bg-gray-200 hover:bg-gray-300 p-2 rounded transition-colors"
            :disabled="zoomLevel >= 200"
          >
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7m6 0v6m0-6h6" />
            </svg>
          </button>
        </div>

        <!-- Iframe -->
        <div class="w-full h-64 sm:h-96 lg:h-[500px] bg-gray-50 rounded-md overflow-hidden shadow-inner">
          <iframe 
            :src="documentUrl" 
            class="w-full h-full border-0" 
            :style="iframeStyle"
            :title="documentTitle"
          ></iframe>
        </div>
      </div>
      
      <!-- Em dispositivos móveis - aviso -->
      <div v-else class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6" role="alert">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-yellow-700">
              Para melhor visualização do documento, recomendamos fazer o download do arquivo ou abri-lo em uma nova aba.
            </p>
          </div>
        </div>
      </div>
      
      <!-- Opções para visualização e download -->
      <div class="mt-4 flex flex-col sm:flex-row justify-center gap-3 sm:gap-4">
        <!-- Link para abrir o documento diretamente -->
        <a 
          :href="documentUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
        >
          <svg class="-ml-1 mr-2 h-4 w-4 sm:h-5 sm:w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          Abrir em nova aba
        </a>
        
        <!-- Botão de download -->
        <a 
          :href="documentUrl" 
          :download="downloadFileName"
          class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
        >
          <svg class="-ml-1 mr-2 h-4 w-4 sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Baixar documento
        </a>
      </div>
    </div>
    
    <!-- Informações adicionais -->
    <div v-if="additionalInfo.length > 0" class="bg-gray-50 p-4 sm:p-6 border-t border-gray-200">
      <h2 class="text-lg font-medium text-gray-800 mb-4">Informações Importantes</h2>
      <ul class="mt-2 text-sm text-gray-600 space-y-1 list-disc list-inside">
        <li v-for="(info, index) in additionalInfo" :key="index">{{ info }}</li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.document-viewer {
  @apply relative;
}

.zoom-controls button:disabled {
  @apply opacity-50 cursor-not-allowed;
}

/* responsividade */
@media (max-width: 480px) {
  .zoom-controls {
    @apply space-x-2;
  }
  
  .zoom-controls button {
    @apply p-1.5;
  }
}

/* Transições */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}
</style>