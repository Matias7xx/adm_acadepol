<script setup>
  import SiteNavbar from './Components/SiteNavbar.vue';
  import Footer from './Components/Footer.vue';
  import { ref } from 'vue';
  import { Head } from '@inertiajs/vue3';
  import Acadepol from '@/src/assets/PLACA-ACADEPOL.jpg'
  import CardDiretores from './Components/CardDiretores.vue';
  import Header from './Components/Header.vue';

  const diretores = 
  { 
    name: "", 
    description: "", 
    image: '' 
  }
  </script>

<template>
  <Head title="Diretores" />
  <div class="min-h-screen flex flex-col bg-gray-100">
    <Header />
    <SiteNavbar />
      <CardDiretores />
    <Footer />
  </div>
</template>