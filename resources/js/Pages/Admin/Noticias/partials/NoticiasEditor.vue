<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { CKEditor } from '@ckeditor/ckeditor5-vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

// Props
const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  carouselImages: {
    type: Array,
    default: () => [],
  },
  error: {
    type: String,
    default: '',
  },
  height: {
    type: String,
    default: '550px',
  },
});

// Emits
const emit = defineEmits([
  'update:modelValue',
  'update:carouselImages',
  'wordCountChange',
]);

// Estado local
const content = ref(props.modelValue);
const carouselImagesLocal = ref([...props.carouselImages]);
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
const isUploadingCarouselImage = ref(false);
const carouselImageUploadProgress = ref(0);
let autoSaveTimer = null;

// Watch para sincronizar com prop externa
watch(
  () => props.carouselImages,
  newVal => {
    carouselImagesLocal.value = [...newVal];
  },
  { deep: true }
);

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
        const validTypes = [
          'image/jpeg',
          'image/jpg',
          'image/png',
          'image/gif',
          'image/webp',
        ];
        if (!validTypes.includes(file.type)) {
          reject(
            new Error(
              'Formato de imagem não suportado. Use JPEG, PNG, GIF ou WebP'
            )
          );
          return;
        }

        // Criar FormData
        const formData = new FormData();
        formData.append('upload', file);

        // Obter token CSRF
        const csrfToken = document
          .querySelector('meta[name="csrf-token"]')
          ?.getAttribute('content');

        // Fazer upload via fetch
        fetch('/api/upload-ckeditor-images', {
          method: 'POST',
          body: formData,
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
          },
        })
          .then(response => {
            if (!response.ok) {
              return response.json().then(data => {
                throw new Error(
                  data.error?.message || `HTTP ${response.status}`
                );
              });
            }
            return response.json();
          })
          .then(data => {
            if (data.uploaded && data.url) {
              console.log(
                '✅ Upload de imagem realizado com sucesso:',
                data.url
              );
              resolve({
                default: data.url,
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
  editor.plugins.get('FileRepository').createUploadAdapter = loader => {
    return new LaravelUploadAdapter(loader);
  };
}

// Upload de imagens para o carrossel
const handleCarouselImageUpload = async event => {
  const files = Array.from(event.target.files);

  for (const file of files) {
    try {
      // Validar tamanho (5MB)
      if (file.size > 5 * 1024 * 1024) {
        alert('A imagem deve ter no máximo 5MB');
        continue;
      }

      // Validar tipo
      const validTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
      ];
      if (!validTypes.includes(file.type)) {
        alert('Formato de imagem não suportado. Use JPEG, PNG, GIF ou WebP');
        continue;
      }

      // Criar FormData
      const formData = new FormData();
      formData.append('upload', file);

      // Obter token CSRF
      const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

      isUploadingCarouselImage.value = true;
      carouselImageUploadProgress.value = 0;

      // Simular progresso
      const progressInterval = setInterval(() => {
        if (carouselImageUploadProgress.value < 90) {
          carouselImageUploadProgress.value += 10;
        }
      }, 100);

      // Fazer upload via fetch
      const response = await fetch('/api/upload-ckeditor-images', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      clearInterval(progressInterval);
      carouselImageUploadProgress.value = 100;

      if (!response.ok) {
        const data = await response.json();
        throw new Error(data.error?.message || `HTTP ${response.status}`);
      }

      const data = await response.json();

      if (data.uploaded && data.url) {
        console.log('✅ Imagem do carrossel enviada:', data.url);

        // Adicionar à lista de imagens do carrossel
        carouselImagesLocal.value.push({
          url: data.url,
          id: Date.now() + Math.random(),
        });

        // Emitir atualização
        emit('update:carouselImages', carouselImagesLocal.value);

        setTimeout(() => {
          isUploadingCarouselImage.value = false;
          carouselImageUploadProgress.value = 0;
        }, 500);
      } else {
        throw new Error(data.error?.message || 'Upload falhou');
      }
    } catch (error) {
      isUploadingCarouselImage.value = false;
      carouselImageUploadProgress.value = 0;
      console.error('❌ Erro no upload de imagem do carrossel:', error);
      alert(`Erro no upload: ${error.message}`);
    }
  }

  // Limpar input
  event.target.value = '';
};

// Remover imagem do carrossel
const removeCarouselImage = index => {
  if (confirm('Deseja realmente remover esta imagem do carrossel?')) {
    carouselImagesLocal.value.splice(index, 1);
    emit('update:carouselImages', carouselImagesLocal.value);
  }
};

// Mover imagem para cima
const moveImageUp = index => {
  if (index > 0) {
    const temp = carouselImagesLocal.value[index];
    carouselImagesLocal.value[index] = carouselImagesLocal.value[index - 1];
    carouselImagesLocal.value[index - 1] = temp;
    emit('update:carouselImages', carouselImagesLocal.value);
  }
};

// Mover imagem para baixo
const moveImageDown = index => {
  if (index < carouselImagesLocal.value.length - 1) {
    const temp = carouselImagesLocal.value[index];
    carouselImagesLocal.value[index] = carouselImagesLocal.value[index + 1];
    carouselImagesLocal.value[index + 1] = temp;
    emit('update:carouselImages', carouselImagesLocal.value);
  }
};

// Função para upload de documentos (mantida do original)
const uploadDocument = async file => {
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
      'application/x-rar-compressed',
    ];

    if (!validTypes.includes(file.type)) {
      reject(
        new Error(
          'Formato de arquivo não suportado. Use PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP ou RAR'
        )
      );
      return;
    }

    // Criar FormData
    const formData = new FormData();
    formData.append('upload', file);

    // Obter token CSRF
    const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content');

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
        'X-Requested-With': 'XMLHttpRequest',
      },
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
          console.log(
            '✅ Upload de documento realizado com sucesso:',
            data.url
          );
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

// Função para inserir documento no editor (mantida do original)
const insertDocumentInEditor = documentData => {
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
    filename: fileName,
  });
  const viewUrl = route('file.view', {
    path: relativePath,
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
           margin-right: 8px;
           transition: all 0.2s;
         "
         onmouseover="this.style.background='#e2e8f0'; this.style.color='#1e293b';"
         onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569';"
      >👁️ Visualizar</a>

      <a href="${downloadUrl}"
         download="${fileName}"
         style="
           color: #ffffff;
           background: #bea55a;
           padding: 4px 8px;
           border-radius: 4px;
           font-size: 12px;
           font-weight: 500;
           text-decoration: none !important;
           display: inline-block;
           cursor: pointer;
           border: none;
           transition: all 0.2s;
         "
         onmouseover="this.style.background='#a08d47';"
         onmouseout="this.style.background='#bea55a';"
      >⬇️ Baixar</a>
    </div>
  `;

  // Inserir no editor
  const viewFragment = editorInstance.value.data.processor.toView(documentHtml);
  const modelFragment = editorInstance.value.data.toModel(viewFragment);
  editorInstance.value.model.insertContent(modelFragment);
};

// Handler para seleção de arquivo de documento
const handleDocumentSelect = async event => {
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

// Configuração do editor (mantida do original, mas SEM imageUpload na toolbar)
const editorConfig = {
  toolbar: {
    shouldNotGroupWhenFull: true,
    items: [
      'heading',
      '|',
      'bold',
      'italic',
      'link',
      'bulletedList',
      'numberedList',
      '|',
      'indent',
      'outdent',
      '|',
      'blockQuote',
      'insertTable',
      'mediaEmbed',
      '|',
      'undo',
      'redo',
    ],
  },
  heading: {
    options: [
      { model: 'paragraph', title: 'Parágrafo', class: 'ck-heading_paragraph' },
      {
        model: 'heading2',
        view: 'h2',
        title: 'Título 2',
        class: 'ck-heading_heading2',
      },
      {
        model: 'heading3',
        view: 'h3',
        title: 'Título 3',
        class: 'ck-heading_heading3',
      },
      {
        model: 'heading4',
        view: 'h4',
        title: 'Título 4',
        class: 'ck-heading_heading4',
      },
    ],
  },
  table: {
    contentToolbar: [
      'tableColumn',
      'tableRow',
      'mergeTableCells',
      'tableProperties',
      'tableCellProperties',
    ],
  },
  mediaEmbed: {
    previewsInData: true,
    providers: [
      {
        name: 'youtube',
        url: [
          /^(?:m\.)?youtube\.com\/watch\?v=([\w-]+)/,
          /^(?:m\.)?youtube\.com\/embed\/([\w-]+)/,
          /^(?:m\.)?youtu\.be\/([\w-]+)/,
        ],
        html: match => {
          const id = match[1];
          return (
            '<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%;margin: 16px 0;">' +
            `<iframe src="https://www.youtube.com/embed/${id}" ` +
            'style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" ' +
            'frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>' +
            '</div>'
          );
        },
      },
      {
        name: 'vimeo',
        url: [
          /^vimeo\.com\/(\d+)/,
          /^(?:www\.)?vimeo\.com\/(\d+)/,
          /^player\.vimeo\.com\/video\/(\d+)/,
        ],
        html: match => {
          const id = match[1];
          return (
            '<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%;margin: 16px 0;">' +
            `<iframe src="https://player.vimeo.com/video/${id}" ` +
            'style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" ' +
            'frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>' +
            '</div>'
          );
        },
      },
    ],
  },
  extraPlugins: [LaravelUploadAdapterPlugin],
  language: 'pt-br',
  placeholder: 'Digite o conteúdo da notícia aqui...',
};

const onReady = editorInst => {
  editorInstance.value = editorInst;

  // Definir conteúdo inicial
  editorInst.setData(content.value);
  updateWordCount();

  // Listener para mudanças no conteúdo - COM DEBOUNCE
  let updateTimeout = null;
  editorInst.model.document.on('change:data', () => {
    const newData = editorInst.getData();

    // Limpar timeout anterior
    if (updateTimeout) {
      clearTimeout(updateTimeout);
    }

    // Agendar atualização
    updateTimeout = setTimeout(() => {
      content.value = newData;
      emit('update:modelValue', newData);
      updateWordCount();
    }, 300); // 300ms de delay
  });
};

const updateWordCount = () => {
  if (!editorInstance.value) return;

  const data = editorInstance.value.getData();
  const text = data.replace(/<[^>]*>/g, '');
  const words = text
    .trim()
    .split(/\s+/)
    .filter(word => word.length > 0);
  wordCount.value = words.length;
  emit('wordCountChange', wordCount.value);
};

const togglePreview = () => {
  isPreviewMode.value = !isPreviewMode.value;
};

const toggleFullScreen = async () => {
  isFullScreen.value = !isFullScreen.value;

  if (isFullScreen.value) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }

  await nextTick();
  if (editorInstance.value) {
    editorInstance.value.editing.view.focus();
  }
};

const documentCount = computed(() => {
  if (!content.value) return 0;
  const matches = content.value.match(/class="document-download"/g);
  return matches ? matches.length : 0;
});

watch(
  () => props.modelValue,
  newVal => {
    if (newVal !== content.value) {
      content.value = newVal;
    }
  }
);

onMounted(() => {
  const handleEsc = e => {
    if (e.key === 'Escape' && isFullScreen.value) {
      toggleFullScreen();
    }
  };
  document.addEventListener('keydown', handleEsc);

  onUnmounted(() => {
    document.removeEventListener('keydown', handleEsc);
    document.body.style.overflow = '';
    if (autoSaveTimer) {
      clearTimeout(autoSaveTimer);
    }
  });
});
</script>

<template>
  <div class="content-editor-container">
    <!-- Seção de Imagens do Carrossel -->
    <div
      class="mb-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 border-2 border-blue-200"
    >
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="bg-blue-500 p-2 rounded-lg">
            <svg
              class="w-6 h-6 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-800">Imagens da Notícia</h3>
            <p class="text-sm text-gray-600">
              Adicione imagens que aparecerão no carrossel da notícia (opcional)
            </p>
          </div>
        </div>

        <label class="cursor-pointer">
          <input
            type="file"
            accept="image/*"
            multiple
            @change="handleCarouselImageUpload"
            class="hidden"
            :disabled="isUploadingCarouselImage"
          />
          <div
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors shadow-md"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            <span>Adicionar Imagens</span>
          </div>
        </label>
      </div>

      <!-- Progress bar durante upload -->
      <div v-if="isUploadingCarouselImage" class="mb-4">
        <div class="bg-white rounded-lg p-3 shadow-sm">
          <div class="flex items-center gap-2 mb-2">
            <svg
              class="w-5 h-5 text-blue-500 animate-spin"
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
            <span class="text-sm font-medium text-gray-700"
              >Enviando imagem...</span
            >
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${carouselImageUploadProgress}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Lista de imagens -->
      <div v-if="carouselImagesLocal.length > 0" class="space-y-3">
        <div
          v-for="(image, index) in carouselImagesLocal"
          :key="image.id"
          class="bg-white rounded-lg p-3 flex items-center gap-4 shadow-sm hover:shadow-md transition-shadow"
        >
          <!-- Miniatura -->
          <img
            :src="image.url"
            alt="Imagem do carrossel"
            class="w-20 h-20 object-cover rounded-md border-2 border-gray-200"
          />

          <!-- Informações -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-700 truncate">
              Imagem {{ index + 1 }}
            </p>
            <p class="text-xs text-gray-500">
              {{ image.url.split('/').pop() }}
            </p>
          </div>

          <!-- Botões de ação -->
          <div class="flex items-center gap-2">
            <!-- Mover para cima -->
            <button
              type="button"
              @click="moveImageUp(index)"
              :disabled="index === 0"
              :class="[
                'p-2 rounded-lg transition-colors',
                index === 0
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
              ]"
              title="Mover para cima"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 15l7-7 7 7"
                />
              </svg>
            </button>

            <!-- Mover para baixo -->
            <button
              type="button"
              @click="moveImageDown(index)"
              :disabled="index === carouselImagesLocal.length - 1"
              :class="[
                'p-2 rounded-lg transition-colors',
                index === carouselImagesLocal.length - 1
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
              ]"
              title="Mover para baixo"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </button>

            <!-- Remover -->
            <button
              type="button"
              @click="removeCarouselImage(index)"
              class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors"
              title="Remover imagem"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
            </button>
          </div>
        </div>

        <!-- Preview do Carrossel -->
        <div class="mt-4 bg-white rounded-lg p-4 border-2 border-blue-200">
          <p
            class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2"
          >
            <svg
              class="w-5 h-5 text-blue-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
              />
            </svg>
            Pré-visualização do Carrossel
          </p>
          <div class="grid grid-cols-3 gap-2">
            <img
              v-for="(image, index) in carouselImagesLocal.slice(0, 6)"
              :key="image.id"
              :src="image.url"
              :alt="`Preview ${index + 1}`"
              class="w-full h-24 object-cover rounded border border-gray-200"
            />
            <div
              v-if="carouselImagesLocal.length > 6"
              class="w-full h-24 bg-gray-100 rounded border border-gray-200 flex items-center justify-center"
            >
              <span class="text-gray-500 text-sm font-medium"
                >+{{ carouselImagesLocal.length - 6 }}</span
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Estado vazio -->
      <div
        v-else
        class="bg-white rounded-lg p-8 text-center border-2 border-dashed border-gray-300"
      >
        <svg
          class="w-16 h-16 text-gray-400 mx-auto mb-3"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
          />
        </svg>
        <p class="text-gray-500 text-sm">
          Nenhuma imagem adicionada ao carrossel
        </p>
        <p class="text-gray-400 text-xs mt-1">
          Clique no botão "Adicionar Imagens" para começar
        </p>
      </div>
    </div>

    <!-- Editor de Conteúdo (mantido o restante do código original) -->
    <div
      ref="editorContainer"
      :class="[
        'editor-wrapper',
        'border',
        'border-gray-300',
        'rounded-lg',
        'overflow-hidden',
        { 'editor-fullscreen': isFullScreen },
        error ? 'border-red-500' : '',
      ]"
    >
      <!-- Toolbar customizada -->
      <div
        class="bg-gray-50 border-b border-gray-300 p-3 flex flex-wrap items-center justify-between gap-2"
      >
        <div class="flex items-center gap-2 flex-wrap">
          <button
            @click="showDocumentUploadModal"
            type="button"
            class="flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors text-sm font-medium"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
              />
            </svg>
            Inserir Documento
          </button>

          <button
            @click="toggleFullScreen"
            type="button"
            class="flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors text-sm font-medium"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                v-if="!isFullScreen"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
              />
              <path
                v-else
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"
              />
            </svg>
            {{ isFullScreen ? 'Sair da Tela Cheia' : 'Tela Cheia' }}
          </button>
        </div>

        <div class="text-sm text-gray-600">
          <span class="font-medium">{{ wordCount }}</span> palavra{{
            wordCount !== 1 ? 's' : ''
          }}
        </div>
      </div>

      <!-- Conteúdo do Editor -->
      <div class="editor-content">
        <ckeditor
          v-if="!isPreviewMode"
          v-model="content"
          :editor="editor"
          :config="editorConfig"
          @ready="onReady"
        />

        <!-- Preview -->
        <div
          v-else
          class="preview-container p-6 overflow-y-auto"
          :style="{ minHeight: editorHeight, maxHeight: '800px' }"
          v-html="content"
        ></div>
      </div>

      <!-- Modal de Upload de Documento -->
      <div
        v-if="showDocumentUpload"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="showDocumentUpload = false"
      >
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
          <h3 class="text-lg font-bold mb-4">Inserir Documento</h3>

          <div v-if="!isUploadingDocument">
            <label
              class="block border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-colors"
            >
              <input
                type="file"
                class="hidden"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar"
                @change="handleDocumentSelect"
              />
              <svg
                class="w-12 h-12 text-gray-400 mx-auto mb-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                />
              </svg>
              <p class="text-sm text-gray-600 mb-1">
                Clique para selecionar um arquivo
              </p>
              <p class="text-xs text-gray-400">
                PDF, DOC, XLS, PPT, TXT, ZIP ou RAR (máx. 10MB)
              </p>
            </label>
          </div>

          <div v-else class="text-center py-8">
            <svg
              class="w-12 h-12 text-blue-500 mx-auto mb-4 animate-spin"
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
            <p class="text-sm text-gray-600 mb-2">Enviando documento...</p>
            <div class="w-full bg-gray-200 rounded-full h-2 max-w-xs mx-auto">
              <div
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${documentUploadProgress}%` }"
              ></div>
            </div>
          </div>

          <div class="mt-4 flex justify-end">
            <button
              @click="showDocumentUpload = false"
              type="button"
              class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900"
              :disabled="isUploadingDocument"
            >
              Cancelar
            </button>
          </div>
        </div>
      </div>

      <!-- Dica sobre documentos -->
      <div
        v-if="documentCount > 0"
        class="bg-blue-50 border-l-4 border-blue-400 p-4"
      >
        <div class="flex">
          <div class="flex-shrink-0">
            <svg
              class="h-5 w-5 text-blue-400"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-blue-700">
              <strong
                >{{ documentCount }} documento(s) inserido(s) na
                notícia.</strong
              >
              Os leitores poderão fazer download dos arquivos clicando nos
              links.
            </p>
          </div>
        </div>
      </div>

      <!-- Instruções para usar o editor -->
      <div class="bg-gray-50 border-l-4 border-t-2 border-gray-400 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg
              class="h-5 w-5 text-gray-400"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-gray-700">
              <strong>Dicas do Editor:</strong>
            </p>
            <ul class="mt-2 text-sm text-gray-600 space-y-1">
              <li>• Use a seção acima para adicionar imagens ao carrossel</li>
              <li>
                • Use o botão "Inserir Documento" para adicionar arquivos (PDF,
                DOC, XLS, etc.)
              </li>
              <li>• Use Ctrl+K para inserir links rapidamente</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Mensagem de erro -->
    <p v-if="error" class="mt-2 text-sm text-red-600">
      {{ error }}
    </p>
  </div>
</template>

<style scoped>
/* Mantidos todos os estilos originais */
.content-editor-container {
  position: relative;
}

.editor-wrapper {
  transition: all 0.3s ease-in-out;
  background: white;
}

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

.editor-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

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
/* Estilos globais mantidos do original */
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

.editor-fullscreen .ck.ck-editor__editable {
  min-height: calc(100vh - 250px) !important;
  max-height: calc(100vh - 250px) !important;
}

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

.ck.ck-editor__editable.ck-placeholder::before {
  color: #9ca3af !important;
}

.ck.ck-toolbar {
  border-color: #e5e7eb !important;
  background: #f9fafb !important;
  padding: 8px !important;
}

.ck.ck-editor__editable:not(.ck-editor__nested-editable).ck-focused {
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

.ck.ck-editor__editable ul li,
.ck.ck-editor__editable ol li {
  color: #111827 !important;
}

.ck.ck-editor__editable table {
  color: #111827 !important;
}

.ck.ck-editor__editable table td,
.ck.ck-editor__editable table th {
  color: #111827 !important;
  border: 1px solid #d1d5db !important;
}

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
