<template>
  <Head title="Cursos" />
  <div class="min-h-screen flex flex-col">
    <Header />
    <SiteNavbar />
    
    <main class="flex-grow bg-gray-100">
      <div class="container mx-auto px-4 py-8">
        <div class="flex items-center mb-8 border-l-4 border-[#bea55a] pl-4">
          <h1 class="text-4xl font-sans text-[#bea55a] uppercase tracking-wider">CURSOS</h1>
        </div>
        
        <div v-if="hasCursos" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div 
            v-for="curso in cursos.data" 
            :key="curso.id"
            class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow duration-300 flex flex-col relative"
            :class="{'ring-2 ring-[#bea55a] ring-offset-2': curso.inscricoesAbertas}"
          >
            <!-- Faixa "Inscrições Abertas" -->
            <div 
              v-if="curso.status == 'aberto'" 
              class="absolute top-5 -right-10 bg-[#bea55a] text-white py-1 px-10 transform rotate-45 z-10 shadow-md"
            >
              <span class="text-xs font-bold uppercase tracking-wider">Inscrições Abertas</span>
            </div>
            
            <div class="relative">
              <img 
                :src="curso.imagem || '/images/default-curso.jpg'" 
                :alt="`Curso de ${curso.nome}`" 
                class="w-full h-56 object-cover" 
                @error="handleImageError"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent opacity-60"></div>
              <div class="absolute bottom-0 left-0 right-0 p-4">
                <span class="bg-[#bea55a] text-white text-xs px-2 py-1 rounded uppercase font-bold">Acadepol</span>
              </div>
            </div>
            
            <div class="p-5 flex flex-col flex-grow">
              <h3 class="text-xl font-bold text-gray-800 mb-3">{{ curso.nome }}</h3>
              <p class="text-gray-600 mb-4 line-clamp-3 flex-grow">{{ curso.descricao }}</p>
              
              <div class="flex flex-col space-y-3 mt-auto">              
                <div class="flex justify-between items-center">
                  <div class="flex items-center text-sm text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Carga Horária: {{ curso.carga_horaria || 'Consultar' }}H
                  </div>
                  <a 
                    :href="`/cursos/${curso.id}`" 
                    class="bg-[#bea55a] text-white px-5 py-2 rounded font-medium hover:bg-[#a38e4d] transition-colors flex items-center"
                  >
                    Ver Detalhes
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="bg-white p-8 rounded-lg shadow-md text-center">
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
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

// Componentes
import Header from './Components/Header.vue';
import SiteNavbar from './Components/SiteNavbar.vue';
import Footer from './Components/Footer.vue';

const props = defineProps({
  cursos: {
    type: Object,
    required: true,
    default: () => ({ data: [] })
  }
});

const hasCursos = computed(() => props.cursos.data && props.cursos.data.length > 0);

// Calcula a porcentagem de vagas disponíveis
const getVagasPercentage = (curso) => {
  if (!curso.capacidade_maxima || curso.capacidade_maxima === 0) return 0;
  return Math.round((curso.vagasDisponiveis / curso.capacidade_maxima) * 100);
};

// Tratamento de erro de imagem
const handleImageError = (event) => {
  event.target.src = '/images/default-curso.jpg';
};
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>