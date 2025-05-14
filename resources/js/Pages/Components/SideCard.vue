<script setup>
import { ref } from 'vue';
import iconAgenda from '@/src/assets/icon-agenda.png'
import iconOnline from '@/src/assets/icon-online.png'
import iconAlojamento from '@/src/assets/icon-alojamento.png'
import { Link } from '@inertiajs/vue3'

</script>

<template>
  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-700 mb-4 pl-2 border-l-4 border-[#bea55a]">
      Serviços
    </h3>
    
    <!-- Card Agendamentos -->
    <div class="bg-white hover:bg-gray-50 shadow rounded-lg transition-all duration-300 
                overflow-hidden group">
      <div class="p-4 flex items-center gap-4">
        <div class="w-12 h-12 flex-shrink-0 rounded-full bg-gray-300 flex items-center justify-center">
          <img 
            :src="iconAgenda" 
            class="h-6 w-auto" 
            alt="Ícone de agenda"
          >
        </div>
        
        <div class="flex-1">
          <h4 class="text-base font-bold text-gray-800 group-hover:text-gray-900">
            AGENDAMENTOS
          </h4>
          <p class="text-sm text-gray-600 mt-1">
            Agende uma visita para o Estande de Tiro físico ou virtual.
          </p>
          <a href="#" class="text-sm font-medium text-yellow-600 hover:text-yellow-800 inline-block mt-2 group-hover:underline">
            Agendar agora →
          </a>
        </div>
      </div>
    </div>

    <!-- Card Requerimentos -->
    <div class="bg-white hover:bg-gray-50 shadow rounded-lg transition-all duration-300 
                overflow-hidden group">
      <div class="p-4 flex items-center gap-4">
        <div class="w-12 h-12 flex-shrink-0 rounded-full bg-gray-300 flex items-center justify-center">
          <img 
            :src="iconOnline" 
            class="h-6 w-auto" 
            alt="Ícone de requerimentos"
          >
        </div>
        
        <div class="flex-1">
          <h4 class="text-base font-bold text-gray-800 group-hover:text-gray-900">
            REQUERIMENTOS
          </h4>
          <p class="text-sm text-gray-600 mt-1">
            Envie documentos e faça solicitações online.
          </p>
          <a href="/requerimentos/novo" class="text-sm font-medium text-yellow-600 hover:text-yellow-800 inline-block mt-2 group-hover:underline">
            Fazer requerimento →
          </a>
        </div>
      </div>
    </div>

    <!-- Card Reserva de Alojamento -->
    <div class="bg-white hover:bg-gray-50 shadow rounded-lg transition-all duration-300 
                overflow-hidden group">
      <div class="p-4 flex items-center gap-4">
        <div class="w-12 h-12 flex-shrink-0 rounded-full bg-gray-300 flex items-center justify-center">
          <img 
            :src="iconAlojamento" 
            class="h-6 w-auto" 
            alt="Ícone de alojamento"
          >
        </div>
        
        <div class="flex-1">
          <h4 class="text-base font-bold text-gray-800 group-hover:text-gray-900">
            RESERVA DE ALOJAMENTO
          </h4>
          <p class="text-sm text-gray-600 mt-1">
            Faça a pré-reserva de Alojamento.
          </p>
          <a href="/alojamento/pre-reserva" class="text-sm font-medium text-yellow-600 hover:text-yellow-800 inline-block mt-2 group-hover:underline">
            Reservar agora →
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.group {
  transition: all 0.2s ease-in-out;
}
.group:hover {
  transform: translateY(-2px);
}
</style>