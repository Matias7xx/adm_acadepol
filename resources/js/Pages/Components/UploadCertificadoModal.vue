<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import { 
  mdiUpload
} from '@mdi/js';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  matricula: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'success']);

// Estado do form
const form = useForm({
  certificado_pdf: null
});

// Estado local
const isDragOver = ref(false);
const fileInputRef = ref(null);
const uploadProgress = ref(0);

const hasFile = computed(() => form.certificado_pdf !== null);

const fileInfo = computed(() => {
  if (!form.certificado_pdf) return null;
  
  return {
    name: form.certificado_pdf.name,
    size: formatFileSize(form.certificado_pdf.size),
    type: form.certificado_pdf.type
  };
});

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (file && validateFile(file)) {
    form.certificado_pdf = file;
  }
};

const handleDrop = (event) => {
  event.preventDefault();
  isDragOver.value = false;
  
  const files = event.dataTransfer.files;
  if (files.length > 0 && validateFile(files[0])) {
    form.certificado_pdf = files[0];
  }
};

const handleDragOver = (event) => {
  event.preventDefault();
  isDragOver.value = true;
};

const handleDragLeave = () => {
  isDragOver.value = false;
};

const validateFile = (file) => {
  // Verificar tipo
  if (file.type !== 'application/pdf') {
    alert('Apenas arquivos PDF são permitidos.');
    return false;
  }
  
  // Verificar tamanho (10MB = 10 * 1024 * 1024 bytes)
  const maxSize = 10 * 1024 * 1024;
  if (file.size > maxSize) {
    alert('O arquivo não pode ser maior que 10MB.');
    return false;
  }
  
  return true;
};

const removeFile = () => {
  form.certificado_pdf = null;
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
};

const openFileDialog = () => {
  fileInputRef.value?.click();
};

const submitForm = () => {
  if (!form.certificado_pdf) {
    alert('Por favor, selecione um arquivo PDF.');
    return;
  }
  
  if (!props.matricula) {
    alert('Dados da matrícula não encontrados.');
    return;
  }
  
  form.post(route('admin.certificados.gerar', props.matricula.id), {
    onProgress: (progress) => {
      uploadProgress.value = progress.percentage;
    },
    onSuccess: () => {
      emit('success');
      closeModal();
    },
    onError: (errors) => {
      console.error('Erro ao enviar certificado:', errors);
    },
    onFinish: () => {
      uploadProgress.value = 0;
    }
  });
};

const closeModal = () => {
  form.reset();
  form.clearErrors();
  removeFile();
  uploadProgress.value = 0;
  emit('close');
};

// Watch para fechar modal quando a prop isOpen mudar
watch(() => props.isOpen, (newVal) => {
  if (!newVal) {
    closeModal();
  }
});
</script>

<template>
  <!-- Modal Overlay -->
  <div 
    v-if="isOpen" 
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
    @click.self="closeModal"
  >
    <!-- Modal Content -->
    <div class="relative mx-auto p-6 border w-[600px] max-w-[90vw] shadow-lg rounded-lg bg-white dark:bg-gray-800">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
          <svg class="h-8 w-8 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
          </svg>
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
              Upload de Certificado
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Insira o arquivo PDF do certificado
            </p>
          </div>
        </div>
        
        <button 
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
          </svg>
        </button>
      </div>

      <!-- Informações da Matrícula -->
      <div class="mb-6" v-if="matricula">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
          <div class="space-y-2 text-sm">
            <div class="flex items-center">
              <svg class="h-4 w-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
              </svg>
              <p><strong>Servidor:</strong> {{ matricula.aluno?.name }}</p>
            </div>
            <p class="ml-6"><strong>CPF:</strong> {{ matricula.aluno?.cpf }}</p>
            <p class="ml-6"><strong>Curso:</strong> {{ matricula.curso?.nome }}</p>
            <p class="ml-6"><strong>Carga Horária:</strong> {{ matricula.curso?.carga_horaria }}h</p>
          </div>
        </div>
      </div>

      <!-- Área de Upload -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Arquivo do Certificado (PDF)
        </label>
        
        <!-- Drop Zone -->
        <div 
          @drop="handleDrop"
          @dragover="handleDragOver"
          @dragleave="handleDragLeave"
          @click="openFileDialog"
          :class="[
            'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors',
            isDragOver 
              ? 'border-amber-400 bg-amber-50 dark:bg-amber-900/20' 
              : 'border-gray-300 dark:border-gray-600 hover:border-amber-400 hover:bg-gray-50 dark:hover:bg-gray-700'
          ]"
        >
          <!-- File Selected -->
          <div v-if="hasFile" class="space-y-3">
            <div class="flex items-center justify-center">
              <svg class="h-12 w-12 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0h8v12H6V4z" clip-rule="evenodd"></path>
              </svg>
            </div>
            
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ fileInfo.name }}</p>
              <p class="text-xs text-gray-500">{{ fileInfo.size }}</p>
            </div>
            
            <button 
              @click.stop="removeFile"
              class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40"
            >
              <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
              Remover
            </button>
          </div>
          
          <!-- No File Selected -->
          <div v-else class="space-y-3">
            <div class="flex items-center justify-center">
              <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
            </div>
            
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium text-amber-600 hover:text-amber-500">Clique para selecionar</span>
                ou arraste o arquivo aqui
              </p>
              <p class="text-xs text-gray-500">PDF até 10MB</p>
            </div>
          </div>
        </div>
        
        <!-- Input File Hidden -->
        <input
          ref="fileInputRef"
          type="file"
          accept=".pdf"
          @change="handleFileSelect"
          class="hidden"
        />
        
        <!-- Erro de Validação -->
        <p v-if="form.errors.certificado_pdf" class="mt-2 text-sm text-red-600">
          {{ form.errors.certificado_pdf }}
        </p>
      </div>

      <!-- Progress Bar -->
      <div v-if="form.processing" class="mb-6">
        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
          <span>Enviando certificado...</span>
          <span>{{ uploadProgress }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div 
            class="bg-amber-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: uploadProgress + '%' }"
          ></div>
        </div>
      </div>

      <!-- Instruções -->
      <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <div class="ml-3">
            <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
              Instruções importantes:
            </h4>
            <ul class="mt-2 text-xs text-yellow-700 dark:text-yellow-300 space-y-1">
              <li>• O arquivo deve ser um PDF válido</li>
              <li>• Tamanho máximo: 10MB</li>
              <li>• Certifique-se de que o certificado está preenchido corretamente</li>
              <li>• Uma vez enviado, o certificado ficará disponível para o servidor</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-4">
        <BaseButton 
          @click="closeModal" 
          label="Cancelar"
          color="white"
          :disabled="form.processing"
        />
        <BaseButton 
          @click="submitForm" 
          label="Enviar Certificado"
          :icon="mdiUpload"
          color="success"
          :disabled="!hasFile || form.processing"
          :loading="form.processing"
        />
      </div>
    </div>
  </div>
</template>