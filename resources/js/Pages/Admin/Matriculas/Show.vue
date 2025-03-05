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
  mdiAccountKey,
  mdiArrowLeftBoldOutline
} from "@mdi/js"

// Props
const props = defineProps({
  matricula: Object,
  id: Number
});

// Toast
const { toast } = useToast();

// Status de matrículas
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

// Modal de confirmação
const showConfirmModal = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref(() => {});

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
const formatDate = (dateString) => {
  if (!dateString) return 'Não informado';
  
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
};

// Funções de Ação
const aprovarMatricula = () => {
  confirmModalTitle.value = 'Aprovar Matrícula';
  confirmModalMessage.value = 'Tem certeza que deseja aprovar esta matrícula? O aluno será notificado e terá acesso ao curso.';
  confirmModalAction.value = () => {
    useForm().patch(route('admin.matriculas.aprovar', props.id), {
      onSuccess: () => {
        toast('Matrícula aprovada com sucesso!', 'success');
        closeConfirmModal();
        // Recarregar para atualizar o status
        window.location.reload();
      },
      onError: (errors) => {
        toast('Erro ao aprovar matrícula', 'error');
        console.error(errors);
        closeConfirmModal();
      }
    });
  };
  showConfirmModal.value = true;
};

const rejeitarMatricula = () => {
  confirmModalTitle.value = 'Rejeitar Matrícula';
  confirmModalMessage.value = 'Tem certeza que deseja rejeitar esta matrícula? O aluno será notificado e não terá acesso ao curso.';
  confirmModalAction.value = () => {
    useForm().patch(route('admin.matriculas.rejeitar', props.id), {
      onSuccess: () => {
        toast('Matrícula rejeitada com sucesso!', 'success');
        closeConfirmModal();
        // Recarregar para atualizar o status
        window.location.reload();
      },
      onError: (errors) => {
        toast('Erro ao rejeitar matrícula', 'error');
        console.error(errors);
        closeConfirmModal();
      }
    });
  };
  showConfirmModal.value = true;
};

const closeConfirmModal = () => {
  showConfirmModal.value = false;
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
          :route-name="route('admin.matriculas.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>

      <!-- Status da Matrícula -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <span 
            :class="statusClasses[matricula.status]" 
            class="px-3 py-1 text-sm font-medium rounded-full"
          >
            {{ statusLabels[matricula.status] }}
          </span>
        </div>
        
        <div v-if="matricula.status === 'pendente'" class="flex space-x-3">
          <BaseButton
            @click="aprovarMatricula"
            label="APROVAR"
            color="success"
            small
          />
          <BaseButton
            @click="rejeitarMatricula"
            label="REJEITAR"
            color="danger"
            small
          />
        </div>
      </div>

      <!-- Informações do Curso -->
      <!-- <CardBox class="mb-6">
        <div class="mb-4">
          <h3 class="text-lg font-medium mb-4">Informações do Curso</h3>
        </div>
        <table>
          <tbody>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Nome do Curso
              </td>
              <td data-label="Nome do Curso">
                {{ matricula.curso.nome }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Período
              </td>
              <td data-label="Período">
                {{ formatDate(matricula.curso.data_inicio) }} a {{ formatDate(matricula.curso.data_fim) }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Carga Horária
              </td>
              <td data-label="Carga Horária">
                {{ matricula.curso.carga_horaria }} HORAS
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Localização
              </td>
              <td data-label="Localização">
                {{ matricula.curso.localizacao }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Capacidade Máxima
              </td>
              <td data-label="Capacidade Máxima">
                {{ matricula.curso.capacidade_maxima }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Modalidade
              </td>
              <td data-label="Modalidade">
                {{ matricula.curso.modalidade }}
              </td>
            </tr>
          </tbody>
        </table>
      </CardBox> -->
      
      <!-- Informações do Aluno -->
      <CardBox class="mb-6">
        <div class="mb-4">
          <h3 class="text-lg font-medium mb-4">Informações do Aluno</h3>
        </div>
        <table>
          <tbody>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Nome
              </td>
              <td data-label="Nome">
                {{ matricula.aluno.name }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Matrícula
              </td>
              <td data-label="Matrícula">
                {{ matricula.aluno.matricula }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Email
              </td>
              <td data-label="Email">
                {{ matricula.aluno.email }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
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
          <h3 class="text-lg font-medium mb-4">Dados Adicionais da Inscrição</h3>
        </div>
        <table>
          <tbody>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Experiência Prévia
              </td>
              <td data-label="Experiência Prévia">
                {{ dadosAdicionais.experiencia || 'Não informado' }}
              </td>
            </tr>
            <tr v-if="dadosAdicionais.expectativas">
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Expectativas para o Curso
              </td>
              <td data-label="Expectativas">
                {{ dadosAdicionais.expectativas }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Data de Preenchimento do Formulário
              </td>
              <td data-label="Data de Preenchimento">
                {{ formatDate(matricula.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </CardBox>
      
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
    </SectionMain>
  </LayoutAuthenticated>
</template>