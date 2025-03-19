<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import BaseButton from "@/Components/BaseButton.vue"
import CardBox from "@/Components/CardBox.vue"
import {
  mdiArrowLeftBoldOutline,
  mdiBookPlusMultiple,
  mdiMagnify,
  mdiEye,
  mdiCheckCircle,
  mdiCloseCircle,
  mdiSwapHorizontal,
  mdiBook
} from "@mdi/js"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import FormField from "@/Components/FormField.vue"
import BaseButtons from "@/Components/BaseButtons.vue"
import RejeicaoMatriculaModal from '@/Pages/Components/RejeicaoMatriculaModal.vue';

// Props
const props = defineProps({
  matriculas: Object,
  cursos: Array
});

// Toast
const { toast } = useToast();

// Estado local
const search = ref('');
const statusFilter = ref('');
const cursoFilter = ref('');

// Modal de confirmação padrão
const showConfirmModal = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref(() => {});

// Modal de rejeição
const showRejeicaoModal = ref(false);
const matriculaParaRejeitar = ref(null);

// Status de matrículas
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
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
};

// Filtragem de matrículas
const filteredMatriculas = computed(() => {
  let filtered = props.matriculas.data;
  
  if (search.value) {
    const searchLower = search.value.toLowerCase();
    filtered = filtered.filter(item => 
      item.aluno.name.toLowerCase().includes(searchLower) ||
      item.aluno.matricula.toLowerCase().includes(searchLower)
    );
  }
  
  if (statusFilter.value) {
    filtered = filtered.filter(item => item.status === statusFilter.value);
  }
  
  if (cursoFilter.value) {
    filtered = filtered.filter(item => item.curso_id === parseInt(cursoFilter.value));
  }
  
  return filtered;
});

// Funções de Ação
const alterarStatus = (id, novoStatus, mensagemConfirmacao) => {
  // Se for rejeição, abre o modal específico
  if (novoStatus === 'rejeitada') {
    const matricula = props.matriculas.data.find(m => m.id === id);
    if (matricula) {
      matriculaParaRejeitar.value = matricula;
      showRejeicaoModal.value = true;
    }
    return;
  }
  
  const mensagens = {
    aprovada: 'aprova',
    pendente: 'retornar para pendente'
  };
  
  confirmModalTitle.value = `${mensagens[novoStatus].charAt(0).toUpperCase() + mensagens[novoStatus].slice(1)} Matrícula`;
  confirmModalMessage.value = mensagemConfirmacao || `Tem certeza que deseja ${mensagens[novoStatus]} esta matrícula?`;
  
  // Define a função de confirmação diretamente
  confirmModalAction.value = () => {
    // Criar novo form para cada solicitação
    const form = useForm({
      status: novoStatus
    });
    
    form.patch(route('admin.matriculas.alterar-status', id), {
      onSuccess: () => {
        closeConfirmModal();
        toast.success(`Matrícula ${mensagens[novoStatus]}da com sucesso!`);
        
        // Atualiza o item na lista local em vez de recarregar a página
        const index = props.matriculas.data.findIndex(m => m.id === id);
        if (index !== -1) {
          props.matriculas.data[index].status = novoStatus;
        }
      },
      onError: (errors) => {
        closeConfirmModal();
        toast.error(`Erro ao ${mensagens[novoStatus]} matrícula`);
        console.error(errors);
      }
    });
  };
  
  showConfirmModal.value = true;
};

const aprovarMatricula = (id) => {
  alterarStatus(id, 'aprovada', 'Tem certeza que deseja aprovar esta matrícula? O aluno será notificado e terá acesso ao curso.');
};

const rejeitarMatricula = (id) => {
  alterarStatus(id, 'rejeitada');
};

const closeConfirmModal = () => {
  showConfirmModal.value = false;
};

// Função para rejeitar a matrícula com motivo
const handleRejeicaoConfirmada = ({ matriculaId, motivo }) => {
  const form = useForm({
    status: 'rejeitada',
    motivo_rejeicao: motivo
  });
    
  form.patch(route('admin.matriculas.alterar-status', matriculaId), {
    onSuccess: () => {
      showRejeicaoModal.value = false;
      toast.success('Matrícula rejeitada com sucesso!');
        
      // Atualiza o item na lista local
      const index = props.matriculas.data.findIndex(m => m.id === matriculaId);
      if (index !== -1) {
        props.matriculas.data[index].status = 'rejeitada';
      }
    },
    onError: (errors) => {
      showRejeicaoModal.value = false;
      toast.error('Erro ao rejeitar matrícula');
      console.error(errors);
    }
  });
};

// Função de confirmação para o modal padrão
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
    <Head title="Matrículas" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiBookPlusMultiple"
        title="Matrículas"
        main
      >
        <BaseButton
          :route-name="route('admin.cursos.index')"
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
              placeholder="Nome ou matrícula do aluno"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
            />
          </FormField>
          
          <!-- Seletor de Status corrigido -->
          <FormField label="Status">
            <select
              v-model="statusFilter"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
            >
              <option value="">Todos os Status</option>
              <option value="pendente">Pendente</option>
              <option value="aprovada">Aprovada</option>
              <option value="rejeitada">Rejeitada</option>
            </select>
          </FormField>
          
          <!-- Seletor de Curso corrigido -->
          <FormField label="Curso">
            <select
              v-model="cursoFilter"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
            >
              <option value="">Todos os Cursos</option>
              <option v-for="curso in cursos" :key="curso.id" :value="curso.id">
                {{ curso.nome }}
              </option>
            </select>
          </FormField>
        </div>
      </CardBox>

      <!-- Tabela de Matrículas -->
      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Aluno</th>
              <th>Curso</th>
              <th>Data de Inscrição</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="matricula in filteredMatriculas" :key="matricula.id">
              <td data-label="ID">{{ matricula.id }}</td>
              <td data-label="Aluno">
                <div>{{ matricula.aluno.name }}</div>
                <small class="text-gray-500 dark:text-gray-400">{{ matricula.aluno.matricula }}</small>
              </td>
              <td data-label="Curso">
                {{ matricula.curso.nome }}
              </td>
              <td data-label="Data">
                {{ formatDate(matricula.created_at) }}
              </td>
              <td data-label="Status" class="lg:w-1">
                <BaseButton
                  :color="statusColors[matricula.status]"
                  :label="statusLabels[matricula.status]"
                  small
                  :rounded="true"
                />
              </td>
              <td data-label="Ações" class="lg:w-1 whitespace-nowrap">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    :route-name="route('admin.matriculas.show', matricula.id)"
                    :icon="mdiEye"
                    small
                    color="info"
                    outline
                  />
                  
                  <!-- Ações para status pendente -->
                  <template v-if="matricula.status === 'pendente'">
                    <BaseButton
                      @click="aprovarMatricula(matricula.id)"
                      :icon="mdiCheckCircle"
                      small
                      color="success"
                      outline
                    />
                    <BaseButton
                      @click="rejeitarMatricula(matricula.id)"
                      :icon="mdiCloseCircle"
                      small
                      color="danger"
                      outline
                    />
                  </template>
                  
                  <!-- Ações para status aprovado -->
                  <template v-if="matricula.status === 'aprovada'">
                    <BaseButton
                      @click="rejeitarMatricula(matricula.id)"
                      :icon="mdiSwapHorizontal"
                      small
                      color="danger"
                      outline
                      title="Mudar para Rejeitado"
                    />
                  </template>
                  
                  <!-- Ações para status rejeitado -->
                  <template v-if="matricula.status === 'rejeitada'">
                    <BaseButton
                      @click="aprovarMatricula(matricula.id)"
                      :icon="mdiSwapHorizontal"
                      small
                      color="success"
                      outline
                      title="Mudar para Aprovado"
                    />
                  </template>
                </BaseButtons>
              </td>
            </tr>
            
            <!-- Mensagem de "Sem resultados" -->
            <tr v-if="filteredMatriculas.length === 0">
              <td colspan="6" class="text-center py-4">
                Nenhuma matrícula encontrada com os filtros selecionados.
              </td>
            </tr>
          </tbody>
        </table>
      </CardBox>
      
      <!-- Paginação -->
      <div class="mt-6" v-if="matriculas.links && matriculas.links.length > 3">
        <CardBox>
          <div class="flex items-center justify-between">
            <small>Mostrando {{ matriculas.from }} a {{ matriculas.to }} de {{ matriculas.total }} resultados</small>
            <div class="flex">
              <Link
                v-for="(link, i) in matriculas.links"
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
        :matricula-id="matriculaParaRejeitar?.id"
        :matricula-info="matriculaParaRejeitar"
        @close="showRejeicaoModal = false"
        @confirm="handleRejeicaoConfirmada"
      />
    </SectionMain>
  </LayoutAuthenticated>
</template>