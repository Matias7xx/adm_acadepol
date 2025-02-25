<script setup>
  import SiteNavbar from './Components/SiteNavbar.vue';
  import Footer from './Components/Footer.vue';
  import { ref } from 'vue';
  import { Head } from '@inertiajs/vue3';
  import Acadepol from '@/src/assets/PLACA-ACADEPOL.jpg'

  const historia = 
  { 
    title: "HISTÓRIA", 
    description: "A Academia de Ensino da Polícia Civil passou a ter existência legal com a Lei nº 4.216 de 17/12/1980, publicada no Diário oficial de 18/12/80, no governo de Tarcísio de Miranda Burity, e estava subordinada à Secretaria de Segurança Pública, cujo secretário era o Cel. Geraldo Amorim Navarro que muito se identificou com os ideais da Polícia Civil e abraçou com simpatia a defesa da classe. Inicialmente, a Academia funcionou em sede provisória denominada “Núcleo de Formação Policial”, localizada no bairro do Miramar.", 
    image: Acadepol 
  }
  </script>

<template>
  <Head title="História" />
  <div class="min-h-screen flex flex-col bg-gray-100">
    <SiteNavbar />

    <section class="w-full bg-gray-100 py-10">
      <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        
        <!-- Bloco 1 -->
        <div class="flex flex-col md:grid md:grid-cols-2 gap-8 md:gap-12 items-center">
          <!-- Imagem 1 -->
          <div class="w-full flex justify-center md:justify-start">
            <img 
              :src="historia.image" 
              :alt="historia.title"
              class="w-full max-w-sm md:max-w-full h-auto object-cover rounded-lg shadow-lg transform transition-all duration-300 hover:scale-105"
            />
          </div>

          <!-- Texto 1 -->
          <div class="text-left md:text-right">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#bea55a] mb-4">
              {{ historia.title }}
            </h2>
            <p class="text-gray-700 leading-relaxed">
              {{ historia.description }}
            </p>
          </div>
        </div>

        <!-- Bloco 2 (Alternado) -->
        <div class="flex flex-col md:grid md:grid-cols-2 gap-8 md:gap-12 items-center">
          <!-- Texto 2 -->
          <div class="text-left">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#bea55a] mb-4">
              {{ historia.title }}
            </h2>
            <p class="text-gray-700 leading-relaxed">
              {{ historia.description }}
            </p>
          </div>

          <!-- Imagem 2 -->
          <div class="w-full flex justify-center md:justify-end">
            <img 
              :src="historia.image" 
              :alt="historia.title"
              class="w-full max-w-sm md:max-w-full h-auto object-cover rounded-lg shadow-lg transform transition-all duration-300 hover:scale-105"
            />
          </div>
        </div>

      </div>
    </section>

    <Footer />
  </div>
</template>



  