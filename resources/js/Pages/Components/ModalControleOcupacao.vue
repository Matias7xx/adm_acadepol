<template>
  <!-- Container fullscreen -->
  <div v-if="show" class="fixed inset-0 bg-gray-900/75 flex items-center justify-center z-50">
    <div class="bg-white w-full h-full overflow-hidden flex flex-col">
      <!-- Header compacto -->
      <div class="bg-gray-800 text-white px-4 py-3 flex-shrink-0">
        <div class="flex justify-between items-center">
          <div>
            <h2 class="text-lg font-semibold">Controle de Ocupação</h2>
            <p class="text-gray-300 text-sm">Gerenciamento dos dormitórios</p>
          </div>
          <div class="flex items-center gap-3">
            <!-- Atualizar dados -->
            <button 
              @click="atualizarDados"
              :disabled="loading"
              class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm flex items-center gap-2 transition-colors"
            >
              <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              {{ loading ? 'Atualizando...' : 'Atualizar' }}
            </button>
            
            <!-- Fechar -->
            <button 
              @click="$emit('close')"
              class="text-white hover:text-gray-300 transition-colors p-1"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Estatísticas compactas -->
        <div class="grid grid-cols-4 gap-3 mt-3">
          <div class="bg-white/10 rounded px-3 py-2">
            <div class="text-white/70 text-xs">Total</div>
            <div class="text-lg font-semibold text-white">{{ estatisticas.total_vagas }}</div>
          </div>
          <div class="bg-white/10 rounded px-3 py-2">
            <div class="text-white/70 text-xs">Ocupadas</div>
            <div class="text-lg font-semibold text-white">{{ estatisticas.vagas_ocupadas }}</div>
          </div>
          <div class="bg-white/10 rounded px-3 py-2">
            <div class="text-white/70 text-xs">Livres</div>
            <div class="text-lg font-semibold text-white">{{ estatisticas.vagas_disponiveis }}</div>
          </div>
          <div class="bg-white/10 rounded px-3 py-2">
            <div class="text-white/70 text-xs">Ocupação</div>
            <div class="text-lg font-semibold text-white">{{ estatisticas.percentual_ocupacao }}%</div>
          </div>
        </div>
      </div>

      <!-- Filtros compactos -->
      <div class="border-b border-gray-200 px-4 py-2 bg-gray-50 flex-shrink-0">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <!-- Filtro por status -->
            <select 
              v-model="filtroStatus" 
              @change="aplicarFiltros"
              class="px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todos</option>
              <option value="disponivel">Disponíveis</option>
              <option value="parcial">Parcial</option>
              <option value="lotado">Lotados</option>
              <option value="reservado">Reservados</option>
            </select>
            
            <!-- Busca -->
            <div class="relative">
              <input 
                v-model="busca"
                @input="aplicarFiltros"
                type="text" 
                placeholder="Buscar..."
                class="pl-8 pr-3 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
              <svg class="absolute left-2 top-1.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Grid de dormitórios -->
      <div class="flex-1 overflow-y-auto p-4 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <div 
            v-for="dormitorio in dormitoriosFiltrados" 
            :key="dormitorio.id"
            :class="[
              'bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden',
              dormitorio.reservado_plantao ? 'opacity-75 border-purple-300' : ''
            ]"
          >
            <!-- Header do Dormitório compacto -->
            <div :class="[
              'px-3 py-2 border-b',
              dormitorio.reservado_plantao 
                ? 'bg-purple-50' 
                : 'bg-gray-50'
            ]">
              <div class="flex justify-between items-center mb-2">
                <div>
                  <h3 class="font-semibold text-gray-900">{{ dormitorio.numero }}</h3>
                  <p class="text-xs text-gray-600">{{ dormitorio.nome || 'Sem nome' }}</p>
                  <p v-if="dormitorio.reservado_plantao" class="text-xs text-purple-600 font-medium">
                    🔒 RESERVADO PARA PLANTÃO
                  </p>
                </div>
                <div class="text-right">
                  <span :class="getStatusBadgeClass(dormitorio)" class="px-2 py-0.5 rounded text-xs font-medium">
                    {{ getStatusText(dormitorio) }}
                  </span>
                  <!-- Omitir percentual de ocupação para dormitórios reservados -->
                  <div v-if="!dormitorio.reservado_plantao" class="text-xs text-gray-500 mt-1">
                    {{ dormitorio.percentual_ocupacao }}%
                  </div>
                </div>
              </div>

              <!-- Barra de progresso - omitir para dormitórios reservados -->
              <div v-if="!dormitorio.reservado_plantao" class="bg-gray-200 rounded-full h-1.5">
                <div 
                  :class="getProgressBarClass(dormitorio.percentual_ocupacao, dormitorio.reservado_plantao)"
                  class="h-1.5 rounded-full transition-all duration-500"
                  :style="`width: ${dormitorio.percentual_ocupacao}%`"
                ></div>
              </div>
              
              <!-- Informações de capacidade - omitir para dormitórios reservados -->
              <div v-if="!dormitorio.reservado_plantao" class="flex justify-between text-xs text-gray-600 mt-1">
                <span>{{ dormitorio.vagas_ocupadas }}/{{ dormitorio.capacidade_maxima }}</span>
                <span>{{ dormitorio.vagas_disponiveis }} livres</span>
              </div>
              
              <!-- Informação simplificada para dormitórios reservados -->
              <div v-else class="text-center text-xs text-purple-600 mt-1 font-medium">
                Indisponível para ocupação
              </div>
            </div>

            <!-- Grid de vagas - modificado para dormitórios reservados -->
            <div class="p-3">
              <!-- Para dormitórios reservados, exibir apenas informação de indisponibilidade -->
              <div v-if="dormitorio.reservado_plantao" class="text-center py-6">
                <div class="text-purple-500 text-2xl mb-2">🔒</div>
                <p class="text-sm text-purple-600 font-medium">Dormitório Reservado</p>
                <p class="text-xs text-gray-500 mt-1">Indisponível para check-in</p>
              </div>
              
              <!-- Para dormitórios normais, exibir grid de vagas -->
              <div v-else :class="getVagasGridClass(dormitorio.capacidade_maxima)">
                <div 
                  v-for="vaga in dormitorio.vagas" 
                  :key="vaga.numero"
                  :class="getVagaCardClass(vaga, dormitorio.reservado_plantao)"
                  class="border rounded p-2 transition-all duration-200 text-center"
                  @click="handleVagaClick(dormitorio, vaga)"
                >
                  <!-- Número da Vaga -->
                  <div class="text-xs font-semibold mb-1">{{ vaga.numero }}</div>
                  
                  <!-- Status -->
                  <div :class="getVagaStatusClass(vaga, dormitorio.reservado_plantao)" class="px-1 py-0.5 rounded text-xs">
                    {{ getVagaStatusText(vaga, dormitorio.reservado_plantao) }}
                  </div>

                  <!-- Info da ocupação (se ocupada) -->
                  <div v-if="vaga.ocupada && vaga.ocupacao" class="mt-1">
                    <p class="text-xs font-medium text-gray-800 truncate">{{ vaga.ocupacao.hospede_nome }}</p>
                    <p class="text-xs text-gray-600">{{ vaga.ocupacao.hospede_tipo }}</p>
                    <p class="text-xs text-gray-500">{{ vaga.ocupacao.checkin_at }}</p>
                    
                    <!-- Botões de ação compactos -->
                    <div class="flex gap-1 mt-1">
                      <button 
                        @click.stop="verDetalhesOcupacao(vaga.ocupacao)"
                        class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-xs py-0.5 px-1 rounded transition-colors"
                        title="Ver detalhes"
                        type="button"
                      >
                        👁
                      </button>
                      <button 
                        @click.stop="iniciarCheckout(vaga.ocupacao)"
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white text-xs py-0.5 px-1 rounded transition-colors"
                        title="Check-out"
                        type="button"
                      >
                        🚪
                      </button>
                    </div>
                  </div>

                  <!-- Vaga livre -->
                  <div v-else class="mt-1">
                    <div class="text-green-600 text-xs">✓</div>
                    
                    <button 
                      @click.stop="iniciarCheckin(dormitorio, vaga)"
                      class="mt-1 bg-green-500 hover:bg-green-600 text-white text-xs py-0.5 px-2 rounded transition-colors"
                      type="button"
                    >
                      Check-in
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mensagem quando não há resultados -->
        <div v-if="dormitoriosFiltrados.length === 0" class="text-center py-8">
          <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-14 0h2m-2 0h-2m16 0v-2a2 2 0 00-2-2V9a2 2 0 00-2-2M7 7h10" />
          </svg>
          <h3 class="text-gray-900 font-medium">Nenhum dormitório encontrado</h3>
          <p class="text-gray-600 text-sm">Tente ajustar os filtros.</p>
        </div>
      </div>
    </div>

    <!-- Modais -->
    <ModalDetalhesOcupacao
      v-if="showDetalhesModal"
      :show="showDetalhesModal"
      :ocupacao="ocupacaoSelecionada"
      @close="closeDetalhesModal"
      @checkout="iniciarCheckout"
      @ver-reserva="verReserva"
    />

    <ModalCheckout
      v-if="showCheckoutModal"
      :show="showCheckoutModal"
      :ocupacao="ocupacaoParaCheckout"
      @close="closeCheckoutModal"
      @confirm="realizarCheckout"
      :loading="loadingCheckout"
    />

    <ModalCheckinRapido
      v-if="showCheckinModal"
      :show="showCheckinModal"
      :dormitorio="dormitorioSelecionado"
      :vaga="vagaSelecionada"
      @close="closeCheckinModal"
      @confirm="realizarCheckin"
      :loading="loadingCheckin"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useNotifications } from '@/Composables/useNotifications'
import ModalDetalhesOcupacao from './ModalDetalhesOcupacao.vue'
import ModalCheckout from './ModalCheckout.vue'
import ModalCheckinRapido from './ModalCheckinRapido.vue'

// Props
const props = defineProps({
  show: Boolean,
  dormitorios: Array,
  estatisticas: Object
})

// Emits
const emit = defineEmits(['close', 'refresh'])

// Composables
const { showSuccess, showError, showWarning, showInfo } = useNotifications()

// Estados reativos
const loading = ref(false)
const filtroStatus = ref('')
const busca = ref('')

// Modais
const showDetalhesModal = ref(false)
const showCheckoutModal = ref(false)
const showCheckinModal = ref(false)

// Dados selecionados
const ocupacaoSelecionada = ref(null)
const ocupacaoParaCheckout = ref(null)
const dormitorioSelecionado = ref(null)
const vagaSelecionada = ref(null)

// Estados de loading
const loadingCheckout = ref(false)
const loadingCheckin = ref(false)

// Computed
const dormitoriosFiltrados = computed(() => {
  if (!props.dormitorios) return []

  let resultado = [...props.dormitorios]

  // Filtrar por status
  if (filtroStatus.value) {
    resultado = resultado.filter(d => {
      switch (filtroStatus.value) {
        case 'disponivel':
          return d.vagas_ocupadas === 0 && !d.reservado_plantao
        case 'parcial':
          return d.vagas_ocupadas > 0 && d.vagas_ocupadas < d.capacidade_maxima
        case 'lotado':
          return d.vagas_ocupadas === d.capacidade_maxima
        case 'reservado':
          return d.reservado_plantao
        default:
          return true
      }
    })
  }

  // Filtrar por busca
  if (busca.value) {
    const termo = busca.value.toLowerCase()
    resultado = resultado.filter(d => 
      d.numero.toLowerCase().includes(termo) ||
      (d.nome && d.nome.toLowerCase().includes(termo))
    )
  }

  return resultado
})

// Métodos auxiliares
const aplicarFiltros = () => {
  // Os filtros são aplicados automaticamente via computed
}

const atualizarDados = async () => {
  loading.value = true
  showInfo('Atualizando dados...')
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    emit('refresh')
    showSuccess('Dados atualizados!')
  } catch (error) {
    showError('Erro ao atualizar dados.')
  } finally {
    loading.value = false
  }
}

const getStatusBadgeClass = (dormitorio) => {
  if (dormitorio.reservado_plantao) {
    return 'bg-purple-100 text-purple-800'
  }
  
  const ocupacao = dormitorio.percentual_ocupacao
  if (ocupacao === 0) return 'bg-green-100 text-green-800'
  if (ocupacao <= 50) return 'bg-yellow-100 text-yellow-800'
  if (ocupacao < 100) return 'bg-orange-100 text-orange-800'
  return 'bg-red-100 text-red-800'
}

const getStatusText = (dormitorio) => {
  if (dormitorio.reservado_plantao) return 'RESERVADO'
  
  const ocupacao = dormitorio.percentual_ocupacao
  if (ocupacao === 0) return 'Livre'
  if (ocupacao < 100) return 'Parcial'
  return 'Lotado'
}

const getProgressBarClass = (percentual, isReservado = false) => {
  if (isReservado) return 'bg-purple-500'
  
  if (percentual === 0) return 'bg-green-500'
  if (percentual <= 50) return 'bg-yellow-500'
  if (percentual < 100) return 'bg-orange-500'
  return 'bg-red-500'
}

const getVagasGridClass = (capacidade) => {
  if (capacidade === 8) {
    return 'grid grid-cols-4 gap-2'
  }
  return 'grid grid-cols-2 gap-2'
}

const getVagaCardClass = (vaga, isReservado = false) => {
  if (isReservado) {
    return 'border-purple-200 bg-purple-50 cursor-not-allowed opacity-75'
  }
  
  if (vaga.ocupada) {
    return 'border-red-200 bg-red-50 hover:border-red-300 cursor-pointer'
  }
  return 'border-green-200 bg-green-50 hover:border-green-300 cursor-pointer'
}

const getVagaStatusClass = (vaga, isReservado = false) => {
  if (isReservado) {
    return 'bg-purple-100 text-purple-800'
  }
  
  return vaga.ocupada 
    ? 'bg-red-100 text-red-800' 
    : 'bg-green-100 text-green-800'
}

const getVagaStatusText = (vaga, isReservado = false) => {
  if (isReservado) return 'Reservado'
  return vaga.ocupada ? 'Ocupada' : 'Livre'
}

const formatarDataCheckin = (dataString) => {
  if (!dataString) return 'N/A'
  return new Date(dataString).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleVagaClick = (dormitorio, vaga) => {
  // Prevenir comportamento padrão se for um evento
  event?.preventDefault?.()
  
  if (dormitorio.reservado_plantao) {
    showWarning('Dormitório reservado para plantão.')
    return
  }
  
  if (vaga.ocupada && vaga.ocupacao) {
    verDetalhesOcupacao(vaga.ocupacao)
  } else {
    iniciarCheckin(dormitorio, vaga)
  }
}

const verDetalhesOcupacao = (ocupacao) => {
  console.log('Abrindo detalhes da ocupação:', ocupacao) // Debug
  if (!ocupacao) {
    console.error('Ocupação não encontrada')
    showError('Dados da ocupação não encontrados.')
    return
  }
  ocupacaoSelecionada.value = ocupacao
  showDetalhesModal.value = true
}

const iniciarCheckout = (ocupacao) => {
  ocupacaoParaCheckout.value = ocupacao
  showCheckoutModal.value = true
  showDetalhesModal.value = false
}

const iniciarCheckin = (dormitorio, vaga) => {
  if (dormitorio.reservado_plantao) {
    showError('Dormitório reservado para plantão.')
    return
  }
  
  dormitorioSelecionado.value = dormitorio
  vagaSelecionada.value = vaga
  showCheckinModal.value = true
}

const abrirModalCheckinRapido = () => {
  showCheckinModal.value = true
}

const closeDetalhesModal = () => {
  console.log('Fechando modal de detalhes') // Debug
  showDetalhesModal.value = false
  ocupacaoSelecionada.value = null
}

const closeCheckoutModal = () => {
  console.log('Fechando modal de checkout') // Debug
  showCheckoutModal.value = false
  ocupacaoParaCheckout.value = null
}

const closeCheckinModal = () => {
  console.log('Fechando modal de checkin') // Debug
  showCheckinModal.value = false
  dormitorioSelecionado.value = null
  vagaSelecionada.value = null
}

const realizarCheckout = async (dados) => {
  loadingCheckout.value = true
  try {
    // Prevenir refresh da página
    event?.preventDefault?.()
    
    const response = await fetch(`/admin/ocupacao/${dados.ocupacao_id}/checkout`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ observacoes: dados.observacoes })
    })

    const result = await response.json()

    if (response.ok && result.success) {
      closeCheckoutModal()
      emit('refresh')
      showSuccess(result.message || 'Check-out realizado!')
    } else {
      throw new Error(result.error || 'Erro ao realizar checkout')
    }
  } catch (error) {
    console.error('Erro no check-out:', error)
    showError(error.message || 'Erro ao realizar check-out.')
  } finally {
    loadingCheckout.value = false
  }
}

const realizarCheckin = async (dados) => {
  loadingCheckin.value = true
  try {
    // Prevenir refresh da página
    event?.preventDefault?.()
    
    const response = await fetch('/admin/ocupacao/checkin', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(dados)
    })

    const result = await response.json()

    if (response.ok && result.success) {
      closeCheckinModal()
      emit('refresh')
      showSuccess(result.message || 'Check-in realizado!')
    } else {
      throw new Error(result.error || 'Erro ao realizar checkin')
    }
  } catch (error) {
    console.error('Erro no check-in:', error)
    showError(error.message || 'Erro ao realizar check-in.')
  } finally {
    loadingCheckin.value = false
  }
}

const verReserva = (ocupacao) => {
  const tipo = ocupacao.reserva_type === 'App\\Models\\Alojamento' ? 'alojamento' : 'visitante'
  const url = `/admin/${tipo}/${ocupacao.reserva_id}`
  window.open(url, '_blank')
}

// Limpar dados quando modal fechar
watch(() => props.show, (newValue) => {
  if (!newValue) {
    filtroStatus.value = ''
    busca.value = ''
  }
})
</script>