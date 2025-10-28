<script setup>
import SiteNavbar from './Components/SiteNavbar.vue';
import HomeCarousel from './Components/HomeCarousel.vue';
import { Head } from '@inertiajs/vue3';
import Cards from './Components/Cards.vue';
import Footer from './Components/Footer.vue';
import Noticias from './Components/Noticias.vue';
import Header from './Components/Header.vue';
import PowerBIComponent from './Components/PowerBIComponent.vue';

// URL do relatório Power BI
const powerBIUrl =
  'https://app.powerbi.com/view?r=eyJrIjoiODA4MDIwNDEtY2ZhNi00NDhkLTllNDktZWQ1ZjMwN2ZiMjM5IiwidCI6IjgxYzUyNzVkLTIxMjMtNDU0Yi1iMjEwLTI4MWI0NjVmZGI4YSJ9';
</script>

<template>
  <Head title="Home" />
  <div class="min-h-screen flex flex-col bg-gray-100">
    <!-- Header Section -->
    <header class="w-full">
      <!--  <Header class="shadow-xl" /> -->
      <Header />
      <!-- Barra de Pesquisa -->
      <!-- <div class="search-bar bg-white shadow-md px-4 py-2">
        <Search />
      </div> -->
      <SiteNavbar />
      <!-- <div class="h-1 bg-gradient-to-r from-transparent via-[black] to-transparent"></div> -->
    </header>

    <!-- Hero Section -->
    <section class="w-full mt-2">
      <HomeCarousel />
    </section>

    <!-- Main Content -->
    <main class="flex-grow">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Cards Grid -->
        <section class="mb-12">
          <Cards />
        </section>

        <!-- Seção do Power BI  -->
        <section class="mb-19">
          <PowerBIComponent
            :src="powerBIUrl"
            title="Dados e Estatísticas"
            height="650px"
          />
        </section>

        <!-- Eventos Grid -->
        <section class="mb-10">
          <Noticias />
        </section>
      </div>
    </main>

    <!-- Footer -->
    <div
      class="h-1 bg-gradient-to-r from-transparent via-[#bea55a] to-transparent"
    ></div>
    <Footer />
  </div>
</template>
