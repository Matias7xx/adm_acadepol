<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
  mdiNewspaper,
  mdiArrowLeftBoldOutline,
  mdiCalendarRange,
  mdiStar,
  mdiFormatText,
  mdiImage,
  mdiEye,
  mdiContentSave,
  mdiAlertCircleOutline
} from "@mdi/js";
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue";
import SectionMain from "@/Components/SectionMain.vue";
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue";
import CardBox from "@/Components/CardBox.vue";
import FormField from '@/Components/FormField.vue';
import FormControl from '@/Components/FormControl.vue';
import FormCheckRadioGroup from '@/Components/FormCheckRadioGroup.vue';
import BaseDivider from '@/Components/BaseDivider.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseButtons from '@/Components/BaseButtons.vue';
import NotificationBar from "@/Components/NotificationBar.vue";
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

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

// Estado
const isPreviewMode = ref(false);
const editorInstance = ref(null);
const imagePreview = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);
const wordCount = ref(0);
const editorHeight = ref('400px');
const hasDraft = ref(false);

// Configurações avançadas do Quill
const editorOptions = {
  modules: {
    toolbar: {
      container: [
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'align': [] }],
        ['blockquote', 'code-block'],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'indent': '-1' }, { 'indent': '+1' }],
        ['link', 'image', 'video'],
        ['clean']
      ],
      handlers: {
        //handlers personalizados se necessário
      }
    },
    clipboard: {
      matchVisual: false
    },
    history: {
      delay: 1000,
      maxStack: 50,
      userOnly: true
    }
  },
  placeholder: 'Escreva o conteúdo da notícia aqui...',
  theme: 'snow',
  formats: [
    'header', 'bold', 'italic', 'underline', 'strike',
    'color', 'background', 'align',
    'blockquote', 'code-block',
    'list', 'bullet', 'indent',
    'link', 'image', 'video'
  ]
};

// Método para calcular o número de palavras
const calculateWordCount = (text) => {
  // Remove HTML tags
  const plainText = text.replace(/<[^>]*>/g, ' ');
  // Split por espaços e filtra strings vazias
  const words = plainText.split(/\s+/).filter(word => word.length > 0);
  return words.length;
};

// Atualizar contagem de palavras quando o conteúdo mudar
const updateWordCount = (content) => {
  wordCount.value = calculateWordCount(content);
};

// Inicializar a contagem de palavras
updateWordCount(form.conteudo);

// Função para lidar com o preview da imagem
const handleImageUpload = (event) => {
  const file = event.target.files[0];
  
  if (!file) {
    if (!form.remover_imagem && props.noticia.imagem) {
      // Restaurar a imagem original se não houver uma nova selecionada
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
  reader.onload = (e) => {
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
const getImageUrl = (imagePath) => {
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
  
  // Atualizar contagem de palavras inicial
  updateWordCount(form.conteudo);
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

// Alternar entre edição e visualização
const togglePreview = () => {
  isPreviewMode.value = !isPreviewMode.value;
};

// Verificar se o formulário tem todos os campos obrigatórios
const isFormValid = computed(() => {
  return form.titulo && 
         form.descricao_curta && 
         form.data_publicacao && 
         form.status;
});

// Método para enviar o formulário
const submit = () => {
  form.transform((data) => ({
    ...data,
    _method: 'PUT',
  })).post(route('admin.noticias.update', props.noticia.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      // Reset após sucesso
      isUploading.value = false;
      uploadProgress.value = 0;
    },
    onError: (errors) => {
      console.error("Erros no envio:", errors);
    },
  });
};

// Referência ao editor
const onEditorMounted = (quill) => {
  editorInstance.value = quill;
  
  // Observar alterações no conteúdo
  quill.on('text-change', () => {
    form.conteudo = quill.root.innerHTML;
    updateWordCount(form.conteudo);
  });
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

// Carrega o editor com o conteúdo existente
onMounted(() => {
  // Se já tem conteúdo, atualiza a contagem de palavras
  if (form.conteudo) {
    updateWordCount(form.conteudo);
  }
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
      
      <NotificationBar
        v-if="$page.props.flash.message"
        color="success"
        :icon="mdiAlertCircleOutline"
      >
        {{ $page.props.flash.message }}
      </NotificationBar>
      
      <!-- Barra de ferramentas flutuante -->
      <div class="fixed bottom-6 right-6 z-50 flex space-x-2">
        <BaseButton
          type="button"
          color="success"
          icon-size="24"
          :icon="mdiContentSave"
          label="Salvar"
          rounded-full
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing || !isFormValid"
          @click="submit"
        />
        <BaseButton
          type="button"
          color="info"
          icon-size="24"
          :icon="isPreviewMode ? mdiFormatText : mdiEye"
          :label="isPreviewMode ? 'Editar' : 'Visualizar'"
          rounded-full
          @click="togglePreview"
        />
      </div>
      
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
                  'rascunho': 'Rascunho',
                  'publicado': 'Publicado',
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
              
              <!-- Controles de imagem atual -->
              <div v-if="currentImagePreview && !form.remover_imagem" class="mt-3">
                <div class="flex items-center">
                  <button 
                    type="button" 
                    @click="removerImagem" 
                    class="text-red-600 hover:text-red-800 text-sm flex items-center"
                  >
                    <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z" />
                    </svg>
                    Remover imagem atual
                  </button>
                </div>
              </div>
              
              <!-- Feedback de remoção -->
              <div v-if="form.remover_imagem" class="mt-3">
                <div class="flex items-center text-gray-500 text-sm">
                  <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
                  </svg>
                  A imagem será removida ao salvar
                </div>
                <button 
                  type="button" 
                  @click="cancelarRemocao" 
                  class="text-blue-600 hover:text-blue-800 text-sm mt-1 flex items-center"
                >
                  <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20,16V10H22V16A2,2 0 0,1 20,18H8C6.89,18 6,17.1 6,16V4C6,2.89 6.89,2 8,2H16V4H8V16H20M10.91,7.08L14,10.17L20.59,3.58L22,5L14,13L9.5,8.5L10.91,7.08M16,20V22H4A2,2 0 0,1 2,20V7H4V20H16Z" />
                  </svg>
                  Cancelar remoção
                </button>
              </div>
              
              <div class="mt-4 flex flex-col gap-2" v-if="currentImagePreview">
                <p class="text-sm font-medium">A imagem será exibida nas listagens e no topo da notícia</p>
                <p class="text-xs text-gray-500">Para melhor qualidade, use imagens na proporção 16:9</p>
              </div>
            </div>
            
            <!-- Preview da imagem -->
            <div v-if="currentImagePreview" class="flex items-center justify-center border rounded-lg p-2 bg-gray-50">
              <img :src="currentImagePreview" alt="Preview" class="max-h-48 object-contain" />
            </div>
            <div v-else class="flex items-center justify-center border rounded-lg p-4 bg-gray-50 border-dashed text-gray-400">
              <div class="text-center">
                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-1">{{ form.remover_imagem ? 'Imagem será removida' : 'Nenhuma imagem selecionada' }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Editor de Conteúdo -->
        <div class="p-4 rounded-lg">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-lg flex items-center">
              <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19.03 6.03L20 7L18.15 8.85L19.03 9.03L19.4 12.41L18.21 13.6L19 15L16.3 17.7L15 17L13.6 18.21L13.03 19.97L10.03 19.97L8.3 18.3L6 19L5 16.5L3.41 14.91L6.83 12.3H7.33L9 13L10.03 13.03V15L12.5 13.5L14 12L12.5 10.5L11 9.1H9.03L5.83 7.5L7.5 5.8L10 5L11 7L14 7L15.56 5.44L19.03 6.03Z" />
                </svg>
              </span>
              Conteúdo da Notícia
            </h3>
            
            <div class="flex items-center space-x-2">
              <button 
                type="button" 
                class="text-sm text-blue-600 hover:text-blue-800 flex items-center"
                @click="editorHeight = editorHeight === '400px' ? '600px' : '400px'"
              >
                <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M10,21V19H6.41L10.91,14.5L9.5,13.09L5,17.59V14H3V21H10M14.5,10.91L19,6.41V10H21V3H14V5H17.59L13.09,9.5L14.5,10.91Z" />
                </svg>
                {{ editorHeight === '400px' ? 'Expandir editor' : 'Reduzir editor' }}
              </button>
            </div>
          </div>
          
          <div class="border rounded-lg overflow-hidden relative">
            <!-- Modo visualização -->
            <div v-if="isPreviewMode" class="p-6 prose max-w-none min-h-[400px]" v-html="form.conteudo"></div>
            
            <!-- Modo edição -->
            <div v-else>
              <QuillEditor 
                v-model:content="form.conteudo"
                content-type="html"
                :options="editorOptions"
                :style="{ height: editorHeight, 'max-height': '800px' }"
                @ready="onEditorMounted"
              />
              
              <div class="flex justify-between items-center px-4 py-2 bg-gray-50 border-t text-xs text-gray-500">
                <div>
                  <span>{{ wordCount }} palavras</span>
                  <span class="mx-2">|</span>
                  <span>Tempo estimado de leitura: {{ Math.max(1, Math.ceil(wordCount / 200)) }} min</span>
                </div>
              </div>
            </div>
            
            <!-- Badge de erro -->
            <div 
              v-if="form.errors.conteudo" 
              class="absolute top-0 right-0 m-2 px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full"
            >
              {{ form.errors.conteudo }}
            </div>
          </div>

            <!-- Dicas de uso do editor -->
          <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mt-4 text-sm text-blue-700">
            <h4 class="font-medium flex items-center mb-2">
              <svg class="w-5 h-5 mr-1" viewBox="0 0 24 24" fill="currentColor">
                <path d="M11,9H13V7H11M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,13H13V17H11V13Z" />
              </svg>
              Dicas para um conteúdo de qualidade:
            </h4>
            <ul class="list-disc list-inside space-y-1">
              <li>Use os títulos (H2, H3) para organizar o conteúdo em seções</li>
              <li>Inclua imagens relevantes ao longo do texto usando o botão de imagem</li>
              <li>Para incorporar vídeos do YouTube, clique no botão de vídeo</li>
              <li>O texto deve ter no mínimo 300 palavras para melhor indexação</li>
              <li>Verifique a formatação e ortografia antes de publicar</li>
            </ul>
          </div>
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