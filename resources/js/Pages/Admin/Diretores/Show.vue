<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiInformationOutline,
  mdiArrowLeftBoldOutline,
} from "@mdi/js"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import BaseButton from "@/Components/BaseButton.vue"
import BaseButtons from "@/Components/BaseButtons.vue"
import { ref, computed } from 'vue'

const props = defineProps({
  diretor: {
    type: Object,
    default: () => ({}),
  },
  can: {
    type: Object,
    default: () => ({}),
  }
})

// Processar realizações
const realizacoes = computed(() => {
  if (!props.diretor.realizacoes) return []
  
  return typeof props.diretor.realizacoes === 'string' 
    ? JSON.parse(props.diretor.realizacoes) 
    : props.diretor.realizacoes
})

// Computed para formatar período
const periodoFormatado = computed(() => {
  const inicio = props.diretor.data_inicio ? new Date(props.diretor.data_inicio).toLocaleDateString('pt-BR') : ''
  if (props.diretor.atual) {
    return `${inicio} - Atualmente`
  }
  const fim = props.diretor.data_fim ? new Date(props.diretor.data_fim).toLocaleDateString('pt-BR') : ''
  return fim ? `${inicio} - ${fim}` : inicio
})
</script>

<template>
  <LayoutAuthenticated>
    <Head :title="`${diretor.nome} - Diretor`" />
    
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiInformationOutline"
        :title="diretor.nome"
        main
      >
        <div class="flex items-center gap-2">
          <BaseButton
            :route-name="route('admin.directors.index')"
            :icon="mdiArrowLeftBoldOutline"
            label="Voltar"
            color="light"
            small
          />
        </div>
      </SectionTitleLineWithButton>
      
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Coluna da Imagem e Ações -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Card da Imagem -->
          <CardBox>
            <div class="p-6">
              <div class="text-center">
                <div class="relative inline-block">
                  <img 
                    v-if="diretor.imagem" 
                    :src="diretor.imagem" 
                    :alt="diretor.nome" 
                    class="w-48 h-48 object-cover rounded-lg shadow-lg mx-auto"
                  />
                  <div 
                    v-else 
                    class="w-48 h-48 bg-gradient-to-br from-gray-200 to-gray-300 rounded-lg shadow-lg mx-auto flex items-center justify-center"
                  >
                    <svg class="w-20 h-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  
                  <!-- Badge de diretor atual -->
                  <div v-if="diretor.atual" class="absolute -top-2 -right-2">
                    <div class="bg-green-500 text-white p-2 rounded-full shadow-lg">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                </div>
                
                <div class="mt-4">
                  <h1 class="text-xl font-bold text-gray-900">{{ diretor.nome }}</h1>
                  <p class="text-gray-600 mt-1">{{ periodoFormatado }}</p>
                  
                  <div v-if="diretor.atual" class="mt-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                      Diretor Atual
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </CardBox>
        </div>
        
        <!-- Coluna Principal - Conteúdo -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Card do Histórico -->
          <CardBox>
            <div class="p-6">
              <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <h2 class="text-xl font-semibold text-gray-900">Histórico</h2>
              </div>
              
              <div v-if="diretor.historico" class="prose prose-gray max-w-none">
                <p class="text-gray-700 leading-relaxed">{{ diretor.historico }}</p>
              </div>
              <div v-else class="text-center py-8">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-gray-500">Nenhum histórico registrado</p>
              </div>
            </div>
          </CardBox>

          <!-- Card das Realizações -->
          <CardBox>
            <div class="p-6">
              <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
                <h2 class="text-xl font-semibold text-gray-900">Principais Realizações</h2>
              </div>
              
              <div v-if="realizacoes.length > 0" class="space-y-3">
                <div 
                  v-for="(realizacao, index) in realizacoes" 
                  :key="index"
                  class="flex items-start gap-3 p-3 bg-green-50 rounded-lg"
                >
                  <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                  <p class="text-gray-700 text-sm leading-relaxed">{{ realizacao }}</p>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
                <p class="text-gray-500">Nenhuma realização registrada</p>
              </div>
            </div>
          </CardBox>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<style scoped>
/* Animações e transições */
.transition-colors {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

/* Estilo para prosa */
.prose {
  color: #374151;
  max-width: 65ch;
}

.prose p {
  margin-top: 1.25em;
  margin-bottom: 1.25em;
}

/* Modal transitions */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>