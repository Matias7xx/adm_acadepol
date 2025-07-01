<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  imagem: {
    type: String,
    required: false,
    default: null
  },
  nome: {
    type: String,
    required: true
  },
  modalidade: {
    type: String,
    required: true
  },
  status: {
    type: String,
    default: null
  }
});

// Estado para controlar se a imagem falhou ao carregar
const imagemFalhou = ref(false);

// Verificar se tem imagem válida
const temImagem = computed(() => {
  return props.imagem && !imagemFalhou.value && props.imagem.trim() !== '';
});

// Função para lidar com erro de imagem
const handleImageError = () => {
  imagemFalhou.value = true;
};

// Função para obter a cor do badge baseado no status
const getStatusBadgeClass = (status) => {
  const statusMap = {
    'aberto': 'bg-green-600',
    'em andamento': 'bg-blue-600', 
    'concluído': 'bg-gray-600',
    'cancelado': 'bg-red-600',
  };
  
  return statusMap[status?.toLowerCase()] || 'bg-red-600';
};

// Função para obter o texto do status
const getStatusText = (status) => {
  const statusTextMap = {
    'aberto': 'Inscrições Abertas',
    'em andamento': 'Em Andamento',
    'concluído': 'Concluído', 
    'cancelado': 'Cancelado',
  };
  
  return statusTextMap[status?.toLowerCase()] || 'Inativo';
};
</script>
  
<template>
  <div class="relative h-80 w-full overflow-hidden">
    <!-- Imagem de fundo (quando disponível) -->
    <img 
      v-if="temImagem"
      :src="imagem" 
      :alt="nome" 
      class="absolute inset-0 w-full h-full object-cover object-center transition-opacity duration-300"
      style="filter: brightness(0.7)"
      @error="handleImageError"
    />
    
    <!-- Fallback: Gradiente acinzentado quando não há imagem -->
    <div 
      v-if="!temImagem"
      class="absolute inset-0 w-full h-full bg-gradient-to-br from-gray-400 via-gray-500 to-gray-600"
    >
      <!-- Padrão sutil para dar textura ao gradiente -->
      <div class="absolute inset-0 opacity-20">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="course-pattern" x="0" y="0" width="50" height="50" patternUnits="userSpaceOnUse">
              <circle cx="25" cy="25" r="1" fill="white" opacity="0.3"/>
              <circle cx="0" cy="0" r="1" fill="white" opacity="0.2"/>
              <circle cx="50" cy="50" r="1" fill="white" opacity="0.2"/>
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#course-pattern)"/>
        </svg>
      </div>
      
      <!-- Ícone -->
      <div class="absolute inset-0 flex items-center justify-center opacity-30">
        <svg class="w-24 h-24 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" 
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      </div>
    </div>
    
    <!-- Overlay gradiente -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
    
    <!-- Conteúdo do header -->
    <div class="relative z-10 container mx-auto px-4 flex h-full items-end pb-8 text-white">
      <div class="w-full">
        <!-- Badges de modalidade e status -->
        <div class="flex items-center mb-4 flex-wrap gap-2">
          <span class="bg-yellow-600 text-white px-3 py-1 text-sm font-medium rounded shadow-sm">
            {{ modalidade }}
          </span>
          <span 
            :class="getStatusBadgeClass(status)" 
            class="text-white px-3 py-1 text-sm font-medium rounded shadow-sm"
          >
            {{ getStatusText(status) }}
          </span>
        </div>
        
        <!-- Título do curso -->
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-2 leading-tight">
          {{ nome }}
        </h1>
        
        <!-- Indicador visual quando não há imagem -->
        <div v-if="!temImagem" class="flex items-center text-gray-300 text-sm mt-2">
          <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="opacity-75">Imagem não disponível</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animação para transições */
.transition-opacity {
  transition-property: opacity;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Textura do gradiente */
.bg-gradient-to-br {
  background-attachment: fixed;
}

/* Responsividade para o título */
@media (max-width: 640px) {
  h1 {
    font-size: 1.5rem;
    line-height: 1.3;
  }
}

/* hover nos badges */
.bg-yellow-600:hover,
.bg-green-600:hover,
.bg-blue-600:hover,
.bg-gray-600:hover,
.bg-red-600:hover {
  filter: brightness(1.1);
  transition: filter 0.2s ease-in-out;
}

/* legibilidade em todos os cenários */
.text-white {
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

/* Padrão de fallback em telas menores */
@media (max-width: 768px) {
  #course-pattern circle {
    opacity: 0.2;
  }
  
  .opacity-30 {
    opacity: 0.2;
  }
}
</style>