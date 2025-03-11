<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
  mdiArrowLeft,
  mdiContentSave,
  mdiAccount,
  mdiCalendarRange,
  mdiAlertBoxOutline,
} from "@mdi/js";
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue";
import SectionMain from "@/Components/SectionMain.vue";
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue";
import CardBox from "@/Components/CardBox.vue";
import BaseButton from "@/Components/BaseButton.vue";
import FormField from "@/Components/FormField.vue";
import FormControl from "@/Components/FormControl.vue";
import FormFilePicker from "@/Components/FormFilePicker.vue";
import BaseButtons from "@/Components/BaseButtons.vue";
import NotificationBar from "@/Components/NotificationBar.vue";
import { ref, computed } from 'vue';

const props = defineProps({
  diretor: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

// Converter as strings JSON para objetos JavaScript
const realizacoes = ref(props.diretor.realizacoes ? 
  (typeof props.diretor.realizacoes === 'string' ? 
    JSON.parse(props.diretor.realizacoes) : props.diretor.realizacoes) : []);

const form = useForm({
  nome: props.diretor.nome,
  data_inicio: props.diretor.data_inicio,
  data_fim: props.diretor.data_fim || '',
  historico: props.diretor.historico || '',
  realizacoes: realizacoes.value,
  atual: Boolean(props.diretor.atual),
  ordem: props.diretor.ordem || 0,
  imagem: props.diretor.imagem || '',
  imagem_file: null,
});

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

const onFileChange = (e) => {
  const file = e.target.files[0];
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
  form.transform((data) => ({
    ...data,
    _method: 'PUT', // Simular método PUT para Laravel
  })).post(route('admin.directors.update', props.diretor.id), {
    preserveScroll: true,
    forceFormData: true, // Importante para upload de arquivos
    onError: (errors) => {
      console.error("Erros no envio:", errors);
    },
    onSuccess: () => {
      console.log("Formulário enviado com sucesso");
    },
  });
};

const previewImagem = computed(() => {
  return form.imagem_file ? URL.createObjectURL(form.imagem_file) : props.diretor.imagem;
});
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Editar Diretor" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiAccount"
        title="Editar Diretor"
        main
      >
        <BaseButton
          :route-name="route('admin.directors.index')"
          :icon="mdiArrowLeft"
          label="Voltar"
          color="info"
          outlined
        />
      </SectionTitleLineWithButton>

      <NotificationBar
        v-if="$page.props.flash.message"
        color="success"
        :icon="mdiAlertBoxOutline"
      >
        {{ $page.props.flash.message }}
      </NotificationBar>

      <CardBox is-form @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-6">
          <!-- Nome do Diretor -->
          <FormField
            label="Nome do Diretor"
            :error="errors.nome"
          >
            <FormControl
              v-model="form.nome"
              :error="errors.nome"
              placeholder="Nome completo do diretor"
              required
            />
          </FormField>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Data Início -->
            <FormField
              label="Data de Início"
              :error="errors.data_inicio"
              :icon="mdiCalendarRange"
            >
              <FormControl
                v-model="form.data_inicio"
                type="date"
                :error="errors.data_inicio"
                required
              />
            </FormField>

            <!-- Data Fim -->
            <FormField
              label="Data de Término"
              :error="errors.data_fim"
              :icon="mdiCalendarRange"
            >
              <FormControl
                v-model="form.data_fim"
                type="date"
                :error="errors.data_fim"
                :disabled="form.atual"
              />
            </FormField>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Diretor Atual -->
            <FormField
              label="Diretor Atual"
              :error="errors.atual"
            >
              <div class="flex items-center space-x-2">
                <FormControl
                  v-model="form.atual"
                  type="checkbox"
                  :error="errors.atual"
                  @change="atualizarDataFim"
                />
                <span>É o diretor atual?</span>
              </div>
            </FormField>

            <!-- Ordem -->
            <FormField
              label="Ordem de Exibição"
              :error="errors.ordem"
            >
              <FormControl
                v-model="form.ordem"
                type="number"
                min="0"
                :error="errors.ordem"
                placeholder="Ordem de exibição (menor valor aparece primeiro)"
              />
            </FormField>
          </div>

          <!-- Imagem -->
          <FormField
            label="Imagem do Diretor"
            :error="errors.imagem_file"
          >
            <div v-if="previewImagem" class="mb-4">
              <p class="text-sm text-gray-500 mb-2">Imagem atual:</p>
              <img :src="previewImagem" alt="Preview" class="max-h-40 rounded">
            </div>
            <FormFilePicker
              v-model="form.imagem_file"
              accept="image/*"
              label="Selecionar nova imagem"
              @change="onFileChange"
            />
          </FormField>

          <!-- Histórico -->
          <FormField
            label="Histórico Profissional"
            :error="errors.historico"
          >
            <FormControl
              v-model="form.historico"
              type="textarea"
              :error="errors.historico"
              placeholder="Breve histórico ou biografia do diretor"
              rows="4"
            />
          </FormField>

          <!-- Realizações (lista) -->
          <FormField
            label="Principais Realizações"
            :error="errors.realizacoes"
          >
            <div class="flex mb-2">
              <FormControl
                v-model="novaRealizacao"
                placeholder="Adicionar realização"
                class="flex-grow mr-2"
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
          </FormField>
        </div>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="button"
              color="info"
              :icon="mdiContentSave"
              label="Salvar Alterações"
              :disabled="form.processing"
              :loading="form.processing"
              @click="submit"
            />
            <BaseButton
              :route-name="route('admin.directors.index')"
              label="Cancelar"
              color="info"
              outlined
            />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>