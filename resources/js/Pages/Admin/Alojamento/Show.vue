<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiBed,
  mdiInformationOutline,
  mdiArrowLeftBoldOutline,
  mdiCheck,
  mdiClose,
  mdiAlertBoxOutline,
} from "@mdi/js"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import BaseButton from "@/Components/BaseButton.vue"
import NotificationBar from "@/Components/NotificationBar.vue"
import CardBoxModal from "@/Components/CardBoxModal.vue"
import { ref, computed } from 'vue'
import { useToast } from '@/Composables/useToast'
import Toast from '@/Pages/Components/Toast.vue'

const props = defineProps({
  reserva: {
    type: Object,
    default: () => ({}),
  }
})

// Toast
const { toast } = useToast();

// Modal de rejeição
const isRejeicaoModalActive = ref(false)
const formRejeicao = useForm({
  motivo_rejeicao: ''
})

// Submeter formulário de rejeição
const submitRejeicao = () => {
  formRejeicao.patch(route('admin.alojamento.rejeitar', props.reserva.id), {
    onSuccess: () => {
      isRejeicaoModalActive.value = false
      toast.success('Reserva rejeitada com sucesso!');
      // Recarregar para atualizar os dados
      /* window.location.reload(); */
    },
    onError: (errors) => {
      toast.error('Erro ao rejeitar reserva');
      console.error(errors);
    }
  })
}

// Estado local para controlar o seletor de status
const selectedStatus = ref(props.reserva.status);
const isChangingStatus = ref(false);

// Status de alojamentos
const statusLabels = {
  pendente: 'Pendente',
  aprovada: 'Aprovada',
  rejeitada: 'Rejeitada'
};

// Cores para os status
function getStatusColor(status) {
  switch (status) {
    case 'pendente':
      return 'bg-yellow-100 text-yellow-800'
    case 'aprovada':
      return 'bg-green-100 text-green-800'
    case 'rejeitada':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

// Modal de confirmação
const showConfirmModal = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref(() => {});

// Função de confirmação para o modal
const handleConfirm = () => {
  if (typeof confirmModalAction.value === 'function') {
    confirmModalAction.value();
  } else {
    console.error('confirmModalAction não é uma função');
    closeConfirmModal();
  }
};

const closeConfirmModal = () => {
  showConfirmModal.value = false;
};

// Alternar Status
const alterarStatus = (novoStatus) => {
  const statusTexto = {
    pendente: 'pendente',
    aprovada: 'aprovada',
    rejeitada: 'rejeitada'
  };

  // Verificar se o status é diferente do atual
  if (novoStatus === props.reserva.status) {
    toast.info(`A reserva já está ${statusTexto[novoStatus]}`);
    return;
  }

  // Se o novo status for "rejeitada", abrir modal de rejeição
  if (novoStatus === 'rejeitada') {
    isRejeicaoModalActive.value = true;
    return;
  }

  confirmModalTitle.value = `Alterar Status para ${statusLabels[novoStatus]}`;
  confirmModalMessage.value = `Tem certeza que deseja alterar o status da reserva para ${statusTexto[novoStatus]}?`;
  
  confirmModalAction.value = () => {
    isChangingStatus.value = true;

    const form = useForm({
      status: novoStatus
    });
    
    // Para aprovação, usamos a rota específica
    if (novoStatus === 'aprovada') {
      form.patch(route('admin.alojamento.aprovar', props.reserva.id), {
        onSuccess: () => {
          closeConfirmModal();
          toast.success(`Reserva aprovada com sucesso!`);
          // Recarregar para atualizar o status
          /* window.location.reload(); */
        },
        onError: (errors) => {
          closeConfirmModal();
          toast.error('Erro ao aprovar reserva');
          console.error(errors);
          isChangingStatus.value = false;
        }
      });
    } else {
      form.patch(route('admin.alojamento.alterar-status', props.reserva.id), {
        onSuccess: () => {
          closeConfirmModal();
          toast.success(`Status alterado para ${statusTexto[novoStatus]} com sucesso!`);
          // Recarregar para atualizar o status
          /* window.location.reload(); */
        },
        onError: (errors) => {
          closeConfirmModal();
          toast.error('Erro ao alterar status da reserva');
          console.error(errors);
          isChangingStatus.value = false;
        }
      });
    }
  };
  
  showConfirmModal.value = true;
};

// Formatar data
function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-BR')
}

// Calcular duração da estadia
function calcularDuracaoEstadia(dataInicial, dataFinal) {
  if (!dataInicial || !dataFinal) return '0 dias'
  
  const inicio = new Date(dataInicial)
  const fim = new Date(dataFinal)
  const diffTime = Math.abs(fim - inicio)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
  
  return `${diffDays} dia${diffDays !== 1 ? 's' : ''}`
}
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Detalhes da Reserva" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiBed"
        title="Detalhes da Reserva de Alojamento"
        main
      >
        <BaseButton
          :route-name="route('admin.alojamento.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>
      
      <!-- Notificações -->
      <NotificationBar
        :key="Date.now()"
        v-if="$page.props.flash.message"
        color="success"
        :icon="mdiAlertBoxOutline"
      >
        {{ $page.props.flash.message }}
      </NotificationBar>
      <NotificationBar
        :key="Date.now() + 1"
        v-if="$page.props.flash.error"
        color="danger"
        :icon="mdiAlertBoxOutline"
      >
        {{ $page.props.flash.error }}
      </NotificationBar>
      
      <!-- Card com status e ações -->
      <CardBox class="mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h3 class="text-lg font-medium mb-2">Status da Reserva</h3>
            <span 
              :class="['px-3 py-1 text-sm font-medium rounded-full', getStatusColor(reserva.status)]"
            >
              {{ statusLabels[reserva.status] || reserva.status.charAt(0).toUpperCase() + reserva.status.slice(1) }}
            </span>
          </div>
          
          <div class="w-full md:w-auto">
            <h3 class="text-lg font-medium mb-2">Alterar Status</h3>
            <div class="flex flex-col sm:flex-row gap-3">
              <select 
                v-model="selectedStatus" 
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                :disabled="isChangingStatus"
              >
                <option value="pendente">Pendente</option>
                <option value="aprovada">Aprovada</option>
                <option value="rejeitada">Rejeitada</option>
              </select>
              
              <BaseButton
                @click="alterarStatus(selectedStatus)"
                label="Atualizar Status"
                color="info"
                :loading="isChangingStatus"
                :disabled="selectedStatus === reserva.status || isChangingStatus"
              />
            </div>
          </div>
        </div>
        
        <div v-if="reserva.status === 'rejeitada' && reserva.motivo_rejeicao" class="mt-6 p-4 bg-red-50 rounded-lg border border-red-200">
          <p class="font-medium text-red-800 mb-1">Motivo da rejeição:</p>
          <p class="text-red-700">{{ reserva.motivo_rejeicao }}</p>
        </div>
      </CardBox>
      
      <!-- Informações do solicitante -->
      <CardBox class="mb-6">
        <div class="font-medium text-lg border-b pb-2 mb-4">Informações do Solicitante</div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <table class="w-full">
              <tbody>
                <tr>
                  <td class="py-2 text-gray-600 font-medium w-1/3">Nome</td>
                  <td class="py-2">{{ reserva.nome }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 font-medium">Matrícula</td>
                  <td class="py-2">{{ reserva.matricula }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 font-medium">Cargo</td>
                  <td class="py-2">{{ reserva.cargo }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 font-medium">Órgão</td>
                  <td class="py-2">{{ reserva.orgao }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 font-medium">CPF</td>
                  <td class="py-2">{{ reserva.cpf }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div>
            <table class="w-full">
              <tbody>
                <tr>
                  <td class="py-2 text-gray-600 font-medium w-1/3">E-mail</td>
                  <td class="py-2">{{ reserva.email }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 font-medium">Telefone</td>
                  <td class="py-2">{{ reserva.telefone }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 font-medium">Condição</td>
                  <td class="py-2">{{ reserva.condicao }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 font-medium">Data do Pedido</td>
                  <td class="py-2">{{ new Date(reserva.created_at).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </CardBox>
      
      <!-- Detalhes da Estadia -->
      <CardBox class="mb-6">
        <div class="font-medium text-lg border-b pb-2 mb-4">Detalhes da Estadia</div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <table class="w-full">
              <tbody>
                <tr>
                  <td class="py-2 text-gray-600 font-medium w-1/3">Check-in</td>
                  <td class="py-2">{{ formatDate(reserva.data_inicial) }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 font-medium">Check-out</td>
                  <td class="py-2">{{ formatDate(reserva.data_final) }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 font-medium">Duração</td>
                  <td class="py-2">{{ calcularDuracaoEstadia(reserva.data_inicial, reserva.data_final) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </CardBox>
      
      <!-- Motivo da Solicitação -->
      <CardBox class="mb-6">
        <div class="font-medium text-lg border-b pb-2 mb-4">Motivo da Solicitação</div>
        <div class="prose max-w-none">
          <p>{{ reserva.motivo }}</p>
        </div>
      </CardBox>
      
      <!-- Modal de rejeição -->
      <CardBoxModal
        v-model="isRejeicaoModalActive"
        title="Rejeitar Solicitação"
        button="danger"
        buttonLabel="Confirmar Rejeição"
        :buttonDisabled="formRejeicao.processing || !formRejeicao.motivo_rejeicao"
        @confirm="submitRejeicao"
      >
        <div class="space-y-4">
          <p>Informe o motivo da rejeição desta solicitação de alojamento:</p>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Motivo da rejeição</label>
            <textarea
              v-model="formRejeicao.motivo_rejeicao"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              rows="5"
              placeholder="Descreva o motivo da rejeição..."
              required
            ></textarea>
            <div v-if="formRejeicao.errors.motivo_rejeicao" class="text-red-600 mt-1 text-sm">
              {{ formRejeicao.errors.motivo_rejeicao }}
            </div>
          </div>
        </div>
      </CardBoxModal>
      
      <!-- Modal de Confirmação -->
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
                @click="handleConfirm" 
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