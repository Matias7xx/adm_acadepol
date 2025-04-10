<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import BaseButton from "@/Components/BaseButton.vue"
import axios from 'axios';
import {
  mdiAccount,
  mdiArrowLeftBoldOutline,
  mdiCheckCircle,
  mdiCloseCircle,
  mdiRefresh,
  mdiFileDocument,
  mdiFileDownload,
  mdiEye,
  mdiPrinter
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
const isGeneratingFicha = ref(false);

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

// Modal de visualização do documento
const showDocumentoModal = ref(false);
const documentoUrl = ref('');

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
      partes.push(`${endereco.cidade}${endereco.uf ? ' - ' + endereco.uf : ''}`);
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

// Verificar se há documento comprobatório
const hasDocumento = computed(() => {
  return props.reserva.documento_url ? true : false;
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
        selectedStatus.value = novoStatus;
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

// Visualizar documento
const visualizarDocumento = () => {
  if (props.reserva.documento_url) {
    documentoUrl.value = props.reserva.documento_url;
    showDocumentoModal.value = true;
  } else {
    toast.error('Documento não disponível');
  }
};

// Fechar modal de documento
const closeDocumentoModal = () => {
  showDocumentoModal.value = false;
  documentoUrl.value = '';
};

const closeModal = () => {
  showConfirmModal.value = false;
};

const closeRejeicaoModal = () => {
  showRejeicaoModal.value = false;
};

// Gerar ficha de hospedagem
const gerarFichaHospedagem = () => {
  if (props.reserva.status !== 'aprovada') {
    toast.error('Apenas reservas aprovadas podem gerar ficha de hospedagem');
    return;
  }

  isGeneratingFicha.value = true;
  
  // Abrir uma nova aba diretamente para a rota que gera o PDF
  const url = route('admin.alojamento.ficha', props.reserva.id);
  window.open(url, '_blank');
  
  isGeneratingFicha.value = false;
};

// Imprimir ficha de hospedagem
const imprimirFichaHospedagem = () => {
  if (props.reserva.status !== 'aprovada') {
    toast.error('Apenas reservas aprovadas podem imprimir ficha de hospedagem');
    return;
  }
  
  // Abrir a visualização da ficha para impressão
  const url = route('admin.alojamento.ficha.visualizar', props.reserva.id);
  
  // Abrir em uma nova aba
  const printWindow = window.open(url, '_blank');
  
  if (!printWindow) {
    toast.error('Seu navegador bloqueou a abertura da janela. Por favor, permita popups para este site.');
    return;
  }
  
  // Após carregar a página, acionar a impressão
  /* printWindow.onload = function() {
    setTimeout(() => {
      try {
        printWindow.print();
      } catch (e) {
        console.error('Erro ao imprimir:', e);
      }
    }, 1000); // Pequeno delay para garantir que o conteúdo seja carregado
  }; */
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
             <!--  <BaseButton
                @click="gerarFichaHospedagem"
                :icon="mdiFileDownload"
                label="Gerar Ficha"
                color="info"
                :loading="isGeneratingFicha"
                :disabled="isGeneratingFicha"
              /> -->
              <BaseButton
                @click="imprimirFichaHospedagem"
                :icon="mdiFileDownload"
                label="Gerar Ficha"
                color="info"
              />
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
                color="white"
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
                color="white"
                :disabled="isChangingStatus"
              />
            </div>
          </div>
        </div>
      </CardBox>

      <!-- Informações da Reserva -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Informações Pessoais -->
        <CardBox>
          <h3 class="text-lg font-medium mb-4">Informações Pessoais</h3>
          <div class="space-y-1">
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Nome:</span>
              <span class="col-span-2">{{ reserva.nome }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">CPF:</span>
              <span class="col-span-2">{{ reserva.cpf }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">RG:</span>
              <span class="col-span-2">{{ reserva.rg || 'Não informado' }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Órgão Expedidor:</span>
              <span class="col-span-2">{{ reserva.orgao_expedidor || 'Não informado' }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Data de Nascimento:</span>
              <span class="col-span-2">{{ reserva.data_nascimento ? formatDate(reserva.data_nascimento) : 'Não informado' }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Sexo:</span>
              <span class="col-span-2">{{ reserva.sexo === 'masculino' ? 'Masculino' : reserva.sexo === 'feminino' ? 'Feminino' : 'Não informado' }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Matrícula:</span>
              <span class="col-span-2">{{ reserva.matricula }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Cargo/Função:</span>
              <span class="col-span-2">{{ reserva.cargo }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Órgão/Instituição:</span>
              <span class="col-span-2">{{ reserva.orgao }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Condição:</span>
              <span class="col-span-2">{{ reserva.condicao }}</span>
            </div>
          </div>
        </CardBox>
          
        <!-- Contato e Endereço -->
        <CardBox>
          <h3 class="text-lg font-medium mb-4">Contato e Endereço</h3>
          <div class="space-y-1">
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Email:</span>
              <span class="col-span-2">{{ reserva.email }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Telefone:</span>
              <span class="col-span-2">{{ reserva.telefone }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Endereço:</span>
              <span class="col-span-2">{{ enderecoFormatado }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">UF:</span>
              <span class="col-span-2">{{ reserva.uf || 'Não informado' }}</span>
            </div>
          </div>
        </CardBox>
      </div>
      
      <!-- Detalhes da Estadia -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Período e Motivo -->
        <CardBox>
          <h3 class="text-lg font-medium mb-4">Detalhes da Estadia</h3>
          <div class="space-y-1">
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Data Inicial:</span>
              <span class="col-span-2">{{ formatDate(reserva.data_inicial) }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Data Final:</span>
              <span class="col-span-2">{{ formatDate(reserva.data_final) }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Duração:</span>
              <span class="col-span-2">{{ duracaoEstadia }}</span>
            </div>
          </div>
        </CardBox>
        
        <!-- Documento e Motivos -->
        <CardBox>
          <h3 class="text-lg font-medium mb-4">Documentação</h3>
          <div class="space-y-4">
            <div v-if="hasDocumento" class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
              <div class="flex items-center">
                <div class="bg-blue-100 dark:bg-blue-900 p-2 rounded-md mr-3">
                  <svg :class="mdiFileDocument" class="w-6 h-6 text-blue-600 dark:text-blue-300" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M6,2A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6M6,4H13V9H18V20H6V4M8,12V14H16V12H8M8,16V18H13V16H8Z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium">Documento Comprobatório</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Documento enviado pelo solicitante</p>
                </div>
              </div>
              <BaseButton
                @click="visualizarDocumento"
                :icon="mdiEye"
                label="Visualizar"
                color="info"
                small
              />
            </div>
            <div v-else class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg text-gray-500 dark:text-gray-400 italic">
              Nenhum documento comprobatório enviado.
            </div>
            
            <div class="pt-4">
              <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Motivo da Solicitação:</h4>
              <p class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">{{ reserva.motivo }}</p>
            </div>
            
            <div v-if="reserva.status === 'rejeitada' && reserva.motivo_rejeicao" class="pt-2">
              <h4 class="font-medium text-red-600 dark:text-red-400 mb-2">Motivo da Rejeição:</h4>
              <p class="p-3 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg">{{ reserva.motivo_rejeicao }}</p>
            </div>
          </div>
        </CardBox>
      </div>
      
      <!-- Informações Adicionais para quando aprovado -->
      <CardBox v-if="reserva.status === 'aprovada'" class="mb-6">
        <h3 class="text-lg font-medium mb-4">Informações para Check-in</h3>
        <div class="bg-green-50 dark:bg-green-900/30 p-4 rounded-lg text-green-800 dark:text-green-200">
          <div class="font-medium mb-2">Instruções para o Alojado:</div>
          <ul class="list-disc ml-5 space-y-1">
            <li>Horário de check-in: entre 8h e 18h</li>
            <li>Horário de check-out: até 12h do dia de saída</li>
            <li>Apresentar documento de identificação na chegada</li>
            <li>Informar que a ACADEPOL <strong>NÃO FORNECE</strong> materiais de higiene pessoal, lençóis, cobertores e toalhas</li>
            <li>Os quartos são compartilhados (beliches) e separados por sexo</li>
          </ul>
        </div>
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
      
      <!-- Modal de Visualização de Documento -->
      <div 
        v-if="showDocumentoModal" 
        class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
      >
        <div class="relative mx-auto p-0 border w-11/12 md:w-4/5 lg:w-3/4 h-5/6 shadow-lg rounded-md bg-white dark:bg-gray-800">
          <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Documento Comprobatório</h3>
            <button 
              @click="closeDocumentoModal" 
              class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white focus:outline-none"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="h-full p-2 bg-gray-100 dark:bg-gray-900 flex justify-center overflow-auto">
            <iframe 
              :src="documentoUrl" 
              class="w-full h-full border-0"
              allowfullscreen
            ></iframe>
          </div>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>