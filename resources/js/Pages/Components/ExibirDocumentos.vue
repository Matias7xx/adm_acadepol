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

// Verificar se está em dispositivo móvel
onMounted(() => {
  isMobile.value = 
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
    window.innerWidth < 768;
    
  // Simular um tempo de carregamento para UI
  setTimeout(() => {
    isLoading.value = false;
  }, 800);
});

// Arquivo para download
const downloadFileName = computed(() => {
  return props.fileName.replace(/\s+/g, '_') + '.pdf';
});
</script>

<template>
  <div class="bg-white shadow-md rounded-lg overflow-hidden">
    <!-- Cabeçalho do documento -->
    <div class="p-6 border-b border-gray-200">
      <h1 class="text-2xl font-bold text-gray-800">{{ documentTitle }}</h1>
      <p v-if="description" class="mt-2 text-gray-600">{{ description }}</p>
    </div>
    
    <!-- Visualizador de documento -->
    <div class="p-6">
      <!-- Estado de carregamento -->
      <div v-if="isLoading" class="flex justify-center items-center h-64" aria-live="polite">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-t-blue-500 border-blue-200"></div>
        <span class="sr-only">Carregando documento...</span>
      </div>
      
      <!-- Visualizador para desktop -->
      <div v-else-if="!isMobile" class="w-full h-[500px] bg-gray-50 rounded-md overflow-hidden shadow-inner">
        <iframe 
          :src="documentUrl" 
          class="w-full h-full border-0" 
          :title="documentTitle"
        ></iframe>
      </div>
      
      <!-- Em dispositivos móveis é mostrado apenas o aviso e as opções de download -->
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
      <div class="mt-4 flex flex-col sm:flex-row justify-center gap-4">
        <!-- Link para abrir o documento diretamente -->
        <a 
          :href="documentUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          Abrir em nova aba
        </a>
        
        <!-- Botão de download -->
        <a 
          :href="documentUrl" 
          :download="downloadFileName"
          class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Baixar documento
        </a>
      </div>
    </div>
    
    <!-- Informações adicionais -->
    <div v-if="additionalInfo.length > 0" class="bg-gray-50 p-6 border-t border-gray-200">
      <h2 class="text-lg font-medium text-gray-800">Informações Importantes</h2>
      <ul class="mt-2 text-sm text-gray-600 space-y-1 list-disc list-inside">
        <li v-for="(info, index) in additionalInfo" :key="index">{{ info }}</li>
      </ul>
    </div>
  </div>
</template>