<script setup>
  import SiteNavbar from './Components/SiteNavbar.vue';
  import Footer from './Components/Footer.vue';
  import { Head } from '@inertiajs/vue3';
  import GaleriaEstrutura from './Components/GaleriaEstrutura.vue';
  import Header from './Components/Header.vue';

  </script>

  <template>
    <Head title="Estrutura" />
  <div class="min-h-screen flex flex-col bg-gray-100">
    <Header />
    <SiteNavbar />
    <GaleriaEstrutura />
   <Footer />
  </div>
  </template>