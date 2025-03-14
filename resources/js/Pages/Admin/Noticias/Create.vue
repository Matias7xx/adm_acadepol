<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiNewspaper,
  mdiArrowLeftBoldOutline,
  mdiCalendarRange,
  mdiStar,
  mdiFormatText,
  mdiImage,
} from "@mdi/js"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import FormField from '@/Components/FormField.vue'
import FormControl from '@/Components/FormControl.vue'
import FormCheckRadioGroup from '@/Components/FormCheckRadioGroup.vue'
import BaseDivider from '@/Components/BaseDivider.vue'
import BaseButton from '@/Components/BaseButton.vue'
import BaseButtons from '@/Components/BaseButtons.vue'
import { ref } from 'vue'

const props = defineProps({
  statusOptions: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  titulo: '',
  descricao_curta: '',
  conteudo: '',
  destaque: false,
  data_publicacao: new Date().toISOString().substring(0, 10), // Data atual como padrão
  status: 'rascunho',
  imagem: null,
})

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.imagem = file;
  }
};

const submit = () => {
  form.post(route('admin.noticias.store'), {
    preserveScroll: true,
    forceFormData: true,
  });
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Nova Notícia" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiNewspaper"
        title="Cadastrar Notícia"
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
      <CardBox
        form
      >
        <!-- Informações Básicas -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4">Informações da Notícia</h3>
          
          <FormField
            label="Título"
            :class="{ 'text-red-400': form.errors.titulo }"
          >
            <FormControl
              v-model="form.titulo"
              type="text"
              placeholder="Informe o título da notícia"
              :error="form.errors.titulo"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.titulo">
                {{ form.errors.titulo }}
              </div>
            </FormControl>
          </FormField>

          <FormField
            label="Descrição Curta"
            :class="{ 'text-red-400': form.errors.descricao_curta }"
            :icon="mdiFormatText"
          >
            <FormControl
              v-model="form.descricao_curta"
              type="textarea"
              placeholder="Resumo da notícia (será exibido nas listagens)"
              :error="form.errors.descricao_curta"
              rows="2"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.descricao_curta">
                {{ form.errors.descricao_curta }}
              </div>
            </FormControl>
          </FormField>
          
          <FormField
            label="Conteúdo Completo"
            :class="{ 'text-red-400': form.errors.conteudo }"
            :icon="mdiFormatText"
          >
            <FormControl
              v-model="form.conteudo"
              type="textarea"
              placeholder="Conteúdo completo da notícia"
              :error="form.errors.conteudo"
              rows="8"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.conteudo">
                {{ form.errors.conteudo }}
              </div>
            </FormControl>
          </FormField>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              label="Data de Publicação"
              :class="{ 'text-red-400': form.errors.data_publicacao }"
              :icon="mdiCalendarRange"
            >
              <FormControl
                v-model="form.data_publicacao"
                type="date"
                :error="form.errors.data_publicacao"
              >
                <div class="text-red-400 text-sm" v-if="form.errors.data_publicacao">
                  {{ form.errors.data_publicacao }}
                </div>
              </FormControl>
            </FormField>

            <FormField
              label="Status"
              :class="{ 'text-red-400': form.errors.status }"
            >
              <FormControl
                v-model="form.status"
                type="select"
                :options="{
                  'rascunho': 'Rascunho',
                  'publicado': 'Publicado',
                  'arquivado': 'Arquivado'
                }"
                :error="form.errors.status"
              >
                <div class="text-red-400 text-sm" v-if="form.errors.status">
                  {{ form.errors.status }}
                </div>
              </FormControl>
            </FormField>
          </div>
          
          <FormField
            label="Destaque"
            :class="{ 'text-red-400': form.errors.destaque }"
            :icon="mdiStar"
          >
            <div class="flex items-center space-x-2">
              <FormControl
                v-model="form.destaque"
                type="checkbox"
                :error="form.errors.destaque"
              />
              <span>Exibir esta notícia como destaque</span>
            </div>
            <div class="text-red-400 text-sm" v-if="form.errors.destaque">
              {{ form.errors.destaque }}
            </div>
          </FormField>
        </div>
        
        <!-- Imagem da Notícia -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4">Imagem de Capa</h3>
          
          <FormField
            label="Upload de Imagem"
            :icon="mdiImage"
          >
            <FormControl
              type="file"
              accept="image/*"
              @change="handleImageUpload"
              :error="form.errors.imagem"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.imagem">
                {{ form.errors.imagem }}
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
              :route-name="route('admin.noticias.index')"
              :class="{ 'opacity-75': form.processing }"
              :disabled="form.processing"
            />
            <BaseButton
              type="button"
              color="info"
              label="Cadastrar Notícia"
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