<script setup>
import SiteNavbar from './Components/SiteNavbar.vue';
import HomeCarousel from './Components/HomeCarousel.vue';
import { Head, Link } from '@inertiajs/vue3';
import Cards from './Components/Cards.vue';
import Footer from './Components/Footer.vue';
import Eventos from './Components/Eventos.vue';
import Header from './Components/Header.vue';
</script>

<template>
  <Head title="Home" />
  <div class="min-h-screen flex flex-col bg-gray-100">
    <!-- Header Section -->
    <header class="w-full">
      <SiteNavbar />
      <Header class="shadow-xl" />
      <div class="h-1 bg-gradient-to-r from-transparent via-[#bea55a] to-transparent"></div>   
      </header>

    <!-- Hero Section -->
    <section class="w-full">
      <HomeCarousel />
    </section>

    <!-- Main Content -->
    <main class="flex-grow">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Cards Grid -->
        <section class="mb-12">
          <Cards />
        </section>

        <!-- Eventos Grid -->
        <section class="mb-12">
          <Eventos />
        </section>
      </div>
    </main>

    <!-- Footer -->
    <div class="h-1 bg-gradient-to-r from-transparent via-[#bea55a] to-transparent"></div>   
    <Footer />
  </div>
</template>