<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import BaseButton from '@/Components/BaseButton.vue';
import CardBox from '@/Components/CardBox.vue';
import {
  mdiArrowLeftBoldOutline,
  mdiEmail,
  mdiMagnify,
  mdiEye,
  mdiCheckCircle,
  mdiCloseCircle,
  mdiReply,
  mdiDelete,
} from '@mdi/js';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import FormField from '@/Components/FormField.vue';
import BaseButtons from '@/Components/BaseButtons.vue';

// Props
const props = defineProps({
  contatos: Object,
  filters: Object,
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

// Modal de resposta
const showRespostaModal = ref(false);
const contatoParaResponder = ref(null);
const mensagemResposta = ref('');
const isSubmittingResposta = ref(false);
const respostaError = ref('');

// Status de contatos
const statusLabels = {
  pendente: 'Pendente',
  respondido: 'Respondido',
  arquivado: 'Arquivado',
};

const statusColors = {
  pendente: 'warning',
  respondido: 'success',
  arquivado: 'info',
};

// Formatação de data
const formatDate = dateString => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
};

// Limitar o texto para exibição
const limitarTexto = (texto, limite = 50) => {
  if (!texto) return '';
  return texto.length > limite ? texto.substring(0, limite) + '...' : texto;
};

// Enviar formulário de busca
const submitSearch = () => {
  useForm({
    search: search.value,
    status: statusFilter.value,
  }).get(route('admin.contato.index'), {
    preserveState: true,
    replace: true,
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
  // Se for resposta, abre o modal específico
  if (novoStatus === 'respondido') {
    const contato = props.contatos.data.find(c => c.id === id);
    if (contato) {
      contatoParaResponder.value = contato;
      mensagemResposta.value = '';
      respostaError.value = '';
      showRespostaModal.value = true;
    }
    return;
  }

  const mensagens = {
    arquivado: 'arquivar',
    pendente: 'retornar para pendente',
  };

  confirmModalTitle.value = `${mensagens[novoStatus].charAt(0).toUpperCase() + mensagens[novoStatus].slice(1)} Mensagem`;
  confirmModalMessage.value =
    mensagemConfirmacao ||
    `Tem certeza que deseja ${mensagens[novoStatus]} esta mensagem?`;

  // Define a função de confirmação
  confirmModalAction.value = () => {
    let routeName = '';

    if (novoStatus === 'arquivado') {
      routeName = 'admin.contato.arquivar';
    } else if (novoStatus === 'pendente') {
      routeName = 'admin.contato.retornar-pendente';
    }

    // Criar form para fazer a requisição
    const form = useForm({});

    form.patch(route(routeName, id), {
      onSuccess: () => {
        closeConfirmModal();
        toast.success(
          `Mensagem ${novoStatus === 'arquivado' ? 'arquivada' : 'retornada para pendente'} com sucesso!`
        );

        // Atualiza o item na lista local
        const index = props.contatos.data.findIndex(c => c.id === id);
        if (index !== -1) {
          props.contatos.data[index].status = novoStatus;
        }
      },
      onError: errors => {
        closeConfirmModal();
        toast.error(
          `Erro ao ${mensagens[novoStatus]} mensagem: ${JSON.stringify(errors)}`
        );
        console.error(errors);
      },
    });
  };

  showConfirmModal.value = true;
};

// Confirmar resposta
const confirmarResposta = () => {
  // Validar mensagem
  if (!mensagemResposta.value || mensagemResposta.value.trim().length < 5) {
    respostaError.value =
      'Por favor, forneça uma resposta válida com pelo menos 5 caracteres.';
    return;
  }

  isSubmittingResposta.value = true;
  respostaError.value = '';

  const form = useForm({
    status: 'respondido',
    resposta: mensagemResposta.value.trim(),
  });

  form.post(route('admin.contato.responder', contatoParaResponder.value.id), {
    onSuccess: () => {
      showRespostaModal.value = false;
      isSubmittingResposta.value = false;
      toast.success('Mensagem respondida com sucesso!');

      // Atualiza o item na lista local
      const index = props.contatos.data.findIndex(
        c => c.id === contatoParaResponder.value.id
      );
      if (index !== -1) {
        props.contatos.data[index].status = 'respondido';
        props.contatos.data[index].resposta = mensagemResposta.value.trim();
      }

      // Limpa o estado
      contatoParaResponder.value = null;
      mensagemResposta.value = '';
    },
    onError: errors => {
      isSubmittingResposta.value = false;
      if (errors.resposta) {
        respostaError.value = errors.resposta;
      } else {
        respostaError.value =
          'Ocorreu um erro ao processar a resposta. Por favor, tente novamente.';
        toast.error('Erro ao responder mensagem');
      }
      console.error('Erro ao responder mensagem:', errors);
    },
  });
};

const responderContato = id => {
  alterarStatus(id, 'respondido');
};

const arquivarContato = id => {
  alterarStatus(
    id,
    'arquivado',
    'Tem certeza que deseja arquivar esta mensagem? Ela ficará marcada como arquivada no sistema.'
  );
};

const retornarParaPendente = id => {
  alterarStatus(
    id,
    'pendente',
    'Tem certeza que deseja retornar esta mensagem para pendente?'
  );
};

// Excluir contato
const excluirContato = id => {
  confirmModalTitle.value = 'Excluir Mensagem';
  confirmModalMessage.value =
    'Tem certeza que deseja excluir permanentemente esta mensagem? Esta ação não pode ser desfeita.';

  confirmModalAction.value = () => {
    const form = useForm({});

    form.delete(route('admin.contato.destroy', id), {
      onSuccess: () => {
        closeConfirmModal();
        toast.success('Mensagem excluída com sucesso!');

        // Remove o item da lista local
        const index = props.contatos.data.findIndex(c => c.id === id);
        if (index !== -1) {
          props.contatos.data.splice(index, 1);
        }
      },
      onError: e => {
        closeConfirmModal();
        toast.error('Erro ao excluir mensagem: ' + JSON.stringify(e));
        console.error(e);
      },
    });
  };

  showConfirmModal.value = true;
};

const closeConfirmModal = () => {
  showConfirmModal.value = false;
};

const closeRespostaModal = () => {
  showRespostaModal.value = false;
  contatoParaResponder.value = null;
  mensagemResposta.value = '';
  respostaError.value = '';
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Gerenciamento de Contatos" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiEmail"
        title="Fale Conosco - Mensagens"
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
              placeholder="Nome, email ou assunto"
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
              <option value="respondido">Respondido</option>
              <option value="arquivado">Arquivado</option>
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

      <!-- Tabela de Contatos -->
      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>Assunto</th>
              <th>Mensagem</th>
              <th>Data</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="contato in props.contatos.data" :key="contato.id">
              <td data-label="ID">{{ contato.id }}</td>
              <td data-label="Nome">
                <div>{{ contato.nome }}</div>
                <small class="text-gray-500 dark:text-gray-400">{{
                  contato.email
                }}</small>
              </td>
              <td data-label="Assunto">
                {{ contato.assunto }}
              </td>
              <td data-label="Mensagem">
                {{ limitarTexto(contato.mensagem) }}
              </td>
              <td data-label="Data">
                {{ formatDate(contato.created_at) }}
              </td>
              <td data-label="Status" class="lg:w-1">
                <BaseButton
                  :color="statusColors[contato.status]"
                  :label="statusLabels[contato.status]"
                  small
                  :rounded="true"
                />
              </td>
              <td data-label="Ações" class="lg:w-1 whitespace-nowrap">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    :route-name="route('admin.contato.show', contato.id)"
                    :icon="mdiEye"
                    small
                    color="info"
                    outline
                    title="Ver detalhes"
                  />

                  <!-- Ações para status pendente -->
                  <template v-if="contato.status === 'pendente'">
                    <BaseButton
                      @click="responderContato(contato.id)"
                      :icon="mdiReply"
                      small
                      color="success"
                      outline
                      title="Responder"
                    />
                    <BaseButton
                      @click="arquivarContato(contato.id)"
                      :icon="mdiCheckCircle"
                      small
                      color="info"
                      outline
                      title="Arquivar"
                    />
                  </template>

                  <!-- Ações para status respondido -->
                  <template v-if="contato.status === 'respondido'">
                    <BaseButton
                      @click="arquivarContato(contato.id)"
                      :icon="mdiCheckCircle"
                      small
                      color="info"
                      outline
                      title="Arquivar"
                    />
                    <BaseButton
                      @click="retornarParaPendente(contato.id)"
                      :icon="mdiReply"
                      small
                      color="warning"
                      outline
                      title="Retornar para Pendente"
                    />
                  </template>

                  <!-- Ações para status arquivado -->
                  <template v-if="contato.status === 'arquivado'">
                    <BaseButton
                      @click="retornarParaPendente(contato.id)"
                      :icon="mdiReply"
                      small
                      color="warning"
                      outline
                      title="Retornar para Pendente"
                    />
                  </template>

                  <!-- Ação de exclusão -->
                  <BaseButton
                    @click="excluirContato(contato.id)"
                    :icon="mdiDelete"
                    small
                    color="danger"
                    outline
                    title="Excluir"
                  />
                </BaseButtons>
              </td>
            </tr>

            <!-- Mensagem de "Sem resultados" -->
            <tr v-if="props.contatos.data.length === 0">
              <td colspan="7" class="text-center py-4">
                Nenhuma mensagem encontrada com os filtros selecionados.
              </td>
            </tr>
          </tbody>
        </table>
      </CardBox>

      <!-- Paginação -->
      <div
        class="mt-6"
        v-if="props.contatos.links && props.contatos.links.length > 3"
      >
        <CardBox>
          <div class="flex items-center justify-between">
            <small
              >Mostrando {{ props.contatos.from }} a {{ props.contatos.to }} de
              {{ props.contatos.total }} resultados</small
            >
            <div class="flex">
              <Link
                v-for="(link, i) in props.contatos.links"
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

      <!-- Modal de Confirmação -->
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

      <!-- Modal de Resposta -->
      <div
        v-if="showRespostaModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
      >
        <div
          class="relative mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800"
        >
          <div class="mt-3">
            <h3
              class="text-lg leading-6 font-medium text-gray-900 dark:text-white text-center"
            >
              Responder Mensagem
            </h3>
            <div class="mt-4 px-7 py-3">
              <div class="mb-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-md">
                <h4
                  class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2"
                >
                  Mensagem Original:
                </h4>
                <p class="text-sm text-gray-800 dark:text-gray-200 mb-1">
                  <span class="font-medium">De:</span>
                  {{ contatoParaResponder?.nome }} ({{
                    contatoParaResponder?.email
                  }})
                </p>
                <p class="text-sm text-gray-800 dark:text-gray-200 mb-1">
                  <span class="font-medium">Assunto:</span>
                  {{ contatoParaResponder?.assunto }}
                </p>
                <p class="text-sm text-gray-800 dark:text-gray-200 mt-2">
                  {{ contatoParaResponder?.mensagem }}
                </p>
              </div>

              <div class="mb-4">
                <label
                  for="mensagem_resposta"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
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
                <p
                  v-if="respostaError"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ respostaError }}
                </p>
              </div>

              <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                Após enviar, esta mensagem será automaticamente marcada como
                respondida e um email será enviado para
                {{ contatoParaResponder?.email }}
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
