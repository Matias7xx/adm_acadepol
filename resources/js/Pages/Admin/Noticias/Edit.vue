<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
  mdiArrowLeft,
  mdiContentSave,
  mdiNewspaper,
  mdiAlertBoxOutline,
  mdiCalendarRange,
  mdiStar,
  mdiFormatText,
  mdiImage,
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
  noticia: {
    type: Object,
    required: true,
  },
  statusOptions: {
    type: Array,
    default: () => [],
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const form = useForm({
  titulo: props.noticia.titulo,
  descricao_curta: props.noticia.descricao_curta || '',
  conteudo: props.noticia.conteudo || '',
  imagem: null,
  remover_imagem: false,
  destaque: Boolean(props.noticia.destaque),
  data_publicacao: props.noticia.data_publicacao,
  status: props.noticia.status,
});

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.imagem = file;
  }
};

const removerImagem = () => {
  form.remover_imagem = true;
};

const submit = () => {
  form.transform((data) => ({
    ...data,
    _method: 'PUT', // Simular método PUT para Laravel
  })).post(route('admin.noticias.update', props.noticia.id), {
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
    if (form.remover_imagem) return null;
    if (form.imagem instanceof File) return URL.createObjectURL(form.imagem);
    
    // Se a imagem já existe no servidor
    if (props.noticia.imagem) {
        // Se já começar com / ou http, usar como está
        if (props.noticia.imagem.startsWith('/') || props.noticia.imagem.startsWith('http')) {
        return props.noticia.imagem;
        }
        // Caso contrário, adicionar o prefixo /storage/
        return `/storage/${props.noticia.imagem}`;
    }
    
    return null;
});
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Editar Notícia" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiNewspaper"
        title="Editar Notícia"
        main
      >
        <BaseButton
          :route-name="route('admin.noticias.index')"
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

      <CardBox form @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-6">
          <!-- Título da Notícia -->
          <FormField
            label="Título da Notícia"
            :error="errors.titulo"
          >
            <FormControl
              v-model="form.titulo"
              :error="errors.titulo"
              placeholder="Título da notícia"
              required
            />
          </FormField>

          <!-- Descrição Curta -->
          <FormField
            label="Descrição Curta"
            :error="errors.descricao_curta"
            :icon="mdiFormatText"
          >
            <FormControl
              v-model="form.descricao_curta"
              type="textarea"
              :error="errors.descricao_curta"
              placeholder="Resumo da notícia (será exibido nas listagens)"
              rows="2"
              required
            />
          </FormField>

          <!-- Conteúdo -->
          <FormField
            label="Conteúdo Completo"
            :error="errors.conteudo"
            :icon="mdiFormatText"
          >
            <FormControl
              v-model="form.conteudo"
              type="textarea"
              :error="errors.conteudo"
              placeholder="Conteúdo completo da notícia"
              rows="8"
            />
          </FormField>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Data de Publicação -->
            <FormField
              label="Data de Publicação"
              :error="errors.data_publicacao"
              :icon="mdiCalendarRange"
            >
              <FormControl
                v-model="form.data_publicacao"
                type="date"
                :error="errors.data_publicacao"
                required
              />
            </FormField>

            <!-- Status -->
            <FormField
              label="Status"
              :error="errors.status"
            >
              <FormControl
                v-model="form.status"
                type="select"
                :options="{
                  'rascunho': 'Rascunho',
                  'publicado': 'Publicado',
                  'arquivado': 'Arquivado'
                }"
                :error="errors.status"
                required
              />
            </FormField>
          </div>

          <!-- Destaque -->
          <FormField
            label="Destaque"
            :error="errors.destaque"
            :icon="mdiStar"
          >
            <div class="flex items-center space-x-2">
              <FormControl
                v-model="form.destaque"
                type="checkbox"
                :error="errors.destaque"
              />
              <span>Exibir esta notícia como destaque</span>
            </div>
          </FormField>

          <!-- Imagem -->
          <FormField
            label="Imagem de Capa"
            :error="errors.imagem"
            :icon="mdiImage"
          >
            <div v-if="previewImagem" class="mb-4">
              <p class="text-sm text-gray-500 mb-2">Imagem atual:</p>
              <img :src="previewImagem" alt="Preview" class="max-h-40 rounded">
              <button 
                type="button" 
                @click="removerImagem" 
                class="mt-2 text-red-600 hover:text-red-800 text-sm"
              >
                Remover imagem
              </button>
            </div>
            <div v-else-if="form.remover_imagem" class="mb-4">
              <p class="text-sm text-gray-500 mb-2">A imagem será removida ao salvar.</p>
              <button 
                type="button" 
                @click="form.remover_imagem = false" 
                class="mt-2 text-blue-600 hover:text-blue-800 text-sm"
              >
                Cancelar remoção
              </button>
            </div>
            <FormFilePicker
              accept="image/*"
              label="Selecionar nova imagem"
              @change="onFileChange"
            />
            <p class="text-sm text-gray-500 mt-1">Formatos aceitos: JPG, PNG, GIF (máx. 2MB)</p>
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
              :route-name="route('admin.noticias.index')"
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