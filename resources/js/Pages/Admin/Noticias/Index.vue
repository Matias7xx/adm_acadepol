<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseButtons from '@/Components/BaseButtons.vue';
import CardBox from '@/Components/CardBox.vue';
import NotificationBar from '@/Components/NotificationBar.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import SubstituirDestaqueModal from '@/Components/SubstituirDestaqueModal.vue';
import DestaquesWidget from '@/Components/DestaquesWidget.vue';
import {
  mdiNewspaper,
  mdiPlus,
  mdiAlertBoxOutline,
  mdiSquareEditOutline,
  mdiTrashCan,
  mdiEye,
  mdiStar,
  mdiStarOutline,
} from '@mdi/js';

const props = defineProps({
  noticias: Object,
  filters: Object,
  can: Object,
});

const page = usePage();
const formDelete = useForm({});
const formDestaque = useForm({});

// Modal de substituição
const showSubstituirModal = ref(false);
const modalData = ref({
  noticiaNova: null,
  destaquesAtuais: [],
});

// Computed para detectar destaques_cheios no flash
const destaquesCheiосFlash = computed(() => page.props.flash?.destaques_cheios);

// Watch para detectar quando precisa mostrar o modal
watch(
  destaquesCheiосFlash,
  destaquesCheios => {
    if (destaquesCheios) {
      console.log('Destaques cheios detectados:', destaquesCheios);
      modalData.value = {
        noticiaNova: destaquesCheios.noticia_nova,
        destaquesAtuais: destaquesCheios.destaques_atuais,
      };
      showSubstituirModal.value = true;
    }
  },
  { immediate: true }
);

function destroy(id) {
  if (confirm('Tem certeza que deseja excluir esta notícia?')) {
    formDelete.delete(route('admin.noticias.destroy', id));
  }
}

function toggleDestaque(id) {
  formDestaque.patch(route('admin.noticias.toggle-destaque', id), {
    preserveScroll: true,
    onSuccess: () => {
      // Recarregar a página para atualizar o widget
      router.reload({ only: ['noticias'] });
    },
  });
}

const statusOptions = [
  { value: '', label: 'Todos os status' },
  { value: 'rascunho', label: 'Rascunho' },
  { value: 'publicado', label: 'Publicado' },
  { value: 'arquivado', label: 'Arquivado' },
];

const destaqueOptions = [
  { value: '', label: 'Todos' },
  { value: 'true', label: 'Em destaque' },
  { value: 'false', label: 'Sem destaque' },
];

function getStatusClass(status) {
  switch (status) {
    case 'publicado':
      return 'bg-green-100 text-green-800';
    case 'rascunho':
      return 'bg-gray-100 text-gray-800';
    case 'arquivado':
      return 'bg-orange-100 text-orange-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Notícias" />
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiNewspaper" title="Notícias" main>
        <div class="flex items-center gap-3">
          <BaseButton
            href="/noticias"
            :icon="mdiEye"
            label="Ver Notícias"
            color="light"
            rounded-full
            small
            target="_blank"
          />

          <BaseButton
            v-if="can.create"
            :route-name="route('admin.noticias.create')"
            :icon="mdiPlus"
            label="Cadastrar Notícia"
            color="info"
            rounded-full
            small
          />
        </div>
      </SectionTitleLineWithButton>

      <NotificationBar
        :key="Date.now()"
        v-if="$page.props.flash.message"
        color="success"
        :icon="mdiAlertBoxOutline"
      >
        {{ $page.props.flash.message }}
      </NotificationBar>

      <!-- Widget de Destaques -->
      <DestaquesWidget />

      <!-- Filtros -->
      <CardBox class="mb-6" has-table>
        <form
          @submit.prevent="router.get(route('admin.noticias.index'), filters)"
        >
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Buscar</label
              >
              <input
                v-model="filters.search"
                type="text"
                placeholder="Título ou descrição..."
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Status</label
              >
              <select
                v-model="filters.status"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
              >
                <option
                  v-for="option in statusOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Destaque</label
              >
              <select
                v-model="filters.destaque"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
              >
                <option
                  v-for="option in destaqueOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </div>
            <div class="flex items-end">
              <BaseButton
                type="submit"
                color="info"
                label="Filtrar"
                class="w-full"
              />
            </div>
          </div>
        </form>
      </CardBox>

      <!-- Tabela de Notícias -->
      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>Título</th>
              <th>Status</th>
              <th>Data Publicação</th>
              <th class="text-center">Destaque</th>
              <th class="text-center">Visualizações</th>
              <th v-if="can.edit || can.delete">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="noticia in noticias.data" :key="noticia.id">
              <td data-label="Título">
                <a
                  :href="route('admin.noticias.show', noticia.id)"
                  class="font-medium text-blue-600 hover:text-blue-800 hover:underline"
                >
                  {{ noticia.titulo }}
                </a>
              </td>
              <td data-label="Status">
                <span
                  class="px-2 py-1 text-xs font-medium rounded"
                  :class="getStatusClass(noticia.status)"
                >
                  {{ noticia.status }}
                </span>
              </td>
              <td data-label="Data">
                {{ new Date(noticia.data_publicacao).toLocaleDateString() }}
              </td>
              <td data-label="destaque" class="text-center">
                <BaseButton
                  v-if="noticia.destaque"
                  :icon="mdiStar"
                  color="warning"
                  small
                  rounded-full
                  @click="toggleDestaque(noticia.id)"
                  title="Remover destaque"
                />
                <BaseButton
                  v-else
                  :icon="mdiStarOutline"
                  color="white"
                  small
                  rounded-full
                  @click="toggleDestaque(noticia.id)"
                  title="Adicionar destaque"
                />
              </td>
              <td data-label="visualizacoes" class="text-center">
                {{ noticia.visualizacoes }}
              </td>
              <td
                v-if="can.edit || can.delete"
                class="before:hidden lg:w-1 whitespace-nowrap"
              >
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    v-if="can.edit"
                    :route-name="route('admin.noticias.edit', noticia.id)"
                    color="info"
                    :icon="mdiSquareEditOutline"
                    small
                  />
                  <BaseButton
                    v-if="can.delete"
                    color="danger"
                    :icon="mdiTrashCan"
                    small
                    @click="destroy(noticia.id)"
                  />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="noticias.data && noticias.data.length === 0">
              <td colspan="6" class="text-center py-4">
                Nenhuma notícia encontrada.
              </td>
            </tr>
          </tbody>
        </table>
        <div class="py-4">
          <Pagination v-if="noticias && noticias.links" :data="noticias" />
        </div>
      </CardBox>
    </SectionMain>

    <!-- Modal de Substituição de Destaque -->
    <SubstituirDestaqueModal
      v-if="modalData.noticiaNova"
      :show="showSubstituirModal"
      :noticia-nova="modalData.noticiaNova"
      :destaques-atuais="modalData.destaquesAtuais"
      @close="showSubstituirModal = false"
    />
  </LayoutAuthenticated>
</template>
