<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import BaseButton from "@/Components/BaseButton.vue"
import CardBox from "@/Components/CardBox.vue"
import {
  mdiArrowLeftBoldOutline,
  mdiFileDocumentOutline,
  mdiMagnify,
  mdiEye,
} from "@mdi/js"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import FormField from "@/Components/FormField.vue"
import BaseButtons from "@/Components/BaseButtons.vue"

// Props
const props = defineProps({
  requerimentos: Object,
  filters: Object
});

// Toast
const { toast } = useToast();

// Estado local
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

// Status de requerimentos
const statusLabels = {
  pendente: 'Pendente',
  deferido: 'Deferido',
  indeferido: 'Indeferido'
};

const statusColors = {
  pendente: 'warning',
  deferido: 'success',
  indeferido: 'danger'
};

// Formatação de data
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date);
};

const tipoRequerimentoFormatado = {
    'segunda_via_certificado': '2ª Via de Certificado',
    'declaracao_participacao': 'Declaração de Participação em Curso',
    'outros': 'Outros'
};

// Enviar formulário de busca
const submitSearch = () => {
  useForm({
    search: search.value,
    status: statusFilter.value
  }).get(route('admin.requerimentos.index'), {
    preserveState: true,
    replace: true
  });
};

// Limpar filtros
const clearFilters = () => {
  search.value = '';
  statusFilter.value = '';
  submitSearch();
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Requerimentos" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiFileDocumentOutline"
        title="Requerimentos"
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
              placeholder="Nome ou matrícula"
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
              <option value="deferido">Deferido</option>
              <option value="indeferido">Indeferido</option>
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
            <BaseButton
              @click="clearFilters"
              label="Limpar"
              color="white"
            />
          </div>
        </div>
      </CardBox>

      <!-- Tabela de Requerimentos -->
      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Solicitante</th>
              <th>Tipo</th>
              <th>Data</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="requerimento in props.requerimentos.data" :key="requerimento.id">
              <td data-label="ID">{{ requerimento.id }}</td>
              <td data-label="Solicitante">
                <div>{{ requerimento.nome }}</div>
                <small class="text-gray-500 dark:text-gray-400">{{ requerimento.matricula }}</small>
              </td>
              <td data-label="Tipo">
                {{ tipoRequerimentoFormatado[requerimento.tipo] || requerimento.tipo }}
              </td>
              <td data-label="Data">
                {{ formatDate(requerimento.created_at) }}
              </td>
              <td data-label="Status" class="lg:w-1">
                <BaseButton
                  :color="statusColors[requerimento.status]"
                  :label="statusLabels[requerimento.status]"
                  small
                  :rounded="true"
                />
              </td>
              <td data-label="Ações" class="lg:w-1 whitespace-nowrap">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    :route-name="route('admin.requerimentos.show', requerimento.id)"
                    :icon="mdiEye"
                    small
                    color="info"
                    outline
                    title="Ver detalhes"
                  />
                </BaseButtons>
              </td>
            </tr>
            
            <!-- Mensagem de "Sem resultados" -->
            <tr v-if="props.requerimentos.data.length === 0">
              <td colspan="7" class="text-center py-4">
                Nenhum requerimento encontrado com os filtros selecionados.
              </td>
            </tr>
          </tbody>
        </table>
      </CardBox>
      
      <!-- Paginação -->
      <div class="mt-6" v-if="props.requerimentos.links && props.requerimentos.links.length > 3">
        <CardBox>
          <div class="flex items-center justify-between">
            <small>Mostrando {{ props.requerimentos.from }} a {{ props.requerimentos.to }} de {{ props.requerimentos.total }} resultados</small>
            <div class="flex">
              <Link
                v-for="(link, i) in props.requerimentos.links"
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
    </SectionMain>
  </LayoutAuthenticated>
</template>