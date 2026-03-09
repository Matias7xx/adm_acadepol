<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
  mdiShieldSearch,
  mdiArrowLeft,
  mdiAccount,
  mdiTag,
  mdiDatabase,
} from '@mdi/js';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import CardBox from '@/Components/CardBox.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseIcon from '@/Components/BaseIcon.vue';

const props = defineProps({
  log: { type: Object, required: true },
  modulos: { type: Object, default: () => ({}) },
  acoes: { type: Object, default: () => ({}) },
});

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

function badgeClass(status) {
  return (
    {
      success: 'bg-green-100 text-green-800 border border-green-200',
      warning: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
      error: 'bg-red-100 text-red-800 border border-red-200',
    }[status] ?? 'bg-gray-100 text-gray-700 border border-gray-200'
  );
}

function labelStatus(status) {
  return (
    { success: 'Sucesso', warning: 'Aviso', error: 'Erro' }[status] ?? status
  );
}

// Formata JSON para exibição
function formatJson(obj) {
  if (!obj) return null;
  return JSON.stringify(obj, null, 2);
}

// Gera diff visual entre dados anteriores e novos
function gerarDiff() {
  if (!props.log.dados_anteriores || !props.log.dados_novos) return null;
  const antes = props.log.dados_anteriores;
  const depois = props.log.dados_novos;
  const keys = new Set([...Object.keys(antes), ...Object.keys(depois)]);
  return [...keys]
    .map(k => ({
      campo: k,
      antes: antes[k] ?? null,
      depois: depois[k] ?? null,
      mudou: JSON.stringify(antes[k]) !== JSON.stringify(depois[k]),
    }))
    .filter(d => d.mudou);
}

const diff = gerarDiff();
</script>

<template>
  <LayoutAuthenticated>
    <Head :title="`Auditoria #${log.id}`" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiShieldSearch"
        :title="`Log de Auditoria #${log.id}`"
        main
      >
        <BaseButton
          :icon="mdiArrowLeft"
          label="Voltar"
          color="whiteDark"
          :route-name="route('admin.audit-logs.index')"
          small
        />
      </SectionTitleLineWithButton>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Informações da ação -->
        <CardBox>
          <h3
            class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2"
          >
            <BaseIcon :path="mdiTag" size="18" class="text-blue-500" />
            Detalhes da Ação
          </h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2">
              <dt class="text-gray-500 font-medium">Status</dt>
              <dd>
                <span
                  class="px-2 py-0.5 rounded text-xs font-semibold"
                  :class="badgeClass(log.status)"
                >
                  {{ labelStatus(log.status) }}
                </span>
              </dd>
            </div>
            <div class="flex justify-between border-b pb-2">
              <dt class="text-gray-500 font-medium">Módulo</dt>
              <dd class="text-gray-800 font-medium">
                {{ modulos[log.modulo] ?? log.modulo }}
              </dd>
            </div>
            <div class="flex justify-between border-b pb-2">
              <dt class="text-gray-500 font-medium">Ação</dt>
              <dd class="text-gray-800 font-medium">
                {{ acoes[log.acao] ?? log.acao }}
              </dd>
            </div>
            <div class="flex justify-between border-b pb-2">
              <dt class="text-gray-500 font-medium">Descrição</dt>
              <dd class="text-gray-800 text-right max-w-xs">
                {{ log.descricao ?? '-' }}
              </dd>
            </div>
            <div class="flex justify-between border-b pb-2">
              <dt class="text-gray-500 font-medium">Data/Hora</dt>
              <dd class="text-gray-800">{{ formatarData(log.created_at) }}</dd>
            </div>
            <div
              v-if="log.model_type"
              class="flex justify-between border-b pb-2"
            >
              <dt class="text-gray-500 font-medium">Registro afetado</dt>
              <dd class="text-gray-800 font-mono text-xs text-right">
                {{ log.model_type }} #{{ log.model_id }}
                <span v-if="log.model_label" class="block text-gray-500">{{
                  log.model_label
                }}</span>
              </dd>
            </div>
          </dl>
        </CardBox>

        <!-- Informações do usuário -->
        <CardBox>
          <h3
            class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2"
          >
            <BaseIcon :path="mdiAccount" size="18" class="text-indigo-500" />
            Contexto do Usuário
          </h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2">
              <dt class="text-gray-500 font-medium">Nome</dt>
              <dd class="text-gray-800">
                {{ log.user_name ?? 'Sistema/Anônimo' }}
              </dd>
            </div>
            <div
              v-if="log.user_matricula"
              class="flex justify-between border-b pb-2"
            >
              <dt class="text-gray-500 font-medium">Matrícula</dt>
              <dd class="text-gray-800 font-mono">{{ log.user_matricula }}</dd>
            </div>
            <div
              v-if="log.user_email"
              class="flex justify-between border-b pb-2"
            >
              <dt class="text-gray-500 font-medium">E-mail</dt>
              <dd class="text-gray-800">{{ log.user_email }}</dd>
            </div>
            <div class="flex justify-between border-b pb-2">
              <dt class="text-gray-500 font-medium">IP</dt>
              <dd class="text-gray-800 font-mono">{{ log.ip ?? '-' }}</dd>
            </div>
            <div class="flex justify-between border-b pb-2">
              <dt class="text-gray-500 font-medium">Método HTTP</dt>
              <dd>
                <span
                  class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-xs font-mono font-semibold"
                >
                  {{ log.method ?? '-' }}
                </span>
              </dd>
            </div>
            <div class="flex justify-between pb-2">
              <dt class="text-gray-500 font-medium">URL</dt>
              <dd
                class="text-gray-700 text-xs text-right max-w-xs truncate font-mono"
                :title="log.url"
              >
                {{ log.url ?? '-' }}
              </dd>
            </div>
            <div
              v-if="log.user_agent"
              class="flex justify-between border-t pt-2 mt-1"
            >
              <dt class="text-gray-500 font-medium shrink-0 mr-4">
                User Agent
              </dt>
              <dd
                class="text-gray-500 text-xs text-right break-all leading-relaxed"
                :title="log.user_agent"
              >
                {{ log.user_agent }}
              </dd>
            </div>
          </dl>
        </CardBox>
      </div>

      <!-- Diff de dados (edições) -->
      <CardBox v-if="diff && diff.length > 0" class="mt-6">
        <h3
          class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2"
        >
          <BaseIcon :path="mdiDatabase" size="18" class="text-orange-500" />
          Alterações realizadas
        </h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr
                class="text-left text-xs font-semibold text-gray-500 uppercase border-b"
              >
                <th class="pb-2 pr-4">Campo</th>
                <th class="pb-2 pr-4 text-red-600">Antes</th>
                <th class="pb-2 text-green-600">Depois</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="d in diff"
                :key="d.campo"
                class="border-b border-gray-100"
              >
                <td
                  class="py-2 pr-4 font-mono text-xs text-gray-600 font-semibold"
                >
                  {{ d.campo }}
                </td>
                <td
                  class="py-2 pr-4 text-xs text-red-700 bg-red-50 rounded px-2 max-w-xs truncate"
                >
                  {{ d.antes !== null ? String(d.antes) : '(vazio)' }}
                </td>
                <td
                  class="py-2 text-xs text-green-700 bg-green-50 rounded px-2 max-w-xs truncate"
                >
                  {{ d.depois !== null ? String(d.depois) : '(vazio)' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <!-- JSON -->
      <div
        v-if="log.dados_anteriores || log.dados_novos"
        class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2"
      >
        <CardBox v-if="log.dados_anteriores">
          <h3 class="text-sm font-semibold text-red-700 mb-3">
            Dados anteriores (JSON)
          </h3>
          <pre
            class="text-xs text-gray-700 bg-gray-50 p-3 rounded overflow-x-auto"
            >{{ formatJson(log.dados_anteriores) }}</pre
          >
        </CardBox>
        <CardBox v-if="log.dados_novos">
          <h3 class="text-sm font-semibold text-green-700 mb-3">
            Dados novos (JSON)
          </h3>
          <pre
            class="text-xs text-gray-700 bg-gray-50 p-3 rounded overflow-x-auto"
            >{{ formatJson(log.dados_novos) }}</pre
          >
        </CardBox>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>
