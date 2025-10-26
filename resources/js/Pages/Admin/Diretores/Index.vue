<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  mdiAccount,
  mdiPlus,
  mdiSquareEditOutline,
  mdiTrashCan,
  mdiAlertBoxOutline,
  mdiEye,
  mdiMagnify,
} from '@mdi/js';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import BaseButton from '@/Components/BaseButton.vue';
import CardBox from '@/Components/CardBox.vue';
import BaseButtons from '@/Components/BaseButtons.vue';
import NotificationBar from '@/Components/NotificationBar.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import Sort from '@/Components/Admin/Sort.vue';
import { ref } from 'vue';

const props = defineProps({
  diretores: {
    type: Object,
    default: () => ({}),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  can: {
    type: Object,
    default: () => ({}),
  },
});

const form = useForm({
  search: props.filters.search || '',
});

const formDelete = useForm({});

// Estado para controlar o modal de confirmação
const deleteModal = ref({
  show: false,
  diretor: null,
});

function showDeleteModal(diretor) {
  deleteModal.value = {
    show: true,
    diretor: diretor,
  };
}

function hideDeleteModal() {
  deleteModal.value = {
    show: false,
    diretor: null,
  };
}

function confirmDelete() {
  if (deleteModal.value.diretor) {
    formDelete.delete(
      route('admin.directors.destroy', deleteModal.value.diretor.id),
      {
        onSuccess: () => {
          hideDeleteModal();
        },
      }
    );
  }
}

function clearSearch() {
  form.search = '';
  form.get(route('admin.directors.index'));
}

// Função para formatar período
function formatPeriod(diretor) {
  const inicio = diretor.data_inicio
    ? new Date(diretor.data_inicio).toLocaleDateString('pt-BR')
    : '';
  if (diretor.atual) {
    return `${inicio} - Atualmente`;
  }
  const fim = diretor.data_fim
    ? new Date(diretor.data_fim).toLocaleDateString('pt-BR')
    : '';
  return fim ? `${inicio} - ${fim}` : inicio;
}
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Diretores" />
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiAccount" title="Diretores" main>
        <div class="flex items-center gap-3">
          <!-- Link para galeria pública -->
          <BaseButton
            href="/diretores"
            :icon="mdiEye"
            label="Ver Galeria"
            color="light"
            rounded-full
            small
            target="_blank"
          />
          <BaseButton
            v-if="can.create"
            :route-name="route('admin.directors.create')"
            :icon="mdiPlus"
            label="Novo Diretor"
            color="info"
            rounded-full
            small
          />
        </div>
      </SectionTitleLineWithButton>

      <NotificationBar
        v-if="$page.props.flash.message"
        color="success"
        :icon="mdiAlertBoxOutline"
      >
        {{ $page.props.flash.message }}
      </NotificationBar>
      <!-- Barra de pesquisa -->
      <CardBox class="mb-6">
        <form
          @submit.prevent="form.get(route('admin.directors.index'))"
          class="p-4"
        >
          <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
              <input
                v-model="form.search"
                type="search"
                placeholder="Pesquisar por nome..."
                class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              />
              <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
              >
                <svg
                  class="h-5 w-5 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                  />
                </svg>
              </div>
              <button
                v-if="form.search"
                type="button"
                @click="clearSearch"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <svg
                  class="h-5 w-5 text-gray-400 hover:text-gray-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
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
            <BaseButton
              type="submit"
              color="info"
              :icon="mdiMagnify"
              label="Pesquisar"
              :disabled="form.processing"
            />
          </div>
        </form>
      </CardBox>

      <!-- Tabela de diretores -->
      <CardBox has-table>
        <!-- Estado vazio -->
        <div
          v-if="!diretores.data || diretores.data.length === 0"
          class="text-center py-12"
        >
          <svg
            class="mx-auto h-16 w-16 text-gray-400 mb-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
            />
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            {{
              form.search
                ? 'Nenhum diretor encontrado'
                : 'Nenhum diretor cadastrado'
            }}
          </h3>
          <p class="text-gray-500 mb-4">
            {{
              form.search
                ? 'Tente ajustar sua pesquisa.'
                : 'Comece cadastrando o primeiro diretor.'
            }}
          </p>
          <BaseButton
            v-if="can.create"
            :route-name="route('admin.directors.create')"
            :icon="mdiPlus"
            label="Cadastrar Primeiro Diretor"
            color="info"
          />
        </div>

        <!-- Tabela -->
        <table v-else>
          <thead>
            <tr>
              <th class="w-20">Foto</th>
              <th>
                <Sort label="Nome" attribute="nome" />
              </th>
              <th>
                <Sort label="Período" attribute="data_inicio" />
              </th>
              <th>
                <Sort label="Status" attribute="atual" />
              </th>
              <th v-if="can.edit || can.delete">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="diretor in diretores.data"
              :key="diretor.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <!-- Foto -->
              <td data-label="Foto" class="w-20">
                <div class="flex items-center justify-center">
                  <img
                    v-if="diretor.imagem"
                    :src="diretor.imagem"
                    :alt="diretor.nome"
                    class="h-12 w-12 object-cover rounded-full shadow-sm"
                  />
                  <div
                    v-else
                    class="h-12 w-12 bg-gray-200 rounded-full flex items-center justify-center"
                  >
                    <svg
                      class="h-6 w-6 text-gray-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                      />
                    </svg>
                  </div>
                </div>
              </td>

              <!-- Nome -->
              <td data-label="Nome">
                <div class="flex items-center gap-2">
                  <Link
                    :href="route('admin.directors.show', diretor.id)"
                    class="font-medium text-blue-600 hover:text-blue-800 transition-colors"
                  >
                    {{ diretor.nome }}
                  </Link>
                  <svg
                    v-if="diretor.atual"
                    class="h-4 w-4 text-green-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    title="Diretor Atual"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
              </td>

              <!-- Período -->
              <td data-label="Período" class="text-sm">
                {{ formatPeriod(diretor) }}
              </td>

              <!-- Status -->
              <td data-label="Status">
                <span
                  v-if="diretor.atual"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                >
                  <svg
                    class="w-3 h-3 mr-1"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Atual
                </span>
                <span
                  v-else
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                >
                  Anterior
                </span>
              </td>

              <!-- Ações -->
              <td
                v-if="can.edit || can.delete"
                class="before:hidden lg:w-1 whitespace-nowrap"
              >
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    :route-name="route('admin.directors.show', diretor.id)"
                    color="light"
                    :icon="mdiEye"
                    small
                    title="Visualizar"
                  />
                  <BaseButton
                    v-if="can.edit"
                    :route-name="route('admin.directors.edit', diretor.id)"
                    color="info"
                    :icon="mdiSquareEditOutline"
                    small
                    title="Editar"
                  />
                  <BaseButton
                    v-if="can.delete"
                    color="danger"
                    :icon="mdiTrashCan"
                    small
                    title="Excluir"
                    @click="showDeleteModal(diretor)"
                  />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Paginação -->
        <div v-if="diretores.data && diretores.data.length > 0" class="py-4">
          <Pagination :data="diretores" />
        </div>
      </CardBox>

      <!-- Modal de confirmação de exclusão -->
      <div
        v-if="deleteModal.show"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
      >
        <div
          class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
        >
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="hideDeleteModal"
          ></div>

          <span
            class="hidden sm:inline-block sm:align-middle sm:h-screen"
            aria-hidden="true"
            >&#8203;</span
          >

          <div
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
          >
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div
                  class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"
                >
                  <svg
                    class="h-6 w-6 text-red-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
                    />
                  </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                  <h3
                    class="text-lg leading-6 font-medium text-gray-900"
                    id="modal-title"
                  >
                    Confirmar exclusão
                  </h3>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">
                      Tem certeza de que deseja excluir o diretor
                      <strong>{{ deleteModal.diretor?.nome }}</strong
                      >? Esta ação não pode ser desfeita.
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div
              class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"
            >
              <button
                type="button"
                @click="confirmDelete"
                :disabled="formDelete.processing"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
              >
                {{ formDelete.processing ? 'Excluindo...' : 'Excluir' }}
              </button>
              <button
                type="button"
                @click="hideDeleteModal"
                :disabled="formDelete.processing"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
              >
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<style scoped>
/* Transições suaves */
.transition-colors {
  transition-property:
    color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Estados de hover para linhas da tabela */
tbody tr:hover {
  background-color: #f9fafb;
}

/* Estilo para o modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
