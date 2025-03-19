<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import BaseButton from "@/Components/BaseButton.vue"
import {
  mdiAccount,
  mdiArrowLeftBoldOutline,
  mdiCheckCircle,
  mdiCloseCircle,
  mdiRefresh
} from "@mdi/js"

// Props
const props = defineProps({
  reserva: Object
});

// Toast
const { toast } = useToast();

// Estado local
const selectedStatus = ref(props.reserva.status);
const isChangingStatus = ref(false);

// Status de reservas
const statusLabels = {
  pendente: 'Pendente',
  aprovada: 'Aprovada',
  rejeitada: 'Rejeitada'
};

const statusClasses = {
  pendente: 'bg-yellow-100 text-yellow-800',
  aprovada: 'bg-green-100 text-green-800',
  rejeitada: 'bg-red-100 text-red-800'
};

// Modal de confirmação padrão
const showConfirmModal = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref(() => {});

// Modal de rejeição
const showRejeicaoModal = ref(false);
const motivoRejeicao = ref('');
const isSubmittingRejeicao = ref(false);
const rejeicaoError = ref('');

// Formatação de endereço
const enderecoFormatado = computed(() => {
  if (!props.reserva.endereco) return 'Não informado';
  
  try {
    const endereco = typeof props.reserva.endereco === 'string' 
      ? JSON.parse(props.reserva.endereco) 
      : props.reserva.endereco;
    
    if (!endereco) return 'Não informado';
    
    const partes = [];
    
    if (endereco.rua) {
      partes.push(`${endereco.rua}${endereco.numero ? ', ' + endereco.numero : ''}`);
    }
    
    if (endereco.bairro) {
      partes.push(endereco.bairro);
    }
    
    if (endereco.cidade) {
      partes.push(endereco.cidade);
    }
    
    return partes.length > 0 ? partes.join(' - ') : 'Não informado';
  } catch (e) {
    console.error('Erro ao formatar endereço:', e);
    return 'Erro ao formatar endereço';
  }
});

// Formatação de data
const formatDate = (dateString) => {
  if (!dateString) return 'Não informado';
  
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date);
};

// Duração da estadia
const duracaoEstadia = computed(() => {
  if (!props.reserva.data_inicial || !props.reserva.data_final) return 'Não disponível';
  
  const dataInicial = new Date(props.reserva.data_inicial);
  const dataFinal = new Date(props.reserva.data_final);
  
  const diffTime = Math.abs(dataFinal - dataInicial);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  return diffDays + (diffDays === 1 ? ' dia' : ' dias');
});

// Mostrar modal de rejeição
const showRejeicaoModalHandler = () => {
  motivoRejeicao.value = '';
  rejeicaoError.value = '';
  showRejeicaoModal.value = true;
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
  
  form.patch(route('admin.alojamento.alterar-status', props.reserva.id), {
    onSuccess: () => {
      showRejeicaoModal.value = false;
      isSubmittingRejeicao.value = false;
      toast.success('Reserva rejeitada com sucesso!');
      
      // Atualizar o status localmente
      props.reserva.status = 'rejeitada';
      props.reserva.motivo_rejeicao = motivoRejeicao.value.trim();
      selectedStatus.value = 'rejeitada';
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

// Alterar Status
const alterarStatus = (novoStatus) => {
  // Se for rejeição, abre o modal específico
  if (novoStatus === 'rejeitada') {
    showRejeicaoModalHandler();
    return;
  }
  
  confirmModalTitle.value = `Alterar Status para ${statusLabels[novoStatus]}`;
  confirmModalMessage.value = `Tem certeza que deseja alterar o status da reserva para ${novoStatus}?`;
  
  confirmModalAction.value = () => {
    isChangingStatus.value = true;
    
    const form = useForm({
      status: novoStatus
    });
    
    form.patch(route('admin.alojamento.alterar-status', props.reserva.id), {
      onSuccess: () => {
        showConfirmModal.value = false;
        isChangingStatus.value = false;
        toast.success(`Status alterado para ${novoStatus} com sucesso!`);
        
        // Atualizar o status localmente
        props.reserva.status = novoStatus;
      },
      onError: (errors) => {
        showConfirmModal.value = false;
        isChangingStatus.value = false;
        toast.error('Erro ao alterar status da reserva');
        console.error('Erro:', errors);
      }
    });
  };
  
  showConfirmModal.value = true;
};

// Ações diretas (sem passar pelo dropdown)
const aprovarReserva = () => {
  alterarStatus('aprovada');
};

const rejeitarReserva = () => {
  alterarStatus('rejeitada');
};

const retornarParaPendente = () => {
  alterarStatus('pendente');
};

const closeModal = () => {
  showConfirmModal.value = false;
};

const closeRejeicaoModal = () => {
  showRejeicaoModal.value = false;
};

</script>

<template>
  <LayoutAuthenticated>
    <Head title="Detalhes da Reserva" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiAccount"
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

      <!-- Seção de Status e Ações -->
      <CardBox class="mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h3 class="text-lg font-medium mb-2">Status da Reserva</h3>
            <span 
              :class="statusClasses[reserva.status]" 
              class="px-3 py-1 text-sm font-medium rounded-full"
            >
              {{ statusLabels[reserva.status] }}
            </span>
          </div>
          
          <div class="flex flex-col sm:flex-row gap-3">
            <!-- Ações diretas baseadas no status atual -->
            <div v-if="reserva.status === 'pendente'" class="flex gap-2">
              <BaseButton
                @click="aprovarReserva"
                :icon="mdiCheckCircle"
                label="Aprovar"
                color="success"
                :disabled="isChangingStatus"
              />
              <BaseButton
                @click="rejeitarReserva"
                :icon="mdiCloseCircle"
                label="Rejeitar"
                color="danger"
                :disabled="isChangingStatus"
              />
            </div>

            <div v-if="reserva.status === 'aprovada'" class="flex gap-2">
              <BaseButton
                @click="rejeitarReserva"
                :icon="mdiCloseCircle"
                label="Rejeitar"
                color="danger"
                :disabled="isChangingStatus"
              />
              <BaseButton
                @click="retornarParaPendente"
                :icon="mdiRefresh"
                label="Retornar para Pendente"
                color="info"
                :disabled="isChangingStatus"
              />
            </div>

            <div v-if="reserva.status === 'rejeitada'" class="flex gap-2">
              <BaseButton
                @click="aprovarReserva"
                :icon="mdiCheckCircle"
                label="Aprovar"
                color="success"
                :disabled="isChangingStatus"
              />
              <BaseButton
                @click="retornarParaPendente"
                :icon="mdiRefresh"
                label="Retornar para Pendente"
                color="info"
                :disabled="isChangingStatus"
              />
            </div>
          </div>
        </div>
      </CardBox>

      <!-- Informações da Reserva -->
      <CardBox class="mb-6">
        <h3 class="text-lg font-medium mb-4">Informações da Reserva</h3>
        
        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <h4 class="font-medium text-gray-500 dark:text-gray-400 mb-2">Dados Pessoais</h4>
            <table class="w-full">
              <tbody>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Nome</td>
                  <td>{{ reserva.nome }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Matrícula</td>
                  <td>{{ reserva.matricula }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Cargo</td>
                  <td>{{ reserva.cargo }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Órgão</td>
                  <td>{{ reserva.orgao }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">CPF</td>
                  <td>{{ reserva.cpf }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Condição</td>
                  <td>{{ reserva.condicao }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div>
            <h4 class="font-medium text-gray-500 dark:text-gray-400 mb-2">Contato e Endereço</h4>
            <table class="w-full">
              <tbody>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Email</td>
                  <td>{{ reserva.email }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Telefone</td>
                  <td>{{ reserva.telefone }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Endereço</td>
                  <td>{{ enderecoFormatado }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </CardBox>
      
      <!-- Detalhes da Estadia -->
      <CardBox class="mb-6">
        <h3 class="text-lg font-medium mb-4">Detalhes da Estadia</h3>
        <table class="w-full">
          <tbody>
            <tr>
              <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Data Inicial</td>
              <td>{{ formatDate(reserva.data_inicial) }}</td>
            </tr>
            <tr>
              <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Data Final</td>
              <td>{{ formatDate(reserva.data_final) }}</td>
            </tr>
            <tr>
              <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Duração</td>
              <td>{{ duracaoEstadia }}</td>
            </tr>
            <tr>
              <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Motivo da Solicitação</td>
              <td>{{ reserva.motivo }}</td>
            </tr>
            <tr v-if="reserva.status === 'rejeitada' && reserva.motivo_rejeicao">
              <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Motivo da Rejeição</td>
              <td class="text-red-600">{{ reserva.motivo_rejeicao }}</td>
            </tr>
          </tbody>
        </table>
      </CardBox>
      
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
                :loading="isChangingStatus"
              />
              <BaseButton 
                @click="closeModal" 
                label="Cancelar"
                color="white"
                :disabled="isChangingStatus"
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
                Você está prestes a rejeitar a reserva de <span class="font-semibold">{{ reserva.nome }}</span>. 
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