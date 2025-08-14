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
const editorContainer = ref(null);
const showDocumentUpload = ref(false);
const documentUploadProgress = ref(0);
const isUploadingDocument = ref(false);
let autoSaveTimer = null;

// Classe adaptadora para upload de imagens usando servidor Laravel
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
            console.log('✅ Upload de imagem realizado com sucesso:', data.url);
            resolve({
              default: data.url
            });
          } else {
            throw new Error(data.error?.message || 'Upload falhou');
          }
        })
        .catch(error => {
          console.error('❌ Erro no upload de imagem:', error);
          reject(error);
        });
      });
    });
  }

  abort() {
    // Método abort se necessário
  }
}

// Função para criar o adaptador de upload de imagens
function LaravelUploadAdapterPlugin(editor) {
  editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
    return new LaravelUploadAdapter(loader);
  };
}

// Função para upload de documentos
const uploadDocument = async (file) => {
  return new Promise((resolve, reject) => {
    // Validar tamanho (10MB)
    if (file.size > 10 * 1024 * 1024) {
      reject(new Error('O documento deve ter no máximo 10MB'));
      return;
    }

    // Validar tipo
    const validTypes = [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/vnd.ms-powerpoint',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      'text/plain',
      'application/zip',
      'application/x-rar-compressed'
    ];

    if (!validTypes.includes(file.type)) {
      reject(new Error('Formato de arquivo não suportado. Use PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP ou RAR'));
      return;
    }

    // Criar FormData
    const formData = new FormData();
    formData.append('upload', file);

    // Obter token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Simular progresso
    isUploadingDocument.value = true;
    documentUploadProgress.value = 0;
    
    const progressInterval = setInterval(() => {
      if (documentUploadProgress.value < 90) {
        documentUploadProgress.value += 10;
      }
    }, 100);

    // Fazer upload via fetch
    fetch('/api/upload-ckeditor-files', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => {
      clearInterval(progressInterval);
      documentUploadProgress.value = 100;
      
      if (!response.ok) {
        return response.json().then(data => {
          throw new Error(data.error?.message || `HTTP ${response.status}`);
        });
      }
      return response.json();
    })
    .then(data => {
      if (data.uploaded && data.url) {
        console.log('✅ Upload de documento realizado com sucesso:', data.url);
        setTimeout(() => {
          isUploadingDocument.value = false;
          documentUploadProgress.value = 0;
          resolve(data);
        }, 500);
      } else {
        throw new Error(data.error?.message || 'Upload falhou');
      }
    })
    .catch(error => {
      clearInterval(progressInterval);
      isUploadingDocument.value = false;
      documentUploadProgress.value = 0;
      console.error('❌ Erro no upload de documento:', error);
      reject(error);
    });
  });
};

// Função para inserir documento no editor
const insertDocumentInEditor = (documentData) => {
  if (!editorInstance.value) return;

  const { url, fileName, fileSize } = documentData;
  
  // Debug: log da URL original
  console.log('URL original do documento:', url);
  
  // Extrair o caminho relativo corretamente
  let relativePath;
  if (url.includes('/storage/')) {
    // Remove /storage/ do início
    relativePath = url.substring(url.indexOf('/storage/') + 9);
  } else if (url.startsWith('http')) {
    // Se for URL completa, extrair apenas a parte após /storage/
    const urlObj = new URL(url);
    relativePath = urlObj.pathname.replace('/storage/', '');
  } else {
    // Se já for um path relativo, usar como está
    relativePath = url;
  }
  
  console.log('Path relativo extraído:', relativePath);
  
  // Gerar URLs para download e visualização usando route() do Laravel
  const downloadUrl = route('file.download', { 
    path: relativePath, 
    filename: fileName 
  });
  const viewUrl = route('file.view', { 
    path: relativePath 
  });
  
  console.log('URL de download:', downloadUrl);
  console.log('URL de visualização:', viewUrl);
  
  // HTML do documento com dois botões: visualizar e baixar
  const documentHtml = `
    <div class="document-attachment" style="
      border: 1px solid #e2e8f0; 
      border-radius: 6px; 
      padding: 8px 12px; 
      margin: 8px 0; 
      background: #fafbfc;
      display: flex !important;
      align-items: center !important;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    ">
      
      <span style="
        color: #334155; 
        font-weight: 500;
        font-size: 14px;
        margin-right: 16px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1 1 auto !important;
        min-width: 0 !important;
      ">${fileName} - </span>
      
      <a href="${viewUrl}" 
         target="_blank" 
         rel="noopener noreferrer"
         style="
           color: #475569;
           background: #f1f5f9;
           padding: 4px 8px;
           border-radius: 4px;
           font-size: 12px;
           font-weight: 500;
           text-decoration: none !important;
           display: inline-block;
           cursor: pointer;
           border: none;
           user-select: none;
           margin-right: 6px;
           flex-shrink: 0 !important;
         "
         onmouseover="this.style.background='#e2e8f0'; this.style.color='#334155'"
         onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'"
         title="Visualizar documento">Visualizar |</a>
         
      <a href="${downloadUrl}" 
         download
         style="
           color: #475569;
           background: #f1f5f9;
           padding: 4px 8px;
           border-radius: 4px;
           font-size: 12px;
           font-weight: 500;
           text-decoration: none !important;
           display: inline-block;
           cursor: pointer;
           border: none;
           user-select: none;
           flex-shrink: 0 !important;
         "
         onmouseover="this.style.background='#e2e8f0'; this.style.color='#334155'"
         onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'"
         title="Baixar documento">Download</a>
    </div>
  `;

  // Inserir no editor
  editorInstance.value.model.change(writer => {
    const viewFragment = editorInstance.value.data.processor.toView(documentHtml);
    const modelFragment = editorInstance.value.data.toModel(viewFragment);
    editorInstance.value.model.insertContent(modelFragment);
  });
};

// Handler para seleção de documento
const handleDocumentSelect = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  try {
    const documentData = await uploadDocument(file);
    insertDocumentInEditor(documentData);
    showDocumentUpload.value = false;
    
    // Limpar input
    event.target.value = '';
  } catch (error) {
    alert(`Erro no upload: ${error.message}`);
    event.target.value = '';
  }
};

// Função para mostrar modal de upload de documentos
const showDocumentUploadModal = () => {
  showDocumentUpload.value = true;
};

// Configuração do editor
const editorConfig = {
  toolbar: {
    shouldNotGroupWhenFull: true,
    items: [
      'heading', '|', 'bold', 'italic', 'link', 
      'bulletedList', 'numberedList', '|',
      'indent', 'outdent', '|',
      'imageUpload', 'blockQuote', 'insertTable', 'mediaEmbed', '|',
      'undo', 'redo'
    ]
  },
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

// Computed para contar documentos
const documentCount = computed(() => {
  if (!content.value) return 0;
  const matches = content.value.match(/class="document-download"/g);
  return matches ? matches.length : 0;
});

// Métodos
const onEditorReady = (editor) => {
  editorInstance.value = editor;
  calculateWordCount(editor.getData());
  
  // Log para verificar se os plugins foram carregados
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

// Handler para ESC key
const handleEscKey = (event) => {
  if (event.key === 'Escape') {
    if (showDocumentUpload.value) {
      showDocumentUpload.value = false;
      event.preventDefault();
    } else if (isFullScreen.value) {
      event.preventDefault();
      toggleFullScreen();
    }
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
  showDocumentUpload.value = false;
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
    <!-- Modal para upload de documentos -->
    <div v-if="showDocumentUpload" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
          <svg class="w-6 h-6 mr-2 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
          </svg>
          Inserir Documento
        </h3>
        
        <div class="mb-4">
          <input
            type="file"
            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar"
            @change="handleDocumentSelect"
            class="block w-full text-sm text-gray-500
              file:mr-4 file:py-2 file:px-4
              file:rounded file:border-0
              file:text-sm file:font-semibold
              file:bg-blue-50 file:text-blue-700
              hover:file:bg-blue-100"
          />
          <p class="text-sm text-gray-500 mt-2">
            Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP, RAR (máx. 10MB)
          </p>
        </div>
        
        <!-- Progresso de upload -->
        <div v-if="isUploadingDocument" class="mb-4">
          <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" 
                 :style="{ width: `${documentUploadProgress}%` }">
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-1 text-center">
            Fazendo upload... {{ documentUploadProgress }}%
          </p>
        </div>
        
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="showDocumentUpload = false"
            :disabled="isUploadingDocument"
            class="px-4 py-2 text-gray-600 bg-gray-100 rounded hover:bg-gray-200 disabled:opacity-50"
          >
            Cancelar
          </button>
        </div>
      </div>
    </div>

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
          <!-- Botão para inserir documentos -->
          <button
            type="button"
            @click="showDocumentUploadModal"
            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md flex items-center text-sm shadow transition"
          >
            <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="currentColor">
              <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
            </svg>
            Inserir Documento
          </button>
          
          <!-- Badge informativo sobre imagens -->
          <div v-if="hasBase64Images" class="text-xs bg-amber-600 px-2 py-1 rounded-md flex items-center">
            <svg class="w-3 h-3 mr-1" viewBox="0 0 24 24" fill="currentColor">
              <path d="M13,9H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
            </svg>
            {{ base64ImageCount }} imagem(s) temporária(s)
          </div>
          
          <!-- Badge para documentos -->
          <div v-if="documentCount > 0" class="text-xs bg-blue-600 px-2 py-1 rounded-md flex items-center">
            <svg class="w-3 h-3 mr-1" viewBox="0 0 24 24" fill="currentColor">
              <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
            </svg>
            {{ documentCount }} documento(s)
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
            
            <span v-if="documentCount > 0" class="flex items-center font-medium">
              <svg class="w-4 h-4 mr-1.5 text-gray-600" viewBox="0 0 24 24" fill="currentColor">
                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
              </svg>
              {{ documentCount }} documento(s)
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

      <!-- Dica sobre documentos -->
      <div v-if="documentCount > 0" class="bg-blue-50 border-l-4 border-blue-400 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-blue-700">
              <strong>{{ documentCount }} documento(s) inserido(s) na notícia.</strong> 
              Os leitores poderão fazer download dos arquivos clicando nos links.
            </p>
          </div>
        </div>
      </div>

      <!-- Instruções para usar o editor -->
      <div class="bg-gray-50 border-l-4 border-gray-400 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-gray-700">
              <strong>Dicas do Editor:</strong>
            </p>
            <ul class="mt-2 text-sm text-gray-600 space-y-1">
              <li>• Use o botão "Inserir Documento" para adicionar arquivos (PDF, DOC, XLS, etc.)</li>
              <li>• Arraste e solte imagens diretamente no editor</li>
              <li>• Use Ctrl+K para inserir links rapidamente</li>
            </ul>
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

/* Estilos para documentos no preview */
.preview-container .document-download {
  border: 2px solid #e5e7eb !important;
  border-radius: 8px !important;
  padding: 16px !important;
  margin: 16px 0 !important;
  background: #f9fafb !important;
  display: flex !important;
  align-items: center !important;
  transition: all 0.2s ease !important;
}

.preview-container .document-download:hover {
  border-color: #3b82f6 !important;
  background: #eff6ff !important;
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

/* Estilos para documentos inseridos no editor */
.ck.ck-editor__editable .document-download {
  border: 2px solid #e5e7eb !important;
  border-radius: 8px !important;
  padding: 16px !important;
  margin: 16px 0 !important;
  background: #f9fafb !important;
  display: flex !important;
  align-items: center !important;
  transition: all 0.2s ease !important;
}

.ck.ck-editor__editable .document-download:hover {
  border-color: #3b82f6 !important;
  background: #eff6ff !important;
}

.ck.ck-editor__editable .document-download a {
  color: #1f2937 !important;
  text-decoration: none !important;
  font-weight: 600 !important;
}

.ck.ck-editor__editable .document-download a:hover {
  color: #3b82f6 !important;
}
</style>