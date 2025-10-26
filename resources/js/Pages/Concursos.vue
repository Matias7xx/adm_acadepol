<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Header from './Components/Header.vue';
import SiteNavbar from './Components/SiteNavbar.vue';
import Footer from './Components/Footer.vue';
import { ref } from 'vue';

const linksConcursos = ref([
  {
    id: 1,
    titulo: 'Concurso PC/PB - 2021',
    descricao: 'Informações completas sobre o edital e processo seletivo',
    url: 'https://www.cebraspe.org.br/concursos/pc_pb_21',
    cor: 'bg-gray-500 hover:bg-[#bea55a]',
  },
  {
    id: 2,
    titulo: 'Concurso PC/PB - 2008',
    descricao: 'Informações completas sobre o edital e processo seletivo',
    url: 'http://www.cespe.unb.br/concursos/pcpb2008/',
    cor: 'bg-gray-500 hover:bg-[#bea55a]',
  },
]);

const abrirLink = url => {
  window.open(url, '_blank', 'noopener,noreferrer');
};
</script>

<template>
  <Head title="Concursos" />

  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
    <Header />
    <SiteNavbar />

    <!-- Cabeçalho -->
    <div
      class="bg-gradient-to-r from-black to-gray-900 text-white py-5 shadow-md"
    >
      <div class="container mx-auto flex justify-between items-center px-4">
        <div class="flex items-center">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 text-[#bea55a] mr-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
            />
          </svg>
          <h1 class="text-2xl font-bold">Concursos Anteriores</h1>
        </div>
        <Link
          :href="route('home')"
          class="flex items-center text-[#bea55a] hover:text-gray-300 transition"
        >
          <span>Voltar</span>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 ml-1"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
        </Link>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container mx-auto py-8 px-4">
      <!-- Grid de Links -->
      <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
        <div
          v-for="link in linksConcursos"
          :key="link.id"
          @click="abrirLink(link.url)"
          class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 cursor-pointer overflow-hidden border border-gray-100"
        >
          <!-- Header do Card -->
          <div
            :class="`${link.cor} p-6 text-white transition-colors duration-300`"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-6 w-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                  />
                </svg>
                <h3 class="text-xl font-semibold">{{ link.titulo }}</h3>
              </div>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 opacity-75"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                />
              </svg>
            </div>
          </div>

          <!-- Conteúdo do Card -->
          <div class="p-6">
            <p class="text-gray-600 leading-relaxed mb-4">
              {{ link.descricao }}
            </p>

            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500 font-medium">
                Clique para acessar
              </span>
              <div
                class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4 text-gray-600"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                  />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <Footer />
</template>
