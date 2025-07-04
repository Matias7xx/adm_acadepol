<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import { ref, computed, watch } from 'vue'
import { useToast } from '@/Composables/useToast'
import {
  mdiAccountKey,
  mdiArrowLeftBoldOutline,
  mdiCertificate,
  mdiPlus,
  mdiDownload,
  mdiDelete,
  mdiEye,
  mdiUpload,
  mdiClose,
  mdiCalendar,
  mdiSchool,
  mdiClockOutline,
  mdiMapMarker,
  mdiWeb
} from "@mdi/js"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import BaseButton from "@/Components/BaseButton.vue"
import BaseButtons from "@/Components/BaseButtons.vue"
import FormField from "@/Components/FormField.vue"

const props = defineProps({
  user: {
    type: Object,
    default: () => ({}),
  },
  roles: {
    type: Object,
    default: () => ({}),
  },
  userHasRoles: {
    type: Object,
    default: () => ({}),
  },
  certificados: {
    type: Array,
    default: () => ([]),
  },
  cursos: {
    type: Array,
    default: () => ([]),
  }
})

// Toast para notificações
const { toast } = useToast()

// Estados dos modais
const showAddCertificadoModal = ref(false)
const showDeleteModal = ref(false)
const certificadoParaExcluir = ref(null)

// Formulário para adicionar certificado (com campos para certificados livres)
const addCertificadoForm = useForm({
  tipo_certificado: 'curso_sistema', // 'curso_sistema' ou 'curso_externo'
  curso_id: '',
  nome_curso_externo: '',
  carga_horaria: '',
  data_conclusao: '',
  certificado_pdf: null
})

// Estados do upload
const isDragOver = ref(false)
const fileInputRef = ref(null)

// Computed
const hasFile = computed(() => addCertificadoForm.certificado_pdf !== null)

// Computed para curso selecionado
const cursoSelecionado = computed(() => {
  if (!addCertificadoForm.curso_id) return null
  return props.cursos.find(curso => curso.id == addCertificadoForm.curso_id)
})

// Watch para auto-preencher campos quando selecionar curso do sistema
watch(() => addCertificadoForm.curso_id, (novoCursoId) => {
  
  if (addCertificadoForm.tipo_certificado === 'curso_sistema' && novoCursoId) {
    const curso = props.cursos.find(c => c.id == novoCursoId)
    
    if (curso && curso.carga_horaria) {
      addCertificadoForm.carga_horaria = curso.carga_horaria
    }
  }
}, { immediate: false })

// Watch para limpar campos quando mudar tipo de certificado
watch(() => addCertificadoForm.tipo_certificado, (novoTipo) => {
  
  // Limpar campos específicos quando mudar o tipo
  addCertificadoForm.curso_id = ''
  addCertificadoForm.nome_curso_externo = ''
  addCertificadoForm.carga_horaria = ''
  addCertificadoForm.data_conclusao = ''
  
  // Limpar erros também
  addCertificadoForm.clearErrors()
}, { immediate: false })

const fileInfo = computed(() => {
  if (!addCertificadoForm.certificado_pdf) return null
  
  return {
    name: addCertificadoForm.certificado_pdf.name,
    size: formatFileSize(addCertificadoForm.certificado_pdf.size),
    type: addCertificadoForm.certificado_pdf.type
  }
})

// Funções de formatação
const formatDate = (dateString) => {
  if (!dateString) return '-';
  
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-BR');
}

const formatCPF = (cpf) => {
  if (!cpf) return '-';
  
  const cleanCPF = cpf.replace(/\D/g, '');
  
  if (cleanCPF.length !== 11) return cpf;
  
  return cleanCPF.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

const formatPhone = (phone) => {
  if (!phone) return '-';
  
  const cleanPhone = phone.replace(/\D/g, '');
  
  if (cleanPhone.length === 11) {
    return cleanPhone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
  } else if (cleanPhone.length === 10) {
    return cleanPhone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
  }
  
  return phone;
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Função para obter badge de tipo de certificado
const getTipoBadge = (certificado) => {
  if (!certificado.tipo_origem) return { label: 'Regular', color: 'success' }
  
  const tipos = {
    'matricula': { label: 'Regular', color: 'success' },
    'curso_sistema': { label: 'Sistema', color: 'info' },
    'curso_externo': { label: 'Externo', color: 'warning' }
  }
  
  return tipos[certificado.tipo_origem] || { label: 'Regular', color: 'success' }
}

// Funções do modal de adicionar certificado
const abrirModalAddCertificado = () => {
  addCertificadoForm.reset()
  addCertificadoForm.clearErrors()
  addCertificadoForm.tipo_certificado = 'curso_sistema' // Reset para padrão
  showAddCertificadoModal.value = true
}

const fecharModalAddCertificado = () => {
  addCertificadoForm.reset()
  addCertificadoForm.clearErrors()
  removeFile()
  showAddCertificadoModal.value = false
}

// Funções de upload
const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file && validateFile(file)) {
    addCertificadoForm.certificado_pdf = file
  }
}

const handleDrop = (event) => {
  event.preventDefault()
  isDragOver.value = false
  
  const files = event.dataTransfer.files
  if (files.length > 0 && validateFile(files[0])) {
    addCertificadoForm.certificado_pdf = files[0]
  }
}

const handleDragOver = (event) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = () => {
  isDragOver.value = false
}

const validateFile = (file) => {
  if (file.type !== 'application/pdf') {
    toast.error('Apenas arquivos PDF são permitidos.')
    return false
  }
  
  const maxSize = 10 * 1024 * 1024
  if (file.size > maxSize) {
    toast.error('O arquivo não pode ser maior que 10MB.')
    return false
  }
  
  return true
}

const removeFile = () => {
  addCertificadoForm.certificado_pdf = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const openFileDialog = () => {
  fileInputRef.value?.click()
}

// Submeter formulário com validações para certificados livres
const adicionarCertificado = () => {
  // Validações específicas por tipo
  if (addCertificadoForm.tipo_certificado === 'curso_sistema' && !addCertificadoForm.curso_id) {
    toast.error('Por favor, selecione um curso do sistema.')
    return
  }
  
  if (addCertificadoForm.tipo_certificado === 'curso_externo' && !addCertificadoForm.nome_curso_externo) {
    toast.error('Por favor, digite o nome do curso externo.')
    return
  }
  
  if (!addCertificadoForm.carga_horaria) {
    toast.error('Por favor, informe a carga horária.')
    return
  }
  
  if (!addCertificadoForm.data_conclusao) {
    toast.error('Por favor, informe a data de conclusão.')
    return
  }
  
  if (!addCertificadoForm.certificado_pdf) {
    toast.error('Por favor, selecione um arquivo PDF.')
    return
  }
  
  addCertificadoForm.post(route('admin.certificados.adicionar.usuario', props.user.id), {
    onSuccess: () => {
      toast.success('Certificado adicionado com sucesso!')
      fecharModalAddCertificado()
    },
    onError: (errors) => {
      console.error('Erro ao adicionar certificado:', errors)
      toast.error('Erro ao adicionar certificado')
    }
  })
}

// Funções de exclusão
const confirmarExclusao = (certificado) => {
  certificadoParaExcluir.value = certificado
  showDeleteModal.value = true
}

const excluirCertificado = () => {
  if (!certificadoParaExcluir.value) return
  
  const form = useForm({})
  
  form.delete(route('admin.certificados.remover.usuario', [props.user.id, certificadoParaExcluir.value.id]), {
    onSuccess: () => {
      toast.success('Certificado excluído com sucesso!')
      showDeleteModal.value = false
      certificadoParaExcluir.value = null
    },
    onError: (errors) => {
      console.error('Erro ao excluir certificado:', errors)
      toast.error('Erro ao excluir certificado')
    }
  })
}

const cancelarExclusao = () => {
  showDeleteModal.value = false
  certificadoParaExcluir.value = null
}

const baixarCertificado = (certificado) => {
  // Usar a rota de certificados autenticada
  window.open(route('certificados.download', certificado.id), '_blank')
}
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Detalhes Usuário" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiAccountKey"
        title="Detalhes do Usuário"
        main
      >
        <BaseButton
          :route-name="route('admin.user.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>
      
      <!-- Informações do Usuário -->
      <CardBox class="mb-6">
        <table>
          <tbody>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Nome
              </td>
              <td data-label="Nome">
                {{ user.name }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                E-mail
              </td>
              <td data-label="E-mail">
                {{ user.email }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Matrícula
              </td>
              <td data-label="Matrícula">
                {{ user.matricula }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                CPF
              </td>
              <td data-label="CPF">
                {{ formatCPF(user.cpf) }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Cargo
              </td>
              <td data-label="Cargo">
                {{ user.cargo || '-' }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Órgão
              </td>
              <td data-label="Órgão">
                {{ user.orgao || '-' }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Lotação
              </td>
              <td data-label="Lotação">
                {{ user.lotacao || '-' }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Telefone
              </td>
              <td data-label="Telefone">
                {{ formatPhone(user.telefone) }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Data de Nascimento
              </td>
              <td data-label="Data de Nascimento">
                {{ formatDate(user.data_nascimento) }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Funções
              </td>
              <td data-label="Funções">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="role in userHasRoles" 
                    :key="role"
                    class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300"
                  >
                    {{ role }}
                  </span>
                </div>
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Criado em
              </td>
              <td data-label="Criado em">
                {{ formatDate(user.created_at) }}
              </td>
            </tr>
            <tr>
              <td class="p-4 pl-8 text-slate-500 dark:text-slate-400 hidden lg:block">
                Atualizado em
              </td>
              <td data-label="Atualizado em">
                {{ formatDate(user.updated_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </CardBox>

      <!-- Seção de Certificados -->
      <CardBox>
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center">
            <svg class="h-6 w-6 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path :d="mdiCertificate" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              Certificados ({{ certificados.length }})
            </h2>
          </div>
          
          <BaseButton
            @click="abrirModalAddCertificado"
            :icon="mdiPlus"
            label="Adicionar Certificado"
            color="success"
            small
          />
        </div>

        <!-- Lista de Certificados -->
        <div v-if="certificados.length > 0" class="space-y-4">
          <div 
            v-for="certificado in certificados" 
            :key="certificado.id"
            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <div class="flex items-center mb-2">
                  <svg class="h-5 w-5 text-amber-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path :d="mdiSchool"></path>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ certificado.nome_curso }}
                  </h3>
                  
                  <!-- Badge do tipo de certificado -->
                  <span 
                    :class="[
                      'ml-2 px-2 py-1 text-xs font-medium rounded-full',
                      {
                        'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': getTipoBadge(certificado).color === 'success',
                        'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400': getTipoBadge(certificado).color === 'info',
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': getTipoBadge(certificado).color === 'warning'
                      }
                    ]"
                  >
                    {{ getTipoBadge(certificado).label }}
                  </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600 dark:text-gray-400">
                  <div class="flex items-center">
                    <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path :d="mdiCertificate"></path>
                    </svg>
                    <span>{{ certificado.numero_certificado }}</span>
                  </div>
                  
                  <div class="flex items-center">
                    <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path :d="mdiCalendar"></path>
                    </svg>
                    <span>{{ formatDate(certificado.data_emissao) }}</span>
                  </div>
                  
                  <div class="flex items-center">
                    <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path :d="mdiClockOutline"></path>
                    </svg>
                    <span>{{ certificado.carga_horaria }}h</span>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center space-x-2 ml-4">
                <BaseButton
                  @click="baixarCertificado(certificado)"
                  :icon="mdiDownload"
                  small
                  color="info"
                  outline
                  title="Baixar Certificado"
                />
                
                <BaseButton
                  @click="confirmarExclusao(certificado)"
                  :icon="mdiDelete"
                  small
                  color="danger"
                  outline
                  title="Excluir Certificado"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Estado vazio -->
        <div v-else class="text-center py-8">
          <svg class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path :d="mdiCertificate" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"></path>
          </svg>
          <p class="text-gray-500 dark:text-gray-400 mb-4">
            Este usuário ainda não possui certificados
          </p>
          <BaseButton
            @click="abrirModalAddCertificado"
            :icon="mdiPlus"
            label="Adicionar Primeiro Certificado"
            color="success"
          />
        </div>
      </CardBox>

      <!-- Modal Adicionar Certificado -->
      <div 
        v-if="showAddCertificadoModal" 
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
        @click.self="fecharModalAddCertificado"
      >
        <div class="relative mx-auto p-6 border w-[700px] max-w-[90vw] shadow-lg rounded-lg bg-white dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
              <svg class="h-8 w-8 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path :d="mdiUpload" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
              <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                  Adicionar Certificado
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Para: {{ user.name }}
                </p>
              </div>
            </div>
            
            <button 
              @click="fecharModalAddCertificado"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path :d="mdiClose" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
            </button>
          </div>

          <!-- Tipo de Certificado -->
          <div class="mb-6">
            <FormField label="Tipo de Certificado" required>
              <div class="flex space-x-6">
                <label class="flex items-center">
                  <input 
                    type="radio" 
                    v-model="addCertificadoForm.tipo_certificado" 
                    value="curso_sistema"
                    class="mr-2 text-blue-600"
                  />
                  <div class="flex items-center">
                    <svg class="h-4 w-4 mr-1 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                      <path :d="mdiSchool"></path>
                    </svg>
                    <span>Curso do Sistema</span>
                  </div>
                </label>
                <label class="flex items-center">
                  <input 
                    type="radio" 
                    v-model="addCertificadoForm.tipo_certificado" 
                    value="curso_externo"
                    class="mr-2 text-blue-600"
                  />
                  <div class="flex items-center">
                    <svg class="h-4 w-4 mr-1 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                      <path :d="mdiWeb"></path>
                    </svg>
                    <span>Curso Externo</span>
                  </div>
                </label>
              </div>
            </FormField>
          </div>

          <!-- Seleção do Curso (se curso do sistema) -->
          <div v-if="addCertificadoForm.tipo_certificado === 'curso_sistema'" class="mb-6">
            <FormField label="Curso do Sistema" required>
              <select
                v-model="addCertificadoForm.curso_id"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                required
              >
                <option value="">Selecione um curso</option>
                <option 
                  v-for="curso in cursos" 
                  :key="curso.id" 
                  :value="curso.id"
                >
                  {{ curso.nome }} ({{ curso.carga_horaria }}h)
                </option>
              </select>
            </FormField>
            <p v-if="addCertificadoForm.errors.curso_id" class="mt-1 text-sm text-red-600">
              {{ addCertificadoForm.errors.curso_id }}
            </p>
          </div>

          <!-- Nome do Curso Externo -->
          <div v-if="addCertificadoForm.tipo_certificado === 'curso_externo'" class="mb-6">
            <FormField label="Nome do Curso Externo" required>
              <input
                v-model="addCertificadoForm.nome_curso_externo"
                type="text"
                placeholder="Ex: Curso de Segurança Pública - ENASP"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                required
              />
            </FormField>
            <p v-if="addCertificadoForm.errors.nome_curso_externo" class="mt-1 text-sm text-red-600">
              {{ addCertificadoForm.errors.nome_curso_externo }}
            </p>
          </div>

          <!-- Carga Horária -->
          <div class="mb-6">
            <FormField label="Carga Horária (horas)" required>
              <input
                v-model="addCertificadoForm.carga_horaria"
                type="number"
                min="1"
                max="2000"
                placeholder="Ex: 40"
                :disabled="addCertificadoForm.tipo_certificado === 'curso_sistema' && cursoSelecionado"
                :class="[
                  'w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white',
                  {
                    'bg-gray-100 dark:bg-gray-700 cursor-not-allowed': addCertificadoForm.tipo_certificado === 'curso_sistema' && cursoSelecionado
                  }
                ]"
                required
              />
              <p v-if="addCertificadoForm.tipo_certificado === 'curso_sistema' && cursoSelecionado" class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                📋 Carga horária preenchida automaticamente do curso selecionado
              </p>
            </FormField>
            <p v-if="addCertificadoForm.errors.carga_horaria" class="mt-1 text-sm text-red-600">
              {{ addCertificadoForm.errors.carga_horaria }}
            </p>
          </div>

          <!-- Data de Conclusão -->
          <div class="mb-6">
            <FormField label="Data de Conclusão" required>
              <input
                v-model="addCertificadoForm.data_conclusao"
                type="date"
                :max="new Date().toISOString().split('T')[0]"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                required
              />
            </FormField>
            <p v-if="addCertificadoForm.errors.data_conclusao" class="mt-1 text-sm text-red-600">
              {{ addCertificadoForm.errors.data_conclusao }}
            </p>
          </div>

          <!-- Área de Upload -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Arquivo do Certificado (PDF) *
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
                    <path :d="mdiClose" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                  </svg>
                  Remover
                </button>
              </div>
              
              <!-- No File Selected -->
              <div v-else class="space-y-3">
                <div class="flex items-center justify-center">
                  <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path :d="mdiUpload" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
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
            <p v-if="addCertificadoForm.errors.certificado_pdf" class="mt-2 text-sm text-red-600">
              {{ addCertificadoForm.errors.certificado_pdf }}
            </p>
          </div>

          <!-- Barra de Progresso -->
          <div v-if="addCertificadoForm.processing" class="mb-6">
            <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
              <span>Enviando certificado...</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-amber-600 h-2 rounded-full transition-all duration-300 animate-pulse w-full"></div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-4">
            <BaseButton 
              @click="fecharModalAddCertificado" 
              label="Cancelar"
              color="white"
              :disabled="addCertificadoForm.processing"
            />
            <BaseButton 
              @click="adicionarCertificado" 
              label="Adicionar Certificado"
              :icon="mdiPlus"
              color="success"
              :disabled="addCertificadoForm.processing"
              :loading="addCertificadoForm.processing"
            />
          </div>
        </div>
      </div>

      <!-- Modal de Confirmação de Exclusão -->
      <div 
        v-if="showDeleteModal" 
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
      >
        <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
          <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 mb-4">
              <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path :d="mdiDelete" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-2">
              Excluir Certificado
            </h3>
            <div class="mt-2 px-7 py-3">
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Tem certeza que deseja excluir o certificado 
                <strong>{{ certificadoParaExcluir?.numero_certificado }}</strong> 
                do curso <strong>{{ certificadoParaExcluir?.nome_curso }}</strong>?
              </p>
              <div class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <div class="flex items-center">
                  <svg class="h-4 w-4 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-xs text-yellow-800 dark:text-yellow-200">
                    Esta ação não pode ser desfeita!
                  </span>
                </div>
              </div>
            </div>
            <div class="flex justify-center gap-4 mt-4">
              <BaseButton 
                @click="excluirCertificado" 
                label="Sim, Excluir"
                color="danger"
              />
              <BaseButton 
                @click="cancelarExclusao" 
                label="Cancelar"
                color="white"
              />
            </div>
          </div>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<style scoped>
.modal-overlay {
  backdrop-filter: blur(4px);
}

/* Animações para modais */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

/* Hover states */
.certificate-card {
  transition: all 0.2s ease-in-out;
}

.certificate-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Loading spinner */
.loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Drag and drop visual feedback */
.drop-zone {
  transition: all 0.2s ease-in-out;
}

.drop-zone.drag-over {
  border-color: #f59e0b;
  background-color: rgba(245, 158, 11, 0.05);
  transform: scale(1.02);
}

/* Badge animations */
.badge {
  transition: all 0.2s ease-in-out;
}

.badge:hover {
  transform: scale(1.05);
}

/* Button hover improvements */
.btn-hover {
  transition: all 0.2s ease-in-out;
}

.btn-hover:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Form field focus states */
.form-input:focus {
  transform: scale(1.01);
  transition: transform 0.2s ease-in-out;
}

/* Certificate number styling */
.certificate-number {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.875rem;
  letter-spacing: 0.05em;
}

/* Progress bar animation */
.progress-bar {
  background: linear-gradient(45deg, #f59e0b, #d97706);
  background-size: 200% 100%;
  animation: gradient-shift 2s ease-in-out infinite;
}

@keyframes gradient-shift {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Dark mode specific adjustments */
@media (prefers-color-scheme: dark) {
  .certificate-card:hover {
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
  }
  
  .drop-zone.drag-over {
    background-color: rgba(245, 158, 11, 0.1);
  }
}

/* Mobile responsiveness improvements */
@media (max-width: 768px) {
  .modal-container {
    margin: 1rem;
    max-height: calc(100vh - 2rem);
  }
  
  .certificate-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 0.5rem;
  }
}

/* Accessibility improvements */
.focus-visible {
  outline: 2px solid #f59e0b;
  outline-offset: 2px;
}

/* File upload area specific styling */
.upload-area {
  background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
  border: 2px dashed #d1d5db;
  transition: all 0.3s ease;
}

.upload-area:hover {
  border-color: #f59e0b;
  background: linear-gradient(135deg, #fef3e2 0%, #fef2e0 100%);
}

.upload-area.drag-active {
  border-color: #f59e0b;
  background: linear-gradient(135deg, #fef3e2 0%, #fef2e0 100%);
  transform: scale(1.02);
}

/* Success/Error message styling */
.message-success {
  background: linear-gradient(90deg, #dcfce7 0%, #bbf7d0 100%);
  border-left: 4px solid #16a34a;
}

.message-error {
  background: linear-gradient(90deg, #fef2f2 0%, #fecaca 100%);
  border-left: 4px solid #dc2626;
}
</style>