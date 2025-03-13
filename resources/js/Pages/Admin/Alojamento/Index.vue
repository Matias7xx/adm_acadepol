<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiBed,
  mdiPlus,
  mdiSquareEditOutline,
  mdiCheck,
  mdiClose,
  mdiAlertBoxOutline,
  mdiEye,
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
import { ref } from "vue"
import { useToast } from '@/Composables/useToast'
import CardBoxModal from "@/Components/CardBoxModal.vue"

const props = defineProps({
  reservas: {
    type: Object,
    default: () => ({}),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
})

// Toast
const { toast } = useToast();

const form = useForm({
  search: props.filters.search,
  status: props.filters.status || '',
})

// Modal de rejeição
const isRejeicaoModalActive = ref(false)
const selectedReservaId = ref(null)
const formRejeicao = useForm({
  motivo_rejeicao: ''
})

// Lista de status para o filtro
const statusOptions = [
  { value: '', label: 'Todos' },
  { value: 'pendente', label: 'Pendente' },
  { value: 'aprovada', label: 'Aprovada' },
  { value: 'rejeitada', label: 'Rejeitada' }
]

// Cores para os status
function getStatusColor(status) {
  switch (status) {
    case 'pendente':
      return 'bg-yellow-100 text-yellow-800'
    case 'aprovada':
      return 'bg-green-100 text-green-800'
    case 'rejeitada':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

// Formatar data
function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-BR')
}

// Abrir modal de rejeição
function abrirModalRejeicao(reservaId) {
  selectedReservaId.value = reservaId
  formRejeicao.motivo_rejeicao = ''
  isRejeicaoModalActive.value = true
}

// Submeter formulário de rejeição
function submeterRejeicao() {
  if (!selectedReservaId.value) return
  
  formRejeicao.patch(route('admin.alojamento.rejeitar', selectedReservaId.value), {
    onSuccess: () => {
      isRejeicaoModalActive.value = false
      toast.success('Reserva rejeitada com sucesso!')
      // Recarregar para atualizar os dados
      /* window.location.reload() */
    },
    onError: (errors) => {
      toast.error('Erro ao rejeitar reserva')
      console.error(errors)
    }
  })
}
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Reservas de Alojamento" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiBed"
        title="Reservas de Alojamento"
        main
      >
      </SectionTitleLineWithButton>
      <NotificationBar
        :key="Date.now()"
        v-if="$page.props.flash.message"
        color="success"
        :icon="mdiAlertBoxOutline"
      >
        {{ $page.props.flash.message }}
      </NotificationBar>
      <NotificationBar
        :key="Date.now() + 1"
        v-if="$page.props.flash.error"
        color="danger"
        :icon="mdiAlertBoxOutline"
      >
        {{ $page.props.flash.error }}
      </NotificationBar>
      <CardBox class="mb-6" has-table>
        <form @submit.prevent="form.get(route('admin.alojamento.index'))">
          <div class="py-2 flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
              <label class="block text-sm font-medium text-gray-700 mb-1">Pesquisar</label>
              <input
                type="search"
                v-model="form.search"
                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-gray-900 w-full"
                placeholder="Nome, matrícula ou email"
              />
            </div>
            <div class="flex-1 min-w-[200px]">
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select
                v-model="form.status"
                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-gray-900 w-full"
              >
                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
            <div>
              <BaseButton
                label="Filtrar"
                type="submit"
                color="info"
                class="inline-flex items-center px-4 py-2"
              />
            </div>
          </div>
        </form>
      </CardBox>
      <CardBox class="mb-6" has-table>
        <table>
            <thead>
            <tr>
              <th>
                <Sort label="Solicitante" attribute="nome" />
              </th>
              <th>
                <Sort label="Matrícula" attribute="matricula" />
              </th>
              <th>
                <Sort label="Período" attribute="data_inicial" />
              </th>
              <th>
                <Sort label="Status" attribute="status" />
              </th>
              <th>Ações</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="reserva in reservas.data" :key="reserva.id">
              <td data-label="nome">
                <Link
                  :href="route('admin.alojamento.show', reserva.id)"
                  class="no-underline hover:underline text-cyan-600 dark:text-cyan-400"
                >
                  {{ reserva.nome }}
                </Link>
              </td>
              <td data-label="matricula">
                {{ reserva.matricula }}
              </td>
              <td data-label="periodo">
                {{ formatDate(reserva.data_inicial) }} a {{ formatDate(reserva.data_final) }}
              </td>
              <td data-label="status">
                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(reserva.status)]">
                  {{ reserva.status.charAt(0).toUpperCase() + reserva.status.slice(1) }}
                </span>
              </td>
              <td class="before:hidden lg:w-1 whitespace-nowrap">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    :route-name="route('admin.alojamento.show', reserva.id)"
                    color="lightDark"
                    :icon="mdiEye"
                    small
                    tooltip="Ver detalhes"
                  />
                  <BaseButton
                    v-if="reserva.status === 'pendente'"
                    as="button"
                    type="button"
                    color="success"
                    :icon="mdiCheck"
                    small
                    tooltip="Aprovar reserva"
                    @click="$inertia.patch(route('admin.alojamento.aprovar', reserva.id))"
                  />
                  <BaseButton
                    v-if="reserva.status === 'pendente'"
                    as="button"
                    type="button"
                    color="danger"
                    :icon="mdiClose"
                    small
                    tooltip="Rejeitar reserva"
                    @click="abrirModalRejeicao(reserva.id)"
                  />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="reservas.data && reservas.data.length === 0">
              <td colspan="5" class="text-center py-8 text-gray-500">
                Nenhuma reserva encontrada
              </td>
            </tr>
          </tbody>
        </table>
        <div class="py-4">
            <Pagination v-if="reservas && reservas.links" :data="reservas" />
        </div>
      </CardBox>
      
      <!-- Modal de rejeição -->
      <CardBoxModal
        v-model="isRejeicaoModalActive"
        title="Rejeitar Solicitação"
        button="danger"
        buttonLabel="Confirmar Rejeição"
        :buttonDisabled="formRejeicao.processing || !formRejeicao.motivo_rejeicao"
        @confirm="submeterRejeicao"
      >
        <div class="space-y-4">
          <p>Informe o motivo da rejeição desta solicitação de alojamento:</p>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Motivo da rejeição</label>
            <textarea
              v-model="formRejeicao.motivo_rejeicao"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              rows="5"
              placeholder="Descreva o motivo da rejeição..."
              required
            ></textarea>
            <div v-if="formRejeicao.errors.motivo_rejeicao" class="text-red-600 mt-1 text-sm">
              {{ formRejeicao.errors.motivo_rejeicao }}
            </div>
          </div>
        </div>
      </CardBoxModal>
    </SectionMain>
  </LayoutAuthenticated>
</template>