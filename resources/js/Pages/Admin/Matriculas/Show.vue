<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Toast from '@/Pages/Components/Toast.vue';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import CardBox from '@/Components/CardBox.vue';
import BaseButton from '@/Components/BaseButton.vue';
import RejeicaoMatriculaModal from '@/Pages/Components/RejeicaoMatriculaModal.vue';
import { mdiAccountKey, mdiArrowLeftBoldOutline } from '@mdi/js';

// Props
const props = defineProps({
  matricula: Object,
  id: Number,
  curso_id: Number,
});

// Toast
const { toast } = useToast();

// Estado local para controlar o seletor de status
const selectedStatus = ref(props.matricula.status);
const isChangingStatus = ref(false);

// Status de matrículas
const statusLabels = {
  pendente: 'Pendente',
  aprovada: 'Aprovada',
  rejeitada: 'Rejeitada',
};

const statusClasses = {
  pendente: 'bg-yellow-100 text-yellow-800',
  aprovada: 'bg-green-100 text-green-800',
  rejeitada: 'bg-red-100 text-red-800',
};

// Modal de confirmação padrão
const showConfirmModal = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref(() => {});

// Modal de rejeição
const showRejeicaoModal = ref(false);

// Dados adicionais da matrícula (parsear o JSON)
const dadosAdicionais = computed(() => {
  if (!props.matricula.dados_adicionais) return {};

  try {
    if (typeof props.matricula.dados_adicionais === 'string') {
      return JSON.parse(props.matricula.dados_adicionais);
    }
    return props.matricula.dados_adicionais;
  } catch (e) {
    console.error('Erro ao parsear dados adicionais:', e);
    return {};
  }
});

// Formatação de data
const formatDate = dateString => {
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

// Alternar Status
const alterarStatus = novoStatus => {
  const statusTexto = {
    pendente: 'pendente',
    aprovada: 'aprovada',
    rejeitada: 'rejeitada',
  };

  // Verificar se o status é diferente do atual
  if (novoStatus === props.matricula.status) {
    toast.info(`A matrícula já está ${statusTexto[novoStatus]}`, 'info');
    return;
  }

  // Se for rejeição, usa o modal específico
  if (novoStatus === 'rejeitada') {
    showRejeicaoModal.value = true;
    return;
  }

  confirmModalTitle.value = `Alterar Status para ${statusLabels[novoStatus]}`;
  confirmModalMessage.value = `Tem certeza que deseja alterar o status da matrícula para ${statusTexto[novoStatus]}?`;

  confirmModalAction.value = () => {
    isChangingStatus.value = true;

    const form = useForm({
      status: novoStatus,
    });

    form.patch(
      route('admin.matriculas.alterar-status', { id: props.matricula.id }),
      {
        onSuccess: () => {
          closeConfirmModal();
          toast.success(
            `Status alterado para ${statusTexto[novoStatus]} com sucesso!`
          );

          // Atualizar o status da matrícula localmente
          props.matricula.status = novoStatus;
          isChangingStatus.value = false;
        },
        onError: errors => {
          closeConfirmModal();
          toast.error('Erro ao alterar status da matrícula');
          console.error(errors);
          isChangingStatus.value = false;
        },
      }
    );
  };

  showConfirmModal.value = true;
};

// Handler para confirmação da rejeição
const handleRejeicaoConfirmada = ({ matriculaId, motivo }) => {
  isChangingStatus.value = true;

  const form = useForm({
    status: 'rejeitada',
    motivo_rejeicao: motivo,
  });

  form.patch(route('admin.matriculas.alterar-status', { id: matriculaId }), {
    onSuccess: () => {
      showRejeicaoModal.value = false;
      toast.success('Matrícula rejeitada com sucesso!');

      // Atualizar o status da matrícula localmente
      props.matricula.status = 'rejeitada';
      isChangingStatus.value = false;

      // Atualizar o seletor também
      selectedStatus.value = 'rejeitada';
    },
    onError: errors => {
      showRejeicaoModal.value = false;
      toast.error('Erro ao rejeitar matrícula');
      console.error(errors);
      isChangingStatus.value = false;
    },
  });
};

const closeConfirmModal = () => {
  showConfirmModal.value = false;
};

// Função de confirmação para o modal
const handleConfirm = () => {
  // Verificar se confirmModalAction.value é uma função antes de chamá-la
  if (typeof confirmModalAction.value === 'function') {
    confirmModalAction.value();
  } else {
    console.error('confirmModalAction não é uma função');
    closeConfirmModal();
  }
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Ficha de Inscrição" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiAccountKey"
        title="Ficha de Inscrição"
        main
      >
        <BaseButton
          :route-name="
            route('admin.matriculas.curso', { curso: props.curso_id })
          "
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>

      <!-- Seção de Alteração de Status -->
      <CardBox class="mb-6">
        <div
          class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
        >
          <div>
            <h3 class="text-lg font-medium mb-2">Status da Matrícula</h3>
            <span
              :class="statusClasses[matricula.status]"
              class="px-3 py-1 text-sm font-medium rounded-full"
            >
              {{ statusLabels[matricula.status] }}
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
                :disabled="
                  selectedStatus === matricula.status || isChangingStatus
                "
              />
            </div>
          </div>
        </div>
      </CardBox>

      <!-- Informações do Aluno -->
      <CardBox class="mb-6">
        <div class="mb-4">
          <h3 class="text-lg font-medium mb-4">Informações do Aluno</h3>
        </div>
        <table>
          <tbody>
            <tr>
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Nome
              </td>
              <td data-label="Nome">
                {{ matricula.aluno.name }}
              </td>
            </tr>
            <tr>
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Matrícula
              </td>
              <td data-label="Matrícula">
                {{ matricula.aluno.matricula }}
              </td>
            </tr>
            <tr>
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Lotação
              </td>
              <td data-label="Matrícula">
                {{ matricula.aluno.lotacao }}
              </td>
            </tr>
            <tr>
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Cargo
              </td>
              <td data-label="Matrícula">
                {{ matricula.aluno.cargo }}
              </td>
            </tr>
            <tr>
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Email
              </td>
              <td data-label="Email">
                {{ matricula.aluno.email }}
              </td>
            </tr>
            <tr>
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Data de Inscrição
              </td>
              <td data-label="Data de Inscrição">
                {{ formatDate(matricula.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </CardBox>

      <!-- Dados Adicionais da Inscrição -->
      <CardBox class="mb-6">
        <div class="mb-4">
          <h3 class="text-lg font-medium mb-4">
            Dados Adicionais da Inscrição
          </h3>
        </div>
        <table>
          <tbody>
            <tr>
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Experiência Prévia
              </td>
              <td data-label="Experiência Prévia">
                {{ dadosAdicionais.experiencia || 'Não informado' }}
              </td>
            </tr>
            <tr>
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Cursos relacionados que já participou
              </td>
              <td data-label="Cursos Anteriores">
                {{ dadosAdicionais.cursosAnteriores || 'Não informado' }}
              </td>
            </tr>
            <tr v-if="dadosAdicionais.expectativas">
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Expectativas para o Curso
              </td>
              <td data-label="Expectativas">
                {{ dadosAdicionais.expectativas }}
              </td>
            </tr>
            <tr v-if="dadosAdicionais.restricoesSaude">
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Há restrição de saúde?
              </td>
              <td data-label="Restrições de Saúde">
                {{ dadosAdicionais.restricoesSaude || 'Não informado' }}
              </td>
            </tr>
            <tr>
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Observações
              </td>
              <td data-label="Observações">
                {{ dadosAdicionais.observacoes || 'Nenhuma' }}
              </td>
            </tr>
            <tr>
              <td
                class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block"
              >
                Data de Preenchimento do Formulário
              </td>
              <td data-label="Data de Preenchimento">
                {{ formatDate(matricula.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
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

      <!-- Modal de Rejeição -->
      <RejeicaoMatriculaModal
        :is-open="showRejeicaoModal"
        :matricula-id="matricula.id"
        :matricula-info="matricula"
        @close="showRejeicaoModal = false"
        @confirm="handleRejeicaoConfirmada"
      />
    </SectionMain>
  </LayoutAuthenticated>
</template>
