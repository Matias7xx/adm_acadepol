<script setup>
import { Link, Head } from '@inertiajs/vue3'
import { mdiAccountTieHat, mdiAccount, mdiShieldCheck, mdiFileDocument } from '@mdi/js'
import LayoutGuest from '@/Layouts/Admin/LayoutGuest.vue'
import SectionFullScreen from '@/Components/SectionFullScreen.vue'
import CardBox from '@/Components/CardBox.vue'
import BaseButton from '@/Components/BaseButton.vue'
import BaseLevel from '@/Components/BaseLevel.vue'
import BaseDivider from '@/Components/BaseDivider.vue'
import imgUrl from '@/src/assets/logo-acadepol.png'
</script>

<template>
  <LayoutGuest>
    <Head title="Reserva de Alojamento - Tipo de Solicitante" />
    
    <SectionFullScreen
      v-slot="{ cardClass }"
      bg="white"
    >
      <CardBox :class="cardClass">
        <!-- Logo -->
        <div class="flex items-center mt-2 justify-center">
          <img :src="imgUrl" class="" alt="ACADEPOL" />
        </div>
        
        <!-- Título e Descrição -->
        <div class="w-full mt-6 px-1 py-1 overflow-hidden text-center flex flex-col items-center">
          <h1 class="text-2xl font-bold text-gray-900 mb-2">Reserva de Alojamento</h1>
          <p class="text-gray-600 mb-6">Escolha o tipo de solicitante para continuar</p>
        </div>

        <!-- Opções de Solicitante -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Servidor da PCPB -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 hover:shadow-md transition-all duration-300 text-center">
            <!-- Ícone -->
            <div class="flex items-center justify-center w-16 h-16 bg-gray-100 rounded-lg mx-auto mb-4">
              <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              Servidor da PCPB
            </h3>
            
            <p class="text-sm text-gray-600 mb-4">
              Você é servidor ativo da Polícia Civil da Paraíba e precisa fazer uma reserva no alojamento.
            </p>
            
            <!-- Botão -->
            <Link 
              :href="route('alojamento.reserva.form')"
              class="w-full inline-flex justify-center items-center px-4 py-2 bg-[#bea54a] border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-[#a38e5d] focus:bg-[#a38e5d] active:bg-[#8b7a51] focus:outline-none focus:ring-2 focus:ring-[#bea54a] focus:ring-offset-2 transition ease-in-out duration-150"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
              </svg>
              Solicitar Alojamento
            </Link>
          </div>

          <!-- Visitante -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 hover:shadow-md transition-all duration-300 text-center">
            <!-- Ícone -->
            <div class="flex items-center justify-center w-16 h-16 bg-gray-100 rounded-lg mx-auto mb-4">
              <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              Visitante
            </h3>
            
            <p class="text-sm text-gray-600 mb-4">
              Você é servidor de outro órgão ou visitante e precisa de hospedagem durante sua estadia.
            </p>
            
            <!-- Botão -->
            <Link 
              :href="route('visitante.formulario')"
              class="w-full inline-flex justify-center items-center px-4 py-2 bg-[#bea54a] border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-[#a38e5d] focus:bg-[#a38e5d] active:bg-[#8b7a51] focus:outline-none focus:ring-2 focus:ring-[#bea54a] focus:ring-offset-2 transition ease-in-out duration-150"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              Solicitar Alojamento
            </Link>
          </div>
        </div>

        <BaseDivider />

        <!-- Botão Voltar -->
        <BaseLevel>
          <div></div>
          <Link
            :href="route('home')"
            class="hover:text-[#a38e5d] text-sm"
          >
            ← Voltar à página inicial
          </Link>
        </BaseLevel>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>