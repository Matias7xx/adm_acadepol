<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiAccount,
  mdiPlus,
  mdiSquareEditOutline,
  mdiTrashCan,
  mdiAlertBoxOutline,
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
})

const form = useForm({
  search: props.filters.search,
})

const formDelete = useForm({})

function destroy(id) {
  if (confirm("Tem certeza de que deseja remover este diretor?")) {
    formDelete.delete(route("admin.directors.destroy", id))
  }
}
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Diretores" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiAccount"
        title="Diretores"
        main
      >
        <BaseButton
          v-if="can.create"
          :route-name="route('admin.directors.create')"
          :icon="mdiPlus"
          label="Cadastrar Diretor"
          color="info"
          rounded-full
          small
        />
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
        <form @submit.prevent="form.get(route('admin.directors.index'))">
          <div class="py-2 flex">
            <div class="flex pl-4">
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
                "
                placeholder="Pesquisar"
              />
              <BaseButton
                label="Pesquisar"
                type="submit"
                color="info"
                class="ml-4 inline-flex items-center px-4 py-2"
              />
            </div>
          </div>
        </form>
      </CardBox>
      <CardBox class="mb-6" has-table>
        <table>
          <thead>
            <tr>
              <th></th> <!-- Coluna da imagem -->
              <th>
                <Sort label="Nome" attribute="nome" />
              </th>
              <th>
                <Sort label="Período" attribute="data_inicio" />
              </th>
              <th>
                <Sort label="Status" attribute="atual" />
              </th>
              <th>
                <Sort label="Ordem" attribute="ordem" />
              </th>
              <th v-if="can.edit || can.delete">Ações</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="diretor in diretores.data" :key="diretor.id">
              <td data-label="Imagem" class="w-20">
                <img 
                  v-if="diretor.imagem" 
                  :src="diretor.imagem" 
                  :alt="diretor.nome"
                  class="h-16 w-16 object-cover rounded-full"
                >
                <div v-else class="h-16 w-16 bg-gray-200 rounded-full flex items-center justify-center">
                  <span class="text-gray-500 text-xs">Sem imagem</span>
                </div>
              </td>
              <td data-label="nome">
                <Link
                  :href="route('admin.directors.show', diretor.id)"
                  class="
                    no-underline
                    hover:underline
                    text-cyan-600
                    dark:text-cyan-400
                  "
                >
                  {{ diretor.nome }}
                </Link>
              </td>
              <td data-label="periodo">
                {{ diretor.data_inicio ? new Date(diretor.data_inicio).toLocaleDateString() : '' }}
                {{ diretor.atual ? '- ATUALMENTE' : (diretor.data_fim ? '- ' + new Date(diretor.data_fim).toLocaleDateString() : '') }}
              </td>
              <td data-label="status">
                <span 
                  v-if="diretor.atual" 
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                >
                  Atual
                </span>
                <span v-else>-</span>
              </td>
              <td data-label="ordem">
                {{ diretor.ordem }}
              </td>
              <td
                v-if="can.edit || can.delete"
                class="before:hidden lg:w-1 whitespace-nowrap"
              >
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    v-if="can.edit"
                    :route-name="route('admin.directors.edit', diretor.id)"
                    color="info"
                    :icon="mdiSquareEditOutline"
                    small
                  />
                  <BaseButton
                    v-if="can.delete"
                    color="danger"
                    :icon="mdiTrashCan"
                    small
                    @click="destroy(diretor.id)"
                  />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="py-4">
          <Pagination v-if="diretores && diretores.links" :data="diretores" />
        </div>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>