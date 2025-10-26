<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import CardBox from '@/Components/CardBox.vue';
import BaseButton from '@/Components/BaseButton.vue';
import {
  mdiAccount,
  mdiArrowLeftBoldOutline,
  mdiCheckCircle,
  mdiCloseCircle,
  mdiRefresh,
  mdiFileDocument,
  mdiDownload,
  mdiFileDownload,
  mdiEye,
  mdiPrinter,
  mdiAttachment,
} from '@mdi/js';

// Props
const props = defineProps({
  requerimento: Object,
});

// Toast
const { toast } = useToast();

// Estado local
const selectedStatus = ref(props.requerimento.status);
const isChangingStatus = ref(false);
const anexoUrl = ref(
  props.requerimento.anexo_url || props.requerimento.documento_url
);
const showAprovarModal = ref(false);
const respostaAprovacao = ref('');
const documentoResposta = ref(null);
const isSubmittingAprovacao = ref(false);
const aprovacaoError = ref('');

// Método para o upload de documento
const handleFileUpload = event => {
  documentoResposta.value = event.target.files[0];
};

// Status de requerimentos
const statusLabels = {
  pendente: 'Pendente',
  deferido: 'Aprovado',
  indeferido: 'Rejeitado',
};

const statusClasses = {
  pendente: 'bg-yellow-100 text-yellow-800',
  deferido: 'bg-green-100 text-green-800',
  indeferido: 'bg-red-100 text-red-800',
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

// Formatação de data
const formatDate = dateString => {
  if (!dateString) return 'Não informado';

  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date);
};

// Formatação de data e hora
const formatDateTime = dateString => {
  if (!dateString) return 'Não informado';

  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
};

// Verificar se há anexo
const hasAnexo = computed(() => {
  return anexoUrl.value ? true : false;
});

// Formato do tipo de requerimento
const tipoRequerimentoFormatado = computed(() => {
  if (!props.requerimento.tipo) return 'Não especificado';

  if (props.requerimento.tipo_formatado) {
    return props.requerimento.tipo_formatado;
  }

  // Caso contrário, formatar manualmente
  const tipos = {
    segunda_via_certificado: '2ª Via de Certificado',
    declaracao_participacao: 'Declaração de Participação em Curso',
    outros: 'Outros',
  };

  return tipos[props.requerimento.tipo] || props.requerimento.tipo;
});

// Determinar o motivo de rejeição
const motivoRejeicaoExibido = computed(() => {
  return (
    props.requerimento.motivo_rejeicao ||
    props.requerimento.motivo_indeferimento ||
    'Nenhum motivo especificado'
  );
});

// Determinar se o requerimento foi rejeitado
const foiRejeitado = computed(() => {
  return (
    props.requerimento.status === 'rejeitado' ||
    props.requerimento.status === 'indeferido'
  );
});

// Mostrar modal de rejeição
const showRejeicaoModalHandler = () => {
  motivoRejeicao.value = '';
  rejeicaoError.value = '';
  showRejeicaoModal.value = true;
};

// Verificar status do requerimento
onMounted(() => {
  console.log('Requerimento carregado:', props.requerimento);
  console.log('Anexo URL:', anexoUrl.value);
});

// Confirmar rejeição
const confirmarRejeicao = () => {
  if (!motivoRejeicao.value || motivoRejeicao.value.trim().length < 5) {
    rejeicaoError.value =
      'Por favor, forneça um motivo válido com pelo menos 5 caracteres.';
    return;
  }

  isSubmittingRejeicao.value = true;
  rejeicaoError.value = '';

  const form = useForm({
    motivo_indeferimento: motivoRejeicao.value.trim(),
  });

  form.post(route('admin.requerimentos.indeferir', props.requerimento.id), {
    onSuccess: () => {
      showRejeicaoModal.value = false;
      isSubmittingRejeicao.value = false;
      toast.success('Requerimento rejeitado com sucesso!');

      // Atualizar o status localmente
      props.requerimento.status = 'indeferido';
      props.requerimento.motivo_indeferimento = motivoRejeicao.value.trim();
      selectedStatus.value = 'indeferido';
    },
    onError: errors => {
      isSubmittingRejeicao.value = false;
      if (errors.motivo_indeferimento) {
        rejeicaoError.value = errors.motivo_indeferimento;
      } else {
        rejeicaoError.value =
          'Ocorreu um erro ao processar a rejeição. Por favor, tente novamente.';
        toast.error('Erro ao rejeitar requerimento');
      }
    },
  });
};

// Alterar Status
const alterarStatus = novoStatus => {
  // Se for rejeição, abre o modal específico
  if (novoStatus === 'rejeitado' || novoStatus === 'indeferido') {
    showRejeicaoModalHandler();
    return;
  }

  confirmModalTitle.value = `Alterar Status para ${statusLabels[novoStatus]}`;
  confirmModalMessage.value = `Tem certeza que deseja alterar o status do requerimento para ${statusLabels[novoStatus]}?`;

  confirmModalAction.value = () => {
    isChangingStatus.value = true;

    const form = useForm({
      status: novoStatus,
    });

    form.patch(
      route('admin.requerimentos.alterar-status', props.requerimento.id),
      {
        onSuccess: () => {
          showConfirmModal.value = false;
          isChangingStatus.value = false;
          toast.success(
            `Status alterado para ${statusLabels[novoStatus]} com sucesso!`
          );

          // Atualizar o status localmente
          props.requerimento.status = novoStatus;
          selectedStatus.value = novoStatus;
        },
        onError: errors => {
          showConfirmModal.value = false;
          isChangingStatus.value = false;
          toast.error('Erro ao alterar status do requerimento');
          console.error('Erro:', errors);
        },
      }
    );
  };

  showConfirmModal.value = true;
};

// Ações diretas
const aprovarRequerimento = () => {
  // Abrir modal para coletar resposta/documento
  showAprovarModal.value = true;
};

// Novo método para concluir aprovação
const confirmarAprovacao = () => {
  if (!respostaAprovacao.value || respostaAprovacao.value.trim().length < 5) {
    aprovacaoError.value =
      'Por favor, forneça uma resposta com pelo menos 5 caracteres.';
    return;
  }

  isSubmittingAprovacao.value = true;
  aprovacaoError.value = '';

  const form = useForm({
    status: 'deferido',
    resposta: respostaAprovacao.value.trim(),
    documento_resposta: documentoResposta.value,
  });

  form.post(route('admin.requerimentos.deferir', props.requerimento.id), {
    onSuccess: () => {
      showAprovarModal.value = false;
      isSubmittingAprovacao.value = false;
      toast.success('Requerimento aprovado com sucesso!');

      // Atualizar o status localmente
      props.requerimento.status = 'deferido';
      props.requerimento.resposta = respostaAprovacao.value.trim();
      selectedStatus.value = 'deferido';
    },
    onError: errors => {
      isSubmittingAprovacao.value = false;
      if (errors.resposta) {
        aprovacaoError.value = errors.resposta;
      } else {
        aprovacaoError.value =
          'Ocorreu um erro ao processar a aprovação. Por favor, tente novamente.';
        toast.error('Erro ao aprovar requerimento');
      }
      console.error('Erro ao aprovar requerimento:', errors);
    },
  });
};

const rejeitarRequerimento = () => {
  showRejeicaoModal.value = true;
};

const retornarParaPendente = () => {
  alterarStatus('pendente');
};

// Visualizar anexo
const visualizarAnexo = () => {
  if (anexoUrl.value) {
    documentoUrl.value = anexoUrl.value;
    showDocumentoModal.value = true;
  } else {
    toast.error('Anexo não disponível');
  }
};

// Download do anexo
const downloadAnexo = () => {
  if (anexoUrl.value) {
    window.open(anexoUrl.value, '_blank');
  } else {
    toast.error('Anexo não disponível para download');
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
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Detalhes do Requerimento" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiFileDocument"
        title="Detalhes do Requerimento"
        main
      >
        <BaseButton
          :route-name="route('admin.requerimentos.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>

      <!-- Seção de Status e Ações -->
      <CardBox class="mb-6">
        <div
          class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
        >
          <div>
            <h3 class="text-lg font-medium mb-2">Status do Requerimento</h3>
            <span
              :class="statusClasses[requerimento.status]"
              class="px-3 py-1 text-sm font-medium rounded-full"
            >
              {{ statusLabels[requerimento.status] || requerimento.status }}
            </span>
          </div>

          <div class="flex flex-col sm:flex-row gap-3">
            <!-- Ações diretas baseadas no status atual -->
            <div v-if="requerimento.status === 'pendente'" class="flex gap-2">
              <BaseButton
                @click="aprovarRequerimento"
                :icon="mdiCheckCircle"
                label="Aprovar"
                color="success"
                :disabled="isChangingStatus"
              />
              <BaseButton
                @click="rejeitarRequerimento"
                :icon="mdiCloseCircle"
                label="Rejeitar"
                color="danger"
                :disabled="isChangingStatus"
              />
            </div>

            <div
              v-if="
                requerimento.status === 'aprovado' ||
                requerimento.status === 'deferido'
              "
              class="flex gap-2"
            >
              <BaseButton
                @click="rejeitarRequerimento"
                :icon="mdiCloseCircle"
                label="Indeferir"
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

            <div
              v-if="
                requerimento.status === 'rejeitado' ||
                requerimento.status === 'indeferido'
              "
              class="flex gap-2"
            >
              <BaseButton
                @click="aprovarRequerimento"
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

      <!-- Informações do Requerimento -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Informações do Solicitante -->
        <CardBox>
          <h3 class="text-lg font-medium mb-4">Informações do Solicitante</h3>
          <div class="space-y-1">
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300"
                >Nome:</span
              >
              <span class="col-span-2">{{ requerimento.nome }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300"
                >Matrícula:</span
              >
              <span class="col-span-2">{{ requerimento.matricula }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300"
                >Cargo/Função:</span
              >
              <span class="col-span-2">{{
                requerimento.cargo || 'Não informado'
              }}</span>
            </div>
            <div v-if="requerimento.orgao" class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300"
                >Órgão:</span
              >
              <span class="col-span-2">{{ requerimento.orgao }}</span>
            </div>
            <div v-if="requerimento.cpf" class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300"
                >CPF:</span
              >
              <span class="col-span-2">{{ requerimento.cpf }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300"
                >E-mail:</span
              >
              <span class="col-span-2">{{ requerimento.email }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300"
                >Telefone:</span
              >
              <span class="col-span-2">{{ requerimento.telefone }}</span>
            </div>
          </div>
        </CardBox>

        <!-- Detalhes do Requerimento -->
        <CardBox>
          <h3 class="text-lg font-medium mb-4">Detalhes do Requerimento</h3>
          <div class="space-y-1">
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300"
                >Tipo Requerimento:</span
              >
              <span class="col-span-2">{{ tipoRequerimentoFormatado }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300"
                >Data:</span
              >
              <span class="col-span-2">{{
                formatDateTime(requerimento.created_at)
              }}</span>
            </div>
            <div v-if="requerimento.protocolo" class="grid grid-cols-3 gap-2">
              <span class="font-medium text-gray-600 dark:text-gray-300"
                >Protocolo:</span
              >
              <span class="col-span-2">{{ requerimento.protocolo }}</span>
            </div>
          </div>
        </CardBox>
      </div>

      <!-- Conteúdo e Anexos -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Conteúdo do Requerimento -->
        <CardBox>
          <h3 class="text-lg font-medium mb-4">Conteúdo do Requerimento</h3>
          <div
            class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg text-gray-800 dark:text-gray-200"
          >
            <p class="whitespace-pre-line">{{ requerimento.conteudo }}</p>
          </div>
        </CardBox>

        <!-- Documento e Dados Adicionais -->
        <CardBox>
          <h3 class="text-lg font-medium mb-4">
            Documentação e Dados Adicionais
          </h3>
          <div class="space-y-4">
            <div
              v-if="hasAnexo"
              class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-3 rounded-lg"
            >
              <div class="flex items-center">
                <div class="bg-blue-100 dark:bg-blue-900 p-2 rounded-md mr-3">
                  <svg
                    class="w-6 h-6 text-blue-600 dark:text-blue-300"
                    viewBox="0 0 24 24"
                  >
                    <path
                      fill="currentColor"
                      d="M6,2A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6M6,4H13V9H18V20H6V4M8,12V14H16V12H8M8,16V18H13V16H8Z"
                    />
                  </svg>
                </div>
                <div>
                  <p class="font-medium">Anexo do Requerimento</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    Documento enviado pelo solicitante
                  </p>
                </div>
              </div>
              <div class="flex gap-2">
                <BaseButton
                  @click="visualizarAnexo"
                  :icon="mdiEye"
                  label="Visualizar"
                  color="info"
                  small
                />
                <BaseButton
                  @click="downloadAnexo"
                  :icon="mdiDownload"
                  label="Download"
                  color="success"
                  small
                />
              </div>
            </div>
            <div
              v-else
              class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg text-gray-500 dark:text-gray-400 italic flex items-center"
            >
              <svg class="w-5 h-5 mr-2 text-gray-400" viewBox="0 0 24 24">
                <path
                  fill="currentColor"
                  d="M13,9H18.5L13,3.5V9M6,2H14L20,8V20A2,2 0 0,1 18,22H6C4.89,22 4,21.1 4,20V4C4,2.89 4.89,2 6,2M15,18V16H6V18H15M18,14V12H6V14H18Z"
                />
              </svg>
              Nenhum anexo enviado.
            </div>

            <div v-if="requerimento.dados_adicionais" class="pt-4">
              <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">
                Dados Adicionais:
              </h4>
              <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                <div
                  v-for="(valor, chave) in requerimento.dados_adicionais"
                  :key="chave"
                  class="mb-2"
                >
                  <span class="font-medium">{{ chave }}:</span> {{ valor }}
                </div>
              </div>
            </div>

            <div v-if="foiRejeitado" class="pt-2">
              <h4 class="font-medium text-red-600 dark:text-red-400 mb-2">
                Motivo da Rejeição:
              </h4>
              <p
                class="p-3 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg"
              >
                {{ motivoRejeicaoExibido }}
              </p>
            </div>
          </div>
        </CardBox>
      </div>

      <!-- Informações Adicionais para quando aprovado -->
      <CardBox
        v-if="
          requerimento.status === 'aprovado' ||
          requerimento.status === 'deferido'
        "
        class="mb-6"
      >
        <h3 class="text-lg font-medium mb-4">Informações da Aprovação</h3>
        <div
          class="bg-green-50 dark:bg-green-900/30 p-4 rounded-lg text-green-800 dark:text-green-200"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <div class="font-medium mb-2">Data de Aprovação:</div>
              <p>
                {{
                  requerimento.data_resposta
                    ? formatDateTime(requerimento.data_resposta)
                    : requerimento.data_aprovacao
                      ? formatDateTime(requerimento.data_aprovacao)
                      : 'Não informado'
                }}
              </p>
            </div>
            <div
              v-if="requerimento.observacao_aprovacao || requerimento.resposta"
              class="col-span-1 md:col-span-2"
            >
              <div class="font-medium mb-2">Observações/Resposta:</div>
              <p>
                {{ requerimento.observacao_aprovacao || requerimento.resposta }}
              </p>
            </div>
          </div>
        </div>
      </CardBox>

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
        <div
          class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800"
        >
          <div class="mt-3">
            <h3
              class="text-lg leading-6 font-medium text-gray-900 dark:text-white text-center"
            >
              Rejeitar Requerimento
            </h3>
            <div class="mt-2 px-7 py-3">
              <p class="text-sm text-gray-500 dark:text-gray-300 mb-4">
                Você está prestes a rejeitar o requerimento de
                <span class="font-semibold">{{ requerimento.nome }}</span
                >. Por favor, forneça um motivo para a rejeição:
              </p>

              <div class="mb-4">
                <label
                  for="motivo_rejeicao"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
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
                <p
                  v-if="rejeicaoError"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ rejeicaoError }}
                </p>
              </div>
            </div>
            <div class="flex justify-center gap-4 mt-4">
              <BaseButton
                @click="confirmarRejeicao"
                label="Rejeitar Requerimento"
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

      <!-- Modal de Aprovação -->
      <div
        v-if="showAprovarModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
      >
        <div
          class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800"
        >
          <div class="mt-3">
            <h3
              class="text-lg leading-6 font-medium text-gray-900 dark:text-white text-center"
            >
              Aprovar Requerimento
            </h3>
            <div class="mt-2 px-7 py-3">
              <p class="text-sm text-gray-500 dark:text-gray-300 mb-4">
                Você está prestes a aprovar o requerimento de
                <span class="font-semibold">{{ requerimento.nome }}</span
                >. Por favor, forneça uma resposta:
              </p>

              <div class="mb-4">
                <label
                  for="resposta_aprovacao"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  Resposta *
                </label>
                <textarea
                  id="resposta_aprovacao"
                  v-model="respostaAprovacao"
                  rows="4"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  placeholder="Descreva a resposta ao requerimento. Esta informação será enviada ao solicitante."
                  :disabled="isSubmittingAprovacao"
                ></textarea>
                <p
                  v-if="aprovacaoError"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ aprovacaoError }}
                </p>
              </div>

              <div class="mb-4">
                <label
                  for="documento_resposta"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  Anexar Documento (opcional)
                </label>
                <input
                  type="file"
                  id="documento_resposta"
                  @change="handleFileUpload"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  :disabled="isSubmittingAprovacao"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Envie o documento solicitado (PDF, DOC, JPG, etc.)
                </p>
              </div>
            </div>
            <div class="flex justify-center gap-4 mt-4">
              <BaseButton
                @click="confirmarAprovacao"
                label="Aprovar Requerimento"
                color="success"
                :loading="isSubmittingAprovacao"
              />
              <BaseButton
                @click="() => (showAprovarModal = false)"
                label="Cancelar"
                color="white"
                :disabled="isSubmittingAprovacao"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Modal de Visualização de Anexo -->
      <div
        v-if="showDocumentoModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
      >
        <div
          class="relative mx-auto p-0 border w-11/12 md:w-4/5 lg:w-3/4 h-5/6 shadow-lg rounded-md bg-white dark:bg-gray-800"
        >
          <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              Anexo do Requerimento
            </h3>
            <div class="flex items-center gap-2">
              <BaseButton
                @click="downloadAnexo"
                :icon="mdiDownload"
                label="Download"
                color="success"
                small
              />
              <button
                @click="closeDocumentoModal"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white focus:outline-none"
              >
                <svg
                  class="h-6 w-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>
          <div
            class="h-5/6 p-2 bg-gray-100 dark:bg-gray-900 flex justify-center overflow-auto"
          >
            <iframe
              type="application/pdf"
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
