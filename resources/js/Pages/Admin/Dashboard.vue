<script setup>
import { Head } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
import {
  mdiAccountMultiple,
  mdiSchool,
  mdiCalendarCheck,
  mdiCalendarClock,
  mdiHome,
  mdiClipboardList,
  mdiEmailOutline,
  mdiChartTimelineVariant,
  mdiReload,
  mdiTrendingUp,
  mdiTrendingDown,
  mdiCalendar,
  mdiAccountGroup,
  mdiFileDocumentOutline,
  mdiAccountKey
} from '@mdi/js'
import LineChart from '@/Components/Charts/LineChart.vue'
import SectionMain from '@/Components/SectionMain.vue'
import CardBoxWidget from '@/Components/CardBoxWidget.vue'
import CardBox from '@/Components/CardBox.vue'
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue'

const props = defineProps({
  dashboardData: Object,
  chartData: Object
})

// Dados reativos
const chartDataReactive = ref(props.chartData)

// Recarregar dados do gráfico
const fillChartData = () => {
  chartDataReactive.value = props.chartData
}

// Métricas
const metricas = computed(() => {
  const data = props.dashboardData
  return {
    usuariosAtivos: data.usuarios.total,
    cursosDisponiveis: data.cursos.total,
    reservasPendentes: data.alojamento.pendentes,
    requerimentosPendentes: data.requerimentos.pendentes,
    contatosPendentes: data.contatos.pendentes,
    matriculasPendentes: data.matriculas?.pendentes || 0,
  }
})

// Função para formatar números
const formatarNumero = (numero) => {
  return new Intl.NumberFormat('pt-BR').format(numero)
}

// Função para obter cor baseada no trend
const getTrendColor = (trend) => {
  switch(trend) {
    case 'up': return 'text-emerald-500'
    case 'down': return 'text-red-500'
    default: return 'text-blue-500'
  }
}

// Dados para resumo do mês atual
const resumoMesAtual = computed(() => {
  const data = props.dashboardData
  const mes = new Date().toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
  
  return {
    mes: mes.charAt(0).toUpperCase() + mes.slice(1),
    novosCadastros: data.usuarios.novos_mes,
    cursosFinalizados: data.cursos.concluidos,
    reservasAlojamento: data.alojamento.reservas_mes,
    requerimentosRecebidos: data.requerimentos.total_mes,
    contatosRecebidos: data.contatos.total_mes,
    matriculasRecebidas: data.matriculas?.total_mes || 0,
  }
})
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Dashboard" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiChartTimelineVariant"
        title="Visão Geral do Sistema"
        main
      >
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Última atualização: {{ new Date().toLocaleString('pt-BR') }}
        </div>
      </SectionTitleLineWithButton>

      <!-- Widgets Principais -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 mb-6">
        <!-- Usuários Cadastrados -->
        <CardBoxWidget
          :trend="`+${props.dashboardData.usuarios.percentage}%`"
          :trend-type="props.dashboardData.usuarios.trend"
          :color="getTrendColor(props.dashboardData.usuarios.trend)"
          :icon="mdiAccountMultiple"
          :number="metricas.usuariosAtivos"
          label="Usuários Cadastrados"
          :suffix="` (+${props.dashboardData.usuarios.novos_mes} este mês)`"
        />

        <!-- Cursos Disponíveis -->
        <CardBoxWidget
          :trend="`${props.dashboardData.cursos.concluidos}/${props.dashboardData.cursos.total}`"
          :trend-type="props.dashboardData.cursos.trend"
          :color="getTrendColor(props.dashboardData.cursos.trend)"
          :icon="mdiSchool"
          :number="metricas.cursosDisponiveis"
          label="Cursos Disponíveis"
          suffix=" cursos ativos"
        />

        <!-- Matrículas Pendentes -->
        <CardBoxWidget
          :trend="`${metricas.matriculasPendentes}`"
          trend-type="alert"
          color="text-purple-500"
          :icon="mdiAccountKey"
          :number="metricas.matriculasPendentes"
          label="Matrículas Pendentes"
          suffix=" aguardando análise"
        />

        <!-- Pendências Gerais -->
        <CardBoxWidget
          :trend="`${metricas.requerimentosPendentes + metricas.contatosPendentes + metricas.reservasPendentes + metricas.matriculasPendentes}`"
          trend-type="alert"
          color="text-amber-500"
          :icon="mdiClipboardList"
          :number="metricas.requerimentosPendentes + metricas.contatosPendentes + metricas.reservasPendentes + metricas.matriculasPendentes"
          label="Pendências Totais"
          suffix=" itens aguardando"
        />
      </div>

      <!-- Detalhamento das Métricas -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Coluna Esquerda -->
        <div class="space-y-4">
          <!-- Cursos -->
          <CardBox title="Gestão de Cursos" :icon="mdiSchool" class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
            <div class="grid grid-cols-3 gap-4 text-center">
              <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ props.dashboardData.cursos.total }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total de Cursos</div>
              </div>
              <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ props.dashboardData.cursos.concluidos }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Concluídos</div>
              </div>
              <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ props.dashboardData.cursos.em_aberto }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Em Aberto</div>
              </div>
            </div>
          </CardBox>

          <!-- Matrículas -->
          <CardBox title="Matrículas em Cursos" :icon="mdiAccountKey" class="bg-gradient-to-r from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20">
            <div class="grid grid-cols-4 gap-2 text-center">
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ props.dashboardData.matriculas?.total_mes || 0 }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Este Mês</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ metricas.matriculasPendentes }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Pendentes</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ props.dashboardData.matriculas?.aprovadas || 0 }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Aprovadas</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ props.dashboardData.matriculas?.rejeitadas || 0 }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Rejeitadas</div>
              </div>
            </div>
          </CardBox>

          <!-- Alojamento -->
          <CardBox title="Reservas de Alojamento" :icon="mdiHome" class="bg-gradient-to-r from-cyan-50 to-teal-50 dark:from-cyan-900/20 dark:to-teal-900/20">
            <div class="grid grid-cols-4 gap-2 text-center">
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-cyan-600 dark:text-cyan-400">{{ props.dashboardData.alojamento.reservas_mes }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Este Mês</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ metricas.reservasPendentes }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Pendentes</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ props.dashboardData.alojamento.aprovadas }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Aprovadas</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ props.dashboardData.alojamento.rejeitadas }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Rejeitadas</div>
              </div>
            </div>
          </CardBox>
        </div>

        <!-- Coluna Direita -->
        <div class="space-y-4">
          <!-- Requerimentos -->
          <CardBox title="Requerimentos" :icon="mdiClipboardList" class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20">
            <div class="grid grid-cols-4 gap-2 text-center">
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ metricas.requerimentosPendentes }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Pendentes</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ props.dashboardData.requerimentos.total_mes }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Este Mês</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ props.dashboardData.requerimentos.deferidos }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Deferidos</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ props.dashboardData.requerimentos.indeferidos }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Indeferidos</div>
              </div>
            </div>
          </CardBox>

          <!-- Fale Conosco -->
          <CardBox title="Fale Conosco" :icon="mdiEmailOutline" class="bg-gradient-to-r from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20">
            <div class="grid grid-cols-4 gap-2 text-center">
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ metricas.contatosPendentes }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Pendentes</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ props.dashboardData.contatos.total_mes }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Este Mês</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ props.dashboardData.contatos.respondidos }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Respondidos</div>
              </div>
              <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="text-xl font-bold text-gray-600 dark:text-gray-400">{{ props.dashboardData.contatos.arquivados }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Arquivados</div>
              </div>
            </div>
          </CardBox>

          <!-- Resumo do Mês -->
          <CardBox title="Resumo do Mês" :icon="mdiCalendar" class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20">
            <div class="space-y-3">
              <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-800 rounded-lg">
                <span class="text-sm text-gray-600 dark:text-gray-400">Novos Cadastros</span>
                <span class="font-semibold text-blue-600 dark:text-blue-400">{{ resumoMesAtual.novosCadastros }}</span>
              </div>
              <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-800 rounded-lg">
                <span class="text-sm text-gray-600 dark:text-gray-400">Cursos Finalizados</span>
                <span class="font-semibold text-green-600 dark:text-green-400">{{ resumoMesAtual.cursosFinalizados }}</span>
              </div>
              <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-800 rounded-lg">
                <span class="text-sm text-gray-600 dark:text-gray-400">Novas Matrículas</span>
                <span class="font-semibold text-purple-600 dark:text-purple-400">{{ resumoMesAtual.matriculasRecebidas }}</span>
              </div>
              <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-800 rounded-lg">
                <span class="text-sm text-gray-600 dark:text-gray-400">Reservas Alojamento</span>
                <span class="font-semibold text-cyan-600 dark:text-cyan-400">{{ resumoMesAtual.reservasAlojamento }}</span>
              </div>
              <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-800 rounded-lg">
                <span class="text-sm text-gray-600 dark:text-gray-400">Requerimentos</span>
                <span class="font-semibold text-orange-600 dark:text-orange-400">{{ resumoMesAtual.requerimentosRecebidos }}</span>
              </div>
            </div>
          </CardBox>
        </div>
      </div>

      <!-- Gráfico de Evolução -->
      <CardBox
        title="Evolução Anual do Sistema"
        :icon="mdiChartTimelineVariant"
        :header-icon="mdiReload"
        class="mb-6"
        @header-icon-click="fillChartData"
      >
        <div v-if="chartDataReactive" class="h-96">
          <LineChart
            :data="chartDataReactive"
            class="h-full"
          />
        </div>
      </CardBox>

      <!-- Ações Rápidas -->
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
          <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Ações Rápidas</h3>
          <div class="space-y-2">
            <a :href="route('admin.user.index')" class="block text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
              → Gerenciar Usuários
            </a>
            <a :href="route('admin.cursos.index')" class="block text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
              → Gerenciar Cursos
            </a>
            <a :href="route('admin.alojamento.index')" class="block text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
              → Reservas de Alojamento
            </a>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-green-500">
          <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Pendências</h3>
          <div class="space-y-2">
            <a :href="route('admin.requerimentos.index')" class="block text-sm text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200">
              → {{ metricas.requerimentosPendentes }} Requerimentos
            </a>
            <a :href="route('admin.contato.index')" class="block text-sm text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200">
              → {{ metricas.contatosPendentes }} Mensagens
            </a>
            <a :href="route('admin.alojamento.index')" class="block text-sm text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200">
              → {{ metricas.reservasPendentes }} Reservas
            </a>
          </div>
        </div>
        
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<style scoped>
/* Animações para os cards */
.card-hover {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Gradientes */
.gradient-blue {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-green {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}

.gradient-purple {
  background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%);
}

.gradient-orange {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
}
</style>