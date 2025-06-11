<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Link, router, useForm, Head } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'
import Header from '../Components/Header.vue'
import SiteNavbar from '../Components/SiteNavbar.vue'
import Footer from '../Components/Footer.vue'

// Props
const props = defineProps({
  tiposOrgao: {
    type: Object,
    default: () => ({})
  },
  condicoes: {
    type: Object,
    default: () => ({})
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Toast notification
const { toast } = useToast()

// Estados reativos
const loading = ref(false)
const buscandoCpf = ref(false)
const cpfBusca = ref('')
const mensagemBusca = ref('')
const dadosEncontrados = ref(false)
const termoVisivel = ref(false)
const isSubmitting = ref(false)

// Lista de estados brasileiros
const estadosBrasileiros = ref([
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 
  'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
])

const form = useForm({
  nome: '',
  cpf: '',
  rg: '',
  orgao_expedidor_rg: '',
  data_nascimento: '',
  sexo: '',
  telefone: '',
  email: '',
  endereco: {
    rua: '',
    numero: '',
    bairro: '',
    cidade: '',
    uf: '',
    cep: ''
  },
  orgao_trabalho: '',
  cargo: '',
  matricula_funcional: '',
  tipo_orgao: '',
  data_inicial: '',
  data_final: '',
  motivo: '',
  condicao: '',
  aceita_termos: false,
  documento_identidade: null,
  documento_funcional: null,
  documento_comprobatorio: null
})

const hoje = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const dataMinimaSaida = computed(() => {
  return form.data_inicial || hoje.value
})

// Função para formatação de CPF
const formatarCpf = (valor) => {
  return valor.replace(/\D/g, '')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d{1,2})/, '$1-$2')
    .replace(/(-\d{2})\d+?$/, '$1')
}

// Função para formatação de telefone
const formatarTelefone = (valor) => {
  return valor.replace(/\D/g, '')
    .replace(/(\d{2})(\d)/, '($1) $2')
    .replace(/(\d{4})(\d)/, '$1-$2')
    .replace(/(\d{4})-(\d)(\d{4})/, '$1$2-$3')
    .replace(/(-\d{4})\d+?$/, '$1')
}

// Função para formatação de CEP
const formatarCep = (valor) => {
  return valor.replace(/\D/g, '')
    .replace(/(\d{5})(\d)/, '$1-$2')
    .replace(/(-\d{3})\d+?$/, '$1')
}

const buscarPorCpf = async () => {
  if (!cpfBusca.value) return
  
  buscandoCpf.value = true
  mensagemBusca.value = ''
  dadosEncontrados.value = false
  
  try {
    const response = await fetch('/api/visitante/buscar-cpf', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ cpf: cpfBusca.value })
    })
    
    const data = await response.json()
    
    if (data.success) {
      // Preencher formulário com dados encontrados
      const visitante = data.visitante
      
      // Atualizar campos individuais  
      form.nome = visitante.nome || ''
      form.cpf = cpfBusca.value
      form.rg = visitante.rg || ''
      form.orgao_expedidor_rg = visitante.orgao_expedidor_rg || ''
      form.data_nascimento = visitante.data_nascimento || ''
      form.sexo = visitante.sexo || ''
      form.telefone = visitante.telefone || ''
      form.email = visitante.email || ''
      
      if (visitante.endereco) {
        Object.assign(form.endereco, {
          rua: visitante.endereco.rua || '',
          numero: visitante.endereco.numero || '',
          bairro: visitante.endereco.bairro || '',
          cidade: visitante.endereco.cidade || '',
          uf: visitante.endereco.uf || '',
          cep: visitante.endereco.cep || ''
        })
      }
      
      form.orgao_trabalho = visitante.orgao_trabalho || ''
      form.cargo = visitante.cargo || ''
      form.matricula_funcional = visitante.matricula_funcional || ''
      form.tipo_orgao = visitante.tipo_orgao || ''
      
      mensagemBusca.value = 'Dados encontrados e preenchidos automaticamente!'
      dadosEncontrados.value = true
      toast.success('Dados encontrados e preenchidos automaticamente!')
    } else {
      mensagemBusca.value = 'CPF não encontrado. Preencha o formulário manualmente.'
      form.cpf = cpfBusca.value
      toast.info('CPF não encontrado. Preencha o formulário manualmente.')
    }
  } catch (error) {
    console.error('Erro ao buscar CPF:', error)
    mensagemBusca.value = 'Erro ao buscar dados. Preencha o formulário manualmente.'
    form.cpf = cpfBusca.value
    toast.error('Erro ao buscar dados. Preencha o formulário manualmente.')
  } finally {
    buscandoCpf.value = false
  }
}

const handleFileChange = (field, event) => {
  const file = event.target.files[0]
  if (file) {
    // Validar tamanho do arquivo (5MB)
    if (file.size > 5 * 1024 * 1024) {
      toast.error('Arquivo muito grande. O tamanho máximo é 5MB.')
      event.target.value = ''
      return
    }
    
    // Validar tipo do arquivo
    const allowedTypes = ['application/pdf']
    if (!allowedTypes.includes(file.type)) {
      toast.error('Tipo de arquivo não permitido. Use PDF.')
      event.target.value = ''
      return
    }
    
    form[field] = file
  }
}

// Handlers para formatação  
const handleCpfInput = (event) => {
  const formatted = formatarCpf(event.target.value)
  form.cpf = formatted
}

const handleTelefoneInput = (event) => {
  const formatted = formatarTelefone(event.target.value)
  form.telefone = formatted
}

const handleCepInput = (event) => {
  const formatted = formatarCep(event.target.value)
  form.endereco.cep = formatted
}

// Validar se as datas são válidas
const validarDatas = () => {
  if (!form.data_inicial || !form.data_final) {
    return false
  }
  
  const dataInicial = new Date(form.data_inicial)
  const dataFinal = new Date(form.data_final)
  const hoje = new Date()
  
  hoje.setHours(0, 0, 0, 0)
  
  if (dataInicial < hoje) {
    toast.error('A data inicial não pode ser anterior à data atual')
    return false
  }
  
  if (dataFinal < dataInicial) {
    toast.error('A data final deve ser igual ou posterior à data inicial')
    return false
  }
  
  return true
}

const submeterFormulario = () => {
  // Validar aceite de termos
  if (!form.aceita_termos) {
    toast.error('Você precisa aceitar os termos para continuar')
    return
  }
  
  // Validar datas
  if (!validarDatas()) {
    return
  }
  
  // Validar campos obrigatórios
  const camposObrigatorios = [
    'nome', 'cpf', 'rg', 'orgao_expedidor_rg', 'data_nascimento', 'sexo',
    'telefone', 'email', 'orgao_trabalho', 'cargo', 'tipo_orgao',
    'data_inicial', 'data_final', 'motivo', 'condicao'
  ]
  
  for (const campo of camposObrigatorios) {
    if (!form[campo]) {
      toast.error(`Por favor, preencha o campo ${campo.replace('_', ' ')}`)
      return
    }
  }
  
  // Verificar se documento de identidade foi enviado
  if (!form.documento_identidade) {
    toast.error('Por favor, anexe o documento de identidade')
    return
  }
  
  isSubmitting.value = true
  
  form.post(route('visitante.store'), {
    preserveScroll: false,
    forceFormData: true, //  enviar arquivos
    onSuccess: () => {
      isSubmitting.value = false
    },
    onError: (errors) => {
      isSubmitting.value = false
      console.error('Erro na validação:', errors)
      if (errors.message) {
        toast.error(errors.message)
      } else {
        toast.error('Ocorreu um erro ao enviar a solicitação. Por favor, tente novamente.')
      }
    }
  })
}

// Exibir/ocultar os termos
const toggleTermos = () => {
  termoVisivel.value = !termoVisivel.value
}

onMounted(() => {
  document.getElementById('cpf_busca')?.focus()
})
</script>

<template>
  <Head title="Reserva de Alojamento - Visitantes"/>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
    <Header />
    <SiteNavbar />
    
    <!-- Cabeçalho -->
    <div class="bg-gradient-to-r from-black to-gray-900 text-white py-5 shadow-md">
      <div class="container mx-auto flex justify-between items-center px-4">
        <div class="flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <h1 class="text-2xl font-bold">Formulário de Pré-Reserva de Alojamento</h1>
        </div>
        <Link href="/alojamento/tipo-usuario" class="flex items-center text-amber-400 hover:text-amber-300 transition">
          <span>Voltar</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </Link>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container mx-auto py-8 px-4">
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border border-gray-200">
        <div class="flex items-center justify-between mb-6 pb-3 border-b border-amber-200">
          <div class="flex items-center">
            <div class="bg-amber-100 p-3 rounded-full mr-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Pré-Reserva de Alojamento</h2>
          </div>
          <div class="text-sm text-gray-500">* Campos obrigatórios</div>
        </div>
        
        <!-- Aviso Importante -->
        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-amber-500 p-4 mb-6 shadow-sm">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-amber-800">Informações Importantes</h3>
              <p class="mt-1 text-sm text-amber-700">
                A ACADEPOL <strong>NÃO FORNECE</strong> materiais de higiene pessoal, lençóis e toalhas. Os quartos são compartilhados (beliches) e separados por sexo.
              </p>
            </div>
          </div>
        </div>
        
        <form @submit.prevent="submeterFormulario" class="space-y-8">
          <!--  Buscar por CPF -->
          <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Verificação de Dados Existentes
            </h3>
            <p class="text-sm text-gray-600 mb-4">
              Se você já fez uma reserva anteriormente, digite seu CPF para pré-preencher os dados:
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <label for="cpf_busca" class="block text-sm font-medium text-gray-700 mb-1">
                  CPF para busca
                </label>
                <input
                  id="cpf_busca"
                  v-model="cpfBusca"
                  type="text"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  placeholder="000.000.000-00"
                  maxlength="14"
                  @input="cpfBusca = formatarCpf($event.target.value)"
                />
              </div>
              <div class="flex items-end">
                <button
                  type="button"
                  @click="buscarPorCpf"
                  :disabled="buscandoCpf || !cpfBusca"
                  class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-2 px-4 rounded-md font-medium transition-all duration-300 shadow hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                >
                  <svg v-if="buscandoCpf" class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ buscandoCpf ? 'Buscando...' : 'Buscar' }}
                </button>
              </div>
            </div>

            <!-- Mensagem de resultado da busca -->
            <div v-if="mensagemBusca" class="mt-3 p-3 rounded-md" :class="{
              'bg-green-50 text-green-800 border border-green-200': dadosEncontrados,
              'bg-yellow-50 text-yellow-800 border border-yellow-200': !dadosEncontrados
            }">
              {{ mensagemBusca }}
            </div>
          </div>

          <!--  Dados Pessoais -->
          <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
              </svg>
              Dados Pessoais
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Nome Completo -->
              <div class="md:col-span-2">
                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">
                  Nome Completo *
                </label>
                <input
                  id="nome"
                  v-model="form.nome"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.nome }"
                />
                <p v-if="form.errors?.nome" class="mt-1 text-sm text-red-600">{{ form.errors.nome }}</p>
              </div>

              <!-- CPF -->
              <div>
                <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">
                  CPF *
                </label>
                <input
                  id="cpf"
                  v-model="form.cpf"
                  type="text"
                  required
                  maxlength="14"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.cpf }"
                  @input="handleCpfInput"
                />
                <p v-if="form.errors?.cpf" class="mt-1 text-sm text-red-600">{{ form.errors.cpf }}</p>
              </div>

              <!-- RG -->
              <div>
                <label for="rg" class="block text-sm font-medium text-gray-700 mb-1">
                  RG/CNH *
                </label>
                <input
                  id="rg"
                  v-model="form.rg"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.rg }"
                />
                <p v-if="form.errors?.rg" class="mt-1 text-sm text-red-600">{{ form.errors.rg }}</p>
              </div>

              <!-- Órgão Expedidor -->
              <div>
                <label for="orgao_expedidor_rg" class="block text-sm font-medium text-gray-700 mb-1">
                  Órgão Expedidor *
                </label>
                <input
                  id="orgao_expedidor_rg"
                  v-model="form.orgao_expedidor_rg"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.orgao_expedidor_rg }"
                  placeholder="Ex: SSP-PB"
                />
                <p v-if="form.errors?.orgao_expedidor_rg" class="mt-1 text-sm text-red-600">{{ form.errors.orgao_expedidor_rg }}</p>
              </div>

              <!-- Data de Nascimento -->
              <div>
                <label for="data_nascimento" class="block text-sm font-medium text-gray-700 mb-1">
                  Data de Nascimento *
                </label>
                <input
                  id="data_nascimento"
                  v-model="form.data_nascimento"
                  type="date"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.data_nascimento }"
                />
                <p v-if="form.errors?.data_nascimento" class="mt-1 text-sm text-red-600">{{ form.errors.data_nascimento }}</p>
              </div>

              <!-- Sexo -->
              <div>
                <label for="sexo" class="block text-sm font-medium text-gray-700 mb-1">
                  Sexo *
                </label>
                <select
                  id="sexo"
                  v-model="form.sexo"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.sexo }"
                >
                  <option value="">Selecione...</option>
                  <option value="masculino">Masculino</option>
                  <option value="feminino">Feminino</option>
                </select>
                <p v-if="form.errors?.sexo" class="mt-1 text-sm text-red-600">{{ form.errors.sexo }}</p>
              </div>

              <!-- Telefone -->
              <div>
                <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">
                  Telefone *
                </label>
                <input
                  id="telefone"
                  v-model="form.telefone"
                  type="text"
                  required
                  maxlength="15"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.telefone }"
                  placeholder="(83) 99999-9999"
                  @input="handleTelefoneInput"
                />
                <p v-if="form.errors?.telefone" class="mt-1 text-sm text-red-600">{{ form.errors.telefone }}</p>
              </div>

              <!-- Email -->
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                  E-mail *
                </label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.email }"
                />
                <p v-if="form.errors?.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
              </div>
            </div>
          </div>

          <!-- Endereço -->
          <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
              </svg>
              Endereço
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Rua -->
              <div class="md:col-span-2">
                <label for="rua" class="block text-sm font-medium text-gray-700 mb-1">
                  Rua/Avenida *
                </label>
                <input
                  id="rua"
                  v-model="form.endereco.rua"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.['endereco.rua'] }"
                />
                <p v-if="form.errors?.['endereco.rua']" class="mt-1 text-sm text-red-600">{{ form.errors['endereco.rua'] }}</p>
              </div>

              <!-- Número -->
              <div>
                <label for="numero" class="block text-sm font-medium text-gray-700 mb-1">
                  Número
                </label>
                <input
                  id="numero"
                  v-model="form.endereco.numero"
                  type="text"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                />
              </div>

              <!-- Bairro -->
              <div>
                <label for="bairro" class="block text-sm font-medium text-gray-700 mb-1">
                  Bairro *
                </label>
                <input
                  id="bairro"
                  v-model="form.endereco.bairro"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.['endereco.bairro'] }"
                />
                <p v-if="form.errors?.['endereco.bairro']" class="mt-1 text-sm text-red-600">{{ form.errors['endereco.bairro'] }}</p>
              </div>

              <!-- Cidade -->
              <div>
                <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">
                  Cidade *
                </label>
                <input
                  id="cidade"
                  v-model="form.endereco.cidade"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.['endereco.cidade'] }"
                />
                <p v-if="form.errors?.['endereco.cidade']" class="mt-1 text-sm text-red-600">{{ form.errors['endereco.cidade'] }}</p>
              </div>

              <!-- UF -->
              <div>
                <label for="uf" class="block text-sm font-medium text-gray-700 mb-1">
                  UF *
                </label>
                <select
                  id="uf"
                  v-model="form.endereco.uf"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.['endereco.uf'] }"
                >
                  <option value="">Selecione...</option>
                  <option v-for="uf in estadosBrasileiros" :key="uf" :value="uf">{{ uf }}</option>
                </select>
                <p v-if="form.errors?.['endereco.uf']" class="mt-1 text-sm text-red-600">{{ form.errors['endereco.uf'] }}</p>
              </div>

              <!-- CEP -->
              <div>
                <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">
                  CEP
                </label>
                <input
                  id="cep"
                  v-model="form.endereco.cep"
                  type="text"
                  maxlength="9"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  placeholder="00000-000"
                  @input="handleCepInput"
                />
              </div>
            </div>
          </div>

          <!--  Dados Profissionais -->
          <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
              </svg>
              Dados Profissionais
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Órgão de Trabalho -->
              <div class="md:col-span-2">
                <label for="orgao_trabalho" class="block text-sm font-medium text-gray-700 mb-1">
                  Órgão de Trabalho *
                </label>
                <input
                  id="orgao_trabalho"
                  v-model="form.orgao_trabalho"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.orgao_trabalho }"
                  placeholder="Ex: Polícia Civil de Pernambuco"
                />
                <p v-if="form.errors?.orgao_trabalho" class="mt-1 text-sm text-red-600">{{ form.errors.orgao_trabalho }}</p>
              </div>

              <!-- Cargo -->
              <div>
                <label for="cargo" class="block text-sm font-medium text-gray-700 mb-1">
                  Cargo *
                </label>
                <input
                  id="cargo"
                  v-model="form.cargo"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.cargo }"
                  placeholder="Ex: Delegado, Escrivão, Agente"
                />
                <p v-if="form.errors?.cargo" class="mt-1 text-sm text-red-600">{{ form.errors.cargo }}</p>
              </div>

              <!-- Matrícula Funcional -->
              <div>
                <label for="matricula_funcional" class="block text-sm font-medium text-gray-700 mb-1">
                  Matrícula Funcional
                </label>
                <input
                  id="matricula_funcional"
                  v-model="form.matricula_funcional"
                  type="text"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  placeholder="Se possuir"
                />
              </div>

              <!-- Tipo de Órgão -->
              <div class="md:col-span-2">
                <label for="tipo_orgao" class="block text-sm font-medium text-gray-700 mb-1">
                  Tipo de Órgão *
                </label>
                <select
                  id="tipo_orgao"
                  v-model="form.tipo_orgao"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.tipo_orgao }"
                >
                  <option value="">Selecione o tipo de órgão</option>
                  <option v-for="(label, value) in props.tiposOrgao" :key="value" :value="value">
                    {{ label }}
                  </option>
                </select>
                <p v-if="form.errors?.tipo_orgao" class="mt-1 text-sm text-red-600">{{ form.errors.tipo_orgao }}</p>
              </div>
            </div>
          </div>

          <!-- Dados da Reserva -->
          <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
              </svg>
              Dados da Reserva
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Data de Entrada -->
              <div>
                <label for="data_inicial" class="block text-sm font-medium text-gray-700 mb-1">
                  Data de Entrada *
                </label>
                <input
                  id="data_inicial"
                  v-model="form.data_inicial"
                  type="date"
                  required
                  :min="hoje"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.data_inicial }"
                />
                <p v-if="form.errors?.data_inicial" class="mt-1 text-sm text-red-600">{{ form.errors.data_inicial }}</p>
              </div>

              <!-- Data de Saída -->
              <div>
                <label for="data_final" class="block text-sm font-medium text-gray-700 mb-1">
                  Data de Saída *
                </label>
                <input
                  id="data_final"
                  v-model="form.data_final"
                  type="date"
                  required
                  :min="dataMinimaSaida"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.data_final }"
                />
                <p v-if="form.errors?.data_final" class="mt-1 text-sm text-red-600">{{ form.errors.data_final }}</p>
              </div>

              <!-- Condição da Reserva -->
              <div class="md:col-span-2">
                <label for="condicao" class="block text-sm font-medium text-gray-700 mb-1">
                  Condição da Reserva *
                </label>
                <select
                  id="condicao"
                  v-model="form.condicao"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.condicao }"
                >
                  <option value="">Selecione a condição</option>
                  <option v-for="(label, value) in props.condicoes" :key="value" :value="value">
                    {{ label }}
                  </option>
                </select>
                <p v-if="form.errors?.condicao" class="mt-1 text-sm text-red-600">{{ form.errors.condicao }}</p>
              </div>

              <!-- Motivo da Reserva -->
              <div class="md:col-span-2">
                <label for="motivo" class="block text-sm font-medium text-gray-700 mb-1">
                  Motivo da Reserva *
                </label>
                <textarea
                  id="motivo"
                  v-model="form.motivo"
                  rows="4"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'border-red-300': form.errors?.motivo }"
                  placeholder="Descreva detalhadamente o motivo da sua estadia..."
                ></textarea>
                <p v-if="form.errors?.motivo" class="mt-1 text-sm text-red-600">{{ form.errors.motivo }}</p>
              </div>
            </div>
          </div>

          <!-- Documentos -->
          <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
              </svg>
              Documentos Obrigatórios
            </h3>
            
            <div class="space-y-6">
              <!-- Documento de Identidade -->
              <div>
                <label for="documento_identidade" class="block text-sm font-medium text-gray-700 mb-1">
                  Documento de Identidade (RG/CNH) *
                </label>
                <input
                  id="documento_identidade"
                  type="file"
                  accept=".pdf"
                  required
                  @change="handleFileChange('documento_identidade', $event)"
                  class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100"
                  :class="{ 'border-red-300': form.errors?.documento_identidade }"
                />
                <p class="mt-1 text-xs text-gray-500">Formato aceito: PDF (máx. 5MB)</p>
                <p v-if="form.errors?.documento_identidade" class="mt-1 text-sm text-red-600">{{ form.errors.documento_identidade }}</p>
              </div>

              <!-- Carteira Funcional -->
              <div>
                <label for="documento_funcional" class="block text-sm font-medium text-gray-700 mb-1">
                  Carteira Funcional (se possuir)
                </label>
                <input
                  id="documento_funcional"
                  type="file"
                  accept=".pdf"
                  @change="handleFileChange('documento_funcional', $event)"
                  class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100"
                />
                <p class="mt-1 text-xs text-gray-500">Formato aceito: PDF (máx. 5MB)</p>
                <p v-if="form.errors?.documento_funcional" class="mt-1 text-sm text-red-600">{{ form.errors.documento_funcional }}</p>
              </div>

              <!-- Documento Comprobatório -->
              <div>
                <label for="documento_comprobatorio" class="block text-sm font-medium text-gray-700 mb-1">
                  Documento Comprobatório
                </label>
                <input
                  id="documento_comprobatorio"
                  type="file"
                  accept=".pdf"
                  @change="handleFileChange('documento_comprobatorio', $event)"
                  class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100"
                />
                <p class="mt-1 text-xs text-gray-500">
                  Convite, ofício, comprovante de curso, etc. (Formato: PDF - máx. 5MB)
                </p>
                <p v-if="form.errors?.documento_comprobatorio" class="mt-1 text-sm text-red-600">{{ form.errors.documento_comprobatorio }}</p>
              </div>
            </div>
          </div>

          <!-- Termos e Condições -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm6 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
              </svg>
              Termos e Condições
            </h3>
            
            <div class="flex justify-end mb-2">
              <button 
                type="button" 
                @click="toggleTermos" 
                class="text-amber-600 hover:text-amber-800 text-sm font-medium"
              >
                {{ termoVisivel ? 'Ocultar Termos' : 'Ver Termos Completos' }}
              </button>
            </div>
            
            <div 
              :class="{'h-40': !termoVisivel, 'h-auto': termoVisivel}"
              class="bg-gray-50 p-4 rounded-lg mb-4 text-sm overflow-y-auto border transition-all duration-300"
            >
              <h4 class="font-bold mb-2">TERMO DE UTILIZAÇÃO DO ALOJAMENTO</h4>
              
              <p class="mb-3">Ao solicitar a reserva de alojamento, o visitante declara estar ciente e concordar com as seguintes condições:</p>
              
              <ol class="list-decimal ml-5 space-y-2 mb-4">
                <li>O uso do alojamento é exclusivo para servidores e pessoas autorizadas pela administração da ACADEPOL;</li>
                <li>A ACADEPOL NÃO FORNECE materiais de higiene pessoal, lençóis, cobertores, travesseiros e toalhas;</li>
                <li>Os quartos são compartilhados (beliches) e separados por sexo. Não há possibilidade de reserva de quartos individuais;</li>
                <li>O ocupante é responsável pela conservação das instalações e equipamentos utilizados;</li>
                <li>É proibido consumir bebidas alcoólicas ou substâncias ilícitas nas dependências do alojamento;</li>
                <li>É proibido realizar festas, reuniões ou eventos que perturbem o silêncio ou a tranquilidade dos demais ocupantes;</li>
                <li>Deve-se respeitar o horário de silêncio entre 22h e 6h;</li>
                <li>A ACADEPOL não se responsabiliza por objetos pessoais deixados nos quartos;</li>
                <li>O check-in deve ser realizado entre 8h e 18h, mediante apresentação de documento de identificação;</li>
                <li>O check-out deve ser realizado até as 12h do dia de saída;</li>
                <li>A pré-reserva está sujeita à disponibilidade e aprovação pela administração da ACADEPOL;</li>
                <li>A administração poderá cancelar a reserva ou solicitar a desocupação do alojamento em caso de descumprimento destas normas.</li>
              </ol>
              
              <p>Este termo poderá ser atualizado a qualquer momento, sendo responsabilidade do usuário manter-se informado sobre as normas vigentes.</p>
            </div>
            
            <div class="flex items-start mb-4">
              <div class="flex items-center h-5">
                <input
                  id="aceita_termos"
                  v-model="form.aceita_termos"
                  type="checkbox"
                  required
                  class="w-4 h-4 border-gray-300 rounded focus:ring-amber-500 text-amber-600"
                  :class="{ 'border-red-300': form.errors?.aceita_termos }"
                />
              </div>
              <label for="aceita_termos" class="ml-2 text-sm font-medium text-gray-700">
                Declaro que li e aceito os termos e condições para hospedagem no alojamento da ACADEPOL *
              </label>
            </div>
            <p v-if="form.errors?.aceita_termos" class="mt-1 text-sm text-red-600">{{ form.errors.aceita_termos }}</p>
          </div>

          <!-- Botões de Ação -->
          <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 mt-8 pt-6 border-t border-gray-200">
            <button 
              type="submit" 
              class="bg-gradient-to-r from-amber-500 to-amber-600 text-white py-3 px-8 rounded-md font-medium transition-all duration-300 shadow hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-300 flex items-center justify-center"
              :disabled="isSubmitting"
            >
              <svg v-if="!isSubmitting" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <svg v-if="isSubmitting" class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-if="isSubmitting">Enviando...</span>
              <span v-else>Enviar Solicitação de Reserva</span>
            </button>
            <Link
              href="/alojamento/tipo-usuario"
              class="text-center border border-gray-300 bg-white text-gray-700 py-3 px-8 rounded-md font-medium transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 flex items-center justify-center"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
              Cancelar
            </Link>
          </div>
        </form>
      </div>
    </div>
    <Footer />
  </div>
</template>