<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

const diretores = ref([]);
const carregando = ref(true);
const erro = ref(null);
const diretorSelecionado = ref(null);
const filtroAtivo = ref('todos');

const carregarDiretores = async () => {
  carregando.value = true;
  erro.value = null;
  
  try {
    const response = await axios.get('/api/directors');
    // Adiciona uma animação aleatória para cada diretor
    diretores.value = response.data.map((diretor, index) => ({
      ...diretor,
      animationDelay: `${index * 0.1}s` // Adiciona um delay de animação
    }));
    carregando.value = false;
  } catch (error) {
    console.error('Erro ao carregar diretores:', error.response);
    erro.value = error.response?.data?.error || error.message || 'Erro ao carregar os dados';
    carregando.value = false;
  }
};

onMounted(() => {
  carregarDiretores();
});

const diretoresFiltrados = computed(() => {
  if (filtroAtivo.value === 'todos') return diretores.value;
  if (filtroAtivo.value === 'atual') {
    return diretores.value.filter(diretor => diretor.periodo.includes('ATUALMENTE'));
  }
  return diretores.value.filter(diretor => !diretor.periodo.includes('ATUALMENTE'));
});

const selecionarDiretor = (diretor) => {
  diretorSelecionado.value = diretor;
  document.body.classList.add('overflow-hidden');
  
  // Acessibilidade: Foco no botão de fechar
  setTimeout(() => {
    const closeButton = document.querySelector('.modal-close-btn');
    closeButton?.focus();
  }, 100);
};

const fecharModal = () => {
  diretorSelecionado.value = null;
  document.body.classList.remove('overflow-hidden');
};

const filtrarPeriodo = (filtro) => {
  filtroAtivo.value = filtro;
};
</script>

<template>
  <Head title="Galeria de Diretores e Ex-Diretores" />
  
  <div class="max-w-7xl mx-auto px-4 py-12">
    <header class="mb-10 text-center">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">
        Galeria de Diretores e Ex-Diretores
      </h1>
      <p class="text-gray-600 max-w-2xl mx-auto">
        Conheça os profissionais que estiveram à frente da direção da instituição ao longo da sua história.
      </p>
    </header>
    
    <!-- Filtro por período -->
    <nav class="mb-8 flex justify-center" aria-label="Filtro de diretores">
      <div class="inline-flex rounded-md shadow-sm" role="group">
        <button 
          v-for="filtro in ['todos', 'atual', 'anteriores']" 
          :key="filtro"
          @click="filtrarPeriodo(filtro)" 
          :aria-pressed="filtroAtivo === filtro"
          :class="[
            'px-4 py-2 text-sm font-medium border',
            filtro === 'todos' && 'rounded-l-lg',
            filtro === 'anteriores' && 'rounded-r-lg',
            filtroAtivo === filtro 
              ? 'bg-blue-700 text-white border-blue-700' 
              : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
          ]"
        >
          {{ 
            filtro === 'todos' ? 'Todos' : 
            filtro === 'atual' ? 'Atual' : 
            'Anteriores' 
          }}
        </button>
      </div>
    </nav>
    
    <!-- Carregamento -->
    <div v-if="carregando" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      <p class="mt-4 text-gray-600">Carregando diretores...</p>
    </div>
    
    <!-- Erro -->
    <div v-else-if="erro" class="text-center py-12">
      <div class="text-red-600 mb-2 text-xl">
        <strong>Erro ao carregar dados:</strong> {{ erro }}
      </div>
      <button 
        @click="carregarDiretores" 
        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:ring-2 focus:ring-blue-300"
      >
        Tentar novamente
      </button>
    </div>
    
    <!-- Grid de diretores -->
    <transition-group 
      tag="div" 
      name="fade-list"
      class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6"
    >
      <div 
        v-for="diretor in diretoresFiltrados" 
        :key="diretor.id" 
        :style="{ transitionDelay: diretor.animationDelay }"
        class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl group"
      >
        <div class="relative h-64">
          <img 
            :src="diretor.imagem" 
            :alt="`Foto de ${diretor.nome}`" 
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
            loading="lazy"
            onerror="this.src='/images/placeholder-profile.jpg'; this.onerror=null;"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent opacity-70 group-hover:opacity-90 transition-opacity"></div>
          
          <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
            <p class="text-xs font-medium text-blue-200 mb-1">{{ diretor.periodo }}</p>
            <h2 class="text-lg font-bold truncate">{{ diretor.nome }}</h2>
          </div>
          
          <button 
            @click="selecionarDiretor(diretor)" 
            class="absolute inset-0 w-full h-full cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-300"
            :aria-label="`Ver detalhes de ${diretor.nome}`"
          ></button>
          
          <div class="absolute top-3 right-3">
            <span 
              v-if="diretor.periodo.includes('ATUALMENTE')" 
              class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800"
            >
              Atual
            </span>
          </div>
        </div>
      </div>
    </transition-group>
    
    <!-- Sem resultados -->
    <div v-if="diretoresFiltrados.length === 0 && !carregando && !erro" class="text-center py-10">
      <p class="text-gray-500">Nenhum diretor encontrado para o filtro selecionado.</p>
    </div>

    <!-- Modal de detalhes -->
    <Teleport to="body">
      <Transition name="fade">
        <div 
          v-if="diretorSelecionado" 
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto"
          @click.self="fecharModal"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="`diretor-nome-${diretorSelecionado.id}`"
        >
          <div 
            class="bg-white rounded-lg shadow-xl max-w-2xl w-full relative overflow-hidden my-8"
            @click.stop
          >
            <button 
              @click="fecharModal" 
              class="modal-close-btn absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-800 transition-colors focus:ring-2 focus:ring-blue-300"
              aria-label="Fechar"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
            
            <div class="flex flex-col md:flex-row">
              <div class="md:w-2/5 relative">
                <img 
                  :src="diretorSelecionado.imagem" 
                  :alt="`Foto de ${diretorSelecionado.nome}`"
                  class="w-full h-64 md:h-full object-cover" 
                  loading="lazy"
                  onerror="this.src='/images/placeholder-profile.jpg'; this.onerror=null;"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent md:hidden"></div>
              </div>
              
              <div class="md:w-3/5 p-6">
                <div>
                  <span 
                    v-if="diretorSelecionado.periodo.includes('ATUALMENTE')" 
                    class="inline-block px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 mb-2"
                  >
                    Atual
                  </span>
                  <h2 :id="`diretor-nome-${diretorSelecionado.id}`" class="text-2xl font-bold text-gray-800">
                    {{ diretorSelecionado.nome }}
                  </h2>
                  <p class="text-gray-600 mt-1 text-sm font-medium">{{ diretorSelecionado.periodo }}</p>
                  
                  <div class="mt-4 space-y-3 text-gray-700">
                    <p v-if="diretorSelecionado.historico">{{ diretorSelecionado.historico }}</p>
                    <div v-if="diretorSelecionado.realizacoes?.length" class="mt-4">
                      <h3 class="font-semibold text-gray-800 mb-2">Principais Realizações:</h3>
                      <ul class="list-disc pl-5 space-y-1">
                        <li v-for="(realizacao, index) in diretorSelecionado.realizacoes" :key="index">
                          {{ realizacao }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
/* Animações de entrada */
.fade-list-enter-active,
.fade-list-leave-active {
  transition: all 0.5s ease;
}
.fade-list-enter-from,
.fade-list-leave-to {
  opacity: 0;
  transform: translateY(30px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>