<template>
  <div v-if="show" class="fixed inset-0 bg-gray-600/50 flex items-center justify-center z-[60]">
    <div class="bg-white rounded-lg p-4 w-full max-w-md mx-4">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Confirmar Check-out</h3>
        <button 
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 transition-colors"
          :disabled="loading"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div v-if="ocupacao" class="space-y-4">
        <!-- Resumo da Estadia -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <h4 class="font-medium text-gray-900 mb-3 text-sm flex items-center">
            <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Resumo da Estadia
          </h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Hóspede:</span>
              <span class="text-gray-900 font-medium">{{ ocupacao.hospede_nome }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Tipo:</span>
              <span class="text-gray-900">{{ ocupacao.hospede_tipo }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Check-in:</span>
              <span class="text-gray-900">{{ ocupacao.checkin_at }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Tempo Decorrido:</span>
              <span class="text-gray-900 font-medium">{{ ocupacao.duracao }}</span>
            </div>
          </div>
        </div>

        <!-- Observações -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Observações do Check-out (opcional):
          </label>
          <textarea 
            v-model="observacoes"
            class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
            rows="2"
            placeholder="Observações sobre o check-out, condições do quarto, etc..."
            :disabled="loading"
          ></textarea>
        </div>

        <!-- Aviso -->
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
          <div class="flex items-start">
            <svg class="w-4 h-4 text-amber-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <div class="text-xs">
              <p class="font-medium text-amber-900">Atenção:</p>
              <p class="text-amber-700">Esta ação irá liberar a vaga e não poderá ser desfeita. Certifique-se de que o hóspede realizou a saída completa.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Botões -->
      <div class="flex justify-end space-x-2 pt-4 border-t border-gray-200 mt-4">
        <button 
          @click="$emit('close')"
          class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded transition-colors"
          :disabled="loading"
        >
          Cancelar
        </button>
        
        <button 
          @click="confirmarCheckout"
          class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded transition-colors flex items-center gap-2"
          :disabled="loading"
        >
          <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          {{ loading ? 'Processando...' : 'Confirmar Check-out' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

// Props
const props = defineProps({
  show: Boolean,
  ocupacao: Object,
  loading: Boolean
})

// Emits
const emit = defineEmits(['close', 'confirm'])

// Estado reativo
const observacoes = ref('')

// Watchers
watch(() => props.show, (newValue) => {
  if (!newValue) {
    observacoes.value = ''
  }
})

// Methods
const formatarData = (dataString) => {
  if (!dataString) return 'N/A'
  return new Date(dataString).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const confirmarCheckout = () => {
  if (!props.ocupacao) return
  
  emit('confirm', {
    ocupacao_id: props.ocupacao.id,
    observacoes: observacoes.value.trim()
  })
}
</script>