<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiInformationOutline,
  mdiArrowLeftBoldOutline,
  mdiPencilOutline,
  mdiEye,
  mdiCalendarRange,
  mdiStar,
  mdiStarOutline,
  mdiTrashCan
} from "@mdi/js"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import BaseButton from "@/Components/BaseButton.vue"
import BaseButtons from "@/Components/BaseButtons.vue"
import { computed } from 'vue'

const props = defineProps({
  noticia: {
    type: Object,
    default: () => ({}),
  },
  can: {
    type: Object,
    default: () => ({}),
  }
})

// Formulário para exclusão
const formDelete = useForm({})
function destroy() {
  if (confirm("Tem certeza de que deseja remover esta notícia?")) {
    formDelete.delete(route("admin.noticias.destroy", props.noticia.id))
  }
}

// Formulário para alterar destaque
const formDestaque = useForm({})
function toggleDestaque() {
  formDestaque.patch(route("admin.noticias.toggle-destaque", props.noticia.id))
}

// Helper para obter a classe CSS do status
function getStatusClass(status) {
  switch (status) {
    case 'publicado': return 'bg-green-100 text-green-800'
    case 'rascunho': return 'bg-gray-100 text-gray-800'
    case 'arquivado': return 'bg-orange-100 text-orange-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

// Formatar data para exibição
const dataFormatada = computed(() => {
  if (!props.noticia.data_publicacao) return ''
  return new Date(props.noticia.data_publicacao).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
})
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Detalhes da Notícia" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiInformationOutline"
        title="Detalhes da Notícia"
        main
      >
        <BaseButton
          :route-name="route('admin.noticias.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>
      
      <!-- Cabeçalho com ações -->
      <div class="flex justify-between items-center mb-4">
        <div class="flex items-center space-x-2">
          <span 
            class="px-2 py-1 text-xs font-medium rounded" 
            :class="getStatusClass(noticia.status)"
          >
            {{ noticia.status }}
          </span>
          <span v-if="noticia.destaque" class="flex items-center text-yellow-600">
            <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            Destaque
          </span>
        </div>
        <BaseButtons>
          <BaseButton
            v-if="can.edit"
            :route-name="route('admin.noticias.edit', noticia.id)"
            :icon="mdiPencilOutline"
            label="Editar"
            color="info"
            small
          />
          <BaseButton
            v-if="can.delete"
            :icon="mdiTrashCan"
            label="Excluir"
            color="danger"
            small
            @click="destroy"
          />
        </BaseButtons>
      </div>
      
      <!-- Conteúdo principal -->
      <CardBox class="mb-6">
        <!-- Título e data -->
        <div class="mb-6">
          <h1 class="text-2xl font-bold mb-2">{{ noticia.titulo }}</h1>
          <div class="flex items-center text-gray-600 text-sm">
            <span class="flex items-center">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
              </svg>
              Publicado em: {{ dataFormatada }}
            </span>
            <span class="mx-2">•</span>
            <span class="flex items-center">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
              </svg>
              {{ noticia.visualizacoes }} visualizações
            </span>
          </div>
        </div>
        
        <!-- Imagem da notícia -->
        <div v-if="noticia.imagem" class="mb-6">
          <img 
            :src="noticia.imagem"
            :alt="noticia.titulo"
            class="rounded-lg max-h-96 object-cover w-full"
          />
        </div>
        
        <!-- Descrição curta -->
        <div class="mb-6">
          <h3 class="text-lg font-semibold mb-2">Descrição Curta</h3>
          <div class="p-4 rounded-lg border border-gray-200">
            {{ noticia.descricao_curta }}
          </div>
        </div>
        
        <!-- Conteúdo completo -->
        <div>
          <h3 class="text-lg font-semibold mb-2">Conteúdo Completo</h3>
          <div class="prose max-w-none">
            <div v-if="noticia.conteudo" v-html="noticia.conteudo"></div>
            <div v-else class="text-gray-500 italic">Sem conteúdo detalhado.</div>
          </div>
        </div>
      </CardBox>
      
      <!-- Metadados -->
      <CardBox class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Informações Adicionais</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <span class="text-gray-600 text-sm">ID:</span>
            <span class="ml-2 font-medium">{{ noticia.id }}</span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Status:</span>
            <span 
              class="ml-2 px-2 py-1 text-xs font-medium rounded" 
              :class="getStatusClass(noticia.status)"
            >
              {{ noticia.status }}
            </span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Data de Criação:</span>
            <span class="ml-2 font-medium">{{ noticia.created_at }}</span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Última Atualização:</span>
            <span class="ml-2 font-medium">{{ noticia.updated_at }}</span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Destaque:</span>
            <span class="ml-2 font-medium">{{ noticia.destaque ? 'Sim' : 'Não' }}</span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Visualizações:</span>
            <span class="ml-2 font-medium">{{ noticia.visualizacoes }}</span>
          </div>
        </div>
      </CardBox>
      
      <!-- Botões de ação -->
      <div class="flex justify-between">
        <BaseButton
          :route-name="route('admin.noticias.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar para Notícias"
          color="white"
        />
        <BaseButtons v-if="can.edit">
          <BaseButton
            v-if="noticia.destaque"
            @click="toggleDestaque"
            :icon="mdiStarOutline"
            label="Remover Destaque"
            color="warning"
            outlined
          />
          <BaseButton
            v-else
            @click="toggleDestaque"
            :icon="mdiStar"
            label="Marcar como Destaque"
            color="warning"
          />
          <BaseButton
            :route-name="route('admin.noticias.edit', noticia.id)"
            :icon="mdiPencilOutline"
            label="Editar Notícia"
            color="info"
          />
        </BaseButtons>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>