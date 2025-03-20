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
  mdiEmail,
  mdiArrowLeftBoldOutline,
  mdiReply,
  mdiCheckCircle,
  mdiRefresh,
  mdiDelete
} from "@mdi/js"

// Props
const props = defineProps({
  contato: Object
});

// Toast
const { toast } = useToast();

// Estado local
const isChangingStatus = ref(false);

// Status de contatos
const statusLabels = {
  pendente: 'Pendente',
  respondido: 'Respondido',
  arquivado: 'Arquivado'
};

const statusClasses = {
  pendente: 'bg-yellow-100 text-yellow-800',
  respondido: 'bg-green-100 text-green-800',
  arquivado: 'bg-blue-100 text-blue-800'
};

// Modal de confirmação
const showConfirmModal = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref(() => {});

// Modal de resposta
const showRespostaModal = ref(false);
const mensagemResposta = ref('');
const isSubmittingResposta = ref(false);
const respostaError = ref('');

// Formatação de data
const formatDateTime = (dateString) => {
  if (!dateString) return 'Não informado';
  
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  }).format(date);
};

// Mostrar modal de resposta
const showRespostaModalHandler = () => {
  mensagemResposta.value = '';
  respostaError.value = '';
  showRespostaModal.value = true;
};

// Confirmar resposta
const confirmarResposta = () => {
  // Validar mensagem
  if (!mensagemResposta.value || mensagemResposta.value.trim().length < 5) {
    respostaError.value = 'Por favor, forneça uma resposta válida com pelo menos 5 caracteres.';
    return;
  }
  
  isSubmittingResposta.value = true;
  respostaError.value = '';
  
  // Enviar requisição de resposta
  const form = useForm({
    resposta: mensagemResposta.value.trim()
  });
  
  form.post(route('admin.contato.responder', props.contato.id), {
    onSuccess: () => {
      showRespostaModal.value = false;
      isSubmittingResposta.value = false;
      toast.success('Mensagem respondida com sucesso!');
      
      // Atualizar o status localmente
      props.contato.status = 'respondido';
      props.contato.resposta = mensagemResposta.value.trim();
      props.contato.data_resposta = new Date().toISOString();
      props.contato.respondido_por = props.currentUser?.id;
    },
    onError: (errors) => {
      isSubmittingResposta.value = false;
      if (errors.resposta) {
        respostaError.value = errors.resposta;
      } else {
        respostaError.value = 'Ocorreu um erro ao processar a resposta. Por favor, tente novamente.';
        toast.error('Erro ao responder mensagem');
      }
      console.error('Erro ao responder mensagem:', errors);
    }
  });
};

// Alterar Status
const alterarStatus = (novoStatus) => {
  // Se for resposta, abre o modal específico
  if (novoStatus === 'respondido') {
    showRespostaModalHandler();
    return;
  }
  
  const statusLabelsFormatados = {
    pendente: 'Pendente',
    respondido: 'Respondido',
    arquivado: 'Arquivado'
  };
  
  confirmModalTitle.value = `Alterar Status para ${statusLabelsFormatados[novoStatus]}`;
  confirmModalMessage.value = `Tem certeza que deseja alterar o status da mensagem para ${novoStatus}?`;
  
  confirmModalAction.value = () => {
    isChangingStatus.value = true;
    
    let routeName = '';
    if (novoStatus === 'arquivado') {
      routeName = 'admin.contato.arquivar';
    } else if (novoStatus === 'pendente') {
      routeName = 'admin.contato.retornar-pendente';
    }
    
    const form = useForm({});
    
    form.patch(route(routeName, props.contato.id), {
      onSuccess: () => {
        showConfirmModal.value = false;
        isChangingStatus.value = false;
        toast.success(`Status alterado para ${novoStatus} com sucesso!`);
        
        // Atualizar o status
        props.contato.status = novoStatus;
      },
      onError: (errors) => {
        showConfirmModal.value = false;
        isChangingStatus.value = false;
        toast.error(`Erro ao alterar status da mensagem: ${JSON.stringify(errors)}`);
        console.error('Erro:', errors);
      }
    });
  };
  
  showConfirmModal.value = true;
};

// Excluir contato
const excluirContato = () => {
  confirmModalTitle.value = 'Excluir Mensagem';
  confirmModalMessage.value = 'Tem certeza que deseja excluir permanentemente esta mensagem? Esta ação não pode ser desfeita.';
  
  confirmModalAction.value = () => {
    const form = useForm({});
    
    form.delete(route('admin.contato.destroy', props.contato.id), {
      onSuccess: () => {
        toast.success('Mensagem excluída com sucesso!');
        // Redirecionar para a listagem
        window.location.href = route('admin.contato.index');
      },
      onError: (e) => {
        showConfirmModal.value = false;
        toast.error('Erro ao excluir mensagem: ' + JSON.stringify(e));
        console.error('Erro:', e);
      }
    });
  };
  
  showConfirmModal.value = true;
};

// Ações diretas
const responderContato = () => {
  alterarStatus('respondido');
};

const arquivarContato = () => {
  alterarStatus('arquivado');
};

const retornarParaPendente = () => {
  alterarStatus('pendente');
};

const closeModal = () => {
  showConfirmModal.value = false;
};

const closeRespostaModal = () => {
  showRespostaModal.value = false;
};

</script>

<template>
  <LayoutAuthenticated>
    <Head title="Detalhes do Contato" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiEmail"
        title="Detalhes da Mensagem de Contato"
        main
      >
        <BaseButton
          :route-name="route('admin.contato.index')"
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
            <h3 class="text-lg font-medium mb-2">Status da Mensagem</h3>
            <span 
              :class="statusClasses[contato.status]" 
              class="px-3 py-1 text-sm font-medium rounded-full"
            >
              {{ statusLabels[contato.status] }}
            </span>
          </div>
          
          <div class="flex flex-wrap gap-2">
            <!-- Ações para status pendente -->
            <div v-if="contato.status === 'pendente'" class="flex gap-2">
              <BaseButton
                @click="responderContato"
                :icon="mdiReply"
                label="Responder"
                color="success"
                :disabled="isChangingStatus"
              />
              <BaseButton
                @click="arquivarContato"
                :icon="mdiCheckCircle"
                label="Arquivar"
                color="info"
                :disabled="isChangingStatus"
              />
            </div>

            <!-- Ações para status respondido -->
            <div v-if="contato.status === 'respondido'" class="flex gap-2">
              <BaseButton
                @click="arquivarContato"
                :icon="mdiCheckCircle"
                label="Arquivar"
                color="info"
                :disabled="isChangingStatus"
              />
              <BaseButton
                @click="retornarParaPendente"
                :icon="mdiRefresh"
                label="Retornar para Pendente"
                color="warning"
                :disabled="isChangingStatus"
              />
            </div>

            <!-- Ações para status arquivado -->
            <div v-if="contato.status === 'arquivado'" class="flex gap-2">
              <BaseButton
                @click="retornarParaPendente"
                :icon="mdiRefresh"
                label="Retornar para Pendente"
                color="warning"
                :disabled="isChangingStatus"
              />
            </div>

            <!-- Botão de exclusão -->
            <BaseButton
              @click="excluirContato"
              :icon="mdiDelete"
              label="Excluir"
              color="danger"
              :disabled="isChangingStatus"
            />
          </div>
        </div>
      </CardBox>

      <!-- Informações do Contato -->
      <CardBox class="mb-6">
        <h3 class="text-lg font-medium mb-4">Informações do Remetente</h3>
        
        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <h4 class="font-medium text-gray-500 dark:text-gray-400 mb-2">Dados Pessoais</h4>
            <table class="w-full">
              <tbody>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Nome</td>
                  <td>{{ contato.nome }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Email</td>
                  <td>
                    <a :href="`mailto:${contato.email}`" class="text-blue-600 dark:text-blue-400 hover:underline">
                      {{ contato.email }}
                    </a>
                  </td>
                </tr>
                <tr v-if="contato.telefone">
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Telefone</td>
                  <td>{{ contato.telefone }}</td>
                </tr>
                <tr v-if="contato.user_id">
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Usuário Registrado</td>
                  <td>
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                      Sim (ID: {{ contato.user_id }})
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div>
            <h4 class="font-medium text-gray-500 dark:text-gray-400 mb-2">Detalhes da Mensagem</h4>
            <table class="w-full">
              <tbody>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Assunto</td>
                  <td>{{ contato.assunto }}</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">Data de Envio</td>
                  <td>{{ formatDateTime(contato.created_at) }}</td>
                </tr>
                <tr v-if="contato.ip">
                  <td class="py-2 text-gray-600 dark:text-gray-300 font-medium">IP</td>
                  <td>{{ contato.ip }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </CardBox>
      
      <!-- Conteúdo da Mensagem -->
      <CardBox class="mb-6">
        <h3 class="text-lg font-medium mb-2">Mensagem</h3>
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-md">
          <p class="whitespace-pre-line">{{ contato.mensagem }}</p>
        </div>
      </CardBox>
      
      <!-- Resposta (se existir) -->
      <CardBox v-if="contato.status === 'respondido' && contato.resposta" class="mb-6">
        <h3 class="text-lg font-medium mb-2">Resposta Enviada</h3>
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-md">
          <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            Respondido em: {{ formatDateTime(contato.data_resposta) }}
            <span v-if="contato.respondente" class="ml-2">
              por {{ contato.respondente.name }}
            </span>
          </div>
          <p class="whitespace-pre-line">{{ contato.resposta }}</p>
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
      
      <!-- Modal de Resposta -->
      <div 
        v-if="showRespostaModal" 
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
      >
        <div class="relative mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800">
          <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white text-center">Responder Mensagem</h3>
            <div class="mt-4 px-7 py-3">
              <div class="mb-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-md">
                <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">Mensagem Original:</h4>
                <p class="text-sm text-gray-800 dark:text-gray-200 mb-1">
                  <span class="font-medium">De:</span> {{ contato.nome }} ({{ contato.email }})
                </p>
                <p class="text-sm text-gray-800 dark:text-gray-200 mb-1">
                  <span class="font-medium">Assunto:</span> {{ contato.assunto }}
                </p>
                <p class="text-sm text-gray-800 dark:text-gray-200 mt-2">
                  {{ contato.mensagem }}
                </p>
              </div>
              
              <div class="mb-4">
                <label for="mensagem_resposta" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Sua Resposta *
                </label>
                <textarea
                  id="mensagem_resposta"
                  v-model="mensagemResposta"
                  rows="6"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  placeholder="Digite sua resposta para o contato..."
                  :disabled="isSubmittingResposta"
                ></textarea>
                <p v-if="respostaError" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ respostaError }}
                </p>
              </div>
              
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                Após enviar, esta mensagem será automaticamente marcada como respondida e um email será enviado para {{ contato.email }}
              </p>
            </div>
            <div class="flex justify-center gap-4 mt-4">
              <BaseButton 
                @click="confirmarResposta" 
                label="Enviar Resposta"
                color="success"
                :loading="isSubmittingResposta"
              />
              <BaseButton 
                @click="closeRespostaModal" 
                label="Cancelar"
                color="white"
                :disabled="isSubmittingResposta"
              />
            </div>
          </div>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>