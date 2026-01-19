<script setup>
import SiteNavbar from './Components/SiteNavbar.vue';
import Footer from './Components/Footer.vue';
import Header from './Components/Header.vue';
import PowerBIComponent from './Components/PowerBIComponent.vue';
import { Head } from '@inertiajs/vue3';

// URL do relatório Power BI
const powerBIUrl =
  'https://app.fabric.microsoft.com/view?r=eyJrIjoiOWQwNjM2MTUtNGVhNS00ZTY4LWE5NDctMjVlYjg0MjEzN2QzIiwidCI6IjgxYzUyNzVkLTIxMjMtNDU0Yi1iMjEwLTI4MWI0NjVmZGI4YSJ9';
/* https://app.powerbi.com/view?r=eyJrIjoiODA4MDIwNDEtY2ZhNi00NDhkLTllNDktZWQ1ZjMwN2ZiMjM5IiwidCI6IjgxYzUyNzVkLTIxMjMtNDU0Yi1iMjEwLTI4MWI0NjVmZGI4YSJ9 */ /* URL da gestão */
</script>

<template>
  <Head title="Dados Estatísticos e Metas" />

  <div class="min-h-screen flex flex-col bg-gray-100">
    <Header />
    <SiteNavbar />

    <!-- Conteúdo Principal -->
    <section class="flex-grow py-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Componente Power BI -->
        <PowerBIComponent
          title="Painel de Indicadores - ACADEPOL"
          :src="powerBIUrl"
          height="700px"
        />
      </div>
    </section>

    <Footer />
  </div>
</template>
