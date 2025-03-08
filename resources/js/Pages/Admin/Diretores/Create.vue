<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiAccount,
  mdiArrowLeftBoldOutline,
  mdiCalendarRange
} from "@mdi/js"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import FormField from '@/Components/FormField.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseDivider from '@/Components/BaseDivider.vue'
import BaseButton from '@/Components/BaseButton.vue'
import BaseButtons from '@/Components/BaseButtons.vue'
import { ref } from 'vue'

const form = useForm({
  nome: '',
  data_inicio: '',
  data_fim: '',
  historico: '',
  realizacoes: [],
  atual: false,
  ordem: 0,
  imagem_file: null,
})

// Para gerenciar campo de array
const novaRealizacao = ref('');

// Funções para adicionar e remover itens dos arrays
const adicionarRealizacao = () => {
  if (novaRealizacao.value.trim()) {
    form.realizacoes.push(novaRealizacao.value.trim());
    novaRealizacao.value = '';
  }
};

const removerRealizacao = (index) => {
  form.realizacoes.splice(index, 1);
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.imagem_file = file;
  }
};

// Função que monitora mudanças no checkbox "atual"
const atualizarDataFim = () => {
  if (form.atual) {
    form.data_fim = ''; // Limpa a data fim se for diretor atual
  }
};

const submit = () => {
  form.post(route('admin.directors.store'), {
    preserveScroll: true,
    forceFormData: true,
  });
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Novo Diretor" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiAccount"
        title="Cadastrar Diretor"
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
      <CardBox
        form
      >
        <!-- Informações Básicas -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4">Informações Básicas</h3>
          
          <FormField
            label="Nome do Diretor"
            :class="{ 'text-red-400': form.errors.nome }"
          >
            <FormControl
              v-model="form.nome"
              type="text"
              placeholder="Nome completo do diretor"
              :error="form.errors.nome"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.nome">
                {{ form.errors.nome }}
              </div>
            </FormControl>
          </FormField>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              label="Data de Início"
              :class="{ 'text-red-400': form.errors.data_inicio }"
              :icon="mdiCalendarRange"
            >
              <FormControl
                v-model="form.data_inicio"
                type="date"
                :error="form.errors.data_inicio"
              >
                <div class="text-red-400 text-sm" v-if="form.errors.data_inicio">
                  {{ form.errors.data_inicio }}
                </div>
              </FormControl>
            </FormField>

            <FormField
              label="Data de Término"
              :class="{ 'text-red-400': form.errors.data_fim }"
              :icon="mdiCalendarRange"
            >
              <FormControl
                v-model="form.data_fim"
                type="date"
                :error="form.errors.data_fim"
                :disabled="form.atual"
              >
                <div class="text-red-400 text-sm" v-if="form.errors.data_fim">
                  {{ form.errors.data_fim }}
                </div>
              </FormControl>
            </FormField>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <FormField
              label="Diretor Atual"
              :class="{ 'text-red-400': form.errors.atual }"
            >
              <div class="flex items-center space-x-2">
                <FormControl
                  v-model="form.atual"
                  type="checkbox"
                  :error="form.errors.atual"
                  @change="atualizarDataFim"
                />
                <span>É o diretor atual?</span>
              </div>
              <div class="text-red-400 text-sm" v-if="form.errors.atual">
                {{ form.errors.atual }}
              </div>
            </FormField>

            <FormField
              label="Ordem de Exibição"
              :class="{ 'text-red-400': form.errors.ordem }"
            >
              <FormControl
                v-model="form.ordem"
                type="number"
                min="0"
                placeholder="Ex: 0 (menor valor aparece primeiro)"
                :error="form.errors.ordem"
              >
                <div class="text-red-400 text-sm" v-if="form.errors.ordem">
                  {{ form.errors.ordem }}
                </div>
              </FormControl>
            </FormField>
          </div>
        </div>
        
        <!-- Histórico e Realizações -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4">Histórico e Realizações</h3>
          
          <FormField
            label="Histórico Profissional"
            :class="{ 'text-red-400': form.errors.historico }"
          >
            <FormControl
              v-model="form.historico"
              type="textarea"
              placeholder="Breve histórico ou biografia do diretor"
              :error="form.errors.historico"
              rows="4"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.historico">
                {{ form.errors.historico }}
              </div>
            </FormControl>
          </FormField>
          
          <!-- Realizações (lista) -->
          <div class="mb-6">
            <FormField
              label="Principais Realizações"
            >
              <div class="flex mb-2">
                <FormControl
                  v-model="novaRealizacao"
                  placeholder="Adicionar realização"
                  class="flex-grow mr-2"
                  :error="form.errors.realizacoes"
                />
                <BaseButton
                  type="button"
                  color="info"
                  label="Adicionar"
                  @click="adicionarRealizacao"
                />
              </div>
              <div v-if="form.realizacoes && form.realizacoes.length > 0" class="mt-2">
                <ul class="list-disc pl-5">
                  <li v-for="(item, index) in form.realizacoes" :key="index" class="mb-1 flex items-center">
                    <span class="flex-grow">{{ item }}</span>
                    <button type="button" @click="removerRealizacao(index)" class="text-red-500 hover:text-red-700">
                      <span>Remover</span>
                    </button>
                  </li>
                </ul>
              </div>
              <div v-else class="text-gray-500 mt-2">
                Nenhuma realização adicionada.
              </div>
              <div class="text-red-400 text-sm" v-if="form.errors.realizacoes">
                {{ form.errors.realizacoes }}
              </div>
            </FormField>
          </div>
        </div>
        
        <!-- Imagem do Diretor -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4">Imagem do Diretor</h3>
          
          <FormField
            label="Upload de Imagem"
          >
            <FormControl
              type="file"
              accept="image/*"
              @change="handleImageUpload"
              :error="form.errors.imagem_file"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.imagem_file">
                {{ form.errors.imagem_file }}
              </div>
            </FormControl>
            <p class="text-sm text-gray-500 mt-1">Formatos aceitos: JPG, PNG, GIF (máx. 2MB)</p>
          </FormField>
        </div>

        <BaseDivider />

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="button"
              color="light"
              label="Cancelar"
              :route-name="route('admin.directors.index')"
              :class="{ 'opacity-75': form.processing }"
              :disabled="form.processing"
            />
            <BaseButton
              type="button"
              color="info"
              label="Cadastrar Diretor"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
              @click="submit"
            />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>