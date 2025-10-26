<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import BaseButton from '@/Components/BaseButton.vue';
import CardBox from '@/Components/CardBox.vue';
import ModalControleOcupacao from '@/Pages/Components/ModalControleOcupacao.vue';
import {
  mdiArrowLeftBoldOutline,
  mdiBedEmpty,
  mdiMagnify,
  mdiEye,
  mdiCheckCircle,
  mdiCloseCircle,
  mdiSwapHorizontal,
  mdiAccount,
  mdiAccountGroup,
  mdiHomeCity,
  mdiLogout,
  mdiLogin,
  mdiBed,
  mdiCheck,
  mdiViewGrid,
  mdiRefresh,
} from '@mdi/js';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import FormField from '@/Components/FormField.vue';
import BaseButtons from '@/Components/BaseButtons.vue';

// Props
const props = defineProps({
  reservas: Object,
  filters: Object,
  estatisticas: Object,
});

const getShowRoute = reserva => {
  if (reserva.tipo === 'visitante') {
    return route('admin.visitante.show', reserva.id);
  } else {
    return route('admin.alojamento.show', reserva.id);
  }
};

// Toast
const { toast } = useToast();

// Estado local
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const ocupacaoFilter = ref(props.filters?.ocupacao || '');

//Estados para o modal de controle de ocupação
const showModalOcupacao = ref(false);
const loadingOcupacao = ref(false);
const dormitoriosData = ref([]);
const estatisticasOcupacao = ref({});

// Modal de confirmação padrão
const showConfirmModal = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref(() => {});

// Status de reservas
const statusLabels = {
  pendente: 'Pendente',
  aprovada: 'Aprovada',
  rejeitada: 'Rejeitada',
};

const statusColors = {
  pendente: 'warning',
  aprovada: 'success',
  rejeitada: 'danger',
};

//Status de ocupação incluindo checkout
const ocupacaoLabels = {
  sem_checkin: 'Sem Check-in',
  com_checkin: 'Com Check-in',
  checkout_realizado: 'Check-out Realizado',
  disponivel: 'Disponível',
};

const ocupacaoColors = {
  sem_checkin: 'info',
  com_checkin: 'success',
  checkout_realizado: 'white',
  disponivel: 'white',
};

// Cores para tipos de reserva
const tipoReservaColors = {
  usuario: {
    border: 'border-l-4 border-blue-500',
    icon: mdiAccount,
    iconColor: 'text-blue-600 dark:text-blue-400',
    label: 'Servidor',
  },
  visitante: {
    border: 'border-l-4 border-green-500',
    icon: mdiAccountGroup,
    iconColor: 'text-green-600 dark:text-green-400',
    label: 'Visitante',
  },
};

// Função para determinar status de ocupação incluindo checkout
const getOcupacaoStatus = reserva => {
  if (reserva.status !== 'aprovada') {
    return 'disponivel';
  }

  // Verifica se tem ocupação ativa
  if (reserva.ocupacao_info || reserva.tem_ocupacao_ativa) {
    return 'com_checkin';
  }

  // Verifica se já teve ocupação mas não tem mais (checkout realizado)
  if (reserva.status === 'aprovada' && !reserva.tem_ocupacao_ativa) {
    if (reserva.checkout_realizado || reserva.teve_ocupacao_anterior) {
      return 'checkout_realizado';
    }

    return 'sem_checkin';
  }

  return 'disponivel';
};

//Função para obter ícone de ocupação incluindo checkout
const getOcupacaoIcon = reserva => {
  const status = getOcupacaoStatus(reserva);

  switch (status) {
    case 'com_checkin':
      return mdiBed;
    case 'sem_checkin':
      return mdiLogin;
    case 'checkout_realizado':
      return mdiCheck;
    default:
      return null;
  }
};

// Função para formatação de CPF
const formatCPF = cpf => {
  if (!cpf) return '';
  const cleaned = cpf.replace(/\D/g, '');
  return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
};

// Função para obter informações de ocupação
const getOcupacaoInfo = reserva => {
  if (reserva.ocupacao_info) {
    return {
      dormitorio: reserva.ocupacao_info.dormitorio_numero,
      vaga: reserva.ocupacao_info.numero_vaga,
      checkin: reserva.ocupacao_info.checkin_at,
    };
  }
  return null;
};

// Formatação de data
const formatDate = dateString => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date);
};

// Função para obter as classes de estilo baseadas no tipo
const getReservaClasses = reserva => {
  const tipo = reserva.tipo || 'usuario';
  const config = tipoReservaColors[tipo];
  return {
    border: config.border,
  };
};

// Função para obter o ícone e cor do tipo
const getTipoConfig = reserva => {
  const tipo = reserva.tipo || 'usuario';
  return tipoReservaColors[tipo];
};

// Enviar formulário de busca
const submitSearch = () => {
  const form = useForm({
    search: search.value,
    status: statusFilter.value,
    ocupacao: ocupacaoFilter.value,
  });

  form.get(route('admin.alojamento.index'), {
    preserveState: true,
    replace: true,
  });
};

// Limpar filtros
const clearFilters = () => {
  search.value = '';
  statusFilter.value = '';
  ocupacaoFilter.value = '';
  submitSearch();
};

const closeConfirmModal = () => {
  showConfirmModal.value = false;
};

// Função para abrir o modal de ocupação
const abrirControleDormitorios = async () => {
  loadingOcupacao.value = true;
  try {
    // Buscar dados de ocupação
    const response = await fetch('/admin/ocupacao', {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (response.ok) {
      const data = await response.json();
      dormitoriosData.value = data.props.dormitorios || [];
      estatisticasOcupacao.value = data.props.estatisticas || {};
      showModalOcupacao.value = true;
    } else {
      toast.error('Erro ao carregar dados de ocupação');
    }
  } catch (error) {
    console.error('Erro:', error);
    toast.error('Erro ao carregar dados de ocupação');
  } finally {
    loadingOcupacao.value = false;
  }
};

// Função para fechar o modal de ocupação
const fecharModalOcupacao = () => {
  showModalOcupacao.value = false;
  dormitoriosData.value = [];
  estatisticasOcupacao.value = {};
};

// Função para atualizar dados do modal
const atualizarDadosOcupacao = async () => {
  await abrirControleDormitorios();
};

// Estatísticas calculadas incluindo checkout
const estatisticasCalculadas = computed(() => {
  const reservasData = props.reservas.data || [];

  return {
    total: reservasData.length,
    com_checkin: reservasData.filter(
      r => getOcupacaoStatus(r) === 'com_checkin'
    ).length,
    sem_checkin: reservasData.filter(
      r => getOcupacaoStatus(r) === 'sem_checkin'
    ).length,
    checkout_realizado: reservasData.filter(
      r => getOcupacaoStatus(r) === 'checkout_realizado'
    ).length,
    pendentes: reservasData.filter(r => r.status === 'pendente').length,
  };
});
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
        <div class="flex gap-3">
          <!-- Botão para Controle de Dormitórios -->
          <BaseButton
            @click="abrirControleDormitorios"
            :icon="mdiViewGrid"
            label="Controle de Dormitórios"
            color="success"
            rounded-full
            :disabled="loadingOcupacao"
            title="Abrir painel de controle de ocupação dos dormitórios"
          />

          <BaseButton
            :route-name="route('admin.dashboard')"
            :icon="mdiArrowLeftBoldOutline"
            label="Voltar"
            color="white"
            rounded-full
            small
          />
        </div>
      </SectionTitleLineWithButton>

      <!-- Estatísticas rápidas incluindo checkout -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <CardBox>
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              {{ estatisticasCalculadas.total }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Total de Reservas
            </div>
          </div>
        </CardBox>

        <CardBox>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
              {{ estatisticasCalculadas.com_checkin }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Com Check-in
            </div>
          </div>
        </CardBox>

        <CardBox>
          <div class="text-center">
            <div
              class="text-2xl font-bold text-orange-600 dark:text-orange-400"
            >
              {{ estatisticasCalculadas.sem_checkin }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Sem Check-in
            </div>
          </div>
        </CardBox>

        <!-- Estatística de checkout -->
        <CardBox>
          <div class="text-center">
            <div
              class="text-2xl font-bold text-purple-600 dark:text-purple-400"
            >
              {{ estatisticasCalculadas.checkout_realizado }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Check-out Realizado
            </div>
          </div>
        </CardBox>

        <CardBox>
          <div class="text-center">
            <div
              class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"
            >
              {{ estatisticasCalculadas.pendentes }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Pendentes
            </div>
          </div>
        </CardBox>
      </div>

      <!-- Legenda de Cores -->
      <CardBox class="mb-6">
        <div class="flex flex-wrap items-center gap-4">
          <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Legenda:
          </div>
          <div
            class="flex items-center gap-2 px-3 py-1 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800"
          >
            <BaseButton
              :icon="mdiAccount"
              small
              color="info"
              outline
              class="!p-1"
            />
            <span class="text-sm text-blue-600 dark:text-blue-400 font-medium"
              >Servidor PCPB</span
            >
          </div>
          <div
            class="flex items-center gap-2 px-3 py-1 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"
          >
            <BaseButton
              :icon="mdiAccountGroup"
              small
              color="success"
              outline
              class="!p-1"
            />
            <span class="text-sm text-green-600 dark:text-green-400 font-medium"
              >Visitante Externo</span
            >
          </div>
          <div
            class="flex items-center gap-2 px-3 py-1 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800"
          >
            <BaseButton
              :icon="mdiBed"
              small
              color="success"
              outline
              class="!p-1"
            />
            <span class="text-sm text-red-600 dark:text-red-400 font-medium"
              >Com Check-in Ativo</span
            >
          </div>
          <!-- Legenda de checkout -->
          <div
            class="flex items-center gap-2 px-3 py-1 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800"
          >
            <BaseButton
              :icon="mdiCheck"
              small
              color="white"
              outline
              class="!p-1"
            />
            <span
              class="text-sm text-purple-600 dark:text-purple-400 font-medium"
              >Check-out Realizado</span
            >
          </div>
        </div>
      </CardBox>

      <!--Filtros -->
      <CardBox class="mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
          <FormField label="Buscar por CPF" :icon="mdiMagnify">
            <input
              v-model="search"
              placeholder="Informe o CPF"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
              @keyup.enter="submitSearch"
            />
          </FormField>

          <!-- Filtro de status -->
          <FormField label="Status da Reserva">
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

          <!--  Filtro de ocupação incluindo checkout -->
          <FormField label="Status de Ocupação">
            <select
              v-model="ocupacaoFilter"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
              @change="submitSearch"
            >
              <option value="">Todos</option>
              <option value="com_checkin">Com Check-in</option>
              <option value="sem_checkin">Sem Check-in</option>
              <option value="checkout_realizado">Check-out Realizado</option>
              <!-- <option value="disponivel">Apenas Disponíveis</option> -->
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
            <BaseButton @click="clearFilters" label="Limpar" color="white" />
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
              <th>Nome / CPF</th>
              <th>Órgão</th>
              <th>Período</th>
              <th>Status</th>
              <th>Ocupação</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="reserva in props.reservas.data"
              :key="reserva.id"
              :class="getReservaClasses(reserva).border"
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
                  <span
                    class="text-xs font-medium"
                    :class="getTipoConfig(reserva).iconColor"
                  >
                    {{ getTipoConfig(reserva).label }}
                  </span>
                </div>
              </td>
              <!-- Nome e CPF -->
              <td data-label="Nome / CPF">
                <div>{{ reserva.nome }}</div>
                <small class="text-gray-500 dark:text-gray-400">{{
                  formatCPF(reserva.cpf)
                }}</small>
                <small class="text-gray-500 dark:text-gray-400 block">{{
                  reserva.matricula
                }}</small>
              </td>
              <td data-label="Órgão">
                {{ reserva.orgao }}
              </td>
              <td data-label="Período">
                {{ formatDate(reserva.data_inicial) }} a
                {{ formatDate(reserva.data_final) }}
              </td>
              <td data-label="Status" class="lg:w-1">
                <BaseButton
                  :color="statusColors[reserva.status]"
                  :label="statusLabels[reserva.status]"
                  small
                  :rounded="true"
                />
              </td>
              <!-- Coluna de Ocupação incluindo checkout -->
              <td data-label="Ocupação" class="lg:w-1">
                <div class="flex items-center gap-2">
                  <BaseButton
                    v-if="getOcupacaoIcon(reserva)"
                    :icon="getOcupacaoIcon(reserva)"
                    :color="ocupacaoColors[getOcupacaoStatus(reserva)]"
                    :title="ocupacaoLabels[getOcupacaoStatus(reserva)]"
                    small
                    outline
                    class="!p-1"
                  />

                  <!-- Informações de ocupação se ativa -->
                  <div v-if="getOcupacaoInfo(reserva)" class="text-xs">
                    <div class="font-medium text-green-600 dark:text-green-400">
                      {{ getOcupacaoInfo(reserva).dormitorio }}
                    </div>
                    <div class="text-gray-500 dark:text-gray-400">
                      V: {{ getOcupacaoInfo(reserva).vaga }}
                    </div>
                  </div>

                  <!-- Status de checkout realizado -->
                  <span
                    v-else-if="
                      getOcupacaoStatus(reserva) === 'checkout_realizado'
                    "
                    class="text-xs text-purple-600 dark:text-purple-400 font-medium"
                  >
                    Finalizado
                  </span>

                  <!-- Status sem ocupação -->
                  <span
                    v-else-if="getOcupacaoStatus(reserva) === 'sem_checkin'"
                    class="text-xs text-orange-600 dark:text-orange-400"
                  >
                    Aguardando
                  </span>

                  <!-- Status disponível -->
                  <span v-else class="text-xs text-gray-500 dark:text-gray-400">
                    -
                  </span>
                </div>
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
              <td colspan="8" class="text-center py-4">
                Nenhuma reserva encontrada com os filtros selecionados.
              </td>
            </tr>
          </tbody>
        </table>
      </CardBox>

      <!-- Paginação -->
      <div
        class="mt-6"
        v-if="props.reservas.links && props.reservas.links.length > 3"
      >
        <CardBox>
          <div class="flex items-center justify-between">
            <small
              >Mostrando {{ props.reservas.from }} a {{ props.reservas.to }} de
              {{ props.reservas.total }} resultados</small
            >
            <div class="flex">
              <Link
                v-for="(link, i) in props.reservas.links"
                :key="i"
                :href="link.url"
                v-html="link.label"
                class="px-3 py-1 border rounded text-sm mx-1"
                :class="[
                  link.active
                    ? 'border-blue-500 bg-blue-50 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400'
                    : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300',
                  { 'opacity-50 cursor-not-allowed': !link.url },
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
        <div
          class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800"
        >
          <div class="mt-3 text-center">
            <h3
              class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
            >
              {{ confirmModalTitle }}
            </h3>
            <div class="mt-2 px-7 py-3">
              <p class="text-sm text-gray-500 dark:text-gray-300">
                {{ confirmModalMessage }}
              </p>
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

      <!-- Modal de Controle de Ocupação -->
      <ModalControleOcupacao
        :show="showModalOcupacao"
        :dormitorios="dormitoriosData"
        :estatisticas="estatisticasOcupacao"
        @close="fecharModalOcupacao"
        @refresh="atualizarDadosOcupacao"
      />
    </SectionMain>
  </LayoutAuthenticated>
</template>
