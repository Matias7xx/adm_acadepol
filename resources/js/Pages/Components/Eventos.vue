<template>
  <section class="w-full bg-gray-100 py-6 sm:py-10">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6">
      <!-- Título responsivo e centralizado -->
      

      <!-- Grid principal -->
      <div class="grid grid-cols- lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Coluna de eventos -->
        <div class="lg:col-span-2 space-y-4">

          <h2 class="text-2xl sm:text-3xl font-sans mb-6 sm:mb-8 text-center sm:text-center">
        Eventos
          </h2>

          <div 
            v-for="(event, index) in events" 
            :key="index" 
            class="bg-white shadow-md hover:shadow-lg transition-shadow duration-300 
                   border-l-4 border-gray-600 rounded-lg p-4 sm:p-6"
          >
            <h3 class="text-lg sm:text-xl font-bold text-black-700">
              {{ event.title }}
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mt-2">
              {{ event.description }}
            </p>
            <a 
              :href="event.link" 
              class="text-yellow-600 font-semibold hover:text-yellow-700 
                     hover:underline mt-3 sm:mt-4 inline-block text-sm sm:text-base"
            >
              Saiba mais »
            </a>
          </div>
        </div>

        <!-- SideCard -->
        <div class="mt-6 lg:mt-0">
          <SideCard />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import SideCard from './SideCard.vue';

const events = [
  { 
    title: "CANDIDATOS NOMEADOS: INFORMAÇÕES SOBRE POSSE E MATRÍCULA", 
    description: "Prezado(a) futuro(a) policial civil, Parabéns pela conquista! Nesta seção você encontrará todas as informações necessárias sobre posse e matrícula.", 
    link: "#" 
  },
  { 
    title: "Curso de Formação para Policiais", 
    description: "O curso de formação capacita novos agentes com técnicas modernas de segurança e investigação.", 
    link: "#" 
  }
];
</script>