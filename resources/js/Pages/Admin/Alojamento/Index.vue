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
  mdiSwapHorizontal
} from "@mdi/js"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import FormField from "@/Components/FormField.vue"
import BaseButtons from "@/Components/BaseButtons.vue"

// Props
const props = defineProps({
  reservas: Object,
  filters: Object
});

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

// Modal de rejeição
const showRejeicaoModal = ref(false);
const reservaParaRejeitar = ref(null);
const motivoRejeicao = ref('');
const isSubmittingRejeicao = ref(false);
const rejeicaoError = ref('');

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

// Formatação de data
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date);
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

// Funções de Ação
const alterarStatus = (id, novoStatus, mensagemConfirmacao) => {
  // Se for rejeição, abre o modal específico
  if (novoStatus === 'rejeitada') {
    const reserva = props.reservas.data.find(r => r.id === id);
    if (reserva) {
      reservaParaRejeitar.value = reserva;
      motivoRejeicao.value = '';
      rejeicaoError.value = '';
      showRejeicaoModal.value = true;
    }
    return;
  }
  
  const mensagens = {
    aprovada: 'aprovar',
    pendente: 'retornar para pendente'
  };
  
  confirmModalTitle.value = `${mensagens[novoStatus].charAt(0).toUpperCase() + mensagens[novoStatus].slice(1)} Reserva`;
  confirmModalMessage.value = mensagemConfirmacao || `Tem certeza que deseja ${mensagens[novoStatus]} esta reserva?`;
  
  // Define a função de confirmação diretamente
  confirmModalAction.value = () => {
    // Criar novo form para cada solicitação
    const form = useForm({
      status: novoStatus
    });
    
    form.patch(route('admin.alojamento.alterar-status', id), {
      onSuccess: () => {
        closeConfirmModal();
        toast.success(`Reserva ${novoStatus === 'aprovada' ? 'aprovada' : 'atualizada'} com sucesso!`);
        
        // Atualiza o item na lista local em vez de recarregar a página
        const index = props.reservas.data.findIndex(r => r.id === id);
        if (index !== -1) {
          props.reservas.data[index].status = novoStatus;
        }
      },
      onError: (errors) => {
        closeConfirmModal();
        toast.error(`Erro ao ${mensagens[novoStatus]} reserva`);
        console.error(errors);
      }
    });
  };
  
  showConfirmModal.value = true;
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
  
  form.patch(route('admin.alojamento.alterar-status', reservaParaRejeitar.value.id), {
    onSuccess: () => {
      showRejeicaoModal.value = false;
      isSubmittingRejeicao.value = false;
      toast.success('Reserva rejeitada com sucesso!');
      
      // Atualiza o item na lista local
      const index = props.reservas.data.findIndex(r => r.id === reservaParaRejeitar.value.id);
      if (index !== -1) {
        props.reservas.data[index].status = 'rejeitada';
        props.reservas.data[index].motivo_rejeicao = motivoRejeicao.value.trim();
      }
      
      // Limpa o estado
      reservaParaRejeitar.value = null;
      motivoRejeicao.value = '';
    },
    onError: (errors) => {
      isSubmittingRejeicao.value = false;
      if (errors.motivo_rejeicao) {
        rejeicaoError.value = errors.motivo_rejeicao;
      } else {
        rejeicaoError.value = 'Ocorreu um erro ao processar a rejeição. Por favor, tente novamente.';
        toast.error('Erro ao rejeitar reserva');
      }
      console.error('Erro ao rejeitar reserva:', errors);
    }
  });
};

const aprovarReserva = (id) => {
  alterarStatus(id, 'aprovada', 'Tem certeza que deseja aprovar esta reserva? O solicitante será notificado e terá acesso ao alojamento.');
};

const rejeitarReserva = (id) => {
  alterarStatus(id, 'rejeitada');
};

const retornarParaPendente = (id) => {
  alterarStatus(id, 'pendente', 'Tem certeza que deseja retornar esta reserva para pendente?');
};

const closeConfirmModal = () => {
  showConfirmModal.value = false;
};

const closeRejeicaoModal = () => {
  showRejeicaoModal.value = false;
  reservaParaRejeitar.value = null;
  motivoRejeicao.value = '';
  rejeicaoError.value = '';
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
              <th>Nome</th>
              <th>Órgão</th>
              <th>Período</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reserva in props.reservas.data" :key="reserva.id">
              <td data-label="ID">{{ reserva.id }}</td>
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
                    :route-name="route('admin.alojamento.show', reserva.id)"
                    :icon="mdiEye"
                    small
                    color="info"
                    outline
                    title="Ver detalhes"
                  />
                  
                  <!-- Ações para status pendente -->
                  <template v-if="reserva.status === 'pendente'">
                    <BaseButton
                      @click="aprovarReserva(reserva.id)"
                      :icon="mdiCheckCircle"
                      small
                      color="success"
                      outline
                      title="Aprovar"
                    />
                    <BaseButton
                      @click="rejeitarReserva(reserva.id)"
                      :icon="mdiCloseCircle"
                      small
                      color="danger"
                      outline
                      title="Rejeitar"
                    />
                  </template>
                  
                  <!-- Ações para status aprovado -->
                  <template v-if="reserva.status === 'aprovada'">
                    <BaseButton
                      @click="rejeitarReserva(reserva.id)"
                      :icon="mdiSwapHorizontal"
                      small
                      color="danger"
                      outline
                      title="Mudar para Rejeitado"
                    />
                    <BaseButton
                      @click="retornarParaPendente(reserva.id)"
                      :icon="mdiSwapHorizontal"
                      small
                      color="warning"
                      outline
                      title="Retornar para Pendente"
                    />
                  </template>
                  
                  <!-- Ações para status rejeitado -->
                  <template v-if="reserva.status === 'rejeitada'">
                    <BaseButton
                      @click="aprovarReserva(reserva.id)"
                      :icon="mdiSwapHorizontal"
                      small
                      color="success"
                      outline
                      title="Mudar para Aprovado"
                    />
                    <BaseButton
                      @click="retornarParaPendente(reserva.id)"
                      :icon="mdiSwapHorizontal"
                      small
                      color="warning"
                      outline
                      title="Retornar para Pendente"
                    />
                  </template>
                </BaseButtons>
              </td>
            </tr>
            
            <!-- Mensagem de "Sem resultados" -->
            <tr v-if="props.reservas.data.length === 0">
              <td colspan="6" class="text-center py-4">
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
      
      <!-- Modal de Rejeição -->
      <div 
        v-if="showRejeicaoModal" 
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
      >
        <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
          <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white text-center">Rejeitar Reserva</h3>
            <div class="mt-2 px-7 py-3">
              <p class="text-sm text-gray-500 dark:text-gray-300 mb-4">
                Você está prestes a rejeitar a reserva de <span class="font-semibold">{{ reservaParaRejeitar?.nome }}</span>. 
                Por favor, forneça um motivo para a rejeição:
              </p>
              
              <div class="mb-4">
                <label for="motivo_rejeicao" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Motivo da Rejeição *
                </label>
                <textarea
                  id="motivo_rejeicao"
                  v-model="motivoRejeicao"
                  rows="4"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  placeholder="Explique o motivo da rejeição. Esta informação será enviada ao solicitante."
                  :disabled="isSubmittingRejeicao"
                ></textarea>
                <p v-if="rejeicaoError" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ rejeicaoError }}
                </p>
              </div>
            </div>
            <div class="flex justify-center gap-4 mt-4">
              <BaseButton 
                @click="confirmarRejeicao" 
                label="Rejeitar Reserva"
                color="danger"
                :loading="isSubmittingRejeicao"
              />
              <BaseButton 
                @click="closeRejeicaoModal" 
                label="Cancelar"
                color="white"
                :disabled="isSubmittingRejeicao"
              />
            </div>
          </div>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>