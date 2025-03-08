<script setup>
import iconAgenda from '@/src/assets/icon-agenda.png'
import iconOnline from '@/src/assets/icon-online.png'
import iconAlojamento from '@/src/assets/icon-alojamento.png'
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Card Agendamentos -->
    <div class="bg-gray-300 shadow-md hover:shadow-lg transition-shadow duration-300 
                rounded-lg p-4 sm:p-6 flex flex-col sm:flex-row items-center sm:items-start gap-4">
      <div class="flex-shrink-0">
        <img 
          :src="iconAgenda" 
          class="h-10 sm:h-12 w-auto" 
          alt="Ícone de agenda"
        >
      </div>
      <div class="flex flex-col text-center sm:text-left">
        <a 
          href="#" 
          class="text-lg sm:text-xl font-sans text-black-700 hover:text-gray-900 transition-colors"
        >
          AGENDAMENTOS
        </a>
        <p class="text-sm sm:text-base text-gray-700 mt-2">
          Agende uma visita para o Estande de Tiro físico ou virtual.
        </p>
      </div>
    </div>

    <!-- Card Acadepol Online -->
    <div class="bg-gray-300 shadow-md hover:shadow-lg transition-shadow duration-300 
                rounded-lg p-4 sm:p-6 flex flex-col sm:flex-row items-center sm:items-start gap-4">
      <div class="flex-shrink-0">
        <img 
          :src="iconOnline" 
          class="h-10 sm:h-12 w-auto" 
          alt="Ícone de ensino online"
        >
      </div>
      <div class="flex flex-col text-center sm:text-left">
        <a 
          href="#" 
          class="text-lg sm:text-xl font-sans text-black-700 hover:text-gray-900 transition-colors"
        >
          REQUERIMENTOS
        </a>
        <p class="text-sm sm:text-base text-gray-700 mt-2">
          Faça um Requerimento.
        </p>
      </div>
    </div>

    <!-- Card Reserva de Alojamento -->
    <div class="bg-gray-300 shadow-md hover:shadow-lg transition-shadow duration-300 
                rounded-lg p-4 sm:p-6 flex flex-col sm:flex-row items-center sm:items-start gap-4">
      <div class="flex-shrink-0">
        <img 
          :src="iconAlojamento" 
          class="h-10 sm:h-12 w-auto" 
          alt="Ícone de alojamento"
        >
      </div>
      <div class="flex flex-col text-center sm:text-left">
        <a 
          href="/alojamento/pre-reserva" 
          class="text-lg sm:text-xl font-sans text-black-700 hover:text-gray-900 transition-colors"
        >
          RESERVA DE ALOJAMENTO
        </a>
        <p class="text-sm sm:text-base text-gray-700 mt-2">
          Faça a pré-reserva de Alojamento.
        </p>
      </div>
    </div>
  </div>
</template>