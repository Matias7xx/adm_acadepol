<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import {
  mdiArrowLeft,
  mdiContentSave,
  mdiAccount,
  mdiAlertBoxOutline,
  mdiPlus
} from "@mdi/js";
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue";
import SectionMain from "@/Components/SectionMain.vue";
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue";
import CardBox from "@/Components/CardBox.vue";
import BaseButton from "@/Components/BaseButton.vue";
import FormField from "@/Components/FormField.vue";
import FormControl from "@/Components/FormControl.vue";
import BaseDivider from "@/Components/BaseDivider.vue";
import NotificationBar from "@/Components/NotificationBar.vue";
import { ref, computed, watch } from 'vue';

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

// Inicializar realizações
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
  imagem: props.diretor.imagem || '',
  imagem_file: null,
});

// Estados para gerenciamento da interface
const novaRealizacao = ref('')
const imagePreview = ref(null)
const dragOver = ref(false)
const hasChanges = ref(false)

// Computed para verificar se pode salvar
const canSave = computed(() => {
  return form.nome.trim() && form.data_inicio && hasChanges.value && !form.processing
})

// Watch para detectar mudanças e limpar data fim quando atual = true
watch(() => form.atual, (novoValor) => {
  if (novoValor) {
    form.data_fim = ''
  }
  hasChanges.value = true
})

watch([
  () => form.nome,
  () => form.data_inicio,
  () => form.data_fim,
  () => form.historico,
  () => form.realizacoes,
  () => form.imagem_file
], () => {
  hasChanges.value = true
}, { deep: true })

// Funções para gerenciar realizações
const adicionarRealizacao = () => {
  const realizacao = novaRealizacao.value.trim()
  if (realizacao && !form.realizacoes.includes(realizacao)) {
    form.realizacoes.push(realizacao)
    novaRealizacao.value = ''
    hasChanges.value = true
  }
}

const removerRealizacao = (index) => {
  form.realizacoes.splice(index, 1)
  hasChanges.value = true
}

const adicionarRealizacaoEnter = (event) => {
  if (event.key === 'Enter') {
    event.preventDefault()
    adicionarRealizacao()
  }
}

// Funções para upload de imagem
const handleImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    processImageFile(file)
  }
}

const handleImageDrop = (event) => {
  event.preventDefault()
  dragOver.value = false
  
  const file = event.dataTransfer.files[0]
  if (file && file.type.startsWith('image/')) {
    processImageFile(file)
  }
}

const processImageFile = (file) => {
  // Validar tamanho (máx 2MB)
  if (file.size > 2 * 1024 * 1024) {
    alert('A imagem deve ter no máximo 2MB')
    return
  }
  
  form.imagem_file = file
  hasChanges.value = true
  
  // Criar preview
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

const removeNewImage = () => {
  form.imagem_file = null
  imagePreview.value = null
  hasChanges.value = true
  // Reset input file
  const fileInput = document.querySelector('input[type="file"]')
  if (fileInput) fileInput.value = ''
}

// Drag and drop handlers
const handleDragOver = (event) => {
  event.preventDefault()
  dragOver.value = true
}

const handleDragLeave = () => {
  dragOver.value = false
}

const submit = () => {
  if (!canSave.value) return
  
  form.transform((data) => ({
    ...data,
    _method: 'PUT',
  })).post(route('admin.directors.update', props.diretor.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      hasChanges.value = false
    },
  })
}

// Computed para URL da imagem atual
const currentImageUrl = computed(() => {
  return imagePreview.value || props.diretor.imagem
})
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

      <!-- Badge de mudanças -->
      <div v-if="hasChanges" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center gap-2">
        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <span class="text-sm text-yellow-800">Você possui alterações não salvas</span>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Coluna principal - Informações básicas -->
        <div class="lg:col-span-2">
          <CardBox class="mb-6">
            <div class="space-y-6">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Básicas</h3>
                
                <FormField
                  label="Nome do Diretor"
                  :error="errors.nome"
                >
                  <FormControl
                    v-model="form.nome"
                    type="text"
                    placeholder="Nome completo do diretor"
                    :error="errors.nome"
                  />
                </FormField>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <FormField
                    label="Data de Início"
                    :error="errors.data_inicio"
                  >
                    <FormControl
                      v-model="form.data_inicio"
                      type="date"
                      :error="errors.data_inicio"
                    />
                  </FormField>

                  <FormField
                    label="Data de Término"
                    :error="errors.data_fim"
                  >
                    <FormControl
                      v-model="form.data_fim"
                      type="date"
                      :error="errors.data_fim"
                      :disabled="form.atual"
                      :class="{ 'opacity-50': form.atual }"
                    />
                  </FormField>
                </div>
                
                <FormField label="Status" :error="errors.atual">
                  <div class="flex items-center space-x-3 p-3 rounded-lg">
                    <FormControl
                      v-model="form.atual"
                      type="checkbox"
                      :error="errors.atual"
                    />
                    <label class="text-sm font-medium text-gray-700 cursor-pointer" @click="form.atual = !form.atual">
                      É o diretor atual
                    </label>
                    <div v-if="form.atual" class="flex items-center text-green-600 text-sm">
                      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                      Diretor atual
                    </div>
                  </div>
                </FormField>
              </div>

              <BaseDivider />

              <!-- Histórico -->
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Histórico Profissional</h3>
                
                <FormField
                  label="Biografia/Histórico"
                  :error="errors.historico"
                >
                  <FormControl
                    v-model="form.historico"
                    type="textarea"
                    placeholder="Breve histórico ou biografia do diretor..."
                    :error="errors.historico"
                    rows="4"
                  />
                </FormField>
              </div>

              <BaseDivider />

              <!-- Realizações -->
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Principais Realizações</h3>
                
                <div class="space-y-3">
                  <div class="flex gap-2">
                    <FormControl
                      v-model="novaRealizacao"
                      placeholder="Digite uma realização e pressione Enter..."
                      class="flex-1"
                      @keydown="adicionarRealizacaoEnter"
                    />
                    <BaseButton
                      type="button"
                      color="info"
                      :icon="mdiPlus"
                      @click="adicionarRealizacao"
                      :disabled="!novaRealizacao.trim()"
                    />
                  </div>
                  
                  <div v-if="form.realizacoes.length > 0" class="space-y-2">
                    <div 
                      v-for="(realizacao, index) in form.realizacoes" 
                      :key="index"
                      class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg group hover:bg-blue-100 transition-colors"
                    >
                      <div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></div>
                      <span class="flex-1 text-sm text-gray-700">{{ realizacao }}</span>
                      <button 
                        type="button" 
                        @click="removerRealizacao(index)"
                        class="p-1 text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity"
                        :title="`Remover '${realizacao}'`"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                  
                  <div v-else class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <p class="text-sm">Nenhuma realização adicionada</p>
                    <p class="text-xs text-gray-400 mt-1">Digite no campo acima para adicionar</p>
                  </div>
                  
                  <div v-if="errors.realizacoes" class="text-red-400 text-sm">
                    {{ errors.realizacoes }}
                  </div>
                </div>
              </div>
            </div>
          </CardBox>
        </div>

        <!-- Sidebar - Upload de imagem -->
        <div class="lg:col-span-1">
          <CardBox>
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900">Imagem do Diretor</h3>
              
              <!-- Preview da imagem atual ou nova -->
              <div v-if="currentImageUrl" class="relative">
                <img 
                  :src="currentImageUrl" 
                  alt="Imagem do diretor" 
                  class="w-full h-48 object-cover rounded-lg shadow-sm"
                />
                <div v-if="imagePreview" class="absolute top-2 left-2">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Nova imagem
                  </span>
                </div>
                <button
                  v-if="imagePreview"
                  type="button"
                  @click="removeNewImage"
                  class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                  title="Cancelar nova imagem"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <!-- Área de upload -->
              <div 
                @drop="handleImageDrop"
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                :class="[
                  'border-2 border-dashed rounded-lg p-6 text-center transition-all cursor-pointer hover:border-blue-400 hover:bg-blue-50',
                  dragOver ? 'border-blue-400 bg-blue-50' : 'border-gray-300'
                ]"
                @click="$refs.fileInput.click()"
              >
                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-sm text-gray-600 mb-1">
                  {{ currentImageUrl ? 'Alterar imagem' : 'Adicionar imagem' }}
                </p>
                <p class="text-xs text-gray-400">PNG, JPG até 2MB</p>
              </div>
              
              <input
                ref="fileInput"
                type="file"
                accept="image/*"
                @change="handleImageUpload"
                class="hidden"
              />
              
              <div v-if="errors.imagem_file" class="text-red-400 text-sm">
                {{ errors.imagem_file }}
              </div>
            </div>
          </CardBox>

          <!-- Botões de ação -->
          <div class="mt-6 space-y-3">
            <BaseButton
              type="button"
              color="info"
              :icon="mdiContentSave"
              label="Salvar Alterações"
              class="w-full"
              :class="{ 'opacity-50': !canSave }"
              :disabled="!canSave"
              @click="submit"
            />
            <BaseButton
              :route-name="route('admin.directors.index')"
              label="Cancelar"
              color="light"
              class="w-full"
              :disabled="form.processing"
            />
          </div>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* styles para UX */
.group:hover .group-hover\:opacity-100 {
  opacity: 1;
}

/* Animação para mudanças de estado */
.transition-colors {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Estados de foco */
.focus\:ring-2:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}
</style>