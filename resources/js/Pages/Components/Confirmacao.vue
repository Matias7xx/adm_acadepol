<script setup>
import { Link, Head } from '@inertiajs/vue3';
import Header from '@/Pages/Components/Header.vue';
import SiteNavbar from '@/Pages/Components/SiteNavbar.vue';
import Footer from '@/Pages/Components/Footer.vue';

// Props
const props = defineProps({
  user: Object,
  mensagem: String,
  detalhes: Object
});
</script>

<template>
  <Head title="Confirmação de Pré-Reserva"/>
  <div class="min-h-screen bg-gray-100">
    <Header />
    <SiteNavbar />
    
    <!-- Cabeçalho -->
    <div class="bg-gray-100 text-[#bea55a] py-4">
      <div class="container mx-auto flex justify-between items-center px-4">
        <h1 class="text-xl font-bold">Confirmação de Pré-Reserva de Alojamento</h1>
        <Link :href="route('home')" class="text[#bea55a] hover:text-amber-300 transition">
          Voltar ao Início
        </Link>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container mx-auto py-8 px-4">
      <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="text-center mb-8">
          <div class="mb-4 flex justify-center">
            <div class="rounded-full bg-green-100 p-3">
              <svg class="h-12 w-12 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>
          <h2 class="text-2xl font-bold text-gray-800 mb-2">Solicitação Enviada!</h2>
          <p class="text-gray-600 max-w-md mx-auto">{{ mensagem }}</p>
        </div>
        
        <div v-if="detalhes" class="bg-gray-50 rounded-lg p-6 mb-6 max-w-lg mx-auto">
          <h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Detalhes da Solicitação</h3>
          
          <div class="space-y-2">
            <p><span class="font-medium">Nome:</span> {{ detalhes.nome }}</p>
            <p><span class="font-medium">Período:</span> {{ detalhes.data_inicial }} a {{ detalhes.data_final }}</p>
            <p><span class="font-medium">Data da Solicitação:</span> {{ detalhes.created_at }}</p>
            <p><span class="font-medium">Situação:</span> <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium">Aguardando Análise</span></p>
          </div>
        </div>
        
        <div class="text-center mt-4 space-y-4">
          <p class="text-gray-600">Você receberá um e-mail com a confirmação ou rejeição da sua solicitação.</p>
          
          <div class="flex flex-col sm:flex-row justify-center gap-4 mt-6">
            <!-- <Link
              :href="route('alojamento.minhas-reservas')"
              class="bg-amber-500 hover:bg-amber-600 text-white py-2 px-6 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-amber-300"
            >
              Ver Minhas Reservas
            </Link> -->
            
            <Link
              :href="route('home')"
              class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-6 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300"
            >
              Voltar ao Início
            </Link>
          </div>
        </div>
      </div>
    </div>
    <Footer />
  </div>
</template>