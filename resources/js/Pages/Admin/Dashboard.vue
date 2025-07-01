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

// Dados da legenda para o gráfico
const legendaGrafico = computed(() => [
  { label: 'Usuários Cadastrados', color: '#3B82F6', key: 'usuarios' },
  { label: 'Cursos Disponíveis', color: '#10B981', key: 'cursos' },
  { label: 'Matrículas', color: '#8B5CF6', key: 'matriculas' },
  { label: 'Reservas de Alojamento', color: '#06B6D4', key: 'alojamento' },
  { label: 'Requerimentos', color: '#F59E0B', key: 'requerimentos' },
  { label: 'Contatos', color: '#EF4444', key: 'contatos' }
])
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Dashboard" />
    <SectionMain>
      <!-- Cabeçalho -->
      <div class="mb-8">
        <SectionTitleLineWithButton
          :icon="mdiChartTimelineVariant"
          title="Visão Geral do Sistema"
          main
        >
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Última atualização: {{ new Date().toLocaleString('pt-BR') }}
          </div>
        </SectionTitleLineWithButton>
      </div>

      <!-- Widgets Principais -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <!-- Usuários Cadastrados -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                Usuários Cadastrados
              </h3>
              <div class="flex items-baseline space-x-2">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ metricas.usuariosAtivos }}
                </span>
                <span class="text-sm text-green-600 dark:text-green-400 font-medium">
                  +{{ props.dashboardData.usuarios.percentage }}%
                </span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                +{{ props.dashboardData.usuarios.novos_mes }} este mês
              </p>
            </div>
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Cursos Disponíveis -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                Cursos Disponíveis
              </h3>
              <div class="flex items-baseline space-x-2">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ metricas.cursosDisponiveis }}
                </span>
                <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                  {{ props.dashboardData.cursos.concluidos }}/{{ props.dashboardData.cursos.total }}
                </span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                cursos finalizados
              </p>
            </div>
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Matrículas Pendentes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border-l-4 border-purple-500">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                Matrículas Pendentes
              </h3>
              <div class="flex items-baseline space-x-2">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ metricas.matriculasPendentes }}
                </span>
                <span class="text-sm text-purple-600 dark:text-purple-400 font-medium">
                  pendentes
                </span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                aguardando análise
              </p>
            </div>
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Pendências Totais -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border-l-4 border-amber-500">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                Pendências Totais
              </h3>
              <div class="flex items-baseline space-x-2">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ metricas.requerimentosPendentes + metricas.contatosPendentes + metricas.reservasPendentes + metricas.matriculasPendentes }}
                </span>
                <span class="text-sm text-amber-600 dark:text-amber-400 font-medium">
                  total
                </span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                itens aguardando
              </p>
            </div>
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                  <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6.414A7.001 7.001 0 0010.02 18H6a2 2 0 01-2-2V5zm3 2a1 1 0 000 2h2a1 1 0 100-2H7zm8 6a5 5 0 11-10 0 5 5 0 0110 0z" clip-rule="evenodd"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfico de Evolução com Legenda -->
      <div class="mb-8">
        <CardBox
          title="Evolução Anual do Sistema"
          :icon="mdiChartTimelineVariant"
          :header-icon="mdiReload"
          class="shadow-lg"
          @header-icon-click="fillChartData"
        >
          <!-- Legenda do Gráfico -->
          <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Legenda das Métricas</h4>
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
              <div 
                v-for="item in legendaGrafico" 
                :key="item.key"
                class="flex items-center space-x-2"
              >
                <div 
                  class="w-4 h-4 rounded-full"
                  :style="{ backgroundColor: item.color }"
                ></div>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ item.label }}</span>
              </div>
            </div>
          </div>
          
          <!-- Gráfico -->
          <div v-if="chartDataReactive" class="h-96">
            <LineChart
              :data="chartDataReactive"
              class="h-full"
            />
          </div>
        </CardBox>
      </div>

      <!-- Detalhamento das Métricas -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
        <!-- Coluna Esquerda -->
        <div class="space-y-6">
          <!-- Cursos -->
          <CardBox 
            title="Gestão de Cursos" 
            :icon="mdiSchool" 
            class="shadow-md hover:shadow-lg transition-shadow duration-300"
          >
            <div class="grid grid-cols-3 gap-4">
              <div class="text-center p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-1">
                  {{ props.dashboardData.cursos.total }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total de Cursos</div>
              </div>
              <div class="text-center p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400 mb-1">
                  {{ props.dashboardData.cursos.concluidos }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Concluídos</div>
              </div>
              <div class="text-center p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400 mb-1">
                  {{ props.dashboardData.cursos.em_aberto }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Em Aberto</div>
              </div>
            </div>
          </CardBox>

          <!-- Matrículas -->
          <CardBox 
            title="Matrículas em Cursos" 
            :icon="mdiAccountKey" 
            class="shadow-md hover:shadow-lg transition-shadow duration-300"
          >
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mb-1">
                  {{ props.dashboardData.matriculas?.total_mes || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Este Mês</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-amber-600 dark:text-amber-400 mb-1">
                  {{ metricas.matriculasPendentes }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Pendentes</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-green-600 dark:text-green-400 mb-1">
                  {{ props.dashboardData.matriculas?.aprovadas || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Aprovadas</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-red-600 dark:text-red-400 mb-1">
                  {{ props.dashboardData.matriculas?.rejeitadas || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Rejeitadas</div>
              </div>
            </div>
          </CardBox>

          <!-- Alojamento -->
          <CardBox 
            title="Reservas de Alojamento" 
            :icon="mdiHome" 
            class="shadow-md hover:shadow-lg transition-shadow duration-300"
          >
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mb-1">
                  {{ props.dashboardData.alojamento.reservas_mes }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Este Mês</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-amber-600 dark:text-amber-400 mb-1">
                  {{ metricas.reservasPendentes }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Pendentes</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-green-600 dark:text-green-400 mb-1">
                  {{ props.dashboardData.alojamento.aprovadas }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Aprovadas</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-red-600 dark:text-red-400 mb-1">
                  {{ props.dashboardData.alojamento.rejeitadas }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Rejeitadas</div>
              </div>
            </div>
          </CardBox>
        </div>

        <!-- Coluna Direita -->
        <div class="space-y-6">
          <!-- Requerimentos -->
          <CardBox 
            title="Requerimentos" 
            :icon="mdiClipboardList" 
            class="shadow-md hover:shadow-lg transition-shadow duration-300"
          >
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-amber-600 dark:text-amber-400 mb-1">
                  {{ metricas.requerimentosPendentes }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Pendentes</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mb-1">
                  {{ props.dashboardData.requerimentos.total_mes }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Este Mês</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-green-600 dark:text-green-400 mb-1">
                  {{ props.dashboardData.requerimentos.deferidos }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Deferidos</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-red-600 dark:text-red-400 mb-1">
                  {{ props.dashboardData.requerimentos.indeferidos }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Indeferidos</div>
              </div>
            </div>
          </CardBox>

          <!-- Fale Conosco -->
          <CardBox 
            title="Fale Conosco" 
            :icon="mdiEmailOutline" 
            class="shadow-md hover:shadow-lg transition-shadow duration-300"
          >
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-amber-600 dark:text-amber-400 mb-1">
                  {{ metricas.contatosPendentes }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Pendentes</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mb-1">
                  {{ props.dashboardData.contatos.total_mes }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Este Mês</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-green-600 dark:text-green-400 mb-1">
                  {{ props.dashboardData.contatos.respondidos }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Respondidos</div>
              </div>
              <div class="text-center p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/20 dark:to-gray-700/20 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-xl font-bold text-gray-600 dark:text-gray-400 mb-1">
                  {{ props.dashboardData.contatos.arquivados }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Arquivados</div>
              </div>
            </div>
          </CardBox>          
        </div>
      </div>

      <!-- Ações Rápidas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Ações Rápidas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border-l-4 border-blue-500">
          <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
              <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
            </svg>
            Ações Rápidas
          </h3>
          <div class="space-y-3">
            <a :href="route('admin.user.index')" class="block p-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors duration-200">
              → Gerenciar Usuários
            </a>
            <a :href="route('admin.cursos.index')" class="block p-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors duration-200">
              → Gerenciar Cursos
            </a>
            <a :href="route('admin.alojamento.index')" class="block p-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors duration-200">
              → Reservas de Alojamento
            </a>
          </div>
        </div>

        <!-- Pendências -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border-l-4 border-amber-500">
          <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Pendências
          </h3>
          <div class="space-y-3">
            <a :href="route('admin.requerimentos.index')" class="block p-2 text-sm text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-200 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors duration-200">
              → {{ metricas.requerimentosPendentes }} Requerimentos
            </a>
            <a :href="route('admin.contato.index')" class="block p-2 text-sm text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-200 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors duration-200">
              → {{ metricas.contatosPendentes }} Mensagens
            </a>
            <a :href="route('admin.alojamento.index')" class="block p-2 text-sm text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-200 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors duration-200">
              → {{ metricas.reservasPendentes }} Reservas
            </a>
          </div>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<style scoped>
/* Animações suaves para os cards */
.transition-shadow {
  transition: box-shadow 0.3s ease-in-out;
}

.hover\:shadow-lg:hover {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Animações para hover nos links */
.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
}

/* Gradientes personalizados */
.bg-gradient-to-br {
  background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

.bg-gradient-to-r {
  background-image: linear-gradient(to right, var(--tw-gradient-stops));
}

/* Efeito de hover para cards menores */
.rounded-xl:hover {
  transform: translateY(-2px);
  transition: transform 0.2s ease-in-out, box-shadow 0.3s ease-in-out;
}

/* Responsividade melhorada */
@media (max-width: 768px) {
  .grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  
  .lg\:grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  
  .xl\:grid-cols-6 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .grid-cols-3 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
  
  .lg\:grid-cols-4 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }

  .xl\:grid-cols-4 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }

  .md\:grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>