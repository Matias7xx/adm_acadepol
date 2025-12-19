<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Header from '../Components/Header.vue';
import SiteNavbar from '../Components/SiteNavbar.vue';
import Footer from '../Components/Footer.vue';

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

// Toast notification
const { toast } = useToast();

// Estado do formulário
const termoVisivel = ref(false);
const isSubmitting = ref(false);
const documentoSelecionado = ref(null);
const estadosBrasileiros = ref([
  'AC',
  'AL',
  'AP',
  'AM',
  'BA',
  'CE',
  'DF',
  'ES',
  'GO',
  'MA',
  'MT',
  'MS',
  'MG',
  'PA',
  'PB',
  'PR',
  'PE',
  'PI',
  'RJ',
  'RN',
  'RS',
  'RO',
  'RR',
  'SC',
  'SP',
  'SE',
  'TO',
]);

//Formatar data para inicializar no formulário
const formatarDataParaInput = data => {
  if (!data) return '';

  if (typeof data === 'string') {
    let dataObj;

    // Formato ISO (YYYY-MM-DD)
    if (data.match(/^\d{4}-\d{2}-\d{2}/)) {
      dataObj = new Date(data);
    }

    if (!isNaN(dataObj.getTime())) {
      return dataObj.toISOString().split('T')[0];
    }
  }

  return '';
};

// Dados para o formulário de pré-reserva
const formData = ref({
  aceitaTermos: false,
  nome: props.user?.name || '',
  cargo: props.user?.cargo || '',
  matricula: props.user?.matricula || '',
  orgao: props.user?.orgao || '',
  cpf: props.user?.cpf || '',
  data_nascimento: formatarDataParaInput(props.user?.data_nascimento),
  rg: props.user?.rg || '',
  orgao_expedidor: props.user?.orgao_expedidor || '',
  sexo: props.user?.sexo || '',
  uf: props.user?.uf || '',
  motivo: '',
  condicao: '',
  email: props.user?.email || '',
  telefone: props.user?.telefone || '',
  endereco_rua: props.user?.endereco?.rua || '',
  endereco_bairro: props.user?.endereco?.bairro || '',
  endereco_cidade: props.user?.endereco?.cidade || '',
  endereco_numero: props.user?.endereco?.numero || '',
  endereco_cep: props.user?.endereco?.cep || '',
  data_inicial: '',
  data_final: '',
});

// Handler para seleção de documento
const handleDocumentoChange = event => {
  documentoSelecionado.value = event.target.files[0];
};

// Verificar se as datas são válidas
const validarDatas = () => {
  if (!formData.value.data_inicial || !formData.value.data_final) {
    return false;
  }

  const dataInicial = new Date(formData.value.data_inicial);
  const dataFinal = new Date(formData.value.data_final);
  const hoje = new Date();

  // Remover a parte do tempo para comparação justa
  hoje.setHours(0, 0, 0, 0);

  if (dataInicial < hoje) {
    toast.error('A data inicial não pode ser anterior à data atual');
    return false;
  }

  if (dataFinal < dataInicial) {
    toast.error('A data final deve ser igual ou posterior à data inicial');
    return false;
  }

  return true;
};

// Função para enviar o formulário
const submeterReserva = () => {
  // Validar aceite de termos
  if (!formData.value.aceitaTermos) {
    toast.error('Você precisa aceitar os termos para continuar');
    return;
  }

  // Validar datas
  if (!validarDatas()) {
    return;
  }

  // Validar campos obrigatórios
  for (const campo of [
    'nome',
    'cargo',
    'matricula',
    'orgao',
    'cpf',
    'motivo',
    'condicao',
    'email',
    'telefone',
    'endereco_rua',
    'endereco_bairro',
    'endereco_cidade',
  ]) {
    if (!formData.value[campo]) {
      toast.error(`Por favor, preencha o campo ${campo.replace('_', ' ')}`);
      return;
    }
  }

  isSubmitting.value = true;

  // Configurar o FormData para enviar arquivos
  const form = useForm({
    nome: formData.value.nome,
    cargo: formData.value.cargo,
    matricula: formData.value.matricula,
    orgao: formData.value.orgao,
    cpf: formData.value.cpf,
    data_nascimento: formData.value.data_nascimento,
    rg: formData.value.rg,
    orgao_expedidor: formData.value.orgao_expedidor,
    sexo: formData.value.sexo,
    uf: formData.value.uf,
    motivo: formData.value.motivo,
    condicao: formData.value.condicao,
    email: formData.value.email,
    telefone: formData.value.telefone,
    endereco: {
      rua: formData.value.endereco_rua,
      bairro: formData.value.endereco_bairro,
      cidade: formData.value.endereco_cidade,
      numero: formData.value.endereco_numero,
      cep: formData.value.endereco_cep,
    },
    data_inicial: formData.value.data_inicial,
    data_final: formData.value.data_final,
    aceita_termos: formData.value.aceitaTermos,
    documento_comprobatorio: documentoSelecionado.value,
  });

  form.post(route('alojamento.reserva.store'), {
    preserveScroll: false,
    forceFormData: true,
    onSuccess: () => {
      isSubmitting.value = false;
    },
    onError: errors => {
      isSubmitting.value = false;
      console.error('Erro na validação:', errors);

      // Erro do servidor (reserva pendente, conflito, etc.)
      if (errors.message) {
        toast.error(errors.message);
        return;
      }

      // Erros de validação específicos
      if (errors.reserva_pendente) {
        toast.error(errors.reserva_pendente);
        return;
      }

      // Primeiro erro de validação encontrado
      const primeiroErro = Object.values(errors)[0];
      if (typeof primeiroErro === 'string') {
        toast.error(primeiroErro);
        return;
      }

      if (Array.isArray(primeiroErro) && primeiroErro.length > 0) {
        toast.error(primeiroErro[0]);
        return;
      }

      // Fallback genérico
      toast.error(
        'Ocorreu um erro ao enviar a solicitação. Por favor, tente novamente.'
      );
    },
  });
};

// Exibir/ocultar os termos
const toggleTermos = () => {
  termoVisivel.value = !termoVisivel.value;
};

// Calcular data mínima (hoje)
const dataMinima = new Date().toISOString().split('T')[0];

// Verificar se CPF tem formato válido
const validarCPF = cpf => {
  const regex = /^\d{3}\.\d{3}\.\d{3}-\d{2}$|^\d{11}$/;
  return regex.test(cpf);
};

// Funções de formatação
const formatarCep = valor => {
  return valor
    .replace(/\D/g, '')
    .replace(/(\d{5})(\d)/, '$1-$2')
    .replace(/(-\d{3})\d+?$/, '$1');
};

const handleCepInput = event => {
  const formatted = formatarCep(event.target.value);
  formData.value.endereco_cep = formatted;
};
</script>

<template>
  <Head title="Pré-Reserva de Alojamento" />
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
    <Header />
    <SiteNavbar />

    <!-- Cabeçalho -->
    <div
      class="bg-gradient-to-r from-black to-gray-900 text-white py-5 shadow-md"
    >
      <div class="container mx-auto flex justify-between items-center px-4">
        <div class="flex items-center">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 text-[#bea55a] mr-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
            />
          </svg>
          <h1 class="text-2xl font-bold">
            Formulário de Pré-Reserva de Alojamento
          </h1>
        </div>
        <Link
          :href="route('home')"
          class="flex items-center text-amber-400 hover:text-amber-300 transition"
        >
          <svg
            class="h-4 w-4 mr-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          <span>Voltar</span>
        </Link>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container mx-auto py-8 px-4">
      <div
        class="bg-white rounded-lg shadow-lg p-6 mb-6 border border-gray-200"
      >
        <div
          class="flex items-center justify-between mb-6 pb-3 border-b border-amber-200"
        >
          <div class="flex items-center">
            <div class="bg-amber-100 p-3 rounded-full mr-3">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6 text-amber-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">
              Pré-Reserva de Alojamento
            </h2>
          </div>
          <div class="text-sm text-gray-500">* Campos obrigatórios</div>
        </div>

        <!-- Aviso Importante -->
        <div
          class="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-amber-500 p-4 mb-6 shadow-sm"
        >
          <div class="flex">
            <div class="flex-shrink-0">
              <svg
                class="h-6 w-6 text-amber-500"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-amber-800">
                Informações Importantes
              </h3>
              <p class="mt-1 text-sm text-amber-700">
                A ACADEPOL <strong>NÃO FORNECE</strong> materiais de higiene
                pessoal, lençóis e toalhas. Os quartos são compartilhados
                (beliches) e separados por sexo.
              </p>
            </div>
          </div>
        </div>

        <form @submit.prevent="submeterReserva" class="space-y-8">
          <!-- Informações Pessoais -->
          <div
            class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm"
          >
            <h3
              class="text-lg font-semibold text-gray-700 mb-4 flex items-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2 text-amber-500"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                  clip-rule="evenodd"
                />
              </svg>
              Informações Pessoais
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Nome Completo -->
              <div>
                <label
                  for="nome"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Nome Completo *
                </label>
                <input
                  id="nome"
                  v-model="formData.nome"
                  type="text"
                  class="w-full bg-slate-100 border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                  readonly
                />
              </div>

              <!-- CPF -->
              <div>
                <label
                  for="cpf"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  CPF *
                </label>
                <input
                  id="cpf"
                  v-model="formData.cpf"
                  type="text"
                  placeholder="000.000.000-00"
                  class="w-full border-gray-300 rounded-md shadow-sm bg-slate-100 focus:border-amber-500 focus:ring focus:ring-amber-200"
                  readonly
                  required
                />
                <p
                  v-if="formData.cpf && !validarCPF(formData.cpf)"
                  class="mt-1 text-sm text-red-600"
                >
                  CPF inválido. Use o formato 000.000.000-00
                </p>
              </div>

              <!-- Data de Nascimento -->
              <div>
                <label
                  for="data_nascimento"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Data de Nascimento *
                </label>
                <input
                  id="data_nascimento"
                  v-model="formData.data_nascimento"
                  type="date"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                />
              </div>

              <!-- RG -->
              <div>
                <label
                  for="rg"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  RG *
                </label>
                <input
                  id="rg"
                  v-model="formData.rg"
                  type="text"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                />
              </div>

              <!-- Órgão Expedidor -->
              <div>
                <label
                  for="orgao_expedidor"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Órgão Expedidor *
                </label>
                <input
                  id="orgao_expedidor"
                  v-model="formData.orgao_expedidor"
                  type="text"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                />
              </div>

              <!-- Sexo -->
              <div>
                <label
                  for="sexo"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Sexo *
                </label>
                <select
                  id="sexo"
                  v-model="formData.sexo"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                >
                  <option value="">Selecione...</option>
                  <option value="masculino">Masculino</option>
                  <option value="feminino">Feminino</option>
                </select>
              </div>

              <!-- Cargo/Função -->
              <div>
                <label
                  for="cargo"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Cargo/Função ou Profissão *
                </label>
                <input
                  id="cargo"
                  v-model="formData.cargo"
                  type="text"
                  class="w-full border-gray-300 bg-slate-100 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                  readonly
                />
              </div>

              <!-- Matrícula -->
              <div>
                <label
                  for="matricula"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Matrícula *
                </label>
                <input
                  id="matricula"
                  v-model="formData.matricula"
                  type="text"
                  class="w-full bg-slate-100 border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                  readonly
                />
              </div>

              <!-- Órgão/Instituição -->
              <div>
                <label
                  for="orgao"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Órgão/Instituição de Origem *
                </label>
                <input
                  id="orgao"
                  v-model="formData.orgao"
                  type="text"
                  class="w-full border-gray-300 bg-slate-100 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                  readonly
                />
              </div>

              <!-- Condição do Alojado -->
              <div>
                <label
                  for="condicao"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Condição do Alojado *
                </label>
                <select
                  id="condicao"
                  v-model="formData.condicao"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                >
                  <option value="">Selecione...</option>
                  <option value="Professor">Professor</option>
                  <option value="Aluno">Aluno</option>
                  <option value="Visitante">Visitante</option>
                  <option value="Outro">Outro</option>
                </select>
              </div>

              <!-- Motivo da Reserva -->
              <div class="md:col-span-2">
                <label
                  for="motivo"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Motivo da Reserva *
                </label>
                <textarea
                  id="motivo"
                  v-model="formData.motivo"
                  rows="2"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  placeholder="Explique o motivo da sua solicitação de alojamento"
                  required
                ></textarea>
              </div>

              <!-- Documento Comprobatório -->
              <div class="md:col-span-2">
                <label
                  for="documento_comprobatorio"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Documento Comprobatório (PDF)
                </label>
                <div class="mt-1 flex items-center">
                  <input
                    id="documento_comprobatorio"
                    type="file"
                    @change="handleDocumentoChange"
                    accept=".pdf"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100"
                  />
                </div>
                <p class="mt-1 text-xs text-gray-500">
                  Envie um documento que comprove o motivo da reserva (ex:
                  licença para curso, documento oficial, etc). Formato aceito:
                  PDF. Tamanho máximo: 10MB
                </p>
              </div>
            </div>
          </div>

          <!-- Informações de Contato -->
          <div
            class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm"
          >
            <h3
              class="text-lg font-semibold text-gray-700 mb-4 flex items-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2 text-amber-500"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"
                />
                <path
                  d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"
                />
              </svg>
              Informações de Contato
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Email -->
              <div>
                <label
                  for="email"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  E-mail *
                </label>
                <input
                  id="email"
                  v-model="formData.email"
                  type="email"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                />
              </div>

              <!-- Telefone -->
              <div>
                <label
                  for="telefone"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Telefone de Contato *
                </label>
                <input
                  id="telefone"
                  v-model="formData.telefone"
                  type="tel"
                  placeholder="(00) 00000-0000"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                />
              </div>
            </div>
          </div>

          <!-- Endereço -->
          <div
            class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm"
          >
            <h3
              class="text-lg font-semibold text-gray-700 mb-4 flex items-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2 text-amber-500"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                  clip-rule="evenodd"
                />
              </svg>
              Endereço
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Rua -->
              <div class="md:col-span-2">
                <label
                  for="endereco_rua"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Rua/Avenida *
                </label>
                <input
                  id="endereco_rua"
                  v-model="formData.endereco_rua"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                />
              </div>

              <!-- Número -->
              <div>
                <label
                  for="endereco_numero"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Número
                </label>
                <input
                  id="endereco_numero"
                  v-model="formData.endereco_numero"
                  type="text"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                />
              </div>

              <!-- Bairro -->
              <div>
                <label
                  for="endereco_bairro"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Bairro *
                </label>
                <input
                  id="endereco_bairro"
                  v-model="formData.endereco_bairro"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                />
              </div>

              <!-- Cidade -->
              <div>
                <label
                  for="endereco_cidade"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Cidade *
                </label>
                <input
                  id="endereco_cidade"
                  v-model="formData.endereco_cidade"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                />
              </div>

              <!-- UF -->
              <div>
                <label
                  for="uf"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  UF *
                </label>
                <select
                  id="uf"
                  v-model="formData.uf"
                  required
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                >
                  <option value="">Selecione...</option>
                  <option
                    v-for="uf in estadosBrasileiros"
                    :key="uf"
                    :value="uf"
                  >
                    {{ uf }}
                  </option>
                </select>
              </div>

              <!-- CEP -->
              <div>
                <label
                  for="endereco_cep"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  CEP *
                </label>
                <input
                  id="endereco_cep"
                  v-model="formData.endereco_cep"
                  type="text"
                  maxlength="9"
                  required
                  placeholder="00000-000"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  @input="handleCepInput"
                />
              </div>
            </div>
          </div>

          <!-- Período de Reserva -->
          <div
            class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm"
          >
            <h3
              class="text-lg font-semibold text-gray-700 mb-4 flex items-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2 text-amber-500"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                  clip-rule="evenodd"
                />
              </svg>
              Período de Reserva
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Data Inicial -->
              <div>
                <label
                  for="data_inicial"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Data Inicial *
                </label>
                <input
                  id="data_inicial"
                  v-model="formData.data_inicial"
                  type="date"
                  :min="dataMinima"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                />
              </div>

              <!-- Data Final -->
              <div>
                <label
                  for="data_final"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Data Final *
                </label>
                <input
                  id="data_final"
                  v-model="formData.data_final"
                  type="date"
                  :min="formData.data_inicial || dataMinima"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                />
              </div>
            </div>
          </div>

          <!-- Termos e Condições -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">
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
              :class="{ 'h-40': !termoVisivel, 'h-auto': termoVisivel }"
              class="bg-gray-50 p-4 rounded-lg mb-4 text-sm overflow-y-auto border transition-all duration-300"
            >
              <h4 class="font-bold mb-2">TERMO DE UTILIZAÇÃO DO ALOJAMENTO</h4>

              <p class="mb-3">
                Ao solicitar a pré-reserva de alojamento, o usuário declara
                estar ciente e concordar com as seguintes condições:
              </p>

              <ol class="list-decimal ml-5 space-y-2 mb-4">
                <li>
                  O uso do alojamento é exclusivo para servidores e pessoas
                  autorizadas pela administração da ACADEPOL;
                </li>
                <li>
                  A ACADEPOL NÃO FORNECE materiais de higiene pessoal, lençóis,
                  cobertores, travesseiros e toalhas;
                </li>
                <li>
                  Os quartos são compartilhados (beliches) e separados por sexo.
                  Não há possibilidade de reserva de quartos individuais;
                </li>
                <li>
                  O ocupante é responsável pela conservação das instalações e
                  equipamentos utilizados;
                </li>
                <li>
                  É proibido consumir bebidas alcoólicas ou substâncias ilícitas
                  nas dependências do alojamento;
                </li>
                <li>
                  É proibido realizar festas, reuniões ou eventos que perturbem
                  o silêncio ou a tranquilidade dos demais ocupantes;
                </li>
                <li>Deve-se respeitar o horário de silêncio entre 22h e 6h;</li>
                <li>
                  A ACADEPOL não se responsabiliza por objetos pessoais deixados
                  nos quartos;
                </li>
                <li>
                  O check-in deve ser realizado entre 8h e 18h, mediante
                  apresentação de documento de identificação;
                </li>
                <li>
                  O check-out deve ser realizado até as 12h do dia de saída;
                </li>
                <li>
                  A pré-reserva está sujeita à disponibilidade e aprovação pela
                  administração da ACADEPOL;
                </li>
                <li>
                  A administração poderá cancelar a reserva ou solicitar a
                  desocupação do alojamento em caso de descumprimento destas
                  normas.
                </li>
              </ol>

              <p>
                Este termo poderá ser atualizado a qualquer momento, sendo
                responsabilidade do usuário manter-se informado sobre as normas
                vigentes.
              </p>
            </div>

            <div class="flex items-start mb-4">
              <div class="flex items-center h-5">
                <input
                  id="aceitaTermos"
                  v-model="formData.aceitaTermos"
                  type="checkbox"
                  class="w-4 h-4 border-gray-300 rounded"
                  required
                />
              </div>
              <label
                for="aceitaTermos"
                class="ml-2 text-sm font-medium text-gray-700"
              >
                Declaro que li e aceito os termos e condições para utilização do
                alojamento
              </label>
            </div>
          </div>

          <!-- Botões de Ação -->
          <div
            class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 mt-8 pt-6 border-t border-gray-200"
          >
            <button
              type="submit"
              class="bg-[#bea55a] text-white py-3 px-8 rounded-md font-medium transition-all duration-300 shadow hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-300 flex items-center justify-center"
              :disabled="isSubmitting"
            >
              <svg
                v-if="!isSubmitting"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
              <svg
                v-if="isSubmitting"
                class="animate-spin h-5 w-5 mr-2"
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
              <span v-if="isSubmitting">Enviando...</span>
              <span v-else>Enviar Solicitação de Reserva</span>
            </button>
            <Link
              :href="route('home')"
              class="text-center border border-gray-300 bg-white text-gray-700 py-3 px-8 rounded-md font-medium transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 flex items-center justify-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
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
