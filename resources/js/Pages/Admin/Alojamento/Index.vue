<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import BaseButton from "@/Components/BaseButton.vue"
import CardBox from "@/Components/CardBox.vue"
import {
  mdiArrowLeftBoldOutline,
  mdiBedEmpty,
  mdiMagnify,
  mdiEye,
  mdiCheckCircle,
  mdiCloseCircle,
  mdiSwapHorizontal,
  mdiAccount,
  mdiAccountGroup
} from "@mdi/js"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import FormField from "@/Components/FormField.vue"
import BaseButtons from "@/Components/BaseButtons.vue"

// Props
const props = defineProps({
  reservas: Object,
  filters: Object,
  estatisticas: Object
});

console.log('=== DADOS BRUTOS RECEBIDOS DO BACKEND ===');
props.reservas.data.forEach((reserva, index) => {
  console.log(`Reserva ${index + 1}:`, {
    id: reserva.id,
    nome: reserva.nome,
    tipo: reserva.tipo,
    tipo_original: reserva.tipo,
    todosOsCampos: Object.keys(reserva)
  });
});

const getShowRoute = (reserva) => {
  if (reserva.tipo === 'visitante') {
    return route('admin.visitante.show', reserva.id)
  } else {
    return route('admin.alojamento.show', reserva.id)
  }
}

// Função para obter a rota baseada no tipo
const getRouteForStatus = (reserva) => {
  console.log('=== DEBUG getRouteForStatus ===');
  console.log('Reserva:', {
    id: reserva.id,
    nome: reserva.nome,
    tipo: reserva.tipo
  });
};

// Toast
const { toast } = useToast();

// Estado local
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

// Modal de confirmação padrão
const showConfirmModal = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref(() => {});

// Status de reservas
const statusLabels = {
  pendente: 'Pendente',
  aprovada: 'Aprovada',
  rejeitada: 'Rejeitada'
};

const statusColors = {
  pendente: 'warning',
  aprovada: 'success',
  rejeitada: 'danger'
};

// Cores para tipos de reserva
const tipoReservaColors = {
  usuario: {
    background: 'bg-blue-50 dark:bg-blue-900/20',
    border: 'border-l-4 border-blue-500',
    icon: mdiAccount,
    iconColor: 'text-blue-600 dark:text-blue-400',
    label: 'Servidor'
  },
  visitante: {
    background: 'bg-green-50 dark:bg-green-900/20',
    border: 'border-l-4 border-green-500',
    icon: mdiAccountGroup,
    iconColor: 'text-green-600 dark:text-green-400',
    label: 'Visitante'
  }
};

// Formatação de data
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date);
};

// Função para obter as classes de estilo baseadas no tipo
const getReservaClasses = (reserva) => {
  const tipo = reserva.tipo || 'usuario';
  const config = tipoReservaColors[tipo];
  return {
    background: config.background,
    border: config.border
  };
};

// Função para obter o ícone e cor do tipo
const getTipoConfig = (reserva) => {
  const tipo = reserva.tipo || 'usuario';
  return tipoReservaColors[tipo];
};

// Enviar formulário de busca
const submitSearch = () => {
  useForm({
    search: search.value,
    status: statusFilter.value
  }).get(route('admin.alojamento.index'), {
    preserveState: true,
    replace: true
  });
};

// Limpar filtros
const clearFilters = () => {
  search.value = '';
  statusFilter.value = '';
  submitSearch();
};

// Confirmar rejeição
const confirmarRejeicao = () => {
  // Validar motivo
  if (!motivoRejeicao.value || motivoRejeicao.value.trim().length < 5) {
    rejeicaoError.value = 'Por favor, forneça um motivo válido com pelo menos 5 caracteres.';
    return;
  }
  
  isSubmittingRejeicao.value = true;
  rejeicaoError.value = '';
  
  // Enviar requisição de rejeição
  const form = useForm({
    status: 'rejeitada',
    motivo_rejeicao: motivoRejeicao.value.trim()
  });
};

const closeConfirmModal = () => {
  showConfirmModal.value = false;
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Reservas de Alojamento" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiBedEmpty"
        title="Reservas de Alojamento"
        main
      >
        <BaseButton
          :route-name="route('admin.dashboard')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>      
      
      <!-- Legenda de Cores -->
      <CardBox class="mb-6">
        <div class="flex flex-wrap items-center gap-4">
          <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Legenda:</div>
          <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <BaseButton :icon="mdiAccount" small color="info" outline class="!p-1" />
            <span class="text-sm text-blue-600 dark:text-blue-400 font-medium">Servidor PCPB</span>
          </div>
          <div class="flex items-center gap-2 px-3 py-1 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
            <BaseButton :icon="mdiAccountGroup" small color="success" outline class="!p-1" />
            <span class="text-sm text-green-600 dark:text-green-400 font-medium">Visitante</span>
          </div>
        </div>
      </CardBox>

      <!-- Filtros -->
      <CardBox class="mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <FormField label="Buscar" :icon="mdiMagnify">
            <input
              v-model="search"
              placeholder="Nome, matrícula ou email"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
              @keyup.enter="submitSearch"
            />
          </FormField>
          
          <!-- Filtro de status -->
          <FormField label="Status">
            <select
              v-model="statusFilter"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
              @change="submitSearch"
            >
              <option value="">Todos os Status</option>
              <option value="pendente">Pendente</option>
              <option value="aprovada">Aprovada</option>
              <option value="rejeitada">Rejeitada</option>
            </select>
          </FormField>
          
          <!-- Botões de ação -->
          <div class="flex items-end justify-end">
            <BaseButton
              @click="submitSearch"
              label="Filtrar"
              color="info"
              class="mr-2"
            />
            <BaseButton
              @click="clearFilters"
              label="Limpar"
              color="white"
            />
          </div>
        </div>
      </CardBox>

      <!-- Tabela de Reservas -->
      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Tipo</th>
              <th>Nome</th>
              <th>Órgão</th>
              <th>Período</th>
              <th>Status</th>
              <th>Ver</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="reserva in props.reservas.data" 
              :key="reserva.id"
              :class="[getReservaClasses(reserva).background, getReservaClasses(reserva).border]"
            >
              <td data-label="ID">{{ reserva.id }}</td>
              <td data-label="Tipo" class="lg:w-1">
                <div class="flex items-center gap-2">
                  <BaseButton 
                    :icon="getTipoConfig(reserva).icon"
                    :color="reserva.tipo === 'visitante' ? 'success' : 'info'"
                    outline
                    small
                    :title="getTipoConfig(reserva).label"
                    class="!p-1"
                  />
                  <span class="text-xs font-medium" :class="getTipoConfig(reserva).iconColor">
                    {{ getTipoConfig(reserva).label }}
                  </span>
                </div>
              </td>
              <td data-label="Nome">
                <div>{{ reserva.nome }}</div>
                <small class="text-gray-500 dark:text-gray-400">{{ reserva.matricula }}</small>
              </td>
              <td data-label="Órgão">
                {{ reserva.orgao }}
              </td>
              <td data-label="Período">
                {{ formatDate(reserva.data_inicial) }} a {{ formatDate(reserva.data_final) }}
              </td>
              <td data-label="Status" class="lg:w-1">
                <BaseButton
                  :color="statusColors[reserva.status]"
                  :label="statusLabels[reserva.status]"
                  small
                  :rounded="true"
                />
              </td>
              <td data-label="Ações" class="lg:w-1 whitespace-nowrap">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    :route-name="getShowRoute(reserva)"
                    :icon="mdiEye"
                    small
                    color="info"
                    outline
                    title="Ver detalhes"
                  />
                </BaseButtons>
              </td>
            </tr>
            
            <!-- Mensagem de "Sem resultados" -->
            <tr v-if="props.reservas.data.length === 0">
              <td colspan="7" class="text-center py-4">
                Nenhuma reserva encontrada com os filtros selecionados.
              </td>
            </tr>
          </tbody>
        </table>
      </CardBox>
      
      <!-- Paginação -->
      <div class="mt-6" v-if="props.reservas.links && props.reservas.links.length > 3">
        <CardBox>
          <div class="flex items-center justify-between">
            <small>Mostrando {{ props.reservas.from }} a {{ props.reservas.to }} de {{ props.reservas.total }} resultados</small>
            <div class="flex">
              <Link
                v-for="(link, i) in props.reservas.links"
                :key="i"
                :href="link.url"
                v-html="link.label"
                class="px-3 py-1 border rounded text-sm mx-1"
                :class="[
                  link.active ? 'border-blue-500 bg-blue-50 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300',
                  { 'opacity-50 cursor-not-allowed': !link.url }
                ]"
              ></Link>
            </div>
          </div>
        </CardBox>
      </div>
      
      <!-- Modal de Confirmação Padrão -->
      <div 
        v-if="showConfirmModal" 
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
      >
        <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
          <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">{{ confirmModalTitle }}</h3>
            <div class="mt-2 px-7 py-3">
              <p class="text-sm text-gray-500 dark:text-gray-300">{{ confirmModalMessage }}</p>
            </div>
            <div class="flex justify-center gap-4 mt-4">
              <BaseButton 
                @click="confirmModalAction" 
                label="Confirmar"
                color="info"
              />
              <BaseButton 
                @click="closeConfirmModal" 
                label="Cancelar"
                color="white"
              />
            </div>
          </div>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>