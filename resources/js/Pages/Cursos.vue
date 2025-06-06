<template>
  <Head title="Cursos" />
  <div class="min-h-screen flex flex-col bg-gray-100">
    <Header />
    <SiteNavbar />
    
    <main class="flex-grow">
      <div class="container mx-auto px-4 py-8">
        <div class="flex items-center mb-8 border-l-4 border-[#bea55a] pl-4">
          <h1 class="text-4xl font-sans text-[#bea55a] uppercase tracking-wider">CURSOS</h1>
        </div>
        
        <div v-if="hasCursos" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div 
            v-for="curso in cursos.data" 
            :key="curso.id"
            class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 flex flex-col relative h-[420px]"
            :class="{
              'ring-1 ring-[#bea55a] ring-offset-2 hover:shadow-lg': curso.status === 'aberto',
              'bg-gray-100 opacity-80': curso.status === 'fechado'
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
              v-if="curso.status === 'fechado'" 
              class="absolute top-3 left-3 bg-gray-600 text-white px-3 py-1 rounded-full z-10"
            >
              <span class="text-xs font-medium">Concluído</span>
            </div>
            
            <!-- Imagem do curso -->
            <div class="relative h-48">
              <img 
                :src="curso.imagem || '/images/default-curso.jpg'" 
                :alt="`Curso de ${curso.nome}`" 
                class="w-full h-full object-cover" 
                :class="{'filter grayscale': curso.status === 'fechado'}"
                @error="handleImageError"
              />
              <div 
                class="absolute inset-0 bg-gradient-to-t from-black to-transparent opacity-60"
                :class="{'opacity-40': curso.status === 'fechado'}"
              ></div>
              <div class="absolute bottom-0 left-0 right-0 p-3">
                <span 
                  class="bg-[#bea55a] text-white text-xs px-2 py-1 rounded uppercase font-bold"
                  :class="{'bg-gray-500': curso.status === 'fechado'}"
                >
                  Acadepol
                </span>
              </div>
            </div>
            
            <!-- Conteúdo do card -->
            <div class="p-4 flex flex-col flex-grow">
              <h3 
                class="text-lg font-bold mb-3 line-clamp-2"
                :class="curso.status === 'fechado' ? 'text-gray-600' : 'text-gray-800'"
              >
                {{ curso.nome }}
              </h3>
              
              <p 
                class="text-sm mb-4 line-clamp-3 flex-grow"
                :class="curso.status === 'fechado' ? 'text-gray-500' : 'text-gray-600'"
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
                
                <div 
                  v-else
                  class="w-full bg-gray-400 text-white py-2 rounded font-medium flex items-center justify-center text-sm cursor-default"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Curso Concluído
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Paginação -->
        <div v-if="cursos.links && cursos.links.length > 3" class="mt-12">
          <div class="flex justify-center space-x-1">
            <template v-for="(link, index) in cursos.links" :key="index">
              <!-- Link anterior -->
              <Link 
                v-if="link.url && index === 0" 
                :href="link.url" 
                class="px-4 py-2 bg-white rounded-md text-gray-700 hover:bg-[#bea55a] hover:text-white border border-gray-300 transition-colors"
                :class="{'opacity-50 cursor-not-allowed': !link.url}"
              >
                &laquo;
              </Link>
              
              <!-- Links numéricos (pular o primeiro e o último) -->
              <Link 
                v-else-if="index !== 0 && index !== cursos.links.length - 1" 
                :href="link.url" 
                class="px-4 py-2 rounded-md transition-colors border"
                :class="link.active ? 
                  'bg-[#bea55a] text-white border-[#bea55a]' : 
                  'bg-white text-gray-700 hover:bg-[#bea55a] hover:text-white border-gray-300'"
              >
                {{ link.label }}
              </Link>
              
              <!-- Link seguinte -->
              <Link 
                v-if="link.url && index === cursos.links.length - 1" 
                :href="link.url" 
                class="px-4 py-2 bg-white rounded-md text-gray-700 hover:bg-[#bea55a] hover:text-white border border-gray-300 transition-colors"
                :class="{'opacity-50 cursor-not-allowed': !link.url}"
              >
                &raquo;
              </Link>
            </template>
          </div>
        </div>
        
        <!-- Estado vazio -->
        <div v-else-if="!hasCursos" class="bg-white p-8 rounded-lg shadow-md text-center">
          <div class="bg-gray-100 p-6 rounded-full inline-block mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-[#bea55a]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <p class="text-gray-700 text-lg font-medium">Nenhum curso disponível no momento.</p>
          <p class="text-gray-500 mt-2">Novos cursos serão adicionados em breve.</p>
        </div>
      </div>
    </main>
      
    <Footer />
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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
      meta: {}
    })
  }
});

// Verificar se há cursos disponíveis
const hasCursos = computed(() => props.cursos.data && props.cursos.data.length > 0);

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
</style>