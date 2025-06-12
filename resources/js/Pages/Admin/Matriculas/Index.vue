<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import BaseButton from "@/Components/BaseButton.vue"
import CardBox from "@/Components/CardBox.vue"
import {
  mdiArrowLeft,
  mdiBook,
  mdiMagnify,
  mdiEye,
  mdiCheck,
  mdiClose,
  mdiSwapHorizontal,
  mdiDownload,
  mdiFileDocument,
  mdiChartLine
} from "@mdi/js"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import FormField from "@/Components/FormField.vue"
import BaseButtons from "@/Components/BaseButtons.vue"
import RejeicaoMatriculaModal from '@/Pages/Components/RejeicaoMatriculaModal.vue';

// Props
const props = defineProps({
  matriculas: Object,
  cursos: Array,
  cursoAtual: Object,
  filters: Object
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

// Modal de relatórios
const showRelatorioModal = ref(false);
const relatorioForm = useForm({
  formato: 'pdf',
  curso_id: ''
});

const getCursoAtual = () => {
  return props.cursoAtual?.id || null;
};



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

// Contagem de matrículas aprovadas para relatório
const matriculasAprovadasCount = computed(() => {
  return props.matriculas.data.filter(m => m.status === 'aprovada').length;
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
  alterarStatus(id, 'aprovada', 'Tem certeza que deseja aprovar esta matrícula? O servidor será notificado e terá acesso ao curso.');
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

// Funções para relatórios
const abrirModalRelatorio = () => {
  relatorioForm.reset();
  relatorioForm.formato = 'pdf'; // definir PDF como padrão
  showRelatorioModal.value = true;
};

const fecharModalRelatorio = () => {
  showRelatorioModal.value = false;
  relatorioForm.reset();
};

const gerarRelatorio = () => {
  // Tentar obter o curso_id
  let cursoId = null;
  
  // Verificar se há um curso atual nas props
  if (props.cursoAtual && props.cursoAtual.id) {
    cursoId = props.cursoAtual.id;
    console.log('Curso ID encontrado em cursoAtual:', cursoId);
  }
  //Verificar se há curso_id nos filtros
  else if (props.filters && props.filters.curso_id) {
    cursoId = props.filters.curso_id;
    console.log('Curso ID encontrado em filters:', cursoId);
  }
  //Verificar na URL
  else {
    const urlParams = new URLSearchParams(window.location.search);
    cursoId = urlParams.get('curso_id');
    console.log('Curso ID encontrado na URL:', cursoId);
  }
  // Verificar se estamos numa rota de curso específico
  if (!cursoId) {
    const path = window.location.pathname;
    const cursoMatch = path.match(/\/curso\/(\d+)/);
    if (cursoMatch) {
      cursoId = cursoMatch[1];
      console.log('Curso ID encontrado no path:', cursoId);
    }
  }
  
  console.log('Curso ID final:', cursoId);
  
  if (!cursoId) {
    toast.error('Erro: Não foi possível identificar o curso. Acesse através de um curso específico.');
    fecharModalRelatorio();
    return;
  }
  
  if (relatorioForm.formato === 'pdf') {
    gerarRelatorioPDF(cursoId);
  } else {
    gerarRelatorioExcel(cursoId);
  }
};

const gerarRelatorioPDF = (cursoId) => {
  try {
    console.log('Gerando PDF para curso:', cursoId);
    const url = route('admin.matriculas.relatorio.pdf', { curso: cursoId });
    console.log('URL gerada:', url);
    
    // Criar um link temporário para download
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    fecharModalRelatorio();
    toast.success('Relatório PDF está sendo gerado...');
  } catch (error) {
    console.error('Erro ao gerar PDF:', error);
    toast.error('Erro ao gerar relatório PDF');
    fecharModalRelatorio();
  }
};

const gerarRelatorioExcel = (cursoId) => {
  try {
    console.log('Gerando Excel para curso:', cursoId);
    const url = route('admin.matriculas.relatorio.excel', { curso: cursoId });
    console.log('URL gerada:', url);
    
    // Criar um link temporário para download
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    fecharModalRelatorio();
    toast.success('Relatório Excel está sendo gerado...');
  } catch (error) {
    console.error('Erro ao gerar Excel:', error);
    toast.error('Erro ao gerar relatório Excel');
    fecharModalRelatorio();
  }
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Matrículas" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiBook"
        title="Matrículas"
        main
      >
        <BaseButtons>
          <BaseButton
            @click="abrirModalRelatorio"
            :icon="mdiChartLine"
            label="Relatórios"
            color="success"
            rounded-full
            small
            v-if="matriculasAprovadasCount > 0"
          />
          <BaseButton
            :route-name="route('admin.cursos.index')"
            :icon="mdiArrowLeft"
            label="Voltar"
            color="white"
            rounded-full
            small
          />
        </BaseButtons>
      </SectionTitleLineWithButton>

      <!-- Card de estatísticas -->
      <CardBox class="mb-6" v-if="matriculasAprovadasCount > 0">
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-green-800 dark:text-green-200">
                {{ matriculasAprovadasCount }} matrícula(s) aprovada(s) disponível(is) para relatório
              </p>
            </div>
          </div>
        </div>
      </CardBox>

      <!-- Filtros -->
      <CardBox class="mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <FormField label="Buscar" :icon="mdiMagnify">
            <input
              v-model="search"
              placeholder="Nome ou matrícula do servidor"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
            />
          </FormField>
          
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
        </div>
      </CardBox>

      <!-- Tabela de Matrículas -->
      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Servidor</th>
              <th>Data de Inscrição</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="matricula in filteredMatriculas" :key="matricula.id">
              <td data-label="ID">{{ matricula.id }}</td>
              <td data-label="Servidor">
                <div>{{ matricula.aluno.name }}</div>
                <small class="text-gray-500 dark:text-gray-400">{{ matricula.aluno.matricula }}</small>
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
                      :icon="mdiCheck"
                      small
                      color="success"
                      outline
                    />
                    <BaseButton
                      @click="rejeitarMatricula(matricula.id)"
                      :icon="mdiClose"
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

      <!-- Modal de Relatórios -->
      <div 
  v-if="showRelatorioModal" 
  class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
>
  <div class="relative mx-auto p-6 border w-[400px] shadow-lg rounded-md bg-white dark:bg-gray-800">
    <div class="mt-3">
      <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
        <svg class="inline w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        Gerar Relatório de Servidores Inscritos
      </h3>
      
      <div class="space-y-4">
        <!-- Informação sobre o curso -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-800 dark:text-blue-200">
                Curso: <strong>{{ cursoAtual?.nome || 'Curso Selecionado' }}</strong><br>
                Total de inscritos: <strong>{{ matriculasAprovadasCount }}</strong>
              </p>
            </div>
          </div>
        </div>

        <!-- Formato do relatório -->
        <FormField label="Formato do Relatório">
          <div class="flex space-x-4">
            <label class="flex items-center">
              <input 
                type="radio" 
                v-model="relatorioForm.formato" 
                value="pdf"
                class="mr-2 text-blue-600"
              />
              <svg class="w-5 h-5 mr-1 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0h8v12H6V4z" clip-rule="evenodd"></path>
              </svg>
              PDF
            </label>
            <label class="flex items-center">
              <input 
                type="radio" 
                v-model="relatorioForm.formato" 
                value="excel"
                class="mr-2 text-blue-600"
              />
              <svg class="w-5 h-5 mr-1 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0h8v12H6V4z" clip-rule="evenodd"></path>
              </svg>
              Excel
            </label>
          </div>
        </FormField>
      </div>
      
      <div class="flex justify-end gap-4 mt-6">
        <BaseButton 
          @click="gerarRelatorio" 
          :label="`Gerar ${relatorioForm.formato.toUpperCase()}`"
          :icon="mdiFileDocument"
          color="success"
        />
        <BaseButton 
          @click="fecharModalRelatorio" 
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