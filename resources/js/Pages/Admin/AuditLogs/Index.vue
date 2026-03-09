<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  mdiShieldSearch,
  mdiFilterOutline,
  mdiFilterOffOutline,
  mdiMagnify,
  mdiEye,
  mdiAlertCircleOutline,
  mdiCheckCircleOutline,
  mdiAlertOutline,
  mdiInformationOutline,
} from '@mdi/js';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import CardBox from '@/Components/CardBox.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseIcon from '@/Components/BaseIcon.vue';
import FormField from '@/Components/FormField.vue';
import FormControl from '@/Components/FormControl.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({
  logs: { type: Object, required: true },
  stats: { type: Object, required: true },
  modulos: { type: Object, default: () => ({}) },
  acoes: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  can: { type: Object, default: () => ({}) },
});

// Estado dos filtros
const filtros = ref({
  modulo: props.filters.modulo ?? '',
  acao: props.filters.acao ?? '',
  status: props.filters.status ?? '',
  usuario: props.filters.usuario ?? '',
  search: props.filters.search ?? '',
  data_inicio: props.filters.data_inicio ?? '',
  data_fim: props.filters.data_fim ?? '',
});

const mostrarFiltros = ref(
  Object.values(props.filters).some(v => v && v !== '')
);

// Opções de status — formato { chave: 'label' }
const statusOptions = {
  '': 'Todos os status',
  success: 'Sucesso',
  warning: 'Aviso',
  error: 'Erro',
};

// Opções de módulos
const moduloOptions = computed(() => ({
  '': 'Todos os módulos',
  ...props.modulos,
}));

// Opções de ações
const acaoOptions = computed(() => ({
  '': 'Todas as ações',
  ...props.acoes,
}));

// Aplicar filtros
function aplicarFiltros() {
  const params = {};
  Object.entries(filtros.value).forEach(([k, v]) => {
    if (v && v !== '') params[k] = v;
  });
  router.get(route('admin.audit-logs.index'), params, { preserveState: true });
}

// Limpar filtros
function limparFiltros() {
  filtros.value = {
    modulo: '',
    acao: '',
    status: '',
    usuario: '',
    search: '',
    data_inicio: '',
    data_fim: '',
  };
  router.get(route('admin.audit-logs.index'), {}, { preserveState: false });
}

// Debounce na busca de texto
let debounceTimer = null;
watch(
  () => filtros.value.search,
  () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(aplicarFiltros, 500);
  }
);

// Cor de badge por status
function badgeClass(status) {
  return (
    {
      success: 'bg-green-100 text-green-800',
      warning: 'bg-yellow-100 text-yellow-800',
      error: 'bg-red-100 text-red-800',
    }[status] ?? 'bg-gray-100 text-gray-800'
  );
}

// Cor de badge por ação
function acaoBadgeClass(acao) {
  const criacao = ['criar', 'login'];
  const edicao = [
    'editar',
    'alterar_status',
    'toggle_destaque',
    'atualizar_ordem',
    'ativar',
    'alterar_senha',
    'atribuir_role',
    'publicar',
    'gerar_pdf',
  ];
  const exclusao = ['excluir', 'logout', 'desativar', 'arquivar'];
  const perigo = ['falha_login', 'acesso_negado', 'erro'];

  if (criacao.includes(acao)) return 'bg-blue-100 text-blue-800';
  if (edicao.includes(acao)) return 'bg-indigo-100 text-indigo-800';
  if (exclusao.includes(acao)) return 'bg-orange-100 text-orange-800';
  if (perigo.includes(acao)) return 'bg-red-100 text-red-800';
  return 'bg-gray-100 text-gray-700';
}

function iconeStatus(status) {
  return (
    {
      success: mdiCheckCircleOutline,
      warning: mdiAlertOutline,
      error: mdiAlertCircleOutline,
    }[status] ?? mdiInformationOutline
  );
}

function corIconeStatus(status) {
  return (
    {
      success: 'text-green-500',
      warning: 'text-yellow-500',
      error: 'text-red-500',
    }[status] ?? 'text-gray-400'
  );
}

function formatarData(iso) {
  if (!iso) return '-';
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

function temFiltroAtivo() {
  return Object.values(filtros.value).some(v => v && v !== '');
}
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Logs de Auditoria" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiShieldSearch"
        title="Logs de Auditoria"
        main
      >
        <BaseButton
          :icon="mostrarFiltros ? mdiFilterOffOutline : mdiFilterOutline"
          :label="mostrarFiltros ? 'Ocultar filtros' : 'Filtros'"
          color="info"
          small
          @click="mostrarFiltros = !mostrarFiltros"
        />
      </SectionTitleLineWithButton>

      <!-- Painel de filtros -->
      <CardBox v-if="mostrarFiltros" class="mb-6">
        <div
          class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
          <!-- Busca geral -->
          <FormField label="Busca">
            <FormControl
              v-model="filtros.search"
              :icon="mdiMagnify"
              placeholder="Descrição, modelo..."
            />
          </FormField>

          <!-- Módulo -->
          <FormField label="Módulo">
            <FormControl
              v-model="filtros.modulo"
              type="select"
              :options="moduloOptions"
            />
          </FormField>

          <!-- Ação -->
          <FormField label="Ação">
            <FormControl
              v-model="filtros.acao"
              type="select"
              :options="acaoOptions"
            />
          </FormField>

          <!-- Status -->
          <FormField label="Status">
            <FormControl
              v-model="filtros.status"
              type="select"
              :options="statusOptions"
            />
          </FormField>

          <!-- Usuário -->
          <FormField label="Usuário (nome ou matrícula)">
            <FormControl
              v-model="filtros.usuario"
              placeholder="Ex: João Silva ou 1234567"
            />
          </FormField>

          <!-- Data início -->
          <FormField label="Data início">
            <FormControl v-model="filtros.data_inicio" type="date" />
          </FormField>

          <!-- Data fim -->
          <FormField label="Data fim">
            <FormControl v-model="filtros.data_fim" type="date" />
          </FormField>
        </div>

        <div class="flex gap-3 mt-4">
          <BaseButton
            label="Aplicar filtros"
            color="info"
            :icon="mdiMagnify"
            @click="aplicarFiltros"
          />
          <BaseButton
            v-if="temFiltroAtivo()"
            label="Limpar filtros"
            color="warning"
            :icon="mdiFilterOffOutline"
            @click="limparFiltros"
          />
        </div>
      </CardBox>

      <!-- Tabela de logs -->
      <CardBox has-table>
        <div
          v-if="logs.data.length === 0"
          class="py-16 text-center text-gray-500"
        >
          <BaseIcon
            :path="mdiShieldSearch"
            size="48"
            class="mx-auto mb-3 text-gray-300"
          />
          <p class="text-lg font-medium">Nenhum registro encontrado</p>
          <p class="text-sm mt-1">Tente ajustar os filtros de busca.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr
                class="border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
              >
                <th class="pb-3 pr-4">Data/Hora</th>
                <th class="pb-3 pr-4">Usuário</th>
                <th class="pb-3 pr-4">Módulo</th>
                <th class="pb-3 pr-4">Ação</th>
                <th class="pb-3 pr-4">Descrição</th>
                <th class="pb-3 pr-4">IP</th>
                <th class="pb-3 pr-2 text-right">Ver</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="log in logs.data"
                :key="log.id"
                class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
              >
                <!-- Data -->
                <td class="py-3 pr-4 whitespace-nowrap text-gray-600 text-xs">
                  {{ formatarData(log.created_at) }}
                </td>

                <!-- Usuário -->
                <td class="py-3 pr-4">
                  <div class="font-medium text-gray-800 text-xs">
                    {{ log.user_name ?? 'Sistema' }}
                  </div>
                  <div v-if="log.user_matricula" class="text-gray-400 text-xs">
                    Mat. {{ log.user_matricula }}
                  </div>
                </td>

                <!-- Módulo -->
                <td class="py-3 pr-4">
                  <span
                    class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700"
                  >
                    {{ modulos[log.modulo] ?? log.modulo }}
                  </span>
                </td>

                <!-- Ação -->
                <td class="py-3 pr-4">
                  <span
                    class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                    :class="acaoBadgeClass(log.acao)"
                  >
                    {{ acoes[log.acao] ?? log.acao }}
                  </span>
                </td>

                <!-- Descrição -->
                <td class="py-3 pr-4 max-w-xs">
                  <p
                    class="truncate text-gray-700 text-xs"
                    :title="log.descricao"
                  >
                    {{ log.descricao ?? '-' }}
                  </p>
                  <p
                    v-if="log.model_label"
                    class="text-gray-400 text-xs truncate"
                  >
                    {{ log.model_label }}
                  </p>
                </td>

                <!-- IP -->
                <td
                  class="py-3 pr-4 whitespace-nowrap text-xs text-gray-500 font-mono"
                >
                  {{ log.ip ?? '-' }}
                </td>

                <!-- Detalhes -->
                <td class="py-3 pr-2 text-right">
                  <Link
                    :href="route('admin.audit-logs.show', log.id)"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-blue-100 text-blue-600 transition-colors"
                    title="Ver detalhes"
                  >
                    <BaseIcon :path="mdiEye" size="16" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginação -->
        <div class="py-4 px-2">
          <Pagination :data="logs" />
        </div>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
