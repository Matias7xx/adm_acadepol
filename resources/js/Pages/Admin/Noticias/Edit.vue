<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  mdiNewspaper,
  mdiArrowLeftBoldOutline,
  mdiCalendarRange,
  mdiStar,
  mdiImage,
  mdiContentSave,
} from '@mdi/js';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import CardBox from '@/Components/CardBox.vue';
import FormField from '@/Components/FormField.vue';
import FormControl from '@/Components/FormControl.vue';
import BaseDivider from '@/Components/BaseDivider.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseButtons from '@/Components/BaseButtons.vue';
import { ref, computed, onMounted } from 'vue';
import NoticiasEditor from './partials/NoticiasEditor.vue';

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
  carousel_images: props.noticia.carousel_images || [],
  remover_imagem: false,
  data_publicacao: props.noticia.data_publicacao,
  status: props.noticia.status,
});

// Estado
const isPreviewMode = ref(false);
const imagePreview = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);
const wordCount = ref(0);
const isProcessingImages = ref(false);

// Função para lidar com o preview da imagem
const handleImageUpload = event => {
  const file = event.target.files[0];

  if (!file) {
    if (!form.remover_imagem && props.noticia.imagem) {
      imagePreview.value = getImageUrl(props.noticia.imagem);
    } else {
      imagePreview.value = null;
    }
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
  reader.onload = e => {
    imagePreview.value = e.target.result;
    form.remover_imagem = false;

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

// Função auxiliar para obter URL da imagem
const getImageUrl = imagePath => {
  if (!imagePath) return null;

  if (imagePath.startsWith('/') || imagePath.startsWith('http')) {
    return imagePath;
  }

  return `/storage/${imagePath}`;
};

// Inicializa o preview da imagem com a imagem existente
onMounted(() => {
  if (props.noticia.imagem && !form.remover_imagem) {
    imagePreview.value = getImageUrl(props.noticia.imagem);
  }
});

// Remover imagem
const removerImagem = () => {
  form.remover_imagem = true;
  imagePreview.value = null;
  form.imagem = null;
};

// Restaurar imagem se cancelar a remoção
const cancelarRemocao = () => {
  form.remover_imagem = false;
  if (props.noticia.imagem) {
    imagePreview.value = getImageUrl(props.noticia.imagem);
  }
};

// Função para processar imagens base64 no conteúdo
const processContentImages = async content => {
  if (!content.includes('data:image/')) {
    return content;
  }

  isProcessingImages.value = true;
  let processedContent = content;

  try {
    // Buscar todas as imagens base64
    const base64ImageRegex =
      /<img[^>]+src="data:image\/([^;]+);base64,([^"]+)"[^>]*>/gi;
    const matches = [...content.matchAll(base64ImageRegex)];

    // Processar cada imagem
    for (const match of matches) {
      const [fullMatch, extension, base64Data] = match;

      // Converter base64 para blob
      const byteCharacters = atob(base64Data);
      const byteNumbers = new Array(byteCharacters.length);
      for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i);
      }
      const byteArray = new Uint8Array(byteNumbers);
      const blob = new Blob([byteArray], { type: `image/${extension}` });

      // Criar FormData para enviar
      const formData = new FormData();
      formData.append('upload', blob, `image.${extension}`);

      try {
        // Usar a rota existente do UploadController
        const response = await fetch('/api/upload-ckeditor-images', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN':
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '',
          },
        });

        const result = await response.json();

        if (result.uploaded && result.url) {
          // Substituir a imagem base64 pela URL real
          processedContent = processedContent.replace(
            fullMatch,
            fullMatch.replace(
              `data:image/${extension};base64,${base64Data}`,
              result.url
            )
          );
        }
      } catch (error) {
        console.error('Erro ao processar imagem:', error);
      }
    }
  } catch (error) {
    console.error('Erro ao processar imagens do conteúdo:', error);
  } finally {
    isProcessingImages.value = false;
  }

  return processedContent;
};

// Método para enviar o formulário
const submit = async () => {
  try {
    // Processar imagens no conteúdo antes de enviar
    if (form.conteudo.includes('data:image/')) {
      const processedContent = await processContentImages(form.conteudo);
      form.conteudo = processedContent;
    }

    form
      .transform(data => ({
        ...data,
        _method: 'PUT',
      }))
      .post(route('admin.noticias.update', props.noticia.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
          // Reset após sucesso
          isUploading.value = false;
          uploadProgress.value = 0;
          isProcessingImages.value = false;
        },
        onError: () => {
          isProcessingImages.value = false;
        },
      });
  } catch (error) {
    console.error('Erro ao enviar formulário:', error);
    isProcessingImages.value = false;
  }
};

// Verificar se o formulário tem todos os campos obrigatórios
const isFormValid = computed(() => {
  return (
    form.titulo && form.data_publicacao && form.status
  );
});

// Verificar se está processando
const isProcessing = computed(() => {
  return form.processing || isProcessingImages.value;
});

// Handler para mudança no contador de palavras
const handleWordCountChange = count => {
  wordCount.value = count;
};

// Computed property para o preview da imagem
const currentImagePreview = computed(() => {
  if (form.remover_imagem) return null;
  if (imagePreview.value) return imagePreview.value;
  if (props.noticia.imagem) {
    return getImageUrl(props.noticia.imagem);
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
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>

      <!-- Indicador de processamento de imagens -->
      <div
        v-if="isProcessingImages"
        class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4"
      >
        <div class="flex items-center">
          <svg
            class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          <span class="text-blue-800 font-medium"
            >Processando imagens do conteúdo...</span
          >
        </div>
      </div>

      <CardBox form class="mb-6" is-hoverable>
        <!-- Informações Básicas -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4 flex items-center">
            <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M14 17H7V15H14V17M17 13H7V11H17V13M17 9H7V7H17V9M19 3H5C3.89 3 3 3.89 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.89 20.1 3 19 3Z"
                />
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
                <div
                  class="text-red-400 text-sm mt-1 flex items-center"
                  v-if="form.errors.titulo"
                >
                  <span class="icon w-4 h-4 mr-1">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path
                        d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"
                      />
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
                  publicado: 'Publicado',
                  rascunho: 'Rascunho',
                  arquivado: 'Arquivado',
                }"
                :error="form.errors.status"
              >
                <div
                  class="text-red-400 text-sm mt-1 flex items-center"
                  v-if="form.errors.status"
                >
                  <span class="icon w-4 h-4 mr-1">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path
                        d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"
                      />
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
              <div
                class="text-red-400 text-sm mt-1 flex items-center"
                v-if="form.errors.descricao_curta"
              >
                <span class="icon w-4 h-4 mr-1">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                      d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"
                    />
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
                <div
                  class="text-red-400 text-sm mt-1 flex items-center"
                  v-if="form.errors.data_publicacao"
                >
                  <span class="icon w-4 h-4 mr-1">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path
                        d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"
                      />
                    </svg>
                  </span>
                  {{ form.errors.data_publicacao }}
                </div>
              </FormControl>
            </FormField>
          </div>
        </div>

        <!-- Imagem da Notícia -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4 flex items-center">
            <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M5,3A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H14.09C14.03,20.67 14,20.34 14,20C14,19.32 14.12,18.64 14.35,18H5L8.5,13.5L11,16.5L14.5,12L16.73,14.97C17.7,14.34 18.84,14 20,14C20.34,14 20.67,14.03 21,14.09V5C21,3.89 20.1,3 19,3H5M19,16V19H16V21H19V24H21V21H24V19H21V16H19Z"
                />
              </svg>
            </span>
            Imagem de Capa
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <FormField label="Upload de Imagem" :icon="mdiImage">
                <input
                  type="file"
                  accept="image/*"
                  @change="handleImageUpload"
                  class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                />
                <div
                  class="text-red-400 text-sm mt-1 flex items-center"
                  v-if="form.errors.imagem"
                >
                  <span class="icon w-4 h-4 mr-1">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path
                        d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"
                      />
                    </svg>
                  </span>
                  {{ form.errors.imagem }}
                </div>
                <p class="text-sm text-gray-500 mt-1">
                  Formatos aceitos: JPG, PNG, GIF (máx. 2MB)
                </p>
              </FormField>

              <!-- Progresso de upload -->
              <div v-if="isUploading" class="mt-2">
                <div
                  class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700"
                >
                  <div
                    class="bg-blue-600 h-2.5 rounded-full"
                    :style="{ width: `${uploadProgress}%` }"
                  ></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                  Processando imagem... {{ uploadProgress }}%
                </p>
              </div>

              <!-- Controles de imagem atual -->
              <div
                v-if="currentImagePreview && !form.remover_imagem"
                class="mt-3"
              >
                <div class="flex items-center">
                  <button
                    type="button"
                    @click="removerImagem"
                    class="text-red-600 hover:text-red-800 text-sm flex items-center"
                  >
                    <svg
                      class="w-4 h-4 mr-1"
                      viewBox="0 0 24 24"
                      fill="currentColor"
                    >
                      <path
                        d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"
                      />
                    </svg>
                    Remover imagem atual
                  </button>
                </div>
              </div>

              <!-- Feedback de remoção -->
              <div v-if="form.remover_imagem" class="mt-3">
                <div class="flex items-center text-gray-500 text-sm">
                  <svg
                    class="w-4 h-4 mr-1"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                  >
                    <path
                      d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"
                    />
                  </svg>
                  A imagem será removida ao salvar
                </div>
                <button
                  type="button"
                  @click="cancelarRemocao"
                  class="text-blue-600 hover:text-blue-800 text-sm mt-1 flex items-center"
                >
                  <svg
                    class="w-4 h-4 mr-1"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                  >
                    <path
                      d="M20,16V10H22V16A2,2 0 0,1 20,18H8C6.89,18 6,17.1 6,16V4C6,2.89 6.89,2 8,2H16V4H8V16H20M10.91,7.08L14,10.17L20.59,3.58L22,5L14,13L9.5,8.5L10.91,7.08M16,20V22H4A2,2 0 0,1 2,20V7H4V20H16Z"
                    />
                  </svg>
                  Cancelar remoção
                </button>
              </div>
            </div>

            <!-- Preview da imagem -->
            <div
              v-if="currentImagePreview"
              class="flex items-center justify-center border rounded-lg p-2 bg-gray-50"
            >
              <img
                :src="currentImagePreview"
                alt="Preview"
                class="max-h-48 object-contain"
              />
            </div>
            <div
              v-else
              class="flex items-center justify-center border rounded-lg p-4 bg-gray-50 border-dashed text-gray-400"
            >
              <div class="text-center">
                <svg
                  class="mx-auto h-12 w-12"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
                <p class="mt-1">
                  {{
                    form.remover_imagem
                      ? 'Imagem será removida'
                      : 'Nenhuma imagem selecionada'
                  }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Editor de Conteúdo -->
        <div class="mb-6">
          <NoticiasEditor
            v-if="!isPreviewMode"
            v-model="form.conteudo"
            v-model:carousel-images="form.carousel_images"
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
              :class="{ 'opacity-75': isProcessing }"
              :disabled="isProcessing"
            />
            <BaseButton
              type="button"
              color="info"
              :label="
                isProcessingImages
                  ? 'Processando imagens...'
                  : 'Salvar Alterações'
              "
              :class="{ 'opacity-25': isProcessing }"
              :disabled="isProcessing || !isFormValid"
              @click="submit"
            />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
