<template>
  <Head title="Cursos" />
  <div class="min-h-screen flex flex-col bg-gray-100">
    <Header />
    <SiteNavbar />
    
    <main class="flex-grow">
      <div class="container mx-auto px-4 py-8">
        <!-- Título -->
        <div class="flex items-center mb-8 border-l-4 border-[#bea55a] pl-4">
          <h1 class="text-4xl font-sans text-[#bea55a] uppercase tracking-wider">CURSOS</h1>
        </div>

        <!-- Seção de Busca -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <div class="flex flex-col md:flex-row gap-4 items-end">
            <!-- Campo de busca -->
            <div class="flex-1">
              <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                Buscar cursos
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                  </svg>
                </div>
                <input
                  id="search"
                  v-model="searchForm.search"
                  type="text"
                  placeholder="Digite o nome do curso, descrição ou modalidade..."
                  class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-[#bea55a] focus:border-[#bea55a] transition-colors"
                  @keyup.enter="buscarCursos"
                />
              </div>
            </div>
            
            <!-- Botões de ação -->
            <div class="flex gap-2">
              <button
                @click="buscarCursos"
                class="bg-[#bea55a] hover:bg-[#a38e4d] text-white px-6 py-3 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center"
              >
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Buscar
              </button>
              
              <button
                v-if="filters.search"
                @click="limparBusca"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-3 rounded-lg text-sm font-medium transition-colors duration-200"
              >
                Limpar
              </button>
            </div>
          </div>
          
          <!-- Filtros ativos -->
          <div v-if="filters.search" class="mt-4 flex items-center gap-2">
            <span class="text-sm text-gray-600">Buscando por:</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#bea55a] bg-opacity-10 text-[#bea55a] border border-[#bea55a] border-opacity-20">
              {{ filters.search }}
              <button @click="limparBusca" class="ml-2 text-[#bea55a] hover:text-[#a38e4d]">
                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
              </button>
            </span>
          </div>
        </div>

        <!-- Contador de resultados -->
        <div v-if="hasCursos" class="mb-6">
          <p class="text-gray-600 text-sm">
            Mostrando {{ cursos.from }} - {{ cursos.to }} de {{ cursos.total }} cursos
            <span v-if="filters.search" class="font-medium">(filtrados por "{{ filters.search }}")</span>
          </p>
        </div>

        <!-- Grid de Cursos -->
        <div v-if="hasCursos" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div 
            v-for="curso in cursos.data" 
            :key="curso.id"
            class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 flex flex-col relative h-[420px]"
            :class="{
              'ring-1 ring-[#bea55a] ring-offset-2 hover:shadow-lg': curso.status === 'aberto',
              'bg-gray-100 opacity-80': curso.status === 'concluído' || curso.status === 'em andamento'
            }"
          >
            <!-- Faixa de status -->
            <div 
              v-if="curso.status === 'aberto'" 
              class="absolute top-4 -right-10 bg-[#bea55a] text-white py-1 px-10 transform rotate-45 z-10 shadow-md"
            >
              <span class="text-xs font-bold uppercase tracking-wider">Inscrições Abertas</span>
            </div>
            
            <!-- Badge de curso concluído -->
            <div 
              v-if="curso.status === 'concluído'" 
              class="absolute top-3 left-3 bg-gray-600 text-white px-3 py-1 rounded-full z-10"
            >
              <span class="text-xs font-medium">Concluído</span>
            </div>

            <!-- Badge de curso em andamento -->
            <div 
              v-if="curso.status === 'em andamento'" 
              class="absolute top-3 left-3 bg-green-600 text-white px-3 py-1 rounded-full z-10"
            >
              <span class="text-xs font-medium">Em andamento</span>
            </div>

            <!-- Badge de curso cancelado -->
            <div 
              v-if="curso.status === 'cancelado'" 
              class="absolute top-3 left-3 bg-yellow-600 text-white px-3 py-1 rounded-full z-10"
            >
              <span class="text-xs font-medium">Cancelado</span>
            </div>
            
            <!-- Imagem do curso -->
            <div class="relative h-48">
              <img 
                :src="curso.imagem || '/images/default-curso.jpg'" 
                :alt="`Curso de ${curso.nome}`" 
                class="w-full h-full object-cover" 
                :class="{
                  'filter grayscale': curso.status === 'concluído' || curso.status === 'em andamento',
                  'filter grayscale opacity-75': curso.status === 'cancelado'
                }"
                @error="handleImageError"
              />
              <div 
                class="absolute inset-0 bg-gradient-to-t from-black to-transparent opacity-60"
                :class="{
                  'opacity-40': curso.status === 'concluído' || curso.status === 'em andamento',
                  'opacity-50': curso.status === 'cancelado'
                }"
              ></div>
              <div class="absolute bottom-0 left-0 right-0 p-3">
                <span 
                  class="bg-[#bea55a] text-white text-xs px-2 py-1 rounded uppercase font-bold"
                  :class="{
                    'bg-gray-500': curso.status === 'concluído' || curso.status === 'em andamento',
                    'bg-yellow-600': curso.status === 'cancelado'
                  }"
                >
                  Acadepol
                </span>
              </div>
            </div>
            
            <!-- Conteúdo do card -->
            <div class="p-4 flex flex-col flex-grow">
              <h3 
                class="text-lg font-bold mb-3 line-clamp-2"
                :class="{
                  'text-gray-600': curso.status === 'concluído' || curso.status === 'em andamento' || curso.status === 'cancelado',
                  'text-gray-800': curso.status === 'aberto'
                }"
              >
                {{ curso.nome }}
              </h3>
              
              <p 
                class="text-sm mb-4 line-clamp-3 flex-grow"
                :class="{
                  'text-gray-500': curso.status === 'concluído' || curso.status === 'em andamento' || curso.status === 'cancelado',
                  'text-gray-600': curso.status === 'aberto'
                }"
              >
                {{ curso.descricao }}
              </p>
              
              <!-- Informações do curso -->
              <div class="space-y-2 mb-4">
                <div class="flex items-center text-xs text-gray-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  {{ curso.carga_horaria || 'Consultar' }}H
                </div>
                
                <div class="flex items-center text-xs text-gray-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ curso.modalidade || 'Presencial' }}
                </div>

                <!-- Localização -->
                <div v-if="curso.localizacao" class="flex items-center text-xs text-gray-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  {{ curso.localizacao }}
                </div>
              </div>
              
              <!-- Ação do card -->
              <div class="mt-auto">
                <Link 
                  v-if="curso.status === 'aberto'"
                  :href="`/cursos/${curso.id}`" 
                  class="w-full bg-[#bea55a] text-white py-2 rounded font-medium hover:bg-[#a38e4d] transition-colors flex items-center justify-center text-sm"
                >
                  Ver Detalhes
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
                
                <Link 
                  v-else-if="curso.status === 'em andamento'"
                  :href="`/cursos/${curso.id}`" 
                  class="w-full bg-gray-500 text-white py-2 rounded font-medium hover:bg-gray-600 transition-colors flex items-center justify-center text-sm"
                >
                  Ver Detalhes
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
                
                <div 
                  v-else-if="curso.status === 'concluído'"
                  class="w-full bg-gray-400 text-white py-2 rounded font-medium flex items-center justify-center text-sm cursor-default"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Curso Concluído
                </div>

                <div 
                  v-else-if="curso.status === 'cancelado'"
                  class="w-full bg-yellow-500 text-white py-2 rounded font-medium flex items-center justify-center text-sm cursor-default"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.888-.833-2.558 0L3.348 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                  Cancelado
                </div>

                <Link 
                  v-else
                  :href="`/cursos/${curso.id}`" 
                  class="w-full bg-[#bea55a] text-white py-2 rounded font-medium hover:bg-[#a38e4d] transition-colors flex items-center justify-center text-sm"
                >
                  Ver Detalhes
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Estado quando não há resultados de busca -->
        <div v-else-if="filters.search && !hasCursos" class="text-center py-12">
          <div class="max-w-md mx-auto bg-white rounded-lg shadow-sm p-8">
            <div class="bg-gray-100 p-6 rounded-full inline-block mb-4">
              <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum curso encontrado</h3>
            <p class="text-gray-500 mb-4">
              Não encontramos cursos que correspondam à sua busca "{{ filters.search }}".
            </p>
            <button
              @click="limparBusca"
              class="bg-[#bea55a] hover:bg-[#a38e4d] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            >
              Ver todos os cursos
            </button>
          </div>
        </div>
        
        <!-- Estado vazio (quando não há cursos no sistema) -->
        <div v-else-if="!hasCursos && !filters.search" class="bg-white p-8 rounded-lg shadow-md text-center">
          <div class="bg-gray-100 p-6 rounded-full inline-block mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-[#bea55a]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <p class="text-gray-700 text-lg font-medium">Nenhum curso disponível no momento.</p>
          <p class="text-gray-500 mt-2">Novos cursos serão adicionados em breve.</p>
        </div>

        <!-- Paginação -->
        <div v-if="cursos.links && cursos.links.length > 3" class="mt-12">
          <div class="flex justify-center space-x-1">
            <template v-for="(link, index) in cursos.links" :key="index">
              <!-- Link anterior -->
              <button
                v-if="link.url && index === 0" 
                @click="irParaPagina(link.url)"
                class="px-4 py-2 bg-white rounded-md text-gray-700 hover:bg-[#bea55a] hover:text-white border border-gray-300 transition-colors"
                :class="{'opacity-50 cursor-not-allowed': !link.url}"
              >
                &laquo;
              </button>
              
              <!-- Links numéricos (pular o primeiro e o último) -->
              <button
                v-else-if="index !== 0 && index !== cursos.links.length - 1 && link.url" 
                @click="irParaPagina(link.url)"
                class="px-4 py-2 rounded-md transition-colors border"
                :class="link.active ? 
                  'bg-[#bea55a] text-white border-[#bea55a]' : 
                  'bg-white text-gray-700 hover:bg-[#bea55a] hover:text-white border-gray-300'"
              >
                {{ link.label }}
              </button>
              
              <!-- Span para números sem link -->
              <span
                v-else-if="index !== 0 && index !== cursos.links.length - 1 && !link.url" 
                class="px-4 py-2 rounded-md border bg-gray-100 text-gray-400 border-gray-300"
              >
                {{ link.label }}
              </span>
              
              <!-- Link seguinte -->
              <button
                v-if="link.url && index === cursos.links.length - 1" 
                @click="irParaPagina(link.url)"
                class="px-4 py-2 bg-white rounded-md text-gray-700 hover:bg-[#bea55a] hover:text-white border border-gray-300 transition-colors"
                :class="{'opacity-50 cursor-not-allowed': !link.url}"
              >
                &raquo;
              </button>
            </template>
          </div>
        </div>
      </div>
    </main>
      
    <Footer />
  </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

// Componentes
import Header from './Components/Header.vue';
import SiteNavbar from './Components/SiteNavbar.vue';
import Footer from './Components/Footer.vue';

const props = defineProps({
  cursos: {
    type: Object,
    required: true,
    default: () => ({ 
      data: [],
      links: [],
      meta: {},
      from: 0,
      to: 0,
      total: 0
    })
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

// Form de busca
const searchForm = reactive({
  search: props.filters.search || ''
});

// Verificar se há cursos disponíveis
const hasCursos = computed(() => props.cursos.data && props.cursos.data.length > 0);

// Função para buscar cursos
const buscarCursos = () => {
  router.get(route('cursos'), {
    search: searchForm.search
  }, {
    preserveState: true,
    preserveScroll: true
  });
};

// Função para limpar busca
const limparBusca = () => {
  searchForm.search = '';
  router.get(route('cursos'), {}, {
    preserveState: true,
    preserveScroll: true
  });
};

// Função para navegar entre páginas
const irParaPagina = (url) => {
  router.visit(url, {
    preserveState: true,
    preserveScroll: true
  });
};

// Tratamento de erro de imagem
const handleImageError = (event) => {
  event.target.src = '/images/placeholder-news2.png';
};
</script>

<style scoped>
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

/* Transições suaves para filtros */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>