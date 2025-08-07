<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiNewspaper,
  mdiPlus,
  mdiSquareEditOutline,
  mdiTrashCan,
  mdiAlertBoxOutline,
  mdiStar,
  mdiStarOutline
} from "@mdi/js"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import BaseButton from "@/Components/BaseButton.vue"
import CardBox from "@/Components/CardBox.vue"
import BaseButtons from "@/Components/BaseButtons.vue"
import NotificationBar from "@/Components/NotificationBar.vue"
import Pagination from "@/Components/Admin/Pagination.vue"
import Sort from "@/Components/Admin/Sort.vue"

const props = defineProps({
  noticias: {
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
})

const form = useForm({
  search: props.filters.search,
  status: props.filters.status || '',
  destaque: props.filters.destaque || '',
})

const formDelete = useForm({})
const formDestaque = useForm({})

function destroy(id) {
  if (confirm("Tem certeza de que deseja remover esta notícia?")) {
    formDelete.delete(route("admin.noticias.destroy", id))
  }
}

function toggleDestaque(id) {
  formDestaque.patch(route("admin.noticias.toggle-destaque", id))
}

const statusOptions = [
  { value: '', label: 'Todos os status' },
  { value: 'rascunho', label: 'Rascunho' },
  { value: 'publicado', label: 'Publicado' },
  { value: 'arquivado', label: 'Arquivado' },
]

const destaqueOptions = [
  { value: '', label: 'Todos' },
  { value: 'true', label: 'Em destaque' },
  { value: 'false', label: 'Sem destaque' },
]

function getStatusClass(status) {
  switch (status) {
    case 'publicado': return 'bg-green-100 text-green-800'
    case 'rascunho': return 'bg-gray-100 text-gray-800'
    case 'arquivado': return 'bg-orange-100 text-orange-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Notícias" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiNewspaper"
        title="Notícias"
        main
      >
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
      <CardBox class="mb-6" has-table>
        <form @submit.prevent="form.get(route('admin.noticias.index'))">
          <div class="py-2 flex flex-wrap gap-2">
            <div class="flex pl-4 flex-grow">
              <input
                type="search"
                v-model="form.search"
                class="
                  rounded-md
                  shadow-sm
                  border-gray-300
                  focus:border-indigo-300
                  focus:ring
                  focus:ring-indigo-200
                  focus:ring-opacity-50
                  text-gray-900
                  flex-grow
                "
                placeholder="Buscar notícias..."
              />
              <BaseButton
                label="Pesquisar"
                type="submit"
                color="info"
                class="ml-4 inline-flex items-center px-4 py-2"
              />
            </div>
            <div class="flex gap-2 pl-4">
              <select 
                v-model="form.status"
                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-gray-900"
                @change="form.get(route('admin.noticias.index'))"
              >
                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>

              <select 
                v-model="form.destaque"
                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-gray-900"
                @change="form.get(route('admin.noticias.index'))"
              >
                <option v-for="option in destaqueOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
          </div>
        </form>
      </CardBox>
      <CardBox class="mb-6" has-table>
        <table>
          <thead>
            <tr>
              <th>
                <Sort label="Título" attribute="titulo" />
              </th>
              <th>
                <Sort label="Data de Publicação" attribute="data_publicacao" />
              </th>
              <th>
                <Sort label="Status" attribute="status" />
              </th>
              <th>
                <Sort label="Destaque" attribute="destaque" />
              </th>
              <th>
                <Sort label="Visualizações" attribute="visualizacoes" />
              </th>
              <th v-if="can.edit || can.delete">Ações</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="noticia in noticias.data" :key="noticia.id">
              <td data-label="titulo">
                <Link
                  :href="route('admin.noticias.show', noticia.id)"
                  class="
                    no-underline
                    hover:underline
                    text-cyan-600
                    dark:text-cyan-400
                  "
                >
                  {{ noticia.titulo }}
                </Link>
              </td>
              <td data-label="data_publicacao">
                {{ new Date(noticia.data_publicacao).toLocaleDateString() }}
              </td>
              <td data-label="status">
                <span 
                  class="px-2 py-1 text-xs font-medium rounded" 
                  :class="getStatusClass(noticia.status)"
                >
                  {{ noticia.status }}
                </span>
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
  </LayoutAuthenticated>
</template>