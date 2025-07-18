<template>
  <div>
    <!-- Grid de Dormitórios -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
      <div 
        v-for="dormitorio in dormitorios" 
        :key="dormitorio.id"
        @click="selecionarDormitorio(dormitorio)"
        :class="[
          'p-3 border-2 rounded-lg transition-all duration-200 cursor-pointer',
          dormitorio.reservado_plantao 
            ? 'border-purple-300 bg-purple-50 opacity-75 cursor-not-allowed'
            : dormitorioSelecionado?.id === dormitorio.id 
              ? 'border-blue-500 bg-blue-50' 
              : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
        ]"
      >
        <div class="flex justify-between items-start mb-2">
          <div>
            <h5 class="font-semibold text-gray-900 text-sm">{{ dormitorio.numero }}</h5>
            <p class="text-xs text-gray-600">{{ dormitorio.nome || 'Sem nome' }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Capacidade: {{ dormitorio.capacidade_maxima }} vagas</p>
            <p v-if="dormitorio.reservado_plantao" class="text-xs text-purple-600 font-medium mt-1">
              🔒 Reservado para Plantão
            </p>
          </div>
          <span v-if="!dormitorio.reservado_plantao" 
                :class="[
                  'px-2 py-0.5 rounded-full text-xs font-medium',
                  dormitorio.vagas_disponiveis > 0 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800'
                ]">
            {{ dormitorio.vagas_disponiveis > 0 
              ? `${dormitorio.vagas_disponiveis} livre${dormitorio.vagas_disponiveis !== 1 ? 's' : ''}` 
              : 'Lotado' 
            }}
          </span>
          <span v-else class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full text-xs font-medium">
            Reservado
          </span>
        </div>

        <!-- Visualização das Vagas -->
        <div :class="getGridClass(dormitorio.capacidade_maxima)">
          <div 
            v-for="numeroVaga in (dormitorio.capacidade_maxima || 4)" 
            :key="numeroVaga"
            :class="[
              'p-1 rounded text-center text-xs border',
              dormitorio.reservado_plantao
                ? 'bg-purple-50 text-purple-700 border-purple-200'
                : isVagaLivre(dormitorio, numeroVaga)
                  ? 'bg-green-50 text-green-700 border-green-200'
                  : 'bg-red-50 text-red-700 border-red-200'
            ]"
          >
            <div class="text-xs font-medium">{{ numeroVaga }}</div>
            <div class="text-xs">
              {{ dormitorio.reservado_plantao 
                ? 'Res' 
                : isVagaLivre(dormitorio, numeroVaga) ? 'Livre' : 'Ocup' 
              }}
            </div>
          </div>
        </div>

        <!-- Indicador de Seleção -->
        <div v-if="dormitorioSelecionado?.id === dormitorio.id && !dormitorio.reservado_plantao" class="mt-2 text-center">
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Selecionado
          </span>
        </div>
        
        <!-- Indicador de Bloqueio -->
        <div v-else-if="dormitorio.reservado_plantao" class="mt-2 text-center">
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            Bloqueado
          </span>
        </div>
      </div>
    </div>

    <!-- Seleção de Vaga -->
    <div v-if="dormitorioSelecionado && !dormitorioSelecionado.reservado_plantao" class="bg-gray-50 rounded-lg p-4 border border-gray-200 mb-4">
      <h5 class="font-medium text-gray-900 mb-3 text-sm flex items-center">
        <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Selecionar Vaga - {{ dormitorioSelecionado.numero }}
      </h5>
      
      <div class="grid grid-cols-4 gap-2 mb-3">
        <button
          v-for="numeroVaga in (dormitorioSelecionado.vagas_livres || [])"
          :key="numeroVaga"
          @click="vagaSelecionada = numeroVaga"
          :class="[
            'p-2 rounded-lg border-2 transition-all duration-200 text-xs font-medium',
            vagaSelecionada === numeroVaga
              ? 'border-blue-500 bg-blue-50 text-blue-700'
              : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'
          ]"
        >
          <div class="text-center">
            <div class="font-medium">{{ numeroVaga }}</div>
            <div class="text-xs text-green-600">Livre</div>
          </div>
        </button>
      </div>
      
      <!-- Info -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 mb-3">
        <div class="flex items-center gap-2 text-xs text-blue-700">
          <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>
            Capacidade: {{ dormitorioSelecionado.capacidade_maxima }} vagas | 
            Disponíveis: {{ dormitorioSelecionado.vagas_disponiveis }}
          </span>
        </div>
      </div>

      <!-- Campo de Observações -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Observações (opcional):
        </label>
        <textarea 
          v-model="observacoes"
          class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
          rows="2"
          placeholder="Observações sobre o check-in..."
        ></textarea>
      </div>
    </div>
    
    <!-- Aviso para dormitório reservado -->
    <div v-else-if="dormitorioSelecionado && dormitorioSelecionado.reservado_plantao" class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <div>
          <p class="font-medium text-purple-900 text-sm">Dormitório Reservado</p>
          <p class="text-xs text-purple-700">Este dormitório está reservado para o plantão da ACADEPOL.</p>
        </div>
      </div>
    </div>

    <!-- Botões de Ação -->
    <div class="flex justify-end space-x-2 pt-4 border-t border-gray-200">
      <BaseButton 
        v-if="isApproving"
        @click="aprovarSemCheckin"
        label="Aprovar sem Check-in"
        color="success"
        :loading="loading"
        small
      />
      
      <BaseButton 
        @click="executarCheckin"
        :disabled="!podeConfirmar || loading"
        :label="loading ? 'Processando...' : (isApproving ? 'Aprovar e Check-in' : 'Fazer Check-in')"
        :color="podeConfirmar && !loading ? 'info' : 'white'"
        :icon="podeConfirmar && !loading ? mdiCheckCircle : null"
        small
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import BaseButton from "@/Components/BaseButton.vue"
import { mdiCheckCircle } from "@mdi/js"

// Props
const props = defineProps({
  dormitorios: Array,
  loading: Boolean,
  isApproving: {
    type: Boolean,
    default: true
  }
})

// Emits
const emit = defineEmits(['approve-with-checkin', 'approve-only'])

// Estados reativos
const dormitorioSelecionado = ref(null)
const vagaSelecionada = ref(null)
const observacoes = ref('')

// Computed
const podeConfirmar = computed(() => {
  return dormitorioSelecionado.value && vagaSelecionada.value && !dormitorioSelecionado.value.reservado_plantao
})

// Métodos
const selecionarDormitorio = (dormitorio) => {
  // Validação para dormitórios reservados
  if (dormitorio.reservado_plantao) {
    if (window.NotificationSystem) {
      window.NotificationSystem.error('Este dormitório está reservado para o plantão da ACADEPOL.')
    }
    return
  }
  
  dormitorioSelecionado.value = dormitorio
  vagaSelecionada.value = null
  
  // Auto-selecionar a primeira vaga disponível
  const vagasLivres = getVagasLivres(dormitorio)
  if (vagasLivres.length > 0) {
    vagaSelecionada.value = vagasLivres[0]
  }
}

// Função para obter vagas livres de forma segura
const getVagasLivres = (dormitorio) => {
  // Se já tem vagas_livres como array, usar
  if (Array.isArray(dormitorio.vagas_livres)) {
    return dormitorio.vagas_livres
  }
  
  // Caso contrário, calcular baseado na capacidade e vagas ocupadas
  const capacidade = dormitorio.capacidade_maxima || 4
  const vagasOcupadas = dormitorio.vagas_ocupadas || 0
  const vagasDisponiveis = dormitorio.vagas_disponiveis || (capacidade - vagasOcupadas)
  
  // Gerar array de vagas livres baseado na capacidade real
  const todasVagas = Array.from({ length: capacidade }, (_, i) => i + 1)
  
  // Se temos informação específica de quais vagas estão livres, usar isso
  // Caso contrário, assumir que as primeiras vagas disponíveis estão livres
  return todasVagas.slice(0, vagasDisponiveis)
}

// Verificar se vaga está livre
const isVagaLivre = (dormitorio, numeroVaga) => {
  const vagasLivres = getVagasLivres(dormitorio)
  return vagasLivres.includes(numeroVaga)
}

// Função para obter classe do grid baseada na capacidade
const getGridClass = (capacidade) => {
  if (capacidade === 8) {
    return 'grid grid-cols-4 gap-1'
  }
  return 'grid grid-cols-2 gap-1'
}

const executarCheckin = () => {
  if (!podeConfirmar.value) {
    console.warn('Tentativa de check-in sem seleção completa:', {
      dormitorio: dormitorioSelecionado.value,
      vaga: vagaSelecionada.value
    })
    return
  }
  
  // Validação final para dormitório reservado
  if (dormitorioSelecionado.value.reservado_plantao) {
    if (window.NotificationSystem) {
      window.NotificationSystem.error('Este dormitório está reservado para o plantão.')
    }
    return
  }
  
  console.log('Executando check-in:', {
    dormitorio_id: dormitorioSelecionado.value.id,
    numero_vaga: vagaSelecionada.value,
    observacoes: observacoes.value,
    isApproving: props.isApproving
  })
  
  emit('approve-with-checkin', {
    dormitorio_id: dormitorioSelecionado.value.id,
    numero_vaga: vagaSelecionada.value,
    observacoes: observacoes.value
  })
}

const aprovarSemCheckin = () => {
  emit('approve-only')
}
</script>