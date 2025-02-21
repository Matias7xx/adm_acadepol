<script setup>
  import SiteNavbar from './Components/SiteNavbar.vue';
  import Header from './Components/Header.vue';
  import Footer from './Components/Footer.vue';
  import { ref } from 'vue';
  import { Head } from '@inertiajs/vue3';
  import Missao from '@/src/assets/missao.jpeg'
  import Missao2 from '@/src/assets/missao2.jpeg'

  const historia = 
  { 
    title: "MISSÃO",
    title2: "VISÃO", 
    description: "Proporcionar a formação de policiais/cidadãos conscientes de seus direitos e deveres, tornando-os aptos para atuação como agentes transformadores da realidade onde estão inseridos, produzindo e difundindo conhecimentos éticos, sociais e técnico-científicos. Fomentando a cultura de paz e a mudança de comportamento para promover melhores condições de vida à sociedade.", 
    description2: "Tornar e manter-se como Centro de Excelência no Ensino e Pesquisa em Segurança Pública.",
    image: Missao,
    image2: Missao2,
  }
  </script>

<template>
  <Head title="História" />
  <div class="min-h-screen flex flex-col bg-gray-100">
  <SiteNavbar />
  <Header />
    <section class="w-full bg-gray-100 py-10">
      <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-12 items-center">
          
          <!-- Imagem -->
          <div class="relative w-full overflow-hidden rounded-lg shadow-lg">
            <img 
              :src="historia.image" 
              :alt="title"
              class="w-full h-auto object-cover transform transition-all duration-300 hover:scale-105"
            />
          </div>
  
          <!-- Texto -->
          <div>
            <h2 class="text-3xl font-bold text-[#bea55a] mb-4">
              {{ historia.title }}
            </h2>
            <p class="text-gray-700 leading-relaxed text-justify">
              {{ historia.description }}
            </p>
          </div>

          <!-- Texto2 -->
          <div>
            <h2 class="text-3xl font-bold text-[#bea55a] mb-4">
              {{ historia.title2 }}
            </h2>
            <p class="text-gray-700 leading-relaxed text-justify">
              {{ historia.description2 }}
            </p>
          </div>

          <!-- Imagem 2 -->
          <div class="relative w-full overflow-hidden rounded-lg shadow-lg">
            <img 
              :src="historia.image2" 
              :alt="title"
              class="w-full h-auto object-cover transform transition-all duration-300 hover:scale-105"
            />
          </div>

        </div>
      </div>
    </section>
    <Footer />
  </div>
  </template>