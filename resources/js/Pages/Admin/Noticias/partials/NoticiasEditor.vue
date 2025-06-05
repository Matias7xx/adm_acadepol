<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { CKEditor } from '@ckeditor/ckeditor5-vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

// Props
const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
  height: {
    type: String,
    default: '550px'
  }
});

// Emits
const emit = defineEmits(['update:modelValue', 'wordCountChange']);

// Estado local
const content = ref(props.modelValue);
const editor = ref(ClassicEditor);
const editorInstance = ref(null);
const isPreviewMode = ref(false);
const isFullScreen = ref(false);
const editorHeight = ref(props.height);
const wordCount = ref(0);
const lastSaved = ref('Agora');
const editorContainer = ref(null);
let autoSaveTimer = null;

// Classe adaptadora para upload usando servidor Laravel
class LaravelUploadAdapter {
  constructor(loader) {
    this.loader = loader;
  }

  upload() {
    return this.loader.file.then(file => {
      return new Promise((resolve, reject) => {
        // Validar tamanho (5MB)
        if (file.size > 5 * 1024 * 1024) {
          reject(new Error('A imagem deve ter no máximo 5MB'));
          return;
        }
        
        // Validar tipo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
          reject(new Error('Formato de imagem não suportado. Use JPEG, PNG, GIF ou WebP'));
          return;
        }
        
        // Criar FormData
        const formData = new FormData();
        formData.append('upload', file);
        
        // Obter token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // Fazer upload via fetch
        fetch('/api/upload-ckeditor-images', {
          method: 'POST',
          body: formData,
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => {
          if (!response.ok) {
            return response.json().then(data => {
              throw new Error(data.error?.message || `HTTP ${response.status}`);
            });
          }
          return response.json();
        })
        .then(data => {
          if (data.uploaded && data.url) {
            console.log('✅ Upload realizado com sucesso:', data.url);
            resolve({
              default: data.url
            });
          } else {
            throw new Error(data.error?.message || 'Upload falhou');
          }
        })
        .catch(error => {
          console.error('❌ Erro no upload:', error);
          reject(error);
        });
      });
    });
  }

  abort() {
    // Método abort se necessário
  }
}

// Função para criar o adaptador de upload
function LaravelUploadAdapterPlugin(editor) {
  editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
    return new LaravelUploadAdapter(loader);
  };
}

// Configuração do editor
const editorConfig = {
  toolbar: [
    'heading', 
    '|', 
    'bold', 'italic', 'link', 'bulletedList', 'numberedList',
    '|',
    'indent', 'outdent',
    '|',
    'imageUpload', 'blockQuote', 'insertTable', 'mediaEmbed',
    '|',
    'undo', 'redo'
  ],
  heading: {
    options: [
      { model: 'paragraph', title: 'Parágrafo', class: 'ck-heading_paragraph' },
      { model: 'heading2', view: 'h2', title: 'Título 2', class: 'ck-heading_heading2' },
      { model: 'heading3', view: 'h3', title: 'Título 3', class: 'ck-heading_heading3' },
      { model: 'heading4', view: 'h4', title: 'Título 4', class: 'ck-heading_heading4' }
    ]
  },
  image: {
    toolbar: [
      'imageStyle:inline',
      'imageStyle:block',
      'imageStyle:side',
      '|',
      'toggleImageCaption',
      'imageTextAlternative'
    ],
    upload: {
      types: ['jpeg', 'png', 'gif', 'jpg', 'webp']
    }
  },
  table: {
    contentToolbar: [
      'tableColumn', 'tableRow', 'mergeTableCells',
      'tableProperties', 'tableCellProperties'
    ]
  },
  mediaEmbed: {
    previewsInData: true,
    providers: [
      {
        name: 'youtube',
        url: [
          /^(?:m\.)?youtube\.com\/watch\?v=([\w-]+)/,
          /^(?:m\.)?youtube\.com\/v\/([\w-]+)/,
          /^youtube\.com\/embed\/([\w-]+)/,
          /^youtu\.be\/([\w-]+)/
        ],
        html: match => {
          const id = match[1];
          return (
            '<div style="position: relative; padding-bottom: 100%; height: 0; padding-bottom: 56.2493%;">' +
              `<iframe src="https://www.youtube.com/embed/${id}" ` +
                'style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" ' +
                'frameborder="0" allow="autoplay; encrypted-media" allowfullscreen>' +
              '</iframe>' +
            '</div>'
          );
        }
      }
    ]
  },
  placeholder: 'Comece a escrever seu conteúdo aqui...',
  extraPlugins: [LaravelUploadAdapterPlugin],
  language: 'pt-br'
};

// Watch para sincronizar com o v-model
watch(() => props.modelValue, (newValue) => {
  if (newValue !== content.value) {
    content.value = newValue;
  }
});

watch(content, (newValue) => {
  emit('update:modelValue', newValue);
  calculateWordCount(newValue);
});

// Computed para verificar se há imagens em base64
const hasBase64Images = computed(() => {
  return content.value && content.value.includes('data:image/');
});

// Computed para contar imagens em base64
const base64ImageCount = computed(() => {
  if (!content.value) return 0;
  const matches = content.value.match(/data:image\/[^"']*/g);
  return matches ? matches.length : 0;
});

// Métodos
const onEditorReady = (editor) => {
  editorInstance.value = editor;
  calculateWordCount(editor.getData());
  
  // Log para verificar se o plugin foi carregado
  try {
    const fileRepository = editor.plugins.get('FileRepository');
    console.log('✅ Plugin FileRepository carregado - upload direto para Laravel');
    
    // Listener para uploads
    fileRepository.on('uploadComplete', (evt, data) => {
      console.log('✅ Upload completo:', data);
    });
    
  } catch (error) {
    console.error('❌ Erro ao configurar plugin de upload:', error);
  }
  
  // Configurar auto-save
  setupAutoSave();
};

const onEditorChange = () => {
  if (editorInstance.value) {
    const data = editorInstance.value.getData();
    content.value = data;
    calculateWordCount(data);
  }
};

const calculateWordCount = (text) => {
  if (!text) {
    wordCount.value = 0;
    emit('wordCountChange', 0);
    return;
  }
  
  // Remove HTML tags
  const plainText = text.replace(/<[^>]*>/g, ' ');
  // Split por espaços e filtra strings vazias
  const words = plainText.split(/\s+/).filter(word => word.length > 0);
  wordCount.value = words.length;
  emit('wordCountChange', wordCount.value);
};

const togglePreview = () => {
  isPreviewMode.value = !isPreviewMode.value;
};

const toggleFullScreen = async () => {
  isFullScreen.value = !isFullScreen.value;
  
  if (isFullScreen.value) {
    // Entrar em tela cheia
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
  } else {
    // Sair da tela cheia
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
  }
  
  // Aguardar o DOM atualizar e reconfigurar o editor
  await nextTick();
  
  if (editorInstance.value) {
    // Forçar refresh do editor
    editorInstance.value.editing.view.change(() => {
      // Força o editor a recalcular suas dimensões
    });
  }
};

const setupAutoSave = () => {
  // Auto-save a cada 60 segundos
  autoSaveTimer = setInterval(() => {
    if (content.value && content.value.length > 10) {
      try {
        // Salvar no localStorage
        localStorage.setItem('noticia_draft_' + Date.now(), JSON.stringify({
          content: content.value,
          timestamp: new Date().toISOString(),
          wordCount: wordCount.value
        }));
        
        // Atualizar timestamp
        const now = new Date();
        lastSaved.value = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
        
        // Limpar drafts antigos (manter apenas os últimos 3)
        const keys = Object.keys(localStorage).filter(key => key.startsWith('noticia_draft_'));
        if (keys.length > 3) {
          keys.sort().slice(0, -3).forEach(key => localStorage.removeItem(key));
        }
      } catch (e) {
        console.warn('Erro ao salvar draft:', e);
      }
    }
  }, 60000);
};

// Handler para ESC key
const handleEscKey = (event) => {
  if (event.key === 'Escape' && isFullScreen.value) {
    event.preventDefault();
    toggleFullScreen();
  }
};

// Cleanup
onUnmounted(() => {
  if (autoSaveTimer) {
    clearInterval(autoSaveTimer);
  }
  
  // Restaurar overflow do body
  document.body.style.overflow = '';
  document.documentElement.style.overflow = '';
  
  // Remover listener de ESC
  document.removeEventListener('keydown', handleEscKey);
  
  // Resetar estado
  isFullScreen.value = false;
});

// Inicialização
onMounted(() => {
  // Adicionar listener para ESC
  document.addEventListener('keydown', handleEscKey);
  
  // Verificar se há meta tag CSRF
  if (!document.querySelector('meta[name="csrf-token"]')) {
    console.warn('⚠️ Meta tag CSRF não encontrada. Adicione no layout: <meta name="csrf-token" content="{{ csrf_token() }}">');
  }
});
</script>

<template>
  <div class="content-editor-container" ref="editorContainer">
    <!-- Container do editor -->
    <div 
      class="editor-wrapper border border-gray-300 overflow-hidden shadow-lg"
      :class="{ 
        'editor-fullscreen': isFullScreen,
        'rounded-lg': !isFullScreen,
        'rounded-b-lg': !isFullScreen 
      }"
    >
      <!-- Cabeçalho do Editor -->
      <div class="flex justify-between items-center bg-slate-800 text-white p-4"
           :class="{ 'rounded-t-lg': !isFullScreen }">
        <h3 class="font-semibold text-lg flex items-center">
          <span class="icon w-6 h-6 mr-2 text-yellow-400">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19.03 6.03L20 7L18.15 8.85L19.03 9.03L19.4 12.41L18.21 13.6L19 15L16.3 17.7L15 17L13.6 18.21L13.03 19.97L10.03 19.97L8.3 18.3L6 19L5 16.5L3.41 14.91L6.83 12.3H7.33L9 13L10.03 13.03V15L12.5 13.5L14 12L12.5 10.5L11 9.1H9.03L5.83 7.5L7.5 5.8L10 5L11 7L14 7L15.56 5.44L19.03 6.03Z" />
            </svg>
          </span>
          Conteúdo da Notícia
        </h3>
        
        <div class="flex items-center space-x-3">
          <!-- Badge informativo sobre imagens -->
          <div v-if="hasBase64Images" class="text-xs bg-amber-600 px-2 py-1 rounded-md flex items-center">
            <svg class="w-3 h-3 mr-1" viewBox="0 0 24 24" fill="currentColor">
              <path d="M13,9H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
            </svg>
            {{ base64ImageCount }} imagem(s) temporária(s)
          </div>
          
          <div v-else class="text-xs bg-green-600 px-2 py-1 rounded-md flex items-center">
            <svg class="w-3 h-3 mr-1" viewBox="0 0 24 24" fill="currentColor">
              <path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" />
            </svg>
            Imagens processadas
          </div>
          
          <button 
            type="button" 
            class="px-3 py-1.5 rounded-md flex items-center text-sm shadow transition"
            :class="isFullScreen 
              ? 'bg-red-600 hover:bg-red-700 text-white' 
              : 'bg-gray-200 hover:bg-gray-300 text-gray-700'"
            @click="toggleFullScreen"
          >
            <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="currentColor">
              <path v-if="isFullScreen" d="M14,14H19V16H16V19H14V14M5,14H10V19H8V16H5V14M8,5H10V10H5V8H8V5M19,8V10H14V5H16V8H19Z" />
              <path v-else d="M5,5H10V7H7V10H5V5M14,5H19V10H17V7H14V5M17,14H19V19H14V17H17V14M10,17V19H5V14H7V17H10Z" />
            </svg>
            {{ isFullScreen ? 'Sair da Tela Cheia (ESC)' : 'Tela Cheia' }}
          </button>
        </div>
      </div>
      
      <!-- Modo Preview -->
      <div 
        v-if="isPreviewMode" 
        class="preview-container bg-white p-6 prose prose-slate max-w-none overflow-y-auto editor-content"
        :style="{ 
          minHeight: isFullScreen ? 'calc(100vh - 200px)' : editorHeight, 
          maxHeight: isFullScreen ? 'calc(100vh - 200px)' : editorHeight 
        }"
        v-html="content"
      ></div>
      
      <!-- Modo Edição com CKEditor -->
      <div v-else class="editor-content">
        <ckeditor
          :editor="editor"
          v-model="content"
          :config="editorConfig"
          @ready="onEditorReady"
          @change="onEditorChange"
        ></ckeditor>
        
        <!-- Barra de status -->
        <div class="flex justify-between items-center px-4 py-2 bg-gray-50 border-t text-sm text-gray-700">
          <div class="flex items-center space-x-4">
            <span class="flex items-center font-medium">
              <svg class="w-4 h-4 mr-1.5 text-gray-600" viewBox="0 0 24 24" fill="currentColor">
                <path d="M14,17H7V15H14M17,13H7V11H17M17,9H7V7H17M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3Z" />
              </svg>
              {{ wordCount }} palavras
            </span>
            
            <span class="flex items-center font-medium">
              <svg class="w-4 h-4 mr-1.5 text-gray-600" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" />
              </svg>
              Tempo de leitura: {{ Math.max(1, Math.ceil(wordCount / 230)) }} min
            </span>
          </div>
          
          <div class="flex items-center space-x-4">
            <!-- Indicador de imagens base64 -->
            <span v-if="hasBase64Images" class="text-xs text-amber-600 font-medium flex items-center">
              <svg class="w-3 h-3 mr-1" viewBox="0 0 24 24" fill="currentColor">
                <path d="M5,3A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H14.09C14.03,20.67 14,20.34 14,20C14,19.32 14.12,18.64 14.35,18H5L8.5,13.5L11,16.5L14.5,12L16.73,14.97C17.7,14.34 18.84,14 20,14C20.34,14 20.67,14.03 21,14.09V5C21,3.89 20.1,3 19,3H5M19,16V19H16V21H19V24H21V21H24V19H21V16H19Z" />
              </svg>
              {{ base64ImageCount }} temporária(s)
            </span>
            
            <span class="text-xs text-gray-500">
              Salvo: {{ lastSaved }}
            </span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Mensagens de ajuda e dicas -->
    <div v-if="!isFullScreen" class="mt-4 space-y-3">
      <!-- Mensagem de erro se houver -->
      <div v-if="error" class="bg-red-50 border-l-4 border-red-500 p-4 text-red-700">
        <div class="flex items-center">
          <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
            <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
          </svg>
          <span class="font-medium">{{ error }}</span>
        </div>
      </div>

      <!-- Dica sobre imagens -->
      <div v-if="hasBase64Images" class="bg-amber-50 border-l-4 border-amber-400 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-amber-700">
              <strong>{{ base64ImageCount }} imagem(s) serão processadas ao salvar a notícia.</strong> 
              As imagens são temporariamente armazenadas no editor e serão organizadas adequadamente quando você salvar.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Container principal */
.content-editor-container {
  position: relative;
}

/* Estilos para o editor */
.editor-wrapper {
  transition: all 0.3s ease-in-out;
  background: white;
}

/* Tela cheia - posicionamento fixo */
.editor-fullscreen {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  z-index: 10000 !important;
  background: white !important;
  border: none !important;
  border-radius: 0 !important;
  padding: 20px !important;
  box-sizing: border-box !important;
  display: flex !important;
  flex-direction: column !important;
}

/* Conteúdo do editor */
.editor-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Preview com texto bem escuro */
.preview-container {
  color: #111827 !important;
  line-height: 1.7;
  font-size: 16px;
}

.preview-container h1,
.preview-container h2,
.preview-container h3,
.preview-container h4,
.preview-container h5,
.preview-container h6 {
  color: #1f2937 !important;
  font-weight: 600;
  margin-top: 1.5em;
  margin-bottom: 0.75em;
}

.preview-container p {
  color: #111827 !important;
  margin-bottom: 1em;
}

.preview-container ul,
.preview-container ol {
  color: #111827 !important;
}

.preview-container li {
  color: #111827 !important;
  margin-bottom: 0.5em;
}

.preview-container blockquote {
  color: #374151 !important;
  border-left: 4px solid #e5e7eb;
  padding-left: 1rem;
  font-style: italic;
}

.preview-container img {
  margin: 1.5em auto;
  border-radius: 4px;
  max-width: 100%;
  height: auto;
}

.preview-container iframe {
  width: 100%;
  min-height: 400px;
  border: none;
  border-radius: 4px;
  margin: 1.5em 0;
}
</style>

<style>
/* Estilos globais para CKEditor */
.ck.ck-editor {
  width: 100% !important;
}

.ck.ck-editor__main {
  flex: 1 !important;
}

.ck.ck-editor__editable {
  min-height: 450px !important;
  max-height: 800px !important;
  overflow-y: auto !important;
  color: #111827 !important;
  line-height: 1.6 !important;
  font-size: 16px !important;
  padding: 20px !important;
}

/* Tela cheia - altura específica */
.editor-fullscreen .ck.ck-editor__editable {
  min-height: calc(100vh - 250px) !important;
  max-height: calc(100vh - 250px) !important;
}

/* Texto escuro em todos os elementos do editor */
.ck.ck-editor__editable p,
.ck.ck-editor__editable h1,
.ck.ck-editor__editable h2,
.ck.ck-editor__editable h3,
.ck.ck-editor__editable h4,
.ck.ck-editor__editable h5,
.ck.ck-editor__editable h6,
.ck.ck-editor__editable li,
.ck.ck-editor__editable blockquote,
.ck.ck-editor__editable td,
.ck.ck-editor__editable th {
  color: #111827 !important;
}

/* Placeholder com cor mais suave */
.ck.ck-editor__editable.ck-placeholder::before {
  color: #9ca3af !important;
}

/* Toolbar styling */
.ck.ck-toolbar {
  border-color: #e5e7eb !important;
  background: #f9fafb !important;
  padding: 8px !important;
}

.ck.ck-editor__editable:not(.ck-editor__nested-editable).ck-focused {
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

/* Garantir que listas tenham texto escuro */
.ck.ck-editor__editable ul li,
.ck.ck-editor__editable ol li {
  color: #111827 !important;
}

/* Tabelas com texto escuro */
.ck.ck-editor__editable table {
  color: #111827 !important;
}

.ck.ck-editor__editable table td,
.ck.ck-editor__editable table th {
  color: #111827 !important;
  border: 1px solid #d1d5db !important;
}

/* Imagens temporárias com indicador visual */
.ck.ck-editor__editable img[src^="data:image"] {
  border: 2px dashed #f59e0b !important;
  background-color: #fef3c7 !important;
  padding: 4px !important;
  position: relative;
}

.ck.ck-editor__editable img[src^="data:image"]::after {
  content: "📸 Temporária";
  position: absolute;
  top: -25px;
  left: 0;
  background: #f59e0b;
  color: white;
  padding: 2px 6px;
  font-size: 10px;
  border-radius: 3px;
  font-family: sans-serif;
}
</style>