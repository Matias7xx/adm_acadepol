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
  mdiPrinter,
  mdiAccountGroup,
  mdiOfficeBuilding,
  mdiBadgeAccount
} from "@mdi/js"

// Props
const props = defineProps({
  reserva: Object
});

// Verificação de segurança
if (!props.reserva) {
  console.error('Reserva não encontrada');
}

// Toast
const { toast } = useToast();

// Estado local
const selectedStatus = ref(props.reserva?.status || 'pendente');
const isChangingStatus = ref(false);
const isGeneratingFicha = ref(false);

// Verificar se é reserva de visitante
const isVisitante = computed(() => {
  if (!props.reserva) return false;
  return props.reserva.tipo_reserva === 'visitante' || props.reserva.tipo === 'visitante';
});

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
const documentoTitulo = ref('');

// Documentos disponíveis para visitantes
const documentosVisitante = computed(() => {
  if (!isVisitante.value || !props.reserva) return [];
  
  const docs = [];
  
  if (props.reserva.documento_url || props.reserva.documento_identidade_url) {
    docs.push({
      tipo: 'identidade',
      titulo: 'Documento de Identidade',
      url: props.reserva.documento_url || props.reserva.documento_identidade_url
    });
  }
  
  if (props.reserva.documento_funcional_url) {
    docs.push({
      tipo: 'funcional',
      titulo: 'Documento Funcional',
      url: props.reserva.documento_funcional_url
    });
  }
  
  if (props.reserva.documento_comprobatorio_url) {
    docs.push({
      tipo: 'comprobatorio',
      titulo: 'Documento Comprobatório',
      url: props.reserva.documento_comprobatorio_url
    });
  }
  
  return docs;
});

// Formatação de endereço
const enderecoFormatado = computed(() => {
  if (!props.reserva || !props.reserva.endereco) return 'Não informado';
  
  try {
    const endereco = typeof props.reserva.endereco === 'string' 
      ? JSON.parse(props.reserva.endereco) 
      : props.reserva.endereco;
    
    if (!endereco) return 'Não informado';
    
    const partes = [];
    
    if (endereco.rua) {
      partes.push(endereco.rua);
    }

    if (endereco.numero) {
      partes.push(endereco.numero);
    }
    
    if (endereco.bairro) {
      partes.push(endereco.bairro);
    }
    
    if (endereco.cidade) {
      let cidadeCompleta = endereco.cidade;

      if (isVisitante.value && endereco.uf) {
        cidadeCompleta += ' - ' + endereco.uf;
      } else if (!isVisitante.value && props.reserva.uf) {
        cidadeCompleta += ' - ' + props.reserva.uf;
      }
      partes.push(cidadeCompleta);
    }

    if (endereco.cep) {
      partes.push(endereco.cep);
    }
    
    return partes.length > 0 ? partes.join(' - ') : 'Não informado';
  } catch (e) {
    console.error('Erro ao formatar endereço:', e);
    return 'Erro ao formatar endereço';
  }
});

// Formatação para tipo de órgão de visitantes
const tipoOrgaoFormatado = computed(() => {
  if (!isVisitante.value || !props.reserva.tipo_orgao) return null;
  
  const tipos = {
    'federal': 'Órgão Federal',
    'estadual': 'Órgão Estadual',
    'municipal': 'Órgão Municipal',
    'privado': 'Empresa Privada',
    'outro': 'Outro'
  };
  
  return tipos[props.reserva.tipo_orgao] || props.reserva.tipo_orgao;
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
  if (!props.reserva || !props.reserva.data_inicial || !props.reserva.data_final) return 'Não disponível';
  
  const dataInicial = new Date(props.reserva.data_inicial);
  const dataFinal = new Date(props.reserva.data_final);
  
  const diffTime = Math.abs(dataFinal - dataInicial);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  return diffDays + (diffDays === 1 ? ' dia' : ' dias');
});

// Verificar se há documento comprobatório
const hasDocumento = computed(() => {
  if (isVisitante.value) {
    return documentosVisitante.value.length > 0;
  }
  return props.reserva.documento_url ? true : false;
});

// Rota baseada no tipo de reserva
const getRouteBasedOnType = (routeName) => {
  if (isVisitante.value) {
    return route(`admin.visitante.${routeName}`, props.reserva.id);
  }
  return route(`admin.alojamento.${routeName}`, props.reserva.id);
};

// Rota para voltar baseada no tipo
const getBackRoute = () => {
  // Sempre voltar para a página principal de alojamento
  return route('admin.alojamento.index');
};

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
  
  form.patch(getRouteBasedOnType('alterar-status'), {
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
    
    form.patch(getRouteBasedOnType('alterar-status'), {
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

// Ações diretas
const aprovarReserva = () => alterarStatus('aprovada');
const rejeitarReserva = () => alterarStatus('rejeitada');
const retornarParaPendente = () => alterarStatus('pendente');

// Visualizar documento
const visualizarDocumento = (documento = null) => {
  if (isVisitante.value && documento) {
    documentoUrl.value = documento.url;
    documentoTitulo.value = documento.titulo;
    showDocumentoModal.value = true;
  } else if (props.reserva.documento_url) {
    documentoUrl.value = props.reserva.documento_url;
    documentoTitulo.value = 'Documento Comprobatório';
    showDocumentoModal.value = true;
  } else {
    toast.error('Documento não disponível');
  }
};

// Fechar modais
const closeDocumentoModal = () => {
  showDocumentoModal.value = false;
  documentoUrl.value = '';
  documentoTitulo.value = '';
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

  // Usar rota específica para visitantes
  const url = isVisitante.value 
    ? route('admin.visitante.ficha', props.reserva.id)
    : route('admin.alojamento.ficha', props.reserva.id);
    
  window.open(url, '_blank');
};
</script>

<template>
  <LayoutAuthenticated>
    <Head :title="`Detalhes da Reserva ${isVisitante ? '(Visitante)' : '(Usuário)'}`" />
    
    <!-- Verificação se a reserva existe -->
    <div v-if="!reserva" class="p-6">
      <div class="bg-red-50 border border-red-200 rounded-md p-4">
        <h3 class="text-lg font-medium text-red-800">Reserva não encontrada</h3>
        <p class="text-red-600">A reserva solicitada não foi encontrada ou não existe.</p>
        <BaseButton
          :route-name="route('admin.alojamento.index')"
          label="Voltar para listagem"
          color="info"
          class="mt-4"
        />
      </div>
    </div>
    
    <!-- Conteúdo normal quando a reserva existe -->
    <SectionMain v-else>
      <SectionTitleLineWithButton
        :icon="isVisitante ? mdiAccountGroup : mdiAccount"
        :title="`Detalhes da Reserva de Alojamento ${isVisitante ? '- Visitante Externo' : '- Servidor PCPB'}`"
        main
      >
        <BaseButton
          :route-name="getBackRoute()"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>

      <!-- Indicador do tipo de reserva -->
      <CardBox class="mb-6">
        <div :class="isVisitante ? 'bg-green-50 dark:bg-green-900/30' : 'bg-blue-50 dark:bg-blue-900/30'" class="p-4 rounded-lg">
          <div class="flex items-center">
            <div :class="isVisitante ? 'bg-green-100 dark:bg-green-900' : 'bg-blue-100 dark:bg-blue-900'" class="p-2 rounded-md mr-3">
              <svg class="w-6 h-6" 
                   :class="isVisitante ? 'text-green-600 dark:text-green-300' : 'text-blue-600 dark:text-blue-300'" 
                   viewBox="0 0 24 24">
                <path fill="currentColor" :d="isVisitante ? 'M16,4A4,4 0 0,1 20,8A4,4 0 0,1 16,12A4,4 0 0,1 12,8A4,4 0 0,1 16,4M16,14C20.42,14 24,15.79 24,18V20H8V18C8,15.79 11.58,14 16,14M6,6A3,3 0 0,1 9,9A3,3 0 0,1 6,12A3,3 0 0,1 3,9A3,3 0 0,1 6,6M6,13C8.67,13 12,14.33 12,17V19H0V17C0,14.33 3.33,13 6,13Z' : 'M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z'" />
              </svg>
            </div>
            <div>
              <p :class="isVisitante ? 'text-green-800 dark:text-green-200' : 'text-blue-800 dark:text-blue-200'" 
                 class="font-medium">
                {{ isVisitante ? 'Reserva de Visitante Externo' : 'Reserva de Servidor da PCPB' }}
              </p>
              <p :class="isVisitante ? 'text-green-600 dark:text-green-300' : 'text-blue-600 dark:text-blue-300'" 
                 class="text-sm">
                {{ isVisitante ? 'Solicitação feita através do formulário público' : 'Solicitação feita por usuário autenticado' }}
              </p>
            </div>
          </div>
        </div>
      </CardBox>

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
                @click="gerarFichaHospedagem"
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
          <div class="flex items-center mb-4">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 mr-2" viewBox="0 0 24 24">
              <path fill="currentColor" d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" />
            </svg>
            <h3 class="text-lg font-medium">Informações Pessoais</h3>
          </div>
          <div class="space-y-3">
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Nome:</span>
              <span class="col-span-2 font-semibold">{{ reserva.nome }}</span>
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
              <span class="font-medium text-gray-600 dark:text-gray-300">
                {{ isVisitante ? 'Órgão Expedidor RG:' : 'Órgão Expedidor:' }}
              </span>
              <span class="col-span-2">{{ isVisitante ? (reserva.orgao_expedidor_rg || 'Não informado') : (reserva.orgao_expedidor || 'Não informado') }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Data de Nascimento:</span>
              <span class="col-span-2">{{ reserva.data_nascimento ? formatDate(reserva.data_nascimento) : 'Não informado' }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Sexo:</span>
              <span class="col-span-2">{{ reserva.sexo === 'masculino' ? 'Masculino' : reserva.sexo === 'feminino' ? 'Feminino' : 'Não informado' }}</span>
            </div>
          </div>
        </CardBox>
          
        <!-- Contato e Endereço -->
        <CardBox>
          <div class="flex items-center mb-4">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 mr-2" viewBox="0 0 24 24">
              <path fill="currentColor" d="M15,3V7.5L12.5,5.5L10,7.5V3H15M21,2H3V22H21V2M19,8H17V6H15V4H19V8M19,20H5V4H8V8L10.5,6L13,8V4H13V6H15V8H17V10H15V12H17V14H15V16H17V18H15V20H19V20M13,10H11V12H13V10M13,14H11V16H13V14M13,18H11V20H13V18M9,10H7V12H9V10M9,14H7V16H9V14M9,18H7V20H9V18Z" />
            </svg>
            <h3 class="text-lg font-medium">Contato e Endereço</h3>
          </div>
          <div class="space-y-3">
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
          </div>
        </CardBox>
      </div>

      <!-- Informações Profissionais -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Dados Profissionais -->
        <CardBox>
          <div class="flex items-center mb-4">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 mr-2" viewBox="0 0 24 24">
              <path fill="currentColor" d="M20,7H4A2,2 0 0,0 2,9V15A2,2 0 0,0 4,17H20A2,2 0 0,0 22,15V9A2,2 0 0,0 20,7M20,15H4V9H20V15M11.5,10A0.5,0.5 0 0,0 11,10.5V13.5A0.5,0.5 0 0,0 11.5,14H12.5A0.5,0.5 0 0,0 13,13.5V10.5A0.5,0.5 0 0,0 12.5,10H11.5Z" />
            </svg>
            <h3 class="text-lg font-medium">Informações Profissionais</h3>
          </div>
          <div class="space-y-3">
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">
                {{ isVisitante ? 'Matrícula Funcional:' : 'Matrícula:' }}
              </span>
              <span class="col-span-2">{{ isVisitante ? reserva.matricula_funcional : reserva.matricula }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Cargo/Função:</span>
              <span class="col-span-2">{{ reserva.cargo }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">
                {{ isVisitante ? 'Órgão de Trabalho:' : 'Órgão/Instituição:' }}
              </span>
              <span class="col-span-2">{{ isVisitante ? reserva.orgao_trabalho : reserva.orgao }}</span>
            </div>
            <div v-if="isVisitante && tipoOrgaoFormatado" class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Tipo de Órgão:</span>
              <span class="col-span-2">{{ tipoOrgaoFormatado }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Condição:</span>
              <span class="col-span-2">{{ reserva.condicao }}</span>
            </div>
          </div>
        </CardBox>
        
        <!-- Detalhes da Estadia -->
        <CardBox>
          <div class="flex items-center mb-4">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="text-lg font-medium">Detalhes da Estadia</h3>
          </div>
          <div class="space-y-3">
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Data Inicial:</span>
              <span class="col-span-2 font-semibold">{{ formatDate(reserva.data_inicial) }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Data Final:</span>
              <span class="col-span-2 font-semibold">{{ formatDate(reserva.data_final) }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300">Duração:</span>
              <span class="col-span-2 text-blue-600 dark:text-blue-400 font-medium">{{ duracaoEstadia }}</span>
            </div>
          </div>
        </CardBox>
      </div>
      
      <!-- Documentação e Motivo -->
      <CardBox class="mb-6">
        <div class="flex items-center mb-4">
          <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 mr-2" viewBox="0 0 24 24">
            <path fill="currentColor" d="M6,2A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6M6,4H13V9H18V20H6V4M8,12V14H16V12H8M8,16V18H13V16H8Z" />
          </svg>
          <h3 class="text-lg font-medium">Documentação e Motivo</h3>
        </div>
        
        <div class="space-y-6">
          <!-- Documentos para visitantes -->
          <div v-if="isVisitante && documentosVisitante.length > 0">
            <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Documentos Enviados:</h4>
            <div class="space-y-3">
              <div 
                v-for="documento in documentosVisitante" 
                :key="documento.tipo"
                class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600"
              >
                <div class="flex items-center">
                  <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-md mr-4">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" viewBox="0 0 24 24">
                      <path fill="currentColor" d="M6,2A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6M6,4H13V9H18V20H6V4M8,12V14H16V12H8M8,16V18H13V16H8Z" />
                    </svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ documento.titulo }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Documento enviado pelo visitante</p>
                  </div>
                </div>
                <BaseButton
                  @click="visualizarDocumento(documento)"
                  :icon="mdiEye"
                  label="Visualizar"
                  color="info"
                  small
                />
              </div>
            </div>
          </div>
          
          <!-- Documento para usuários do sistema -->
          <div v-else-if="!isVisitante && hasDocumento">
            <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Documento Comprobatório:</h4>
            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
              <div class="flex items-center">
                <div class="bg-green-100 dark:bg-green-900 p-3 rounded-md mr-4">
                  <svg class="w-6 h-6 text-green-600 dark:text-green-300" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M6,2A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6M6,4H13V9H18V20H6V4M8,12V14H16V12H8M8,16V18H13V16H8Z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">Documento Comprobatório</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Documento enviado pelo usuário</p>
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
          </div>
          
          <!-- Caso não tenha documentos -->
          <div v-else>
            <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Documentos:</h4>
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
              <div class="flex items-center">
                <svg class="w-6 h-6 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-gray-500 dark:text-gray-400 italic">Nenhum documento foi enviado com esta solicitação</span>
              </div>
            </div>
          </div>
          
          <!-- Motivo da Solicitação -->
          <div class="border-t border-gray-200 dark:border-gray-600 pt-6">
            <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Motivo da Solicitação:</h4>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
              <p class="text-gray-800 dark:text-gray-200 leading-relaxed">{{ reserva.motivo }}</p>
            </div>
          </div>
          
          <!-- Motivo da Rejeição -->
          <div v-if="reserva.status === 'rejeitada' && reserva.motivo_rejeicao" class="border-t border-gray-200 dark:border-gray-600 pt-6">
            <h4 class="font-medium text-red-600 dark:text-red-400 mb-3">Motivo da Rejeição:</h4>
            <div class="bg-red-50 dark:bg-red-900/30 p-4 rounded-lg border border-red-200 dark:border-red-700">
              <p class="text-red-800 dark:text-red-200 leading-relaxed">{{ reserva.motivo_rejeicao }}</p>
            </div>
          </div>
        </div>
      </CardBox>
      
      <!-- Modal de Confirmação Padrão -->
      <div 
        v-if="showConfirmModal" 
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
        @click.self="closeModal"
      >
        <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white dark:bg-gray-800">
          <div class="text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">{{ confirmModalTitle }}</h3>
            <div class="mb-6">
              <p class="text-sm text-gray-500 dark:text-gray-300">{{ confirmModalMessage }}</p>
            </div>
            <div class="flex justify-center gap-4">
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
        @click.self="closeRejeicaoModal"
      >
        <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white dark:bg-gray-800">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white text-center mb-4">Rejeitar Reserva</h3>
            <div class="mb-6">
              <p class="text-sm text-gray-500 dark:text-gray-300 mb-4">
                Você está prestes a rejeitar a reserva de <span class="font-semibold">{{ reserva.nome }}</span>. 
                Por favor, forneça um motivo para a rejeição:
              </p>
              
              <div class="mb-4">
                <label for="motivo_rejeicao" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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
                <p v-if="rejeicaoError" class="mt-2 text-sm text-red-600 dark:text-red-400">
                  {{ rejeicaoError }}
                </p>
              </div>
            </div>
            <div class="flex justify-center gap-4">
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
        @click.self="closeDocumentoModal"
      >
        <div class="relative mx-auto p-0 border w-11/12 md:w-4/5 lg:w-3/4 h-5/6 shadow-lg rounded-lg bg-white dark:bg-gray-800">
          <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-600">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ documentoTitulo }}</h3>
            <button 
              @click="closeDocumentoModal" 
              class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white focus:outline-none transition-colors"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="h-full p-2 bg-gray-100 dark:bg-gray-900 flex justify-center overflow-auto">
            <iframe 
              :src="documentoUrl" 
              class="w-full h-full border-0 rounded"
              allowfullscreen
            ></iframe>
          </div>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>