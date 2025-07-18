<template>
  <div v-if="show" class="fixed inset-0 bg-gray-600/50 flex items-center justify-center z-[60]">
    <div class="bg-white rounded-lg p-4 w-full max-w-xl mx-4 max-h-[85vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Check-in Rápido</h3>
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

      <div class="space-y-4">
        <!-- Vaga Pré-selecionada -->
        <div v-if="dormitorio && vaga" class="bg-green-50 p-3 rounded-lg">
          <h4 class="font-medium text-green-900 mb-2 text-sm">Vaga Pré-selecionada</h4>
          <div class="flex items-center justify-between text-sm">
            <div class="space-x-4">
              <span class="text-green-700">Dormitório:</span>
              <span class="text-green-900 font-medium">{{ dormitorio.numero }}</span>
              <span class="text-green-700">Vaga:</span>
              <span class="text-green-900 font-medium">{{ vaga.numero }}</span>
            </div>
            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs font-medium">
              Disponível
            </span>
          </div>
        </div>

        <!-- Seleção Manual -->
        <div v-if="!dormitorio && reservaSelecionada" class="bg-blue-50 p-3 rounded-lg">
          <h4 class="font-medium text-blue-900 mb-2 text-sm">Selecionar Dormitório e Vaga</h4>
          
          <!-- Lista compacta de dormitórios -->
          <div class="grid grid-cols-1 gap-2 max-h-24 overflow-y-auto mb-3">
            <div 
              v-for="dorm in dormitoriosDisponiveis" 
              :key="dorm.id"
              @click="selecionarDormitorio(dorm)"
              :class="[
                'p-2 border rounded cursor-pointer transition-colors text-sm',
                dormitorioSelecionadoTemp?.id === dorm.id 
                  ? 'border-blue-500 bg-blue-100' 
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <div class="flex justify-between items-center">
                <div>
                  <span class="font-medium">{{ dorm.numero }}</span>
                  <span class="text-xs text-gray-500 ml-2">({{ dorm.capacidade_maxima }} vagas)</span>
                </div>
                <span class="text-green-600 text-xs">{{ dorm.vagas_disponiveis }} livres</span>
              </div>
            </div>
          </div>

          <!-- Seleção de vaga compacta -->
          <div v-if="dormitorioSelecionadoTemp" class="mt-2">
            <p class="text-sm font-medium text-blue-700 mb-2">
              Vagas ({{ dormitorioSelecionadoTemp.vagas_livres?.length || 0 }} disponíveis):
            </p>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="numeroVaga in (dormitorioSelecionadoTemp.vagas_livres || [])"
                :key="numeroVaga"
                @click="vagaSelecionadaTemp = numeroVaga"
                :class="[
                  'p-1.5 text-xs border rounded transition-colors',
                  vagaSelecionadaTemp === numeroVaga
                    ? 'border-blue-500 bg-blue-100 text-blue-700'
                    : 'border-gray-200 hover:border-gray-300'
                ]"
              >
                {{ numeroVaga }}
              </button>
            </div>
            
            <!-- Aviso se não há vagas -->
            <div v-if="!dormitorioSelecionadoTemp.vagas_livres || dormitorioSelecionadoTemp.vagas_livres.length === 0" 
                 class="text-center py-3 text-gray-500">
              <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-xs">Nenhuma vaga disponível</p>
            </div>
          </div>
        </div>

        <!-- Busca de Reserva compacta -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Buscar Reserva Aprovada
          </label>
          <div class="flex gap-2">
            <input 
              v-model="termoBusca"
              type="text" 
              placeholder="CPF, nome ou matrícula..."
              class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :disabled="loading || buscandoReservas"
              @input="buscarReservas"
              @keyup.enter="buscarReservas"
            >
            <button 
              @click="limparBusca"
              class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded transition-colors"
              :disabled="loading"
            >
              Limpar
            </button>
          </div>
          <p class="text-xs text-gray-500 mt-1">Mínimo 3 caracteres</p>
        </div>

        <!-- Loading -->
        <div v-if="buscandoReservas" class="text-center py-3">
          <svg class="animate-spin w-5 h-5 mx-auto text-blue-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <p class="text-sm text-gray-600">Buscando...</p>
        </div>

        <!-- Resultados compactos -->
        <div v-else-if="reservasEncontradas.length > 0" class="space-y-2">
          <h4 class="font-medium text-gray-900 text-sm">Reservas ({{ reservasEncontradas.length }})</h4>
          <div class="max-h-48 overflow-y-auto space-y-2">
            <div 
              v-for="reserva in reservasEncontradas" 
              :key="`${reserva.tipo}-${reserva.id}`"
              @click="selecionarReserva(reserva)"
              :class="[
                'p-3 border-2 rounded-lg cursor-pointer transition-all',
                reservaSelecionada?.id === reserva.id && reservaSelecionada?.tipo === reserva.tipo
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <div class="flex justify-between items-start">
                <div>
                  <p class="font-medium text-gray-900 text-sm">{{ reserva.nome }}</p>
                  <p class="text-xs text-gray-600">{{ formatarCPF(reserva.cpf) }}</p>
                  <p class="text-xs text-gray-600">{{ reserva.orgao }}</p>
                </div>
                <div class="text-right">
                  <span :class="getTipoClass(reserva.tipo)" class="px-2 py-0.5 rounded-full text-xs font-medium">
                    {{ reserva.tipo === 'visitante' ? 'Visitante' : 'Servidor' }}
                  </span>
                  <p class="text-gray-500 text-xs mt-1">
                    {{ formatarData(reserva.data_inicial) }} - {{ formatarData(reserva.data_final) }}
                  </p>
                </div>
              </div>

              <!-- Indicador de seleção -->
              <div v-if="reservaSelecionada?.id === reserva.id && reservaSelecionada?.tipo === reserva.tipo" class="mt-2 flex items-center text-blue-600">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-xs font-medium">Selecionada</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sem resultados -->
        <div v-else-if="termoBusca && termoBusca.length >= 3 && !buscandoReservas" class="text-center py-6">
          <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <p class="text-gray-600 text-sm">Nenhuma reserva encontrada para: <strong>{{ termoBusca }}</strong></p>
          <p class="text-xs text-gray-500 mt-1">Verifique se a reserva está aprovada e sem check-in ativo.</p>
        </div>

        <!-- Observações compactas -->
        <div v-if="reservaSelecionada">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Observações (opcional):
          </label>
          <textarea 
            v-model="observacoes"
            class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
            rows="2"
            placeholder="Observações sobre o check-in..."
            :disabled="loading"
          ></textarea>
        </div>
      </div>

      <!-- Botões compactos -->
      <div class="flex justify-end space-x-2 pt-4 border-t border-gray-200 mt-4">
        <button 
          @click="$emit('close')"
          class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded transition-colors"
          :disabled="loading"
        >
          Cancelar
        </button>
        
        <button 
          @click="confirmarCheckin"
          :disabled="!podeConfirmar || loading"
          :class="[
            'px-3 py-1.5 text-sm font-medium rounded transition-colors flex items-center gap-2',
            podeConfirmar && !loading
              ? 'text-white bg-blue-600 hover:bg-blue-700'
              : 'text-gray-400 bg-gray-200 cursor-not-allowed'
          ]"
        >
          <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          {{ loading ? 'Processando...' : 'Confirmar Check-in' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

// Props
const props = defineProps({
  show: Boolean,
  dormitorio: Object,
  vaga: Object,
  loading: Boolean
})

// Emits
const emit = defineEmits(['close', 'confirm'])

// Estados reativos
const termoBusca = ref('')
const observacoes = ref('')
const reservasEncontradas = ref([])
const reservaSelecionada = ref(null)
const buscandoReservas = ref(false)

// Estados para seleção de dormitório
const dormitoriosDisponiveis = ref([])
const dormitorioSelecionadoTemp = ref(null)
const vagaSelecionadaTemp = ref(null)

// Computed
const podeConfirmar = computed(() => {
  if (!reservaSelecionada.value) return false
  
  // Se tem dormitório e vaga pré-selecionados
  if (props.dormitorio && props.vaga) return true
  
  // Se selecionou dormitório e vaga manualmente
  if (dormitorioSelecionadoTemp.value && vagaSelecionadaTemp.value) return true
  
  return false
})

// Watchers
watch(() => props.show, (newValue) => {
  if (!newValue) {
    // Reset quando modal fechar
    termoBusca.value = ''
    observacoes.value = ''
    reservasEncontradas.value = []
    reservaSelecionada.value = null
    dormitorioSelecionadoTemp.value = null
    vagaSelecionadaTemp.value = null
  } else {
    // Carregar dormitórios disponíveis quando abrir
    carregarDormitoriosDisponiveis()
  }
})

// Debounce para busca
let timeoutBusca = null

// Methods
const carregarDormitoriosDisponiveis = async () => {
  try {
    console.log('Carregando dormitórios disponíveis...')
    const response = await fetch('/admin/ocupacao/dormitorios-disponiveis', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      // Filtrar dormitórios reservados (não devem aparecer na lista)
      dormitoriosDisponiveis.value = (data || []).filter(dorm => !dorm.reservado_plantao)
      console.log('Dormitórios carregados (excluindo reservados):', dormitoriosDisponiveis.value.length)
    } else {
      console.error('Erro ao carregar dormitórios:', response.status, response.statusText)
      if (window.NotificationSystem) {
        window.NotificationSystem.error('Erro ao carregar lista de dormitórios disponíveis.')
      }
    }
  } catch (error) {
    console.error('Erro ao carregar dormitórios:', error)
    if (window.NotificationSystem) {
      window.NotificationSystem.error('Erro de conexão ao carregar dormitórios.')
    }
  }
}

const selecionarDormitorio = (dorm) => {
  console.log('Dormitório selecionado:', dorm)
  
  // Validação adicional para dormitórios reservados
  if (dorm.reservado_plantao) {
    if (window.NotificationSystem) {
      window.NotificationSystem.error('Este dormitório está reservado para o plantão e não pode receber check-ins externos.')
    }
    return
  }
  
  dormitorioSelecionadoTemp.value = dorm
  vagaSelecionadaTemp.value = null // Reset vaga quando mudar dormitório
  
  if (window.NotificationSystem) {
    window.NotificationSystem.info(`Dormitório ${dorm.numero} selecionado (${dorm.capacidade_maxima} vagas). Escolha uma vaga.`)
  }
}

const buscarReservas = () => {
  if (timeoutBusca) clearTimeout(timeoutBusca)
  
  timeoutBusca = setTimeout(async () => {
    if (!termoBusca.value || termoBusca.value.length < 3) {
      reservasEncontradas.value = []
      return
    }

    console.log('Iniciando busca por:', termoBusca.value)
    buscandoReservas.value = true
    
    try {
      const url = `/admin/ocupacao/buscar-reservas?termo=${encodeURIComponent(termoBusca.value)}`
      console.log('URL da busca:', url)
      
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      
      console.log('Status da resposta:', response.status)
      
      if (response.ok) {
        const data = await response.json()
        console.log('Dados recebidos:', data)
        reservasEncontradas.value = data.reservas || []
        console.log('Reservas encontradas:', reservasEncontradas.value.length)
        
        if (reservasEncontradas.value.length === 0) {
          if (window.NotificationSystem) {
            window.NotificationSystem.warning('Nenhuma reserva aprovada encontrada. Verifique se a reserva está aprovada e sem check-in ativo.')
          }
        } else {
          if (window.NotificationSystem) {
            window.NotificationSystem.success(`${reservasEncontradas.value.length} reserva(s) encontrada(s)!`)
          }
        }
      } else {
        const errorText = await response.text()
        console.error('Erro na resposta:', response.status, response.statusText, errorText)
        if (window.NotificationSystem) {
          window.NotificationSystem.error(`Erro ao buscar reservas: ${response.status} ${response.statusText}`)
        }
        reservasEncontradas.value = []
      }
    } catch (error) {
      console.error('Erro ao buscar reservas:', error)
      if (window.NotificationSystem) {
        window.NotificationSystem.error('Erro de conexão ao buscar reservas.')
      }
      reservasEncontradas.value = []
    } finally {
      buscandoReservas.value = false
    }
  }, 500)
}

const limparBusca = () => {
  termoBusca.value = ''
  reservasEncontradas.value = []
  reservaSelecionada.value = null
  if (window.NotificationSystem) {
    window.NotificationSystem.info('Busca limpa.')
  }
}

const selecionarReserva = (reserva) => {
  console.log('Reserva selecionada:', reserva)
  reservaSelecionada.value = reserva
  if (window.NotificationSystem) {
    window.NotificationSystem.success(`Reserva de ${reserva.nome} selecionada!`)
  }
}

const formatarCPF = (cpf) => {
  if (!cpf) return ''
  const cleaned = cpf.replace(/\D/g, '')
  return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
}

const formatarData = (dataString) => {
  if (!dataString) return ''
  try {
    return new Date(dataString).toLocaleDateString('pt-BR')
  } catch (e) {
    return dataString
  }
}

const getTipoClass = (tipo) => {
  return tipo === 'visitante' 
    ? 'bg-green-100 text-green-800' 
    : 'bg-blue-100 text-blue-800'
}

const confirmarCheckin = async () => {
  if (!podeConfirmar.value) {
    if (window.NotificationSystem) {
      window.NotificationSystem.error('Selecione uma reserva e dormitório/vaga antes de confirmar.')
    }
    return
  }
  
  let dormitorioId, numeroVaga
  
  // Se tem dormitório e vaga pré-selecionados
  if (props.dormitorio && props.vaga) {
    // Validação adicional para dormitório reservado
    if (props.dormitorio.reservado_plantao) {
      if (window.NotificationSystem) {
        window.NotificationSystem.error('Este dormitório está reservado para o plantão e não pode receber check-ins externos.')
      }
      return
    }
    dormitorioId = props.dormitorio.id
    numeroVaga = props.vaga.numero
  } 
  // Se selecionou manualmente
  else if (dormitorioSelecionadoTemp.value && vagaSelecionadaTemp.value) {
    // Validação adicional para dormitório reservado
    if (dormitorioSelecionadoTemp.value.reservado_plantao) {
      if (window.NotificationSystem) {
        window.NotificationSystem.error('Este dormitório está reservado para o plantão e não pode receber check-ins externos.')
      }
      return
    }
    dormitorioId = dormitorioSelecionadoTemp.value.id
    numeroVaga = vagaSelecionadaTemp.value
  }
  
  if (!dormitorioId || !numeroVaga) {
    console.error('Dados insuficientes para check-in')
    if (window.NotificationSystem) {
      window.NotificationSystem.error('Dados insuficientes para realizar o check-in.')
    }
    return
  }
  
  const dados = {
    tipo: reservaSelecionada.value.tipo,
    reserva_id: reservaSelecionada.value.id,
    dormitorio_id: dormitorioId,
    numero_vaga: numeroVaga,
    observacoes: observacoes.value.trim()
  }

  console.log('Dados do check-in:', dados)
  if (window.NotificationSystem) {
    window.NotificationSystem.info('Processando check-in...')
  }
  emit('confirm', dados)
}
</script>