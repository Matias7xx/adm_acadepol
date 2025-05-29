<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiNewspaper,
  mdiArrowLeftBoldOutline,
  mdiCalendarRange,
  mdiStar,
  mdiImage,
  mdiContentSave,
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
import { ref, computed } from 'vue'
import NoticiasEditor from "./partials/NoticiasEditor.vue"

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
  status: 'publicado', // Status padrão
  imagem: null,
});

// Estado
const imagePreview = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);
const wordCount = ref(0);

// Função para lidar com o preview da imagem
const handleImageUpload = (event) => {
  const file = event.target.files[0];
  
  if (!file) {
    imagePreview.value = null;
    form.imagem = null;
    return;
  }
  
  // Validar tamanho (2MB)
  if (file.size > 2 * 1024 * 1024) {
    alert('A imagem deve ter no máximo 2MB');
    event.target.value = null;
    return;
  }
  
  // Validar tipo
  if (!file.type.match('image.*')) {
    alert('Por favor, selecione uma imagem válida');
    event.target.value = null;
    return;
  }
  
  // Simular upload
  isUploading.value = true;
  uploadProgress.value = 0;
  
  // Mostrar preview
  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
    
    // Simulação de progresso de upload
    const interval = setInterval(() => {
      uploadProgress.value += 10;
      if (uploadProgress.value >= 100) {
        clearInterval(interval);
        setTimeout(() => {
          isUploading.value = false;
        }, 500);
      }
    }, 100);
  };
  reader.readAsDataURL(file);
  
  form.imagem = file;
};

// Handler para mudança no contador de palavras
const handleWordCountChange = (count) => {
  wordCount.value = count;
};

// Método para enviar o formulário
const submit = () => {
  form.post(route('admin.noticias.store'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      // Reset do formulário após sucesso
      isUploading.value = false;
      uploadProgress.value = 0;
      imagePreview.value = null;
    },
  });
};

// Verificar se o formulário tem todos os campos obrigatórios
const isFormValid = computed(() => {
  return form.titulo && 
         form.descricao_curta && 
         form.data_publicacao && 
         form.status;
});
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
        class="mb-6"
        is-hoverable
      >
        <!-- Informações Básicas -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4 flex items-center">
            <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M14 17H7V15H14V17M17 13H7V11H17V13M17 9H7V7H17V9M19 3H5C3.89 3 3 3.89 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.89 20.1 3 19 3Z" />
              </svg>
            </span>
            Informações da Notícia
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                <div class="text-red-400 text-sm mt-1 flex items-center" v-if="form.errors.titulo">
                  <span class="icon w-4 h-4 mr-1">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
                    </svg>
                  </span>
                  {{ form.errors.titulo }}
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
                  'publicado': 'Publicado',
                  'rascunho': 'Rascunho',
                  'arquivado': 'Arquivado'
                }"
                :error="form.errors.status"
              >
                <div class="text-red-400 text-sm mt-1 flex items-center" v-if="form.errors.status">
                  <span class="icon w-4 h-4 mr-1">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
                    </svg>
                  </span>
                  {{ form.errors.status }}
                </div>
              </FormControl>
            </FormField>
          </div>
          
          <FormField
            label="Descrição Curta"
            :class="{ 'text-red-400': form.errors.descricao_curta }"
            help="Este texto será exibido nas listagens e cards de notícias"
          >
            <FormControl
              v-model="form.descricao_curta"
              type="textarea"
              placeholder="Escreva um resumo atrativo da notícia"
              :error="form.errors.descricao_curta"
              rows="2"
              maxlength="500"
            >
              <div class="text-red-400 text-sm mt-1 flex items-center" v-if="form.errors.descricao_curta">
                <span class="icon w-4 h-4 mr-1">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
                  </svg>
                </span>
                {{ form.errors.descricao_curta }}
              </div>
              
              <div class="text-xs text-gray-500 mt-1 text-right">
                {{ form.descricao_curta.length }}/500 caracteres
              </div>
            </FormControl>
          </FormField>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
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
                <div class="text-red-400 text-sm mt-1 flex items-center" v-if="form.errors.data_publicacao">
                  <span class="icon w-4 h-4 mr-1">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
                    </svg>
                  </span>
                  {{ form.errors.data_publicacao }}
                </div>
              </FormControl>
            </FormField>

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
                <span>Exibir esta notícia como destaque na página inicial</span>
              </div>
              <div class="text-red-400 text-sm mt-1 flex items-center" v-if="form.errors.destaque">
                <span class="icon w-4 h-4 mr-1">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
                  </svg>
                </span>
                {{ form.errors.destaque }}
              </div>
            </FormField>
          </div>
        </div>
        
        <!-- Imagem da Notícia -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4 flex items-center">
            <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M5,3A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H14.09C14.03,20.67 14,20.34 14,20C14,19.32 14.12,18.64 14.35,18H5L8.5,13.5L11,16.5L14.5,12L16.73,14.97C17.7,14.34 18.84,14 20,14C20.34,14 20.67,14.03 21,14.09V5C21,3.89 20.1,3 19,3H5M19,16V19H16V21H19V24H21V21H24V19H21V16H19Z" />
              </svg>
            </span>
            Imagem de Capa
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <FormField
                label="Upload de Imagem"
                :icon="mdiImage"
              >
                <input
                  type="file"
                  accept="image/*"
                  @change="handleImageUpload"
                  class="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100"
                />
                <div class="text-red-400 text-sm mt-1 flex items-center" v-if="form.errors.imagem">
                  <span class="icon w-4 h-4 mr-1">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
                    </svg>
                  </span>
                  {{ form.errors.imagem }}
                </div>
                <p class="text-sm text-gray-500 mt-1">Formatos aceitos: JPG, PNG, GIF (máx. 2MB)</p>
              </FormField>
              
              <!-- Progresso de upload -->
              <div v-if="isUploading" class="mt-2">
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                  <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: `${uploadProgress}%` }"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Processando imagem... {{ uploadProgress }}%</p>
              </div>
              
              <div class="mt-4 flex flex-col gap-2" v-if="imagePreview">
                <p class="text-sm font-medium">A imagem será exibida nas listagens e no topo da notícia</p>
                <p class="text-xs text-gray-500">Para melhor qualidade, use imagens na proporção 16:9</p>
              </div>
            </div>
            
            <!-- Preview da imagem -->
            <div v-if="imagePreview" class="flex items-center justify-center border rounded-lg p-2 bg-gray-50">
              <img :src="imagePreview" alt="Preview" class="max-h-48 object-contain" />
            </div>
            <div v-else class="flex items-center justify-center border rounded-lg p-4 bg-gray-50 border-dashed text-gray-400">
              <div class="text-center">
                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-1">Nenhuma imagem selecionada</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Editor de Conteúdo -->
        <div class="mb-6">
          <NoticiasEditor
            v-model="form.conteudo"
            :error="form.errors.conteudo"
            @word-count-change="handleWordCountChange"
          />
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
              label="Publicar Notícia"
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