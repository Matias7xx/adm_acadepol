<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiInformationOutline,
  mdiArrowLeftBoldOutline,
  mdiPencilOutline,
  mdiDeleteOutline
} from "@mdi/js"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import BaseButton from "@/Components/BaseButton.vue"
import BaseButtons from "@/Components/BaseButtons.vue"

const props = defineProps({
  diretor: {
    type: Object,
    default: () => ({}),
  },
  can: {
    type: Object,
    default: () => ({}),
  }
})

// Verificar se realizacoes é uma string JSON e converter para array
const realizacoes = typeof props.diretor.realizacoes === 'string' 
  ? JSON.parse(props.diretor.realizacoes) 
  : (props.diretor.realizacoes || []);

const formDelete = useForm({})

function destroy(id) {
  if (confirm("Tem certeza de que deseja remover este diretor?")) {
    formDelete.delete(route("admin.directors.destroy", id))
  }
}
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Detalhes do Diretor" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiInformationOutline"
        title="Detalhes do Diretor"
        main
      >
        <BaseButton
          :route-name="route('admin.directors.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Coluna da Imagem -->
        <div class="mb-6 md:mb-0">
          <CardBox class="mb-6">
            <div class="flex flex-col items-center justify-center p-4">
              <div v-if="diretor.imagem" class="w-full mb-4 rounded-lg overflow-hidden shadow-lg">
                <img :src="diretor.imagem" :alt="diretor.nome" class="w-full h-auto">
              </div>
              <div v-else class="w-full h-64 bg-gray-200 flex items-center justify-center mb-4 rounded-lg">
                <span class="text-gray-500">Sem imagem</span>
              </div>
              
              <div v-if="diretor.atual" class="mt-2 px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                Diretor Atual
              </div>
            </div>
            
            <div class="px-4 pb-4">
              <BaseButtons>
                <BaseButton
                  v-if="can.edit"
                  :route-name="route('admin.directors.edit', diretor.id)"
                  :icon="mdiPencilOutline"
                  label="Editar"
                  color="info"
                  small
                />
                <BaseButton
                  v-if="can.delete"
                  :icon="mdiDeleteOutline"
                  label="Excluir"
                  color="danger"
                  small
                  @click="destroy(diretor.id)"
                />
              </BaseButtons>
            </div>
          </CardBox>
        </div>
        
        <!-- Coluna das Informações -->
        <div class="col-span-1 md:col-span-2">
          <CardBox class="mb-6">
            <div class="p-4">
              <h2 class="text-2xl font-bold mb-2">{{ diretor.nome }}</h2>
              <div class="text-gray-600 mb-4">
                <span>
                  {{ diretor.data_inicio ? new Date(diretor.data_inicio).toLocaleDateString() : '' }}
                  {{ diretor.atual ? ' - ATUALMENTE' : (diretor.data_fim ? ' - ' + new Date(diretor.data_fim).toLocaleDateString() : '') }}
                </span>
              </div>
              
              <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">Histórico</h3>
                <p v-if="diretor.historico" class="text-gray-700">
                  {{ diretor.historico }}
                </p>
                <p v-else class="text-gray-500 italic">
                  Nenhum histórico disponível.
                </p>
              </div>
              
              <div>
                <h3 class="text-lg font-semibold mb-2">Principais Realizações</h3>
                <ul v-if="realizacoes && realizacoes.length > 0" class="list-disc pl-5 space-y-1">
                  <li v-for="(realizacao, index) in realizacoes" :key="index" class="text-gray-700">
                    {{ realizacao }}
                  </li>
                </ul>
                <p v-else class="text-gray-500 italic">
                  Nenhuma realização registrada.
                </p>
              </div>
              
              <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="flex flex-wrap gap-4">
                  <div>
                    <span class="text-gray-600 font-medium">Ordem de exibição:</span>
                    <span class="ml-2">{{ diretor.ordem }}</span>
                  </div>
                  <div>
                    <span class="text-gray-600 font-medium">ID:</span>
                    <span class="ml-2">{{ diretor.id }}</span>
                  </div>
                </div>
              </div>
            </div>
          </CardBox>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>