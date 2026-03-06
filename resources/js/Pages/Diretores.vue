<script setup>
import SiteNavbar from './Components/SiteNavbar.vue';
import Footer from './Components/Footer.vue';
import { Head } from '@inertiajs/vue3';
import CardDiretores from './Components/CardDiretores.vue';
import Header from './Components/Header.vue';
</script>

<template>
  <Head title="Diretores" />
  <div class="min-h-screen flex flex-col bg-gray-50">
    <Header />
    <SiteNavbar />
    <CardDiretores />
  </div>
  <Footer />
</template>
