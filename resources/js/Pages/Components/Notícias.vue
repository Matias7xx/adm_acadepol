<template>
  <section class="w-full bg-gray-100 py-8 sm:py-12">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
      <!-- Título com destaque visual -->
      <div class="mb-8 text-center">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 relative inline-block">
          Notícias
          <span class="absolute bottom-0 left-0 w-full h-1 bg-[#bea55a]"></span>
        </h2>
      </div>

      <!-- Layout flexível que se adapta melhor em diferentes tamanhos de tela -->
      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Coluna principal de notícias - agora com 2/3 em desktop -->
        <div class="w-full lg:w-2/3 space-y-6">
          <div 
            v-for="(noticia, index) in noticias" 
            :key="index" 
            class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1"
            :class="noticia.highlight ? 'border-l-4 border-[#bea55a]' : ''"
          >
            <div class="p-5">
              <!-- Data com ícone -->
              <div v-if="noticia.date" class="text-sm text-gray-500 mb-2 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ formatDate(noticia.date) }}
              </div>
              
              <!-- Título com badge de destaque -->
              <div class="flex items-start justify-between">
                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-3">
                  {{ noticia.title }}
                </h3>
                <span 
                  v-if="noticia.highlight" 
                  class="ml-2 flex-shrink-0 bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"
                >
                  Destaque
                </span>
              </div>
              
              <!-- Descrição mais legível -->
              <p class="text-sm sm:text-base text-gray-600 mb-4 leading-relaxed">
                {{ noticia.description }}
              </p>
              
              <!-- Botão de "Saiba mais" mais atraente -->
              <div class="mt-2">
                <a 
                  :href="noticia.link" 
                  class="inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-800 transition-colors"
                >
                  Saiba mais
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                  </svg>
                </a>
              </div>
            </div>
          </div>
          
          <!-- Botão "Ver todas as notícias" melhorado -->
          <div class="text-center pt-4" v-if="noticias.length > 2">
            <a href="#" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-700 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
              Ver todas as notícias
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </a>
          </div>
        </div>

        <!-- Sidebar com cards de serviços - agora com 1/3 em desktop -->
        <div class="w-full lg:w-1/3">
          <SideCard />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import SideCard from './SideCard.vue';
import { ref } from 'vue';

// Nome do componente mais descritivo 
const noticias = ref([
  { 
    title: "CANDIDATOS NOMEADOS: INFORMAÇÕES SOBRE POSSE E MATRÍCULA", 
    description: "Prezado(a) futuro(a) policial civil, Parabéns pela conquista! Nesta seção você encontrará todas as informações necessárias sobre posse e matrícula.", 
    link: "#",
    date: "2025-03-20",
    highlight: true
  },
  { 
    title: "Curso de Formação para Policiais", 
    description: "O curso de formação capacita novos agentes com técnicas modernas de segurança e investigação.",
    link: "#",
    date: "2025-04-05"
  },
  { 
    title: "Seminário sobre Investigação Criminal", 
    description: "Participação especial de especialistas nacionais e internacionais discutindo as mais modernas técnicas investigativas.",
    link: "#",
    date: "2025-04-12"
  }
]);

// Função para formatar datas
const formatDate = (dateString) => {
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  } catch (e) {
    return dateString;
  }
};
</script>

<style scoped>
/* Transições suaves */
.transform {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
</style>